<?php

namespace App\Http\Controllers;

use App\Data\Mappers\ReservationMapper;
use App\Data\Mappers\RoomMapper;
use App\Data\Mappers\UserMapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller {

    //Constant to signify Maximum # of users that can be associated to 1 timeslot
    const MAX_PER_TIMESLOT = 4;
    //Constant to signify Maximum # of reservation that can be made by a user in 1 week
    const MAX_PER_USER = 3;

    //Signifies if the operation is to modify an existing reservation
    private $modifying = false;
    //Used only if the operation is to modify
    //Used to keep track if the constraints are followed before the modification is allowed to continue
    private $success1 = true;
    private $success2 = true;

    /**
     * Create a new controller instance.
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Returns the My Reservation List view for the given User
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function viewReservationList(Request $request) {
        $roomMapper = RoomMapper::getInstance();
        $roomMapper->clearStudent(Auth::id());

        $reservationMapper = ReservationMapper::getInstance();
        $reservations = $reservationMapper->findPositionsForUser(Auth::id());

        if (isset($_SESSION["view"]) && $_SESSION["view"] == true) {

            $_SESSION["timestamp"] = date("Y-m-d G:i:s");
            $_SESSION["user"] = Auth::id();
            unset($_SESSION["view"]);
        }

        return view('reservation.list', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Returns the Show Reservation view for the given User 
     * 
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function viewReservation(Request $request, $id) {
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
     * Returns the Modify Reservation view for the given user's Reservation
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function showModifyForm(Request $request, $id) {
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
     * Modify the Reservation to the new Details entered
     * 
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function modifyReservation(Request $request, $id) {
        // validate reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        //If the reservation doesn't get passed or the user's Id does not match that of the Reservation
        //Return a 404 error
        if ($reservation === null || $reservation->getUserId() !== Auth::id()) {
            return abort(404);
        }
    
        //For when the user is waiting, but can still click on the reservations they have made
        if (isset($_SESSION["timestamp"]) && $_SESSION["user"] == Auth::id() ){
            
            //Find the time right now, to compare to the timestamp
            $now = date("Y-m-d G:i:s");

            $end = strtotime($_SESSION["timestamp"]);
            $end = $end + 30;
            $end = date("Y-m-d G:i:s", $end);

            if($end > $now){
                    return redirect()->route('calendar')
                            ->with('error', sprintf("You must wait your turn! Please try again later. We apologize for any inconvenience."));
            }
        }
        //Get the Timeslot of the "old" Version of the Reservation
        $date = substr($reservation->getTimeslot()->toDateTimeString(), 0, 10);
        $timeslot = $date . " " . $request->input('timeslot', "") . ":00:00";

        //Get the Timeslot of the "new" Version of the Reservation
        $newTimeslot = new Carbon($date . " " . $request->input('timeslot', "") . ":00:00");
        $new = $newTimeslot->format('Y-m-d\TH');

        //Get the new Room of the "new" Version of the Reservation
        $newRoom = $request->input('roomName', "");

        //Get the new Equipment of the "new" Version of the Reservation
        $newLaptops = $request->input('laptops', "");
        $newProjectors = $request->input('projectors', "");
        $newCables = $request->input('cables', "");
        $newMarkers = $request->input('markers', "");

        //Get the Timeslot of the "old" Version of the Reservation in the TH format
        $OGTimeslot = $reservation->getTimeslot();
        $OG = $reservation->getTimeslot()->format('Y-m-d\TH');
        $OGRoom = $reservation->getRoomName();

        //Get the Equipment of the "old" Version of the Reservation
        $OGLaptops = $reservation->getLaptops();
        $OGProjectors = $reservation->getProjectors();
        $OGCables = $reservation->getCables();
        $OGMarkers = $reservation->getMarkers();

        //Status to check if any of the info besides the description changed
        $sameEquip = true;
        $sameTime = false;

        //Check if the Timeslot and Room are the same
        if ($newTimeslot == $OGTimeslot && $newRoom == $OGRoom) {
            $sameTime = true;
        }

        //Check if the Equipment is the Same
        if ($newLaptops != $OGLaptops) {
            $sameEquip = false;
        } elseif ($newProjectors != $OGProjectors) {
            $sameEquip = false;
        } elseif ($newCables != $OGCables) {
            $sameEquip = false;
        } elseif ($newMarkers != $OGMarkers) {
            $sameEquip = false;
        }

        //Same Room, Timeslot and Equipment means.... 
        if ($sameTime && $sameEquip) {
            // ...update the description only
            $reservationMapper->set($reservation->getId(), $request->input('description', ""), $newMarkers, $newProjectors, $newLaptops, $newCables, $request->input('timeslot', ""), $newRoom);

            $reservationMapper->done();

            $_SESSION["timestamp"] = date("Y-m-d G:i:s");
            $_SESSION["user"] = Auth::id();

            return redirect()
                            ->route('reservation', ['id' => $reservation->getId(), 'back' => $request->input('back')])
                            ->with('success', 'Successfully updated reservation description!');
        }
        //Same Room, Timeslot but different Equipment
        elseif ($sameTime && !$sameEquip) {
            //Set the variable to true, to let other components of the system know that a Reservation is being modified
            $this->modifying = true;

            //Go through the constraints outlined in the Show Request Form Method
            $temp1 = $this->showRequestForm($request, $OGRoom, $new);

            if (($this->success1) == true) {
                //Check if the equipment requested is available for that Timeslot, excluding the current Reservation's equipment count
                $eStatus = $reservationMapper->statusEquipmentExclude($newTimeslot, $reservation->getId(), $newMarkers, $newLaptops, $newProjectors, $newCables);
                if ($eStatus) {
                    //Check if no one is in the Room
                    $roomHasActive = $reservationMapper->findTimeslotWinner($newTimeslot, $newRoom);

                    //If no one is in the Room and my equipment status is ok upon modification, become the active user.
                    if (!$roomHasActive) {
                        $reservationMapper->setNewWaitlist($reservation->getId(), 0);
                        $reservationMapper->done();
                    }

                    //Update the quantities of the equipment
                    $reservationMapper->set($reservation->getId(), $request->input('description', ""), $newMarkers, $newProjectors, $newLaptops, $newCables, $request->input('timeslot', ""), $newRoom);

                    $reservationMapper->done();

                    //Check if anyone needed the equipment
                    //If they do, then give it to them
                    $this->cleanup($reservation->getId(), $newTimeslot);

                    $_SESSION["timestamp"] = date("Y-m-d G:i:s");
                    $_SESSION["user"] = Auth::id();

                    //Return Status message
                    return redirect()
                                    ->route('reservation', ['id' => $reservation->getId(), 'back' => $request->input('back')])
                                    ->with('success', 'Successfully modified reservation equipment!');
                } else {
                    //Inform the user that the equipment is not available
                    return redirect()
                                    ->route('reservation', ['id' => $reservation->getId(), 'back' => $request->input('back')])
                                    ->with('error', 'The Equipment is not Available. Your reservation has been kept.<br>
                                            If you require more equipment than what is available, you must give up your Reservation and create a new one with the specifications!');
                }
            } else {
                //Return the error caught by the Show Request Form Method
                //Reaching here signifies that the modification did not go through
                $this->success1 = true;
                $this->modifying = false;
                return $temp1;
            }
        }
        //If any of the New Data is not the same as the old data, change it
        else {
            //Set the variable to true, to let other components of the system know that a Reservation is being modified
            $this->modifying = true;

            //Go through the constraints outlined in the Show Request Form Method
            $temp1 = $this->showRequestForm($request, $newRoom, $new);
            if (($this->success1) == true) {
                //Go through the constraints outlined in the Request Reservation Method
                //Add the modified Reservation to the database
                $temp2 = $this->requestReservation($request, $newRoom, $new);
                if (($this->success2) == true) {
                    //Now that the Reservation has been added
                    //Find the new ID of the reservation so that we can redirect to that page
                    $active = $reservationMapper->findForTimeslot($newRoom, $newTimeslot);
                    foreach ($active as $a) {
                        if ($a->getUserId() == Auth::id()) {
                            $newID = $a->getId();
                        }
                    }

                    //Remove the old version of the Reservation from the Database
                    //Return to the Reservation page with the details of the modified Reservation
                    $this->cancelReservation($request, $id, $OGRoom, $OG);
                    $this->modifying = false;

                    $_SESSION["timestamp"] = date("Y-m-d G:i:s");
                    $_SESSION["user"] = Auth::id();

                    return redirect()
                                    ->route('reservation', ['id' => $newID, 'back' => $request->input('back')]);
                } else {
                    //Return the error caught by the Request Reservation method
                    //Reaching here signifies that the modification did not go through
                    $this->success2 = true;
                    $this->modifying = false;
                    return $temp2;
                }
            } else {
                //Return the error caught by the Show Request Form Method
                //Reaching here signifies that the modification did not go through
                $this->success1 = true;
                $this->modifying = false;
                return $temp1;
            }
        }
    }

    /**
     * Initiates the check to see if a User is allowed to Request a Reservation
     * 
     * @param Request $request
     * @param string $roomName
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function showRequestForm(Request $request, $roomName, $timeslot) {
        //Format the Timeslot passed in
        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

        // validate room exists
        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);

        if ($room === null) {
            return abort(404);
        }

        //Initialize this variable in case the user just viewed the page and did not request anything
        //They still entered the room
        //This is to prevent people from entering the room, then clicking Calendar and trying to avoid a time penalty
        if (!isset($_SESSION['view']) && $this->modifying == false) {
            $_SESSION['view'] = true;
        }

        //Check to see who is currently using the room
        $roomStatus = $roomMapper->getStatus($roomName);

        //If the room is busy with someone else, return error
        if (($roomStatus[0]->busy) != 0 && $roomStatus[0]->busy != Auth::id()) {
            //If we are modifiying, then tell the Modification component that the room is busy
            if (($this->modifying) == true) {
                $this->success1 = false;
            }
            unset($_SESSION['view']);
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                            ->with('error', sprintf("The room %s is currently busy! Please try again later. We apologize for any inconvenience.", $roomName));
        }
        //If its not busy, then set it to busy with the user's ID
        else {
            $roomStatus = $roomMapper->setBusy($roomName, Auth::id(), date("Y-m-d G:i:s"));
        }

        $reservationMapper = ReservationMapper::getInstance();

        if (($this->modifying) == true) {
            //since you are modifying an active reservation, the count should not matter
            $reservationCount = 0;
        } else {
            //Check if user exceeded maximum amount of reservations
            $reservationCount = count($reservationMapper->countInRange(Auth::id(), $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek()));
        }

        //If the user went over, return an error
        if ($reservationCount >= static::MAX_PER_USER) {
            //If we are modifiying, then tell the Modification component that the user went over
            if (($this->modifying) == true) {
                $this->success1 = false;
            }
            unset($_SESSION['view']);
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                            ->with('error', sprintf("You've exceeded your reservation request limit of (%d) for this week.<br> 
                    Please try reserving next week or remove a reservation from this week to be eligible.", static::MAX_PER_USER));
        }

        //Check if the timeslot is full
        $reservations = $reservationMapper->findForTimeslot($roomName, $timeslot);

        //If the Timeslot is full, return an error
        if (count($reservations) >= static::MAX_PER_TIMESLOT) {
            //If we are modifiying, then tell the Modification component that the Timeslot is full
            if (($this->modifying) == true) {
                $this->success1 = false;
            }
            unset($_SESSION['view']);
            return redirect()->route('calendar', ['date' => $timeslot->toDateString()])
                            ->with('error', 'The waiting list for that time slot is full.');
        }

        //If we are modifying, we do not want to change the View
        //The method is simply being used for the constraints in place
        if (($this->modifying) == true) {
            //Do nothing
        }
        //Proceed to the Request Form
        else {
            return view('reservation.request', [
                'room' => $room,
                'timeslot' => $timeslot
            ]);
        }
    }

    /**
     * Initiates the request of a Reservation
     * @param Request $request
     * @param string $roomName
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function requestReservation(Request $request, $roomName, $timeslot) {
        $reservationMapper = ReservationMapper::getInstance();
        $userMapper = UserMapper::getInstance();

        $this->validate($request, [
            'description' => 'required',
            'markers' <= 3,
            'projectors' <= 3,
            'laptops' <= 3,
            'cables' <= 3
        ]);

        //Format the timeslot
        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

        //Validate the room passed in exists
        $roomMapper = RoomMapper::getInstance();
        $room = $roomMapper->find($roomName);

        if ($room === null) {
            return abort(404);
        }

        //Generate a UUID for this reservation session, which will link recurring reservations together
        $uuid = \Uuid::generate();
        $reservations = [];

        //Get the  # of times the user wants the Reservation to recur for
        $recur = intval($request->input('recur', 1));

        //Status message arrays
        $successful = [];
        $waitlisted = [];
        $errored = [];

        //Loop over every recurring week and independently request the reservation for that week
        for ($t = $timeslot->copy(), $i = 0; $i < $recur; $t->addWeek(), ++$i) {
            /*
             * Pre-insert checks
             */
            //Check if user exceeded maximum amount of reservations
            //This is done after every loop because future reservation (from Recursion) are not initially checked with the Show Request Form method

            if (($this->modifying) == true) {
                //since you are modifying an active reservation, the count should not matter
                $reservationCount = 0;
            } else {
                //Check if user exceeded maximum amount of reservations
                $reservationCount = count($reservationMapper->countInRange(Auth::id(), $t->copy()->startOfWeek(), $t->copy()->startOfWeek()->addWeek()));
            }

            if ($reservationCount >= static::MAX_PER_USER) {
                //If we are modifiying, then tell the Modification component that the user went over
                if (($this->modifying) == true) {
                    $this->success2 = false;
                }
                $errored[] = [$t->copy(), sprintf("You've exceeded your weekly reservation request limit of %d.", static::MAX_PER_USER)];
                continue;
            }

            //Check if waiting list for timeslot is full
            //This is done after every loop because future reservation (from Recursion) are not initially checked with the Show Request Form method
            $waitingList = $reservationMapper->findForTimeslot($roomName, $t);

            if (count($waitingList) >= static::MAX_PER_TIMESLOT) {
                //If we are modifiying, then tell the Modification component that the Timeslot is full
                if (($this->modifying) == true) {
                    $this->success2 = false;
                }
                $errored[] = [$t->copy(), 'The waiting list is full.'];
                continue;
            }

            //Check if any active reservations in any other rooms would overlap with the current reservation
            $overlap = $reservationMapper->findAllTimeslotActive($t, Auth::id());

            if (count($overlap)) {
                //If we are modifiying, then tell the Modification component that the user has an overlapping Reservation
                if (($this->modifying) == true) {
                    $this->success2 = false;
                }
                $errored[] = [$t->copy(), 'You already have a reservation for that time in Room ' . $overlap[0]->room_name . '. Choose another time slot.'];
                continue;
            }

            //Compile all the requested equipment
            $markersRequest = intval($request->input('markers', 1));
            $laptopsRequest = intval($request->input('laptops', 1));
            $projectorsRequest = intval($request->input('projectors', 1));
            $cablesRequest = intval($request->input('cables', 1));

            //Check if the equipment request is available for that Timeslot
            $eStatus = $reservationMapper->statusEquipment($t, $markersRequest, $laptopsRequest, $projectorsRequest, $cablesRequest);

            //Check if the student is in capstone, so we can know to give him priority or not
            $capstone = $userMapper->capstone(Auth::id());

            //Only execute if the student is a Capstone student
            //And if there are already someone in the waitling list
            //Iterate through all the reservations found
            $count = 1;
            if ($capstone && count($waitingList) > 1) {
                foreach ($waitingList as $w) {
                    //Find the students that are waiting as well as their Reservaiton Positions and if they are part of capstone
                    $student = $w->getUserId();
                    $position = $w->getPosition();
                    $status = $userMapper->capstone($student);
                    //If any of the users are from capstone, then increment the count variable
                    if ($status) {
                        $count++;
                    }
                    //This means the person who has the room is not a capstone student
                    //However we will not kick him out, we will just skip him
                    elseif ($position == 0) {
                        continue;
                    }
                    //Move non-capstone students on the waiting list down by incrementing (increasing their rank) their position on the waitlist
                    else {
                        //Increment waitlist position
                        $reservationMapper->moveDown($w);
                    }
                }
                /*
                 * Insert
                 */
                $reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, $count, $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
            }
            //For Capstone, if no one is in the room, then execute as if it was a regular student
            //For all Regular students, follow here
            else {
                $roomHasActive = $reservationMapper->findTimeslotWinner($timeslot, $room->getName());
                /*
                 * Insert
                 * If you are a regular student, no one is in the room (Active or Waiting), but your equipment is not available, then you are placed 1 on the Waiting List
                 */
                if (!$eStatus && count($waitingList) == 0) {
                    $reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, 1, $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
                }
                //If you are a regular student, no one is Active in the room, someone is on the waiting list, and your equipment is available
                //They get the Reservation
                elseif ($eStatus && count($roomHasActive) == 0 && count($waitingList) != 0) {
                    $reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, 0, $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
                }
                //If you are a regular student, no one is Active in the room, someone is on the waiting list, and your equipment is not available
                //They get last in the last
                elseif (!$eStatus && count($roomHasActive) == 0 && count($waitingList) != 0) {
                    $reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, count($waitingList) + 1, $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
                }
                //All other cases you are placed behind the current users in the List
                else {
                    $reservations[] = $reservationMapper->create(intval(Auth::id()), $room->getName(), $t->copy(), $request->input('description', ''), $uuid, count($waitingList), $markersRequest, $projectorsRequest, $laptopsRequest, $cablesRequest);
                }
            }
        }

        // run the reservation operations now, as we need to process the results
        $reservationMapper->done();

        /*
         * Post-insert checks
         */

        foreach ($reservations as $reservation) {
            $t = $reservation->getTimeslot();

            if (($this->modifying) == true) {
                //since you are modifying an active reservation, the amount of active should not matter
                $active = 0;
            } else {
                // Check the current active reservations
                $active = $reservationMapper->countInRange(Auth::id(), $t->copy()->startOfWeek(), $t->copy()->startOfWeek()->addWeek());
            }

            // Check the current waitlisted reservations
            $waited = $reservationMapper->countAll(Auth::id(), $t->copy()->startOfWeek(), $t->copy()->startOfWeek()->addWeek());

            //Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
            $overlap = $reservationMapper->findAllTimeslotWaitlisted($t, Auth::id(), $reservation->getRoomName());

            //If the amount of active reservaitons is at 3, then delete all the ones on the waitlist from the current week
            if (count($active) == 3) {
                foreach ($waited as $w) {
                    $temp = $reservationMapper->find($w->id);
                    $t2 = $temp->getTimeslot();

                    $reservationMapper->delete($w->id);
                    $errored[] = [$t2, 'You have been removed from the waitlist in room ' . $w->room_name . ' at ' . $t2->format('g a') . '.'];
                    continue;
                }
            }
            //If any waitlisted reservations in any parallel rooms exist for the user, delete them
            elseif (count($overlap) && $reservation->getPosition() == 0) {
                foreach ($overlap as $o) {
                    $temp = $reservationMapper->find($o->id);
                    $t2 = $temp->getTimeslot();

                    $errored[] = [$t2, 'You have been removed from the waitlist in room ' . $o->room_name . ' at ' . $t2->format('g a') . '.'];
                    $reservationMapper->delete($o->id);
                }
            }

            // check if there was an error inserting the reservation, ie. duplicate reservation
            if ($reservation->getId() === null) {
                if (($this->modifying) == true) {
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
        //If reservations have been successfully added, display the appropriate message
        if (count($successful)) {
            $_SESSION["timestamp"] = date("Y-m-d G:i:s");
            $_SESSION["user"] = Auth::id();
            unset($_SESSION['view']);
            if (count($active) == 3) {
                $response = $response->with('success', sprintf('You have reached the maximum reservations for the week! Removing all waitlists.<br> The following reservations have been successfully created for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                                            return sprintf("<li><strong>%s</strong></li>", $m->format('l, F jS, Y'));
                                        }, $successful))));
            } else {
                $response = $response->with('success', sprintf('The following reservations have been successfully created for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                                            return sprintf("<li><strong>%s</strong></li>", $m->format('l, F jS, Y'));
                                        }, $successful))));
            }
        }
        //If reservations have been placed on the waitlist, display the appropriate message
        if (count($waitlisted)) {
            $_SESSION["timestamp"] = date("Y-m-d G:i:s");
            $_SESSION["user"] = Auth::id();
            unset($_SESSION['view']);
            if (!$eStatus) {
                $response = $response->with('warning', sprintf('Equipment is not available for your Reservation. You have been put on a waiting list for the following: %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                                            return sprintf("<li><strong>%s</strong>: Position #%d</li>", $m[0]->format('l, F jS, Y'), $m[1]);
                                        }, $waitlisted))));
            } else {
                $response = $response->with('warning', sprintf('You have been put on a waiting list for the following reservations for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                                            return sprintf("<li><strong>%s</strong>: Position #%d</li>", $m[0]->format('l, F jS, Y'), $m[1]);
                                        }, $waitlisted))));
            }
        }
        //If reservations have encountered an error, display the appropriate message
        if (count($errored)) {
            unset($_SESSION['view']);
            $response = $response->with('error', sprintf('The following requests were unsuccessful for %s at %s:<ul class="mb-0">%s</ul>', $room->getName(), $timeslot->format('g a'), implode("\n", array_map(function ($m) {
                                        return sprintf("<li><strong>%s</strong>: %s</li>", $m[0]->format('l, F jS, Y'), $m[1]);
                                    }, $errored))));
        }

        $roomStatus = $roomMapper->getStatus($roomName);

        //Now that the user is done with the room, open it up again
        if ($roomStatus[0]->busy == Auth::id()) {
            $roomMapper->setFree($roomName);
        }

        return $response;
    }

    /**
     * @param string $roomName

     * @return \Illuminate\Http\Response
     */
    public function requestCancel($roomName) {
        //Now that the user is done with the room, open it up again
        $roomMapper = RoomMapper::getInstance();
        $roomMapper->setFree($roomName);

        $response = redirect()
                ->route('calendar');

        $_SESSION["timestamp"] = date("Y-m-d G:i:s");
        $_SESSION["user"] = Auth::id();
        unset($_SESSION['view']);
        return $response;
    }

    /**
     * @param Request $request
     * @param string $id
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function cancelReservation(Request $request, $id, $roomName, $timeslot) {
        // validate reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        $timeslot = Carbon::createFromFormat('Y-m-d\TH', $timeslot);

        //Waiting List for the same room the reservation is being deleted from
        $waitingList = $reservationMapper->findForTimeslot($roomName, $timeslot);

        //Find out the position in the waiting list of the Reservation we will be deleting
        foreach ($waitingList as $w) {
            $target = $w->getId();
            if ($target == $id) {
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
        if ($position == 0) {
            $eStatus = false;

            //Iterate through every Reservation
            foreach ($everything as $e) {
                $roomHasActive = $reservationMapper->findTimeslotWinner($timeslot, $e->getRoomName());

                //If the current Room has an active reservation, there is nothing to do
                if (count($roomHasActive)) {
                    continue;
                }
                //If its in the removed array, we skip it
                elseif (in_array($e->getId(), $removed)) {
                    continue;
                }
                //We have reached a new one, reset the variables
                elseif (!$eStatus) {
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
                    if ($eStatus == true) {
                        $reservationMapper->setNewWaitlist($e->getId(), 0);
                        $waitingList = $reservationMapper->findForTimeslot($curRoom, $timeslot);

                        //Decrement everyone down
                        $pos = $e->getPosition();
                        foreach ($waitingList as $w) {
                            $next = $w->getPosition();
                            if ($pos < $next) {
                                $reservationMapper->setNewWaitlist($w->getId(), $next - 1);
                            }
                        }
                        $reservationMapper->done();

                        //Add to the list of modified
                        $added[] = $reservationMapper->find($e->getId());

                        //Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
                        $same = $reservationMapper->findAllTimeslotWaitlisted($timeslot, $e->getUserID(), $e->getRoomName());

                        //If any waitlisted reservations in any parallel rooms exist for the winnning user, delete them
                        //For the same timeslot, to prevent the same person from getting 2 spots
                        if (count($same)) {
                            foreach ($same as $s) {
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
        else {
            //For everyone in position after the Reservation to be deleted, move them down one
            foreach ($waitingList as $w) {
                $next = $w->getPosition();
                if ($position < $next) {
                    $reservationMapper->setNewWaitlist($w->getId(), $next - 1);
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
        if (count($added) > 0) {
            foreach ($added as $a) {
                $winnerID = $a->getUserId();

                // Check the current amount of active reservations for the winner
                $active = $reservationMapper->countInRange($winnerID, $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());

                // Check the current waitlisted reservations
                $waited = $reservationMapper->countAll($winnerID, $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());

                //Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
                $overlap = $reservationMapper->findAllTimeslotWaitlisted($timeslot, $winnerID, $reservation->getRoomName());

                //If the amount of active reservaitons is at 3, then delete all the ones on the waitlist from the current week
                if (count($active) == 3) {
                    foreach ($waited as $w) {
                        $temp = $reservationMapper->find($w->id);
                        $t2 = $temp->getTimeslot();

                        $reservationMapper->delete($w->id);
                        //No need to display this information to the user
                        continue;
                    }
                }
                //If any waitlisted reservations in any parallel rooms exist for the winnning user, delete them
                //Not the same timeslot
                elseif (count($overlap) && $a->getPosition() == 0) {
                    foreach ($overlap as $o) {
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

        if (($this->modifying) == true) {
            $this->modifying = false;
        } else {
            return $response->with('success', 'Successfully cancelled reservation!');
        }
    }

    /**
     * @param Reservation ID $id
     * @param string $timeslot
     * @return \Illuminate\Http\Response
     */
    public function cleanup($id, $timeslot) {
        // validate reservation exists and is owned by user
        $reservationMapper = ReservationMapper::getInstance();
        $reservation = $reservationMapper->find($id);

        //All Reservations for every room in the same Timeslot
        $everything = $reservationMapper->findTimeslot($timeslot);

        //Keep tabs of Reservations that have become active
        $added = [];

        //Keep tabs of Reservations that have been removed
        $removed = [];

        $eStatus = false;

        //Iterate through every Reservation
        foreach ($everything as $e) {
            $roomHasActive = $reservationMapper->findTimeslotWinner($timeslot, $e->getRoomName());

            //If the current Room has an active reservation, there is nothing to do
            if (count($roomHasActive)) {
                continue;
            }
            //If its in the removed array, we skip it
            elseif (in_array($e->getId(), $removed)) {
                continue;
            }
            //We have reached a new one, reset the variables
            elseif (!$eStatus) {
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
                if ($eStatus == true) {
                    $reservationMapper->setNewWaitlist($e->getId(), 0);
                    $waitingList = $reservationMapper->findForTimeslot($curRoom, $timeslot);

                    //Decrement everyone down
                    $pos = $e->getPosition();
                    foreach ($waitingList as $w) {
                        $next = $w->getPosition();
                        if ($pos < $next) {
                            $reservationMapper->setNewWaitlist($w->getId(), $next - 1);
                        }
                    }
                    $reservationMapper->done();

                    //Add to the list of modified
                    $added[] = $reservationMapper->find($e->getId());

                    //Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
                    $same = $reservationMapper->findAllTimeslotWaitlisted($timeslot, $e->getUserID(), $e->getRoomName());

                    //If any waitlisted reservations in any parallel rooms exist for the winnning user, delete them
                    //For the same timeslot, to prevent the same person from getting 2 spots
                    if (count($same)) {
                        foreach ($same as $s) {
                            $reservationMapper->delete($s->id);
                            //If its in the list we are curerntly searching we must skip to prevent errors
                            $removed[] = $s->id;
                        }
                    }
                    $eStatus = false;
                }
            }
        }

        // Commit all of the Edits to the reservations
        $reservationMapper->done();

        /**
         * 	Post delete checks
         */
        //For all Reservations that were added as a result of the deletion, perform the following checks
        if (count($added) > 0) {
            foreach ($added as $a) {
                $winnerID = $a->getUserId();

                // Check the current amount of active reservations for the winner
                $active = $reservationMapper->countInRange($winnerID, $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());

                // Check the current waitlisted reservations
                $waited = $reservationMapper->countAll($winnerID, $timeslot->copy()->startOfWeek(), $timeslot->copy()->startOfWeek()->addWeek());

                //Check if any waitlisted reservations in any other rooms would overlap with the recently added active reservation
                $overlap = $reservationMapper->findAllTimeslotWaitlisted($timeslot, $winnerID, $reservation->getRoomName());

                //If the amount of active reservaitons is at 3, then delete all the ones on the waitlist from the current week
                if (count($active) == 3) {
                    foreach ($waited as $w) {
                        $temp = $reservationMapper->find($w->id);
                        $t2 = $temp->getTimeslot();

                        $reservationMapper->delete($w->id);
                        //No need to display this information to the user
                        continue;
                    }
                }
                //If any waitlisted reservations in any parallel rooms exist for the winnning user, delete them
                //Not the same timeslot
                elseif (count($overlap) && $a->getPosition() == 0) {
                    foreach ($overlap as $o) {
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
    }

}
