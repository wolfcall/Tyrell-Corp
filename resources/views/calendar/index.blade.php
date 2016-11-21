@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="pb-1">
            Room calendar
            <small class="text-muted">for {{ $date->format('l, F jS, Y') }}</small>
        </h1>

        <div class="row pb-1">
            <div class="col-md-4 col-sm-12">
                <p>Choose from one of the time slots below to request a reservation.</p>
            </div>
            <div class="col-md-8 col-sm-12 text-xs-right">
                <form class="form-inline">
                    @if (\Carbon\Carbon::today()->ne($date))
                        <a href="{{ route('calendar') }}" class="btn btn-secondary">Return to today</a>
                    @endif
                    @if ($date->isFuture())
                        <a href="{{ route('calendar', ['date' => $date->copy()->subDay()->toDateString() ]) }}" class="btn btn-secondary"><i class="fa fa-step-backward" aria-hidden="true"></i></a>
                    @endif
                    <a href="{{ route('calendar', ['date' => $date->copy()->addDay()->toDateString() ]) }}" class="btn btn-secondary"><i class="fa fa-step-forward" aria-hidden="true"></i></a>
                    <div class="form-group">
                        <input class="form-control" type="date" name="date" min="{{ \Carbon\Carbon::today()->toDateString() }}" value="{{ $date->toDateString() }}" id="example-date-input">
                    </div>
                    <button type="submit" class="btn btn-primary">Jump to date</button>
                </form>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 col-md-10 offset-md-1">
                <table class="table table-bordered calendar">
                    <thead>
                    <tr>
                        <th></th>
                        @for ($h = 7; $h < 23; ++$h)
                            <th class="text-xs-center">{{ $h }}:00</th>
                        @endfor
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rooms as $room)
                        <tr class="calendar-room-row">
                            <th class="align-middle text-xs-center">{{ $room->getName() }}</th>
                            @for ($timeslot = $date->copy()->addHours(7); $timeslot->hour < 23; $timeslot->addHour())
                                @include('calendar.timeslot')
                            @endfor
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $(".calendar-timeslot-selectable").click(function () {
            window.document.location = $(this).data("href");
        });
    });
</script>
@endpush
