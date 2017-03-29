@extends('layouts.app')
<?php

use App\Data\Mappers\RoomMapper;
//Obtain the current date and time
$now = date("Y-m-d G:i:s");

//Check to see that the user's timestamp has not exceeded 30 seconds
$compare = $time = date("Y-m-d G:i:s", time() - 30);

//Check to see if the session variable timestamp and user are set, signifying that the user just entered a room
//Also make sure that the session variable user matches the user that is currently logged in
if (isset($_SESSION["timestamp"]) && ($_SESSION["user"] == (Auth::id()) ) && $compare > $_SESSION["timestamp"]) {
    //Pass the timestamp to a variable, signifying the time that the room was accessed
    $time = $_SESSION["timestamp"];
    $times = array();
    $times["past"][0][0] = explode("-", explode(" ", $time)[0])[0];
    $times["past"][0][1] = explode("-", explode(" ", $time)[0])[1];
    $times["past"][0][2] = explode("-", explode(" ", $time)[0])[2];
    $times["past"][1][0] = explode(":", explode(" ", $time)[1])[0];
    $times["past"][1][1] = explode(":", explode(" ", $time)[1])[1];
    $times["past"][1][2] = explode(":", explode(" ", $time)[1])[2];
    $times["now"][0][0] = explode("-", explode(" ", $now)[0])[0];
    $times["now"][0][1] = explode("-", explode(" ", $now)[0])[1];
    $times["now"][0][2] = explode("-", explode(" ", $now)[0])[2];
    $times["now"][1][0] = explode(":", explode(" ", $now)[1])[0];
    $times["now"][1][1] = explode(":", explode(" ", $now)[1])[1];
    $times["now"][1][2] = explode(":", explode(" ", $now)[1])[2];
    $result = array(0, 0, 0, 0, 0, 0);

    //Loops through the time now, puts it in the proper format for comparison
    for ($x = 5; $x >= 0; $x--) {
        $result[$x] = $times["now"][$x / 3][$x % 3] - $times["past"][$x / 3][$x % 3];
    }

    //If it has been more than 30 seconds since the user accessed the room, set the lock to 0
    if ($result[0] + $result[1] + $result[2] + $result[3] + $result[4] > 0 || $result[5] >= 30) {
        $lock = 0;
        unset($_SESSION["timestamp"]);
        unset($_SESSION["user"]);
    } 
    //Else just keep decrementing the lock
    else {
        $lock = (30 - $result[5]);
    }
} 
//If the same user is not signed in, unset the variables and put the lock to 0
else {
    $lock = 0;
    unset($_SESSION["timestamp"]);
    unset($_SESSION["user"]);
}
?>
@section('content')

<?php
//If the lock is greater than 0, call the timer
if ($lock > 0) {
    echo "<body onload = 'onTimer()'>";
    //Show the user how much time they have left to wait before accessing the rooms again
    echo "<div class = 'timer' style='color:red;text-align: center;'>Reservation Block ends in <span id='timer'></span> seconds!</div><br>";
} else {
    echo "<body>";
}
?>
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
                    <li type="square" style="color:#b3b3cc">Unavailable (Time passed or you must wait)</li>
                    <li type="square" style="color:#84d2f9">Room is being used by another student</li>
                </ul>
            </fieldset>
        </div>

        @if($lock == 0)

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

        @endif
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
                    //Check if the Room is busy or not
                    //To be used in the Calendar Timeslot
                    $roomMapper = RoomMapper::getInstance();
                    $roomStatus = $roomMapper->getStatus($room->getName());

                    $unlockTime = strtotime($roomStatus[0]->dateTime) + 60;
                    $compare = strtotime($now);

                    //Populate the Rooms for the Morning
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
                    //Check if the Room is busy or not
                    //To be used in the Calendar Timeslot
                    $roomMapper = RoomMapper::getInstance();
                    $roomStatus = $roomMapper->getStatus($room->getName());

                    $unlockTime = strtotime($roomStatus[0]->dateTime) + 60;
                    $compare = strtotime($now);

                    //Populate the Rooms for the Afternoon and Evening
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
</body>
@endsection

@push('scripts')
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $(".calendar-timeslot-selectable").click(function () {
            window.document.location = $(this).data("href");
        });
    });

    var i = "<?php echo $lock; ?>";

    //Function to create the 30 second wait Timer
    function onTimer()
    {
        //Populate the inner HTML of the ID where the timer is placed
        document.getElementById('timer').innerHTML = i;
        i--;

        if (i < 0)
        {
            //When the timer runs out, remove the data from local storage and then re-direct the user back to the calendar
            window.location.href = '{{route("calendar")}}';
        } else
        {
            //Recurse after 1 second has passed
            setTimeout(onTimer, 1000);
        }
    }

<?php
if (isset($lock) && $lock > 0) {
    echo "setTimeout(function (){
                        $('.unlock').removeClass('table-active');
                        $('.unlock').addClass('calendar-timeslot-selectable');
                        $('.calendar-timeslot-selectable').click(function () {
                                window.document.location = $(this).data('href');
                        });
                }, " . ($lock * 1000) . ");";
}
?>

</script>
@endpush
