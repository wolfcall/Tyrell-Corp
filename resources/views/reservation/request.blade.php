@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="pb-1">
            Request a reservation
            <small class="text-muted">for {{ $timeslot->format('l, F jS, Y') }} at {{ $timeslot->format('g a') }} in {{ $room->getName() }}</small>
        </h1>

        <form method="post" action="{{ route('requestPost', ['room' => $room->getName(), 'date' => $timeslot->format('Y-m-d\TH')]) }}">
            {{ csrf_field() }}
            <div class="form-group row{{ $errors->has('description') ? ' has-danger' : '' }}">
                <label for="inputDescription" class="col-sm-2 col-form-label">Description</label>
                <div class="col-sm-10">
                    <textarea class="form-control{{ $errors->has('description') ? ' form-control-danger' : '' }}" id="inputDescription" name="description" rows="3" placeholder="A brief description of the purpose of the reservation" required autofocus>{{ old('description') }}</textarea>
                    @if ($errors->has('description'))
                        <div class="form-control-feedback">
                            {{ $errors->first('description') }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="form-group row{{ $errors->has('recur') ? ' has-danger' : '' }}">
                <label for="inputRecur" class="col-xs-2 col-form-label">Recur for</label>
                <div class="col-xs-3">
                    <div class="input-group">
                        <input class="form-control{{ $errors->has('recur') ? ' form-control-danger' : '' }}" type="number" name="recur" min="1" max="10" value="1" id="inputRecur">
                        <span class="input-group-addon">week(s)</span>
                    </div>
                    @if ($errors->has('recur'))
                        <div class="form-control-feedback">
                            {{ $errors->first('recur') }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <div class="offset-sm-2 col-sm-10">
                    <a href="{{ route('calendar', ['date' => $timeslot->toDateString()]) }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary">Request</button>
                </div>
            </div>
        </form>
    </div>
@endsection
