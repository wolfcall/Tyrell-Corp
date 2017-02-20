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
	private $modifying = false;
	private $success1 = true;
	private $success2 = true;
	
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

		$date = substr($reservation->getTimeslot()->toDateTimeString(), 0, 10);
        $timeslot = $date." ".$request->input('timeslot', "").":00:00" ;
		$newTimeslot = new Carbon ( $date." ".$request->input('timeslot', "").":00:00" );
		$new = $newTimeslot->format('Y-m-d\TH');
		
		$newRoom = $request->input('roomName', "");
		
		$newLaptops = $request->input('laptops', "");
		$newProjectors = $request->input('projectors', "");
		$newCables = $request->input('cables', "");
		$newMarkers = $request->input('markers', "");
		
		$OGTimeslot = $reservation->getTimeslot();
		$OG = $reservation->getTimeslot()->format('Y-m-d\TH');
		$OGRoom = $reservation->getRoomName();
		
		$OGLaptops = $reservation->getLaptops();
		$OGProjectors = $reservation->getProjectors();
		$OGCables = $reservation->getCables();
		$OGMarkers = $reservation->getMarkers();
		
		//Status to check if any of the info besides the description changed
		$status = true;
		
		if($newTimeslot != $OGTimeslot)
		{
			$status = false;
		}
		elseif($newRoom != $OGRoom)
		{
			$status = false;
		}
		elseif($newLaptops != $OGLaptops)
		{
			$status = false;
		}
		elseif($newProjectors != $OGProjectors)
		{
			$status = false;
		}
		elseif($newCables != $OGCables)
		{
			$status = false;
		}
		elseif($newMarkers != $OGMarkers)
		{
			$status = false;
		}
				
		//Same Room and Timeslot means 
		if($status)
		{
			// update the description, and all equipment
			$reservationMapper->set($reservation->getId(), $request->input('description', ""), $newMarkers,	$newProjectors, $newLaptops, $newCables,
				$request->input('timeslot', ""), $newRoom);
				
			$reservationMapper->done();

			return redirect()
				->route('reservation', ['id' => $reservation->getId(), 'back' => $request->input('back')])
				->with('success', 'Successfully modified reservation!');
		}
		else
		{
			$this->modifying = true;
			
			$temp1 = $this->showRequestForm($request, $newRoom, $new);
			
			if( ($this->success1) == true )
			{
				$temp2 = $this->requestReservation($request, $newRoom, $new);
				if ( ($this->success2) == true )
				{
					$active = $reservationMapper->findForTimeslot($newRoom, $newTimeslot);
					
					foreach($active as $a)
					{
						if($a->getUserId() == Auth::id() )
						{
							$newID = $a->getId();
						}
					}
					
					$this->cancelReservation($request, $id, $OGRoom, $OG);
					return redirect()
						->route('reservation', ['id' => $newID, 'back' => $request->input('back')]);
				}
				else
				{
					$this->success2 = true;
					return $temp2;
				}
			}
			else
			{
				$this->success1 = true;
				return $temp1;
			}
		}
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
            if( ($this->modifying) == true)
			{
				$this->success1 = false;
			}
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
            if( ($this->modifying) == true)
			{
				$this->success1 = false;
			}
			return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', sprintf("You've exceeded your reservation request limit of (%d) for this week.<br> 
				Please try reserving next week or remove a reservation from this week to be eligible.", static::MAX_PER_USER));
        }

        // check if waiting list for timeslot is full
        $reservations = $reservationMapper->findForTimeslot($roomName, $timeslot);

        if (count($reservations) >= static::MAX_PER_TIMESLOT) {
			if( ($this->modifying) == true)
			{
				$this->success1 = false;
			}
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                ->with('error', 'The waiting list for that time slot is full.');
        }
	
        if( ($this->modifying) == true)
		{
			//Do nothing
		}
		else
		{
			return view('reservation.request', [
            'room' => $room,
            'timeslot' => $timeslot
			]);
		}
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
			//'recur' => 'required|integer|min:1|max:'.static::MAX_PER_USER,
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
                if( ($this->modifying) == true)
				{
					$this->success2 = false;
				}
				$errored[] = [$t->copy(), sprintf("You've exceeded your weekly reservation request limit of %d.", static::MAX_PER_USER)];
				continue;
            }

            // check if waiting list for timeslot is full
           	$waitingList = $reservationMapper->findForTimeslot($roomName, $t);
            
			if (count($waitingList) >= static::MAX_PER_TIMESLOT) {
                if( ($this->modifying) == true)
				{
					$this->success2 = false;
				}
				$errored[] = [$t->copy(), 'The waiting list is full.'];
				continue;
            }			
			
			//Check if any active reservations in any other rooms would overlap with the current reservation
			$overlap = $reservationMapper->findAllTimeslotActive($t, Auth::id());
						
			if (count($overlap)) {
                if( ($this->modifying) == true)
				{
					$this->success2 = false;
				}
				$errored[] = [$t->copy(), 'You already have a reservation for that time in Room '.$overlap[0]->room_name.'. Choose another time slot.'];
                continue;
            }

			//Compile all the requested equipment
			$markersRequest = intval($request->input('markers', 1));
			$laptopsRequest = intval($request->input('laptops', 1));
			$projectorsRequest = intval($request->input('projectors', 1));
			$cablesRequest = intval($request->input('cables', 1));
			
			$eStatus = $reservationMapper->statusEquipment($t, $markersRequest, $laptopsRequest, $projectorsRequest, $cablesRequest);
			
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
				if(!$eStatus && count($waitingList) == 0)
				{
					$reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, 1, $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
				}
				else
				{
					$reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, count($waitingList), $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
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
					$temp = $reservationMapper->find($o->id);
					$t2 = $temp->getTimeslot();
					
					$errored[] = [$t2, 'You have been removed from the waitlist in room '.$o->room_name.' at '. $t2->format('g a').'.'];
					$reservationMapper->delete($o->id);
				}
            }
			
            // check if there was an error inserting the reservation, ie. duplicate reservation
            if ($reservation->getId() === null) {
                if( ($this->modifying) == true)
				{
					$this->success2 = false;
				}
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

		$roomStatus = $roomMapper->getStatus($roomName);
		
		//Now that the user is done with the room, open it up again
		if( $roomStatus[0]->busy == Auth::id() )
		{
			$roomMapper->setFree($roomName);
		}
		
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
		// validate reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);
		
		$timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);
		
		//Waiting List for the same room the reservation is being deleted from
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

		// Delete the reservation & commit for DB update
        $reservationMapper->delete($reservation->getId());
        $reservationMapper->done();
   
        //All Reservations for every room in the same Timeslot
		$everything = $reservationMapper->findTimeslot($timeslot);		

		//Keep tabs of Reservations that have become active
		$added = [];
		
		//Keep tabs of Reservations that have been removed
		$removed = [];
		
		//If user cancelling has the active reservation
        if($position == 0)
        {    
            $eStatus = false;
			
            //Iterate through every Reservation
			foreach($everything as $e)
            {   
                $roomHasActive = $reservationMapper->findTimeslotWinner($timeslot, $e->getRoomName());
				
				//If the current Room has an active reservation, there is nothing to do
				if( count($roomHasActive) )
				{
					continue;
				}
				//If its in the removed array, we skip it
				elseif (in_array($e->getId(), $removed))
				{
					continue;
				}
				//We have reached a new one, reset the variables
				elseif(!$eStatus)
				{
					$roomHasActive = false;
					$curRoom = $e->getRoomName();
					
					//Get # of each equipment requests
					$markersRequest = $e->getMarkers();
					$laptopsRequest = $e->getLaptops();
					$projectorsRequest = $e->getProjectors();
					$cablesRequest = $e->getCables();

					//Use statusEquipment(...) method on line 264 of ReservationMapper.php to see if reservation can be made active
					$eStatus = $reservationMapper->statusEquipment($timeslot, $markersRequest, $laptopsRequest, $projectorsRequest, $cablesRequest);
					
					//Set valid candidate as new active reservation
					if($eStatus == true)
					{
						$reservationMapper->setNewWaitlist($e->getId(), 0);
						$waitingList = $reservationMapper->findForTimeslot($curRoom, $timeslot);
						
						//Decrement everyone down
						$pos = $e->getPosition();
						foreach($waitingList as $w)
						{
							$next = $w->getPosition();
							if($pos < $next)
							{
								$reservationMapper->setNewWaitlist($w->getId(), $next-1);
							}
						}
						$reservationMapper->done();
						
						//Add to the list of modified
						$added[] = $reservationMapper->find($e->getId());
						
						//Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
						$same = $reservationMapper->findAllTimeslotWaitlisted($timeslot, $e->getUserID(), $e->getRoomName());
						
						//If any waitlisted reservations in any parallel rooms exist for the winnning user, delete them
						//For the same timeslot, to prevent the same person from getting 2 spots
						if (count($same))
						{
							foreach($same as $s)
							{
								$reservationMapper->delete($s->id);
								//If its in the list we are curerntly searching we must skip to prevent errors
								$removed[] = $s->id;
							}
						}
						$eStatus = false;
					}
				}             
            }
        }   
        //If user cancelling is on waiting list
        else 
		{
			//For everyone in position after the Reservation to be deleted, move them down one
            foreach($waitingList as $w)
            {
                $next = $w->getPosition();
                if($position < $next)
                {
                    $reservationMapper->setNewWaitlist($w->getId(), $next-1);
                }
            }
        }
				
        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }

        // Commit all of the Edits to the reservations
        $reservationMapper->done();
		
		/**
		* 	Post delete checks
		*/
		
		//For all Reservations that were added as a result of the deletion, perform the following checks
		if(count($added) > 0)
		{
			foreach($added as $a)			
			{
				$winnerID = $a->getUserId();
							
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
				//Not the same timeslot
				elseif (count($overlap) && $a->getPosition() == 0) 
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
		}
				
        $response = redirect();

        // redirect to appropriate back page
        if ($request->input('back') === 'list') {
            $response = $response->route('reservationList');
        } else {
            $response = $response->route('calendar', ['date' => $reservation->getTimeslot()->toDateString()]);

        }

        if( ($this->modifying) == true)
		{
			$this->modifying = false;
		}
		else
		{
			return $response->with('success', 'Successfully cancelled reservation!');
		}
    }
}
