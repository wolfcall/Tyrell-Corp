<?php

namespace App\Http\Controllers;

use App\Data\Mappers\ReservationMapper;
use App\Data\Mappers\RoomMapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    const MAX_PER_TIMESLOT = 4;
    const MAX_PER_USER = 10;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function viewReservationList(Request $request)
    {
        $reservationMapper = ReservationMapper::getInstance();
        $reservations = $reservationMapper->findPositionsForUser(Auth::id());

        return view('reservation.list', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function viewReservation(Request $request, $id)
    {
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        // get a list of all the other reservations for the same room-timeslot
        $position = $reservationMapper->findPosition($reservation);

        return view('reservation.show', [
            'reservation' => $reservation,
            'position' => $position,
            'back' => $request->input('back', 'calendar')
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function requestModificationForm(Request $request, $id)
    {
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        return view('reservation.modify', [
            'reservation' => $reservation,
            'back' => $request->input('back')
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function modifyReservation(Request $request, $id)
    {
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        // update the description
        $reservationMapper->set($reservation->getId(), $request->input('description', ""));
        $reservationMapper->done();

        return redirect()
            ->route('reservation', ['id' => $reservation->getId(), 'back' => $request->input('back')])
            ->with('success', 'Successfully modified reservation!');
    }

    /**
     * @param Request $request
     * @param string $roomName
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function showRequestForm(Request $request, $roomName, $timeslot)
    {
        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);

        if ($room === null) {
            return abort(404);
        }

        $reservationMapper = ReservationMapper::getInstance();

        // check if user exceeded maximum amount of reservations
        $reservations = $reservationMapper->findPositionsForUser(Auth::id());

        if (count($reservations) >= static::MAX_PER_USER) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', sprintf('You cannot have more than %d reservation requests at a time.', static::MAX_PER_USER));
        }

        // check if waiting list for timeslot is full
        $reservations = $reservationMapper->findForTimeslot($roomName, $timeslot);

        if (count($reservations) >= static::MAX_PER_TIMESLOT) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', 'The waiting list for that time slot is full.');
        }

        return view('reservation.request', [
            'room' => $room,
            'timeslot' => $timeslot
        ]);
    }

    /**
     * @param Request $request
     * @param string $roomName
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function requestReservation(Request $request, $roomName, $timeslot)
    {
        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);

        if ($room === null) {
            return abort(404);
        }

        $reservationMapper = ReservationMapper::getInstance();

        /*
         * Pre-insert checks
         */

        // check if user exceeded maximum amount of reservations
        $reservations = $reservationMapper->findPositionsForUser(Auth::id());

        if (count($reservations) >= static::MAX_PER_USER) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', sprintf('You cannot have more than %d reservation requests at a time.', static::MAX_PER_USER));
        }

        // check if waiting list for timeslot is full
        $reservations = $reservationMapper->findForTimeslot($roomName, $timeslot);

        if (count($reservations) >= static::MAX_PER_TIMESLOT) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', 'The waiting list for that time slot is full.');
        }

        /*
         * Insert
         */

        $reservation = $reservationMapper->create(intval(Auth::id()), $room->getName(), $timeslot, $request->input('description', ""));
        $reservationMapper->done();

        /*
         * Post-insert checks
         */

        // find the new reservation's position #
        $position = $reservationMapper->findPosition($reservation);

        $response = redirect()
            ->route('calendar', ['date' => $timeslot->toDateString()]);

        if ($position >= static::MAX_PER_TIMESLOT) {
            // ensure this request hasn't exceeded the limit
            $reservationMapper->delete($reservation->getId());
            $reservationMapper->done();

            return $response->with('error', 'The waiting list for this room and time slot has exceeded the maximum allowable amount.');
        } else if ($position === 0) {
            // not waitlisted
            return $response->with('success', 'Successfully requested reservation! Your reservation is now active.');
        } else {
            // waitlisted
            return $response->with('warning', sprintf("You've been placed on a waiting list for your reservation. Your position is #%d.", $position));
        }
    }

    /**
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function cancelReservation(Request $request, $id)
    {
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        $reservationMapper->delete($reservation->getId());
        $reservationMapper->done();

        $response = redirect();

        if ($request->input('back') === 'list') {
            $response = $response->route('reservationList');
        }

        return $response->route('calendar', ['date' => $reservation->getTimeslot()->toDateString()])
            ->with('success', 'Successfully cancelled reservation!');
    }
}
