@if ($r = $userReservations->first(function ($r) use ($room, $timeslot) { return $r[0]->getRoomName() === $room->getName() && $r[0]->getTimeslot()->eq($timeslot); }))
    {{-- User has a reservation for this timeslot --}}
    @if ($r[1] === 0)
        {{-- Active reservation --}}
        <td class="table-success calendar-timeslot-selectable align-middle text-xs-center" title="Show reservation" data-href="{{ route('reservation', ['id' => $r[0]->getId()]) }}">
            
        </td>
    @else
        {{-- On the waiting list --}}
        <td class="table-warning calendar-timeslot-selectable align-middle text-xs-center" title="Show reservation" data-href="{{ route('reservation', ['id' => $r[0]->getId()]) }}">
            Waiting, #{{ $r[1] }}
        </td>
    @endif
@elseif ($r = $activeReservations->first(function ($r) use ($room, $timeslot) { return $r->getRoomName() === $room->getName() && $r->getTimeslot()->eq($timeslot); }))
    {{-- Room has been booked by someone else --}}
    <td class="table-info calendar-timeslot-selectable align-middle text-xs-center" title="Reserve" data-href="{{ route('request', ['room' => $room->getName(), 'timeslot' => $timeslot->format('Y-m-d\TH') ]) }}">
        
    </td>
@else
    {{-- Room is free --}}
    <td class="calendar-timeslot-selectable align-middle text-xs-center" title="Reserve" data-href="{{ route('request', ['room' => $room->getName(), 'timeslot' => $timeslot->format('Y-m-d\TH') ]) }}"></td>
@endif