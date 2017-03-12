<?php

namespace App\Http\Controllers;

use App\Data\Mappers\ReservationMapper;
use App\Data\Mappers\RoomMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarController extends Controller {

    /**
     * Create a new controller instance.
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function viewCalendar(Request $request) {
        $this->validate($request, [
            'date' => 'date_format:Y-m-d'
        ]);

        // parse requested date from input
        $date = $request->input('date');

        if ($date === null) {
            // default to today
            $date = Carbon::today();
        } else {
            $date = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        }

        // fetch all rooms to display
        $roomMapper = RoomMapper::getInstance();
        $rooms = $roomMapper->findAll();

        // Clear student from any room that they were keeping busy
        $roomMapper->clearStudent(Auth::id());

        $reservationMapper = ReservationMapper::getInstance();

        // find all of today's active (ie. not wait listed) reservations
        $activeReservations = $reservationMapper->findAllActive($date);

        // find all of the user's reservations, wait listed or not
        $userReservations = $reservationMapper->findPositionsForUser(Auth::id());

        return view('calendar.index', [
            'date' => $date,
            'rooms' => collect($rooms),
            'activeReservations' => collect($activeReservations),
            'userReservations' => collect($userReservations)
        ]);
    }

}
