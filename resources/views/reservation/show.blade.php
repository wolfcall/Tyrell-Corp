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
            Your reservation
            <small class="text-muted">for {{ $reservation->getTimeslot()->format('l, F jS, Y') }} at {{ $reservation->getTimeslot()->format('ga') }} in {{ $reservation->getRoomName() }}</small>
        </h1>

        <dl class="row">
            <dt class="col-sm-2">Description</dt>
            <dd class="col-sm-10 pre bg-faded">{{ $reservation->getDescription() ?: 'None specified.' }}</dd>

            <dt class="col-sm-2">Status</dt>
            <dd class="col-sm-10">
                @if ($position === 0)
                    Your reservation is <strong>active</strong>.
                @else
                    You are currently <strong>{{ ordinal($position) }}</strong> on the waiting list.
                @endif
            </dd>
        </dl>

        @if ($back === 'list')
            <a href="{{ route('reservationList') }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Go back</a>
        @else
            <a href="{{ route('calendar', ['date' => $reservation->getTimeslot()->toDateString()]) }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Go back</a>
        @endif
        <a href="{{ route('reservationModify', ['id' => $reservation->getId(), 'back' => $back]) }}" class="btn btn-primary">Modify</a>
        <a href="{{ route('reservationCancel', ['id' => $reservation->getId(), 'back' => $back]) }}" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel your reservation?');">Cancel</a>
    </div>
@endsection
