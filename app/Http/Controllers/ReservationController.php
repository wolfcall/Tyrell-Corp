<?php

namespace App\Http\Controllers;

use App\Data\Mappers\ReservationMapper;
use App\Data\Mappers\RoomMapper;
use App\Data\Mappers\UserMapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    const MAX_PER_TIMESLOT = 4;
    const MAX_PER_USER = 3;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function viewReservationList(Request $request)
    {
        $roomMapper = RoomMapper::getInstance();
		$roomMapper->clearStudent(Auth::id());
		
		$reservationMapper = ReservationMapper::getInstance();
        $reservations = $reservationMapper->findPositionsForUser(Auth::id());

        return view('reservation.list', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function viewReservation(Request $request, $id)
    {
        // validate reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        // get a list of all the other reservations for the same room-timeslot
        $position = $reservationMapper->findPosition($reservation);

        return view('reservation.show', [
            'reservation' => $reservation,
            'position' => $position,
            'back' => $request->input('back', 'calendar')
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function showModifyForm(Request $request, $id)
    {
        // validate reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        return view('reservation.modify', [
            'reservation' => $reservation,
            'back' => $request->input('back')
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function modifyReservation(Request $request, $id)
    {
        // validate reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        // update the description, and all equipment
        $reservationMapper->set($reservation->getId(), $request->input('description', ""), $request->input('markers', ""),
			$request->input('projectors', ""), $request->input('laptops', ""), $request->input('cables', ""));

		requestReservation($request, $reservation->getRoomName(), $reservation->getTimeslot());
        $reservationMapper->done();

        return redirect()
            ->route('reservation', ['id' => $reservation->getId(), 'back' => $request->input('back')])
            ->with('success', 'Successfully modified reservation!');
    }

    /**
     * @param Request $request
     * @param string $roomName
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function showRequestForm(Request $request, $roomName, $timeslot)
    {
        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);
    
		// validate room exists
        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);
		
		if ($room === null) {
            return abort(404);
        }

		//Check to see who is currently using the room
		$roomStatus = $roomMapper->getStatus($roomName);

		if (($roomStatus[0]->busy) != 0 && $roomStatus[0]->busy != Auth::id()) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', sprintf("The room %s is currently busy! Please try again later. We apologize for any inconvenience.", $roomName));
        }
		//If its not busy, then set it to busy
		else
		{
			$roomStatus = $roomMapper->setBusy($roomName, Auth::id());
		}
		
        $reservationMapper = ReservationMapper::getInstance();
			
        // check if user exceeded maximum amount of reservations
        $reservationCount = count($reservationMapper->countInRange(Auth::id(), $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek()));

		if ($reservationCount >= static::MAX_PER_USER) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', sprintf("You've exceeded your reservation request limit of (%d) for this week.<br> 
				Please try reserving next week or remove a reservation from this week to be eligible.", static::MAX_PER_USER));
        }

        // check if waiting list for timeslot is full
        $reservations = $reservationMapper->findForTimeslot($roomName, $timeslot);

        if (count($reservations) >= static::MAX_PER_TIMESLOT) {
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', 'The waiting list for that time slot is full.');
        }
	
        return view('reservation.request', [
            'room' => $room,
            'timeslot' => $timeslot
        ]);
    }

    /**
     * @param Request $request
     * @param string $roomName
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function requestReservation(Request $request, $roomName, $timeslot)
    {
        $reservationMapper = ReservationMapper::getInstance();
		$userMapper = UserMapper::getInstance();
		
		$this->validate($request, [
            'description' => 'required',
			'recur' => 'required|integer|min:1|max:'.static::MAX_PER_USER,
			'quantity' => 'required|integer|min:0|max:'.static::MAX_PER_USER
        ]);

        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

        // validate room exists
        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);

        if ($room === null) {
            return abort(404);
        }

        // generate a UUID for this reservation session, which will link recurring reservations together
        $uuid = \Uuid::generate();
        $reservations = [];

        $recur = intval($request->input('recur', 1));
				
        // status message arrays
        $successful = [];
		$waitlisted = [];
        $errored = [];

        // loop over every recurring week and independently request the reservation for that week
        for ($t = $timeslot->copy(), $i = 0; $i < $recur; $t->addWeek(), ++$i) 
		{
            /*
             * Pre-insert checks
             */

            // check if user exceeded maximum amount of reservations
            $reservationCount = count($reservationMapper->countInRange(Auth::id(), $t->copy()->startOfWeek(), $t->copy()->startOfWeek()->addWeek()));
            if ($reservationCount >= static::MAX_PER_USER) {
                $errored[] = [$t->copy(), sprintf("You've exceeded your weekly reservation request limit of %d.", static::MAX_PER_USER)];
                continue;
            }

            // check if waiting list for timeslot is full
           	$waitingList = $reservationMapper->findForTimeslot($roomName, $t);
            
			if (count($waitingList) >= static::MAX_PER_TIMESLOT) {
                $errored[] = [$t->copy(), 'The waiting list is full.'];
                continue;
            }			
			
			//Check if any active reservations in any other rooms would overlap with the current reservation
			$overlap = $reservationMapper->findAllTimeslotActive($t, Auth::id());
						
			if (count($overlap)) {
                $errored[] = [$t->copy(), 'You already have a reservation for that time in Room '.$overlap[0]->room_name.'. Choose another time slot.'];
                continue;
            }

			$markersCount = 0;
			$projectorsCount = 0;
			$laptopsCount = 0;
			$cablesCount = 0;
			
			//Check all the equipment that is being used during that timeslot
			$equipmentCount = $reservationMapper->countEquipment($t);
			foreach($equipmentCount as $e)
			{
				$markersCount += $e->quantity_markers;
				$projectorsCount += $e->quantity_projectors;
				$laptopsCount += $e->quantity_laptops;
				$cablesCount += $e->quantity_cables;
			}
						
			//Compile all the requested equipment into an associative array
			$equipmentRequest = array();
			
			$q1 = intval($request->input('quantity', ''));
			$e1 = $request->input('equipment', '');
			if($q1 && $e1 != '')
			{
				$equipmentRequest[$e1] = $q1;
			}
			
			$q2 = intval($request->input('quantity1', ''));
			$e2 = $request->input('equipment1', '');
			if($q2 && $e2 != '')
			{
				$equipmentRequest[$e2] = $q2;
			}
			
			$q3 = intval($request->input('quantity2', ''));
			$e3 = $request->input('equipment2', '');
			if($q3 && $e3 != '')
			{
				$equipmentRequest[$e3] = $q3;
			}
			
			$q4 = intval($request->input('quantity3', ''));
			$e4 = $request->input('equipment3', '');
			if($q4 && $e4 != '')
			{
				$equipmentRequest[$e4] = $q4;
			}
			
			//Use a boolean to know if the status of the equipment is ok
			//Start the boolean as true
			$eStatus = true;
			
			//Keep track of how much they asked for
			$markersRequest = 0;
			$projectorsRequest = 0;
			$laptopsRequest = 0;
			$cablesRequest = 0;
			
			//Loop through all the equipment and check if it is available compared to what is found in the database 
			foreach ($equipmentRequest as $key => $value) 
			{
				if($key == 'WhiteBoard Markers')
				{
					$markersRequest = $value;
					if($markersRequest > (3-$markersCount))
					{
						$eStatus = false;
						continue;
					}
				}
				else if ($key == 'Laptop')
				{
					$laptopsRequest = $value;
					if($laptopsRequest > (3-$laptopsCount))
					{
						$eStatus = false;
						continue;
					}
				}
				else if ($key == 'Projector')
				{
					$projectorsRequest = $value;
					if($projectorsRequest > (3-$projectorsCount))
					{
						$eStatus = false;
						continue;
					}
				}
				else if ($key == 'Display Cables')
				{
					$cablesRequest = $value;
					if($cablesRequest > (3-$cablesCount))
					{
						$eStatus = false;
						continue;
					}
				}
			}

			//Check if the student is in capstone, so we can know to give him priority or not
			$capstone = $userMapper->capstone(Auth::id());
				
			//Only execute if the student is a Capstone student
			//And if there are already someone in the waitling list
			//Iterate through all the reservations found
			$count = 1;
			if($capstone && count($waitingList) > 1)
			{
				foreach($waitingList as $w)
				{
					$student = $w->getUserId();
					$position = $w->getPosition();
					$status = $userMapper->capstone($student);
					//If any of the users are from capstone, then increment the count variable
					if($status)
					{
						$count++;
					}
					//This means the person who has the room is not a capstone student
					//However we will not kick him out, we will just skip him
					elseif($position == 0)
					{
						continue;
					}
					//Move them down by incrementing their position on the waitlist
					else
					{
						//Increment waitlist position
						$reservationMapper->moveDown($w);
					}
				}
				/*
				* Insert
				*/
				$reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, $count, $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
			}
			//If no one is in the room, then execute as if it was a regular student
			else
			{
				/*
				* Insert
				*/
				if($eStatus)
				{
					$reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, count($waitingList), $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
				}
				else if(!$eStatus)
				{
					$reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, 1, $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
				}
			}	
		}

        // run the reservation operations now, as we need to process the results
        $reservationMapper->done();

        /*
         * Post-insert checks
         */

        foreach ($reservations as $reservation) 
		{
            $t = $reservation->getTimeslot();
			
			// Check the current active reservations
			$active = $reservationMapper->countInRange(Auth::id(), $t->copy()->startOfWeek(), $t->copy()->startOfWeek()->addWeek());

			// Check the current waitlisted reservations
			$waited = $reservationMapper->countAll(Auth::id(), $t->copy()->startOfWeek(), $t->copy()->startOfWeek()->addWeek());

			//Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
			$overlap = $reservationMapper->findAllTimeslotWaitlisted($t, Auth::id(), $reservation->getRoomName());
			
			//If the amount of active reservaitons is at 3, then delete all the ones on the waitlist from the current week
			if(count($active) == 3)
			{
				foreach($waited as $w)
				{
					$temp = $reservationMapper->find($w->id);
					$t2 = $temp->getTimeslot();
					
					$reservationMapper->delete($w->id);
					$errored[] = [$t2, 'You have been removed from the waitlist in room '.$w->room_name.' at '. $t2->format('g a').'.'];
					continue;
				}
			}
			//If any waitlisted reservations in any parallel rooms exist for the user, delete them
			elseif (count($overlap) && $reservation->getPosition() == 0) 
			{
                foreach($overlap as $o)
				{
					var_dump($o);
					$temp = $reservationMapper->find($o->id);
					$t2 = $temp->getTimeslot();
					
					$errored[] = [$t2, 'You have been removed from the waitlist in room '.$o->room_name.' at '. $t2->format('g a').'.'];
					$reservationMapper->delete($o->id);
				}
            }
			
            // check if there was an error inserting the reservation, ie. duplicate reservation
            if ($reservation->getId() === null) {
                $errored[] = [$t, 'You already have a reservation for this time slot.'];
                continue;
            }
							
            // find the new reservation's position #
            $position = $reservation->getPosition();

            if ($position > static::MAX_PER_TIMESLOT) {
                // this request has exceeded the limit, delete it
                $reservationMapper->delete($reservation->getId());
                $errored[] = [$t, 'The waiting list is full.'];
            } else if ($position === 0) {
                // the reservation is active
                $successful[] = $t;
            } else {
                // user has been put on a waiting list
                $waitlisted[] = [$t, $position];
            }
        }

        // commit one last time, to finalize any deletes we had to do
        $reservationMapper->done();

        $response = redirect()
            ->route('calendar', ['date' => $timeslot->toDateString()]);

        /*
         * Format the status messages
         */
        if (count($successful)) {
			if(count($active) == 3)
			{
				$response = $response->with('success', sprintf('You have reached the maximum reservations for the week! Removing all waitlists.<br> The following reservations have been successfully created for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
					return sprintf("<li><strong>%s</strong></li>", $m->format('l, F jS, Y'));
				}, $successful))));				
			}
			else			
			{
				$response = $response->with('success', sprintf('The following reservations have been successfully created for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
					return sprintf("<li><strong>%s</strong></li>", $m->format('l, F jS, Y'));
				}, $successful))));
			}			
        }

        if (count($waitlisted)) {
            if(!$eStatus)
			{
				$response = $response->with('warning', sprintf('Equipment is not available for your Reservation. You have been put on a waiting list for the following: %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                return sprintf("<li><strong>%s</strong>: Position #%d</li>", $m[0]->format('l, F jS, Y'), $m[1]);
				}, $waitlisted))));
			}
			else
			{
				$response = $response->with('warning', sprintf('You have been put on a waiting list for the following reservations for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                return sprintf("<li><strong>%s</strong>: Position #%d</li>", $m[0]->format('l, F jS, Y'), $m[1]);
				}, $waitlisted))));
			}
        }

        if (count($errored)) {
            $response = $response->with('error', sprintf('The following requests were unsuccessful for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                return sprintf("<li><strong>%s</strong>: %s</li>", $m[0]->format('l, F jS, Y'), $m[1]);
            }, $errored))));
        }

		//Now that the user is done with the room, open it up again
		$roomStatus = $roomMapper->setFree($roomName);
		
        return $response;
    }

	/**
	* @param string $roomName

	* @return \Illuminate\Http\Response
	*/
    public function requestCancel($roomName)
    {
		//Now that the user is done with the room, open it up again
		$roomMapper = RoomMapper::getInstance();
		$roomStatus = $roomMapper->setFree($roomName);
		
		$response = redirect()
            ->route('calendar');
		
		return $response;
	}
		
    /**
     * @param Request $request
     * @param string $id
	 * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function cancelReservation(Request $request, $id, $roomName, $timeslot)
    {
		// valiadte reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);
		
		$timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

		$waitingList = $reservationMapper->findForTimeslot($roomName, $timeslot);
			
		//Find out the position in the waiting list of the Reservation we will be deleting
		foreach($waitingList as $w)
		{
			$target = $w->getId();
			if($target == $id)
			{
				$position = $w->getPosition();
			}
		}
		
		//For everyone in position after the Reservation to be deleted, move them down one
		foreach($waitingList as $w)
		{
			$next = $w->getPosition();
			if($position < $next)
			{
				$reservationMapper->setNewWaitlist($w->getId(), $next-1);
			}
		}
				
        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        // delete the reservation
        $reservationMapper->delete($reservation->getId());
        $reservationMapper->done();
		
		/**
		* 	Post delete checks
		*/
		
		//Find out who took the place of the user who deleted their reservation
		$winner = $reservationMapper->findTimeslotWinner($timeslot, $reservation->getRoomName());
		
		if(count($winner))
		{
			$winnerID = $winner[0]->user_id;
						
			// Check the current amount of active reservations for the winner
			$active = $reservationMapper->countInRange($winnerID, $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());
			
			// Check the current waitlisted reservations
			$waited = $reservationMapper->countAll($winnerID, $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());
			
			//Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
			$overlap = $reservationMapper->findAllTimeslotWaitlisted($timeslot, $winnerID, $reservation->getRoomName());
		
			//If the amount of active reservaitons is at 3, then delete all the ones on the waitlist from the current week
			if(count($active) == 3)
			{
				foreach($waited as $w)
				{
					$temp = $reservationMapper->find($w->id);
					$t2 = $temp->getTimeslot();
					
					$reservationMapper->delete($w->id);
					//No need to display this information to the user
					continue;
				}
			}
			//If any waitlisted reservations in any parallel rooms exist for the winnning user, delete them
			elseif (count($overlap) && $winner[0]->wait_position == 0) 
			{
				foreach($overlap as $o)
				{
					$temp = $reservationMapper->find($o->id);
					$t2 = $temp->getTimeslot();
					
					$reservationMapper->delete($o->id);
					//No need to display this information to the user
					continue;
				}
			}
			
			//Commit once again
			$reservationMapper->done();	
		}
		
		
        $response = redirect();

        // redirect to appropriate back page
        if ($request->input('back') === 'list') {
            $response = $response->route('reservationList');
        } else {
            $response = $response->route('calendar', ['date' => $reservation->getTimeslot()->toDateString()]);

        }

        return $response->with('success', 'Successfully cancelled reservation!');
    }
}
