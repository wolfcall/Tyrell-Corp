@extends('layouts.app')
<?php
	use App\Data\Mappers\RoomMapper;
?>
@section('content')
    <div class="container">
        <h1 class="pb-1">
            Room Calendar
            <small class="text-muted">for {{ $date->format('l, F jS, Y') }}</small>
        </h1>

        <div class="row pb-1">
            <div class="col-md-4 col-sm-12">
                <p>Choose from one of the time slots below to request a reservation.</p>
            </div>
            
			<div class="col-md-4 col-sm-12">
				<fieldset>
				<legend style="text-align: center">Legend</legend>
				<ul>
				<li type="square" style="color:#61ad2e">Your Reservations</li>
				<li type="square" style="color:#f98b8b">Reserved by another User</li>
				<li type="square" style="color:#c4c10b">Waiting List Position</li>
				<li type="square" style="color:#b3b3cc">Unavailable. Cannot book a time in the past</li>
				<li type="square" style="color:#84d2f9">Room is being used by another student</li>
				</ul>
				</fieldset>
			</div>
			           
            <div class="col-md-4 col-sm-12 ">
                <form class="form-inline">
                    @if (\Carbon\Carbon::today()->ne($date))
                        <a href="{{ route('calendar') }}" class="btn btn-secondary">Return to today</a>
                    @endif
                    @if ($date->isFuture())
                        <a href="{{ route('calendar', ['date' => $date->copy()->subDay()->toDateString() ]) }}" class="btn btn-secondary"><i class="fa fa-step-backward" aria-hidden="true"></i></a>
                    @endif
                    <a href="{{ route('calendar', ['date' => $date->copy()->addDay()->toDateString() ]) }}" class="btn btn-secondary"><i class="fa fa-step-forward" aria-hidden="true"></i></a>
                    <div class="form-group">
                        <input class="form-control" type="date" name="date" min="{{ \Carbon\Carbon::today()->toDateString() }}" value="{{ $date->toDateString() }}" title="Date">
                    </div>
                    <button type="submit" class="btn btn-primary">Jump to date</button>
                </form>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-12 col-xl-10 offset-xl-1">
                <table class="table table-bordered calendar">
                 	<thead class="thead-inverse">
                    <tr>
                        <th></th>
                        @for ($h = 0; $h < 12; $h++)
                            <th class="text-xs-center">{{ $h == 0 ? (12) : $h }} {{ 'am' }}</th>
                        @endfor
                    </tr>
                    </thead>
					<tbody>
                    @foreach($rooms as $room)
					<?php						
						$roomMapper = RoomMapper::getInstance();
						$roomStatus = $roomMapper->getStatus($room->getName());
					?>
						<tr class="calendar-room-row">
							<th class="align-middle text-xs-center">{{ $room->getName() }}</th>
                            @for ($timeslot = $date->copy()->addHours(0); $timeslot->hour < 12; $timeslot->addHour())
                               	@include('calendar.timeslot')
                            @endfor
                        </tr>
                    @endforeach
                    </tbody>
					<thead class="thead-inverse">
                    <tr>
                        <th></th>
                        @for ($h = 12; $h < 24; $h++)
                            <th class="text-xs-center">{{ $h == 12 ? (12) : ($h % 13 + 1) }}{{ 'pm' }}</th>
                        @endfor
                    </tr>
                    </thead>
					<tbody>
                    @foreach($rooms as $room)
                    <?php						
						$roomMapper = RoomMapper::getInstance();
						$roomStatus = $roomMapper->getStatus($room->getName());
					?>
						<tr class="calendar-room-row">
                            <th class="align-middle text-xs-center">{{ $room->getName() }}</th>
							@for ($timeslot = $date->copy()->addHours(12); $timeslot->hour < 23; $timeslot->addHour())              
								@include('calendar.timeslot')
							@endfor
                        @include('calendar.timeslot')
						</tr>	
                    @endforeach             
                    </tbody>
					<thead>
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
