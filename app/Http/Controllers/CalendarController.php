<?php

namespace App\Http\Controllers;

use App\Data\Mappers\ReservationMapper;
use App\Data\Mappers\RoomMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function viewCalendar(Request $request)
    {
        $this->validate($request, [
            'date' => 'date_format:Y-m-d'
        ]);

        $date = $request->input('date');

        if ($date === null) {
            $date = Carbon::today();
        } else {
            $date = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        }

        $roomMapper = RoomMapper::getInstance();
        $rooms = $roomMapper->findAll();

        $reservationMapper = ReservationMapper::getInstance();
        $activeReservations = $reservationMapper->findAllActive($date);
        $userReservations = $reservationMapper->findPositionsForUser(Auth::id());

        return view('calendar.index', [
            'date' => $date,
            'rooms' => collect($rooms),
            'activeReservations' => collect($activeReservations),
            'userReservations' => collect($userReservations)
        ]);
    }
}
