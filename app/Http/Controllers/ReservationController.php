<?php

namespace App\Http\Controllers;

use App\Data\Mappers\ReservationMapper;
use App\Data\Mappers\RoomMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
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
    public function listReservations(Request $request)
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
    public function showReservation(Request $request, $id)
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
    public function showModifyForm(Request $request, $id)
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

}
