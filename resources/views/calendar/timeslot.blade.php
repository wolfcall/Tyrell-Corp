	<?php
	//Obtaining the current Date and Time
	date_default_timezone_set('US/Eastern');
	$ourTime = date('H');	
	$ourDate = date('Y-m-d');
	$passedDate = $date->toDateString();
	?>
	
	{{-- Room cannot be selected. Time has past --}}
	@if ( ($timeslot->hour) <= $ourTime && $passedDate <= $ourDate )
		<td class="table-active align-middle text-xs-center">
		</td>
	@elseif ($r = $userReservations->first(function ($r) use ($room, $timeslot) 
	{ 
		{{-- First half makes sure the room is the same --}}
		{{-- Second half makes sure it is the right timeslot --}}
		return $r[0]->getRoomName() === $room->getName() && $r[0]->getTimeslot()->eq($timeslot); 
	}
	))
    	{{-- User has a reservation for this timeslot --}}
		@if ($r[0]->getPosition() === 0)
			{{-- Active reservation --}}
			<td class="table-success calendar-timeslot-selectable align-middle text-xs-center" title="Show reservation" data-href="{{ route('reservation', ['id' => $r[0]->getId()]) }}">
			</td>
		{{-- On the waiting list --}}
		@else			
			<td class="table-warning calendar-timeslot-selectable align-middle text-xs-center" title="Show reservation" data-href="{{ route('reservation', ['id' => $r[0]->getId()]) }}">
				Waiting #{{ $r[0]->getPosition() }}
			</td>
		@endif
	{{-- Room is being used --}}
	@elseif (($roomStatus[0]->busy) != 0)
		<td class="table-info align-middle text-xs-center" title="Reserve">
		</td>
	{{-- Room has been booked by someone else --}}
	@elseif ($r = $activeReservations->first(function ($r) use ($room, $timeslot) { return $r->getRoomName() === $room->getName() && $r->getTimeslot()->eq($timeslot); }))
		<td class="table-danger calendar-timeslot-selectable align-middle text-xs-center" title="Reserve" data-href="{{ route('request', ['room' => $room->getName(), 'timeslot' => $timeslot->format('Y-m-d\TH') ]) }}">
		</td>
	{{-- Room is free --}}
	@else		
		<td class="calendar-timeslot-selectable align-middle text-xs-center" title="Reserve" data-href="{{ route('request', ['room' => $room->getName(), 'timeslot' => $timeslot->format('Y-m-d\TH') ]) }}"></td>
	@endif