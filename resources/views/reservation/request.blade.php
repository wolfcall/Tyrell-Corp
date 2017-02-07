@extends('layouts.app')
<script>
	var i = 60;
	var left = localStorage.left;
	//Check to see if the page was reloaded
	//If it was, continue the timer where it left off
	if (performance.navigation.type == 1) 
	{
	  console.info( "This page is reloaded" );
	  i = localStorage.left;
	}
	//If it was not, simply continue
	else
	{
	  console.info( "This page is not reloaded");
	}
	
	function onTimer() 
	{
	document.getElementById('timer').innerHTML = i;
	i--;
	localStorage.left = i;	
		if (i < 0) 
		{
			localStorage.removeItem("left");
			window.location.href = '{{route("calendar")}}';
		}
		else 
		{
			setTimeout(onTimer, 1000);
		}
	}
		
	var j = 0;

	function duplicate() {

		if(j<3)
		{
			var original = document.getElementById('duplicater' + j);
			var clone = original.cloneNode(true); // "deep" clone
			clone.id = "duplicater" + ++j; // there can only be one element with an ID
			original.parentNode.appendChild(clone);
		}
		else
		{
			alert("You cannot add anymore Equipment!");
		}
	}	
	
	function remove() {

		if(j == 0)
		{
			alert("You cannot remove any more Equipment!");
		}
		else
		{
			var original = document.getElementById('duplicater' + j);
			original.parentNode.removeChild(original);
			--j;
			
		}
	}	
	
</script>
@section('content')
	<!-- <body onload="onTimer()"> -->
	<div class="container" >
        <h1 class="pb-1">
            Request a Reservation
            <small class="text-muted">for {{ $timeslot->format('l, F jS, Y') }} at {{ $timeslot->format('g a') }} in {{ $room->getName() }}</small>
        </h1>
			
		<div class = "timer" style="color:red;text-align: center;">Reservation Request closes in <span id="timer"></span> seconds!</div><br>
        <form method="post" action="{{ route('requestPost', ['room' => $room->getName(), 'date' => $timeslot->format('Y-m-d\TH')]) }}">
            {{ csrf_field() }}
			<div class="container2">
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
				<div id="duplicater0" class="form-group row{{ $errors->has('equipment') ? ' has-danger' : '' }}">
					<label for="inputEquipment" class="col-xs-3 col-sm-2 col-form-label">Equipment</label>
					<div class="col-xs-9 col-md-3">
						<div class="input-group">
							<select class="form-control{{ $errors->has('equipment') ? ' form-control-danger' : '' }}">
								<option value="None" selected="selected">None</option>
								<option value="Display Cables">Display Cables</option>
								<option value="Laptop">Laptop</option>
								<option value="Projector">Projector</option>
								<option value="WhiteBoard Markers">WhiteBoard Markers</option>
							</select>
						</div>
						@if ($errors->has('equipment'))
							<div class="form-control-feedback">
								{{ $errors->first('equipment') }}
							</div>
						@endif
					</div>
					<div class="col-xs-9 col-md-3">
						<div class="input-group">
							<input class="form-control{{ $errors->has('quantity') ? ' form-control-danger' : '' }}" type="number" name="quantity" min="0" max="3" value="0" id="quantity">
							<span class="input-group-addon">Quantity</span>
						</div>
						@if ($errors->has('quantity'))
							<div class="form-control-feedback">
								{{ $errors->first('quantity') }}
							</div>
						@endif
					</div>
				</div>	
			</div>
			<div class="form-group row">
				<div class="offset-sm-2 col-sm-10">
					<a id = 'adding' onclick = duplicate() class="btn btn-secondary"><i class="fa fa-chevron-down" aria-hidden="true"></i> Add more Equipment</a>
					<a id = 'remove' onclick = remove() class="btn btn-secondary"><i class="fa fa-chevron-up" aria-hidden="true"></i> Remove Equipment</a>
				</div>
			</div>
            <div class="form-group row{{ $errors->has('recur') ? ' has-danger' : '' }}">
                <label for="inputRecur" class="col-xs-3 col-sm-2 col-form-label">Recur for</label>
                <div class="col-xs-9 col-md-3">
                    <div class="input-group">
                        <input class="form-control{{ $errors->has('recur') ? ' form-control-danger' : '' }}" type="number" name="recur" min="1" max="3" value="1" id="inputRecur">
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
                    <a href="{{ route('requestCancel', ['room' => $room->getName()]) }}" class="btn btn-secondary"><i class="fa fa-chevron-left" aria-hidden="true"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary">Request</button>
                </div>
            </div>
        </form>
    </div>
	<!-- </body> -->
@endsection
