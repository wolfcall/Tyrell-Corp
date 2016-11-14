@if ($r = $userReservations->first(function ($r) use ($room, $timeslot) { return $r[0]->getRoomName() === $room->getName() && $r[0]->getTimeslot()->eq($timeslot); }))
    @if ($r[1] === 0)
        <td class="table-success calendar-timeslot-selectable align-middle text-xs-center" title="Reserved by you" data-href="{{ route('reservation', ['id' => $r[0]->getId()]) }}">
            Reserved
        </td>
    @else
        <td class="table-warning calendar-timeslot-selectable align-middle text-xs-center" title="Reserved by you, waiting" data-href="{{ route('reservation', ['id' => $r[0]->getId()]) }}">
            Waiting<br>(#{{ $r[1] }})
        </td>
    @endif
@elseif ($r = $activeReservations->first(function ($r) use ($room, $timeslot) { return $r->getRoomName() === $room->getName() && $r->getTimeslot()->eq($timeslot); }))
    <td class="table-info calendar-timeslot-selectable align-middle text-xs-center" data-href="{{ route('request', ['room' => $room->getName(), 'timeslot' => $timeslot->format('Y-m-d\TH') ]) }}">
        Taken
    </td>
@else
    <td class="calendar-timeslot-selectable align-middle text-xs-center" title="Reserve" data-href="{{ route('request', ['room' => $room->getName(), 'timeslot' => $timeslot->format('Y-m-d\TH') ]) }}"></td>
@endif