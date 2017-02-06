@extends('layouts.app')

@php
    function ordinal($number) {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13)
            return $number . 'th';
        else
            return $number . $ends[$number % 10];
    }
@endphp

@section('content')
    <div class="container">
        <h1 class="pb-1">
            Your Reservation
            <small class="text-muted">for {{ $reservation->getTimeslot()->format('l, F jS, Y') }} at {{ $reservation->getTimeslot()->format('g a') }} in {{ $reservation->getRoomName() }}</small>
        </h1>

        <dl class="row">
            <dt class="col-sm-2">Description</dt>
            <dd class="col-sm-10 pre bg-faded">{{ $reservation->getDescription() ?: 'None specified.' }}</dd>

            <dt class="col-sm-2">Status</dt>
            <dd class="col-sm-10">
                @if ($reservation->getPosition() === 0)
                    Your reservation is <strong>active</strong>.
                @else
                    You are currently <strong>{{ ordinal($reservation->getPosition()) }}</strong> on the waiting list.
                @endif
            </dd>
        </dl>

        <div class="row">
            <div class="col-md-10 offset-sm-2">
                @if ($back === 'list')
                    <a href="{{ route('reservationList') }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Go back</a>
                @else
                    <a href="{{ route('calendar', ['date' => $reservation->getTimeslot()->toDateString()]) }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Go back</a>
                @endif
                <a href="{{ route('reservationModify', ['id' => $reservation->getId(), 'back' => $back]) }}" class="btn btn-primary">
                    <i class="fa fa-pencil" aria-hidden="true"></i>
                    Modify
                </a>
                <a href="{{ route('reservationCancel', ['id' => $reservation->getId(), 'room' => $reservation->getRoomName(), 'timeslot' => $reservation->getTimeslot()->format('Y-m-d\TH'), 'back' => $back]) }}" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this reservation?');">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                    Cancel this Reservation
                </a>
            </div>
        </div>
    </div>
@endsection
