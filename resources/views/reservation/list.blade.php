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
        <h1 class="pb-1">Your reservations</h1>

        <table class="table">
            <thead>
            <tr>
                <th>Status</th>
                <th>Date</th>
                <th>Time</th>
                <th>Room</th>
                <th>Description</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($reservations as $r)
                <?php
				
				date_default_timezone_set('US/Eastern');
				$ourTime = date('H');	
				$ourDate = date('Y-m-d');
				$passedDate = $r[0]->getTimeslot()->format('Y-m-d')
				
				?>
				@if ( $r[0]->getTimeslot()->format('H') > $ourTime && $passedDate >= $ourDate )
					<tr class="{{ $r[1] > 0 ? 'table-warning' : '' }}">
						<th scope="row">
							@if ($r[1] === 0)
								Active
							@else
								Waiting, position #{{ $r[1] }}
							@endif
						</th>
						<td>{{ $r[0]->getTimeslot()->format('l, F jS, Y') }}</td>
						<td>{{ $r[0]->getTimeslot()->format('g a') }}</td>
						<td>{{ $r[0]->getRoomName() }}</td>
						<td class="pre">{{ $r[0]->getDescription() }}</td>
						<td><a href="{{ route('reservation', ['id' => $r[0]->getId(), 'back' => 'list']) }}" class="btn btn-primary">View</a></td>
					</tr>
				@endif
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
