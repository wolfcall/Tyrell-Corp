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
        // validate reservation exists and is owned by user
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
    public function showModifyForm(Request $request, $id)
    {
        // validate reservation exists and is owned by user
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
        // validate reservation exists and is owned by user
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

        // don't allow reserving in the past
        if ($timeslot->copy()->addDay()->isPast()) {
            return abort(404);
        }

        // validate room exists
        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);

        if ($room === null) {
            return abort(404);
        }

        $reservationMapper = ReservationMapper::getInstance();

        // check if user exceeded maximum amount of reservations
        $reservationCount = $reservationMapper->countInRange(Auth::id(), $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());

        if ($reservationCount >= static::MAX_PER_USER) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', sprintf("You've exceeded your reservation request limit (%d).", static::MAX_PER_USER));
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
        $this->validate($request, [
            'description' => 'required',
            'recur' => 'required|integer|min:1|max:'.static::MAX_PER_USER
        ]);

        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

        // don't allow reserving in the past
        if ($timeslot->copy()->addDay()->isPast()) {
            return abort(404);
        }

        // validate room exists
        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);

        if ($room === null) {
            return abort(404);
        }

        // generate a UUID for this reservation session, which will link recurring reservations together
        $uuid = \Uuid::generate();
        $reservations = [];

        $reservationMapper = ReservationMapper::getInstance();
        $recur = intval($request->input('recur', 1));

        // status message arrays
        $successful = [];
        $waitlisted = [];
        $errored = [];

        // loop over every recurring week and independently request the reservation for that week
        for ($t = $timeslot->copy(), $i = 0; $i < $recur; $t->addWeek(), ++$i) {

            /*
             * Pre-insert checks
             */

            // check if user exceeded maximum amount of reservations
            $reservationCount = $reservationMapper->countInRange(Auth::id(), $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());
            if ($reservationCount >= static::MAX_PER_USER) {
                $errored[] = [$t, sprintf("You've exceeded your weekly reservation request limit of %d.", static::MAX_PER_USER)];
                continue;
            }

            // check if waiting list for timeslot is full
            $waitingList = $reservationMapper->findForTimeslot($roomName, $t);
            if (count($waitingList) >= static::MAX_PER_TIMESLOT) {
                $errored[] = [$t, 'The waiting list is full.'];
                continue;
            }

            /*
             * Insert
             */

            $reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid);
        }

        // run the reservation operations now, as we need to process the results
        $reservationMapper->done();

        /*
         * Post-insert checks
         */

        foreach ($reservations as $reservation) {
            $t = $reservation->getTimeslot();

            // check if there was an error inserting the reservation, ie. duplicate reservation
            if ($reservation->getId() === null) {
                $errored[] = [$reservation, 'You already have a reservation for this time slot.'];
                continue;
            }

            // find the new reservation's position #
            $position = $reservationMapper->findPosition($reservation);

            if ($position > static::MAX_PER_TIMESLOT) {
                // this request has exceeded the limit, delete it
                $reservationMapper->delete($reservation->getId());
                $errored[] = [$t, 'The waiting list is full.'];
            } else if ($position === 0) {
                // the reservation is active
                $successful[] = $t;
            } else {
                // user has been put on a waiting list
                $waitlisted[] = [$t, $position];
            }
        }

        // commit one last time, to finalize any deletes we had to do
        $reservationMapper->done();

        $response = redirect()
            ->route('calendar', ['date' => $timeslot->toDateString()]);

        /*
         * Format the status messages
         */

        if (count($successful)) {
            $response = $response->with('success', sprintf('The following reservations have been successfully created for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                return sprintf("<li><strong>%s</strong></li>", $m->format('l, F jS, Y'));
            }, $successful))));
        }

        if (count($waitlisted)) {
            $response = $response->with('warning', sprintf('You have been put on a waiting list for the following reservations for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                return sprintf("<li><strong>%s</strong>: Position #%d</li>", $m[0]->format('l, F jS, Y'), $m[1]);
            }, $waitlisted))));
        }

        if (count($errored)) {
            $response = $response->with('error', sprintf('The following requests were unsuccessful for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                return sprintf("<li><strong>%s</strong>: %s</li>", $m[0]->format('l, F jS, Y'), $m[1]);
            }, $errored))));
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function cancelReservation(Request $request, $id)
    {
        // valiadte reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        // delete the reservation
        $reservationMapper->delete($reservation->getId());
        $reservationMapper->done();

        $response = redirect();

        // redirect to appropriate back page
        if ($request->input('back') === 'list') {
            $response = $response->route('reservationList');
        } else {
            $response = $response->route('calendar', ['date' => $reservation->getTimeslot()->toDateString()]);

        }

        return $response->with('success', 'Successfully cancelled reservation!');
    }
}
