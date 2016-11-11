@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="pb-1">
            Modify your reservation
            <small class="text-muted">for {{ $reservation->getTimeslot()->format('l, F jS, Y') }} at {{ $reservation->getTimeslot()->format('ga') }} in {{ $reservation->getRoomName() }}</small>
        </h1>

        <form method="post" action="{{ route('reservationModifyPost', ['id' => $reservation->getId()]) }}">
            {{ csrf_field() }}
            <div class="form-group row{{ $errors->has('description') ? ' has-danger' : '' }}">
                <label for="inputDescription" class="col-sm-2 col-form-label">Description</label>
                <div class="col-sm-10">
                    <textarea class="form-control{{ $errors->has('description') ? ' form-control-danger' : '' }}" id="inputDescription" name="description" rows="3" placeholder="A brief description of the purpose of the reservation" required autofocus>{{ old('description') ?? $reservation->getDescription() }}</textarea>
                    @if ($errors->has('description'))
                        <div class="form-control-feedback">
                            {{ $errors->first('description') }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <div class="offset-sm-2 col-sm-10">
                    <a href="{{ route('reservation', ['id' => $reservation->getId(), 'back' => $back]) }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary">Modify</button>
                </div>
            </div>
        </form>
    </div>
@endsection
