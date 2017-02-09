@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="pb-1">
            Modify your reservation
            <small class="text-muted">for {{ $reservation->getTimeslot()->format('l, F jS, Y') }} at {{ $reservation->getTimeslot()->format('g a') }} in {{ $reservation->getRoomName() }}</small>
        </h1>

        <form method="post" action="{{ route('reservationModifyPost', ['id' => $reservation->getId()]) }}">
            {{ csrf_field() }}
         <!--   <?php var_dump($reservation); ?> -->
            <div class="form-group row{{ $errors->has('description') ? ' has-danger' : '' }}">
                <label for="inputDescription" class="col-sm-3 col-form-label">Description</label>
                <div class="col-sm-9">
                    <textarea class="form-control{{ $errors->has('description') ? ' form-control-danger' : '' }}" id="inputDescription" name="description" rows="3" placeholder="A brief description of the purpose of the reservation" required autofocus>{{ old('description') ?? $reservation->getDescription() }}</textarea>
                    @if ($errors->has('description'))
                        <div class="form-control-feedback">
                            {{ $errors->first('description') }}
                        </div>
                    @endif
                </div>
            </div>
            <div class = "form-group row">
                <label for="inputMarkers" class="col-sm-3 col-form-label">Whiteboard Markers</label>
                <div class = "col-sm-3">
                    <input type = "number" class="form-control" id="inputMarkers" name="markers" value = "{{$reservation->getMarkers()}}" 
                    min = "0" max = "3" required >
                </div>
            </div>
            <div class = "form-group row">
                <label for="inputProjectors" class="col-sm-3 col-form-label">Projector</label>
                <div class = "col-sm-3">
                    <input type = "number" class="form-control" id="inputProjectors" name="projectors" value = "{{$reservation->getProjectors()}}"
                    min = "0" max = "3"  required >
                </div>
            </div>
            <div class = "form-group row">
                <label for="inputLaptops" class="col-sm-3 col-form-label">Laptop</label>
                <div class = "col-sm-3">
                    <input type = "number" class="form-control" id="inputLaptop" name="laptop" value = "{{$reservation->getLaptops()}}"
                    min = "0" max = "3"  required >
                </div>
            </div>
            <div class = "form-group row">
                <label for="inputCables" class="col-sm-3 col-form-label">Display Cables</label>
                <div class = "col-sm-3">
                    <input type = "number" class="form-control" id="inputCables" name="cables" value = "{{$reservation->getCables()}}"
                    min = "0" max = "3"  required >
                </div>
            </div>
            <div class="form-group row">
                <div class="offset-sm-3 col-sm-10">
                    <a href="{{ route('reservation', ['id' => $reservation->getId(), 'back' => $back]) }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary">Modify</button>
                </div>
            </div>
        </form>
    </div>
@endsection
