<?php

namespace App\Data\Mappers;

use App\Data\IdentityMaps\ReservationIdentityMap;
use App\Data\TDGs\ReservationTDG;
use App\Data\UoWs\ReservationUoW;
use App\Data\Reservation;
use App\Singleton;
use Carbon\Carbon;

/**
 * @method static ReservationMapper getInstance()
 */
class ReservationMapper extends Singleton {

    /**
     * @var ReservationTDG
     */
    private $tdg;

    /**
     * @var ReservationIdentityMap
     */
    private $identityMap;

    /**
     * ReservationMapper constructor
     * Obtain the instance of both the Reservation TDG and Identity Map
     */
    protected function __construct() {
        parent::__construct();

        $this->tdg = ReservationTDG::getInstance();
        $this->identityMap = ReservationIdentityMap::getInstance();
    }

    /**
     * Handles the creation of a new object of type Reservation
     *
     * @param int $userId
     * @param string $roomName
     * @param \DateTime $timeslot
     * @param string $description
     * @param string $uuid
     * @param string $position
     * @param string $projectors
     * @param string $laptops
     * @param string $cables
     * @param int $markers
     * @return Reservation
     */
    public function create(int $userId, string $roomName, \DateTime $timeslot, string $description, string $uuid, int $position, int $markers, int $projectors, int $laptops, int $cables): Reservation {
        //Create an object of type Reservation
        $reservation = new Reservation($userId, $roomName, $timeslot, $description, $uuid, null, $position, $markers, $projectors, $laptops, $cables);

        // add the new Reservation to the list of existing objects in live memory
        $this->identityMap->add($reservation);

        // add to UoW registry so that we create it in the DB once the reservation is ready to commit everything
        // This will be intercepted by the ReservationUoWAspect class when running the system on a Linux based web-server
        ReservationUoW::getInstance()->registerNew($reservation);

        return $reservation;
    }

    /**
     * Retrieve a Reservation with the given ID
     *
     * @param int $id
     * @return Reservation|null
     */
    public function find(int $id) {
        $result = null;

        //First check if the reservation is in the Identity Map        
        $reservation = $this->identityMap->get($id);

        //If Identity Map doesn't have it, use the TDG
        if ($reservation === null) {
            $result = $this->tdg->find($id);
        }

        //Use variables for the quantity of equipement in the reservation
        //Facilitates the creation of the new reservation Object below
        $markers = $result->quantity_markers;
        $projectors = $result->quantity_projectors;
        $laptops = $result->quantity_laptops;
        $cables = $result->quantity_cables;

        // if TDG doesn't have it, it doesn't exist
        if ($result !== null) {
            // we got the Reservation from the TDG who got it from the DB and now the mapper must add it to the ReservationIdentityMap
            $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position, $markers, $projectors, $laptops, $cables);
            $this->identityMap->add($reservation);
        }
        return $reservation;
    }

    /**
     * Returns a list of all Reservations for a given Room and Time-slot, ordered by ID from the database
     * 
     * @param string $roomName
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findForTimeslot(string $roomName, \DateTime $timeslot): array {
        //Obtain all of the reservations from the database using the TDG
        $results = $this->tdg->findForTimeslot($roomName, $timeslot);
        $reservations = [];

        //With the data obtained, check if they are in the identity Map
        foreach ($results as $result) {
            //If it is in the Identity Map, then simply add it to the Reservation Array
            if ($reservation = $this->identityMap->get($result->id)) {
                $reservations[] = $reservation;
            }
            //If it is not in the Identity Map, then create the Reservation object
            // Then add it to the Identity Map as well as the Reservaiton Array
            else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position, $result->quantity_markers, $result->quantity_projectors, $result->quantity_laptops, $result->quantity_cables);
                $this->identityMap->add($reservation);
                $reservations[] = $reservation;
            }
        }

        return $reservations;
    }

    /**
     * Returns a list of all active Reservations (if any) for a given Time-slot by the User passed in
     * 
     * @param int $id
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findAllTimeslotActive(\DateTime $timeslot, $id) {
        return $this->tdg->findAllTimeslotActive($timeslot, $id);
    }

    /**
     * Returns a list of all Wait-listed Reservations (if any) for a given Time-slot by the User passed in
     * 
     * @param int $id
     * @param Room Name $roomName
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findAllTimeslotWaitlisted(\DateTime $timeslot, $id, $roomName) {
        return $this->tdg->findAllTimeslotWaitlisted($timeslot, $id, $roomName);
    }

    /**
     * Returns the User who has the active Reservation for the Time-slot and Room passed in
     * 
     * @param \DateTime $timeslot
     * @param Room Name $roomName
     * @return Reservation[]
     */
    public function findTimeslotWinner(\DateTime $timeslot, $roomName) {
        return $this->tdg->findTimeslotWinner($timeslot, $roomName);
    }

    /**
     * Returns a list of all Reservation for a given Time-slot other than the room passed in
     *
     * @param \DateTime $timeslot
     * @param Room Name $roomName
     * @return Reservation[]
     */
    public function findTimeslot(\DateTime $timeslot) {
        //Obtain all the reservations for a given Time-slot other than the room passed in from the database using the TDG
        $results = $this->tdg->findTimeslot($timeslot);
        $reservations = [];

        //With the data obtained, check if they are in the identity Map
        foreach ($results as $result) {
            //If it is in the Identity Map, then simply add it to the Reservation Array
            if ($reservation = $this->identityMap->get($result->id)) {
                $reservations[] = $reservation;
            }
            //If it is not in the Identity Map, then create the Reservation object
            // Then add it to the Identity Map as well as the Reservaiton Array
            else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position, $result->quantity_markers, $result->quantity_projectors, $result->quantity_laptops, $result->quantity_cables);
                $this->identityMap->add($reservation);
                $reservations[] = $reservation;
            }
        }

        return $reservations;
    }

    /**
     * Returns the position of the User for the Reservation
     *
     * @param Reservation $reservation
     * @return int
     */
    public function findPosition(Reservation $reservation): int {
        // Get a list of all the other reservations for the same room-timeslot
        $reservations = $this->findForTimeslot($reservation->getRoomName(), $reservation->getTimeslot());

        // Find the position of the user compared to all other reservations for the same room-timeslot
        $position = 0;
        foreach ($reservations as $r) {
            //If i'm the next in the list, stop incrementing
            if ($r->getId() === $reservation->getId()) {
                break;
            }
            ++$position;
        }
        return $position;
    }

    /**
     * Returns the active Reservations for the given Date
     * 
     * @param \DateTime $date
     * @return Reservation[]|array
     */
    public function findAllActive(\DateTime $date): array {
        //Obtain all active Reservation for the given Date from the databse using the TDG
        $results = $this->tdg->findAllActive($date);
        $reservations = [];

        //With the data obtained, check if they are in the identity Map
        foreach ($results as $result) {
            //If it is in the Identity Map, then simply add it to the Reservation Array
            if ($reservation = $this->identityMap->get($result->id)) {
                $reservations[] = $reservation;
            }
            //If it is not in the Identity Map, then create the Reservation object
            // Then add it to the Identity Map as well as the Reservaiton Array
            else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position, $result->quantity_markers, $result->quantity_projectors, $result->quantity_laptops, $result->quantity_cables);
                $this->identityMap->add($reservation);
                $reservations[] = $reservation;
            }
        }

        return $reservations;
    }

    /**
     * Returns the Position for the User passed in
     * 
     * @param int $user_id
     * @return array[]
     */
    public function findPositionsForUser(int $user_id): array {
        //Obtain Position for the User passed in from the databse using the TDG
        $results = $this->tdg->findPositionsForUser($user_id);
        $reservations = [];

        //With the data obtained, check if they are in the identity Map
        foreach ($results as $result) {
            //If it is in the Identity Map, then simply add it to the Reservation Array
            if ($reservation = $this->identityMap->get($result->id)) {
                $reservations[] = [$reservation, $result->position];
            }
            //If it is not in the Identity Map, then create the Reservation object
            // Then add it to the Identity Map as well as the Reservaiton Array
            else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position, $result->wait_position, $result->quantity_markers, $result->quantity_projectors, $result->quantity_laptops, $result->quantity_cables);
                $this->identityMap->add($reservation);
                $reservations[] = [$reservation, intval($result->position)];
            }
        }

        return $reservations;
    }

    /**
     * Returns the number of reservations for a certain user within a date range
     *
     * @param int $userId
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countInRange(int $userId, \DateTime $start, \DateTime $end) {
        return $this->tdg->countInRange($userId, $start, $end);
    }

    /**
     * Return a count of all wait-listed reservations for a certain user within a date range
     *
     * @param int $userId
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countAll(int $userId, \DateTime $start, \DateTime $end) {
        return $this->tdg->countAll($userId, $start, $end);
    }

    /**
     * Return a count all Equipment in active reservations for a certain user within a date range
     *
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countEquipment(\DateTime $timeslot) {
        return $this->tdg->countEquipment($timeslot);
    }

    /**
     * Return a count all Equipment in active reservations excluding a certain user's reservation within a date range
     *
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countEquipmentExclude(\DateTime $timeslot, $id) {
        return $this->tdg->countEquipmentExclude($timeslot, $id);
    }

    /**
     * Confirm the status of all the equipment that is requested by the user
     *
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function statusEquipment(\DateTime $timeslot, int $markersRequest, int $laptopsRequest, int $projectorsRequest, int $cablesRequest) {
        //Variables to store the count of all equipment already being used at the passed in time-slot
        $markersCount = 0;
        $projectorsCount = 0;
        $laptopsCount = 0;
        $cablesCount = 0;

        //Check all the equipment that is being used during that timeslot
        //Assign the values to the variables listed above
        $equipmentCount = $this->countEquipment($timeslot);
        foreach ($equipmentCount as $e) {
            $markersCount += $e->quantity_markers;
            $projectorsCount += $e->quantity_projectors;
            $laptopsCount += $e->quantity_laptops;
            $cablesCount += $e->quantity_cables;
        }

        //Use a boolean to know if the status of the equipment remains ok
        //Start the boolean as true
        $eStatus = true;

        //The total amount of equipment available at any given timeslot across all rooms is 3
        //Therefore a check on the current count will be compared to the maximum
        //And the amount requested must be less than what is remaining
        //Check the markers
        if ($markersRequest > (3 - $markersCount)) {
            $eStatus = false;
        }

        //Check the laptops
        if ($laptopsRequest > (3 - $laptopsCount)) {
            $eStatus = false;
        }

        //Check the projectors
        if ($projectorsRequest > (3 - $projectorsCount)) {
            $eStatus = false;
        }

        //Check the cables
        if ($cablesRequest > (3 - $cablesCount)) {
            $eStatus = false;
        }

        return $eStatus;
    }

    /**
     * Confirm the status of all the equipment that is requested by the user, excluding counting equipment part of their reservation
     *
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function statusEquipmentExclude(\DateTime $timeslot, int $id, int $markersRequest, int $laptopsRequest, int $projectorsRequest, int $cablesRequest) {
        //Variables to store the count of all equipment already being used at the passed in time-slot
        $markersCount = 0;
        $projectorsCount = 0;
        $laptopsCount = 0;
        $cablesCount = 0;

        //Check all the equipment that is being used during that timeslot
        //Assign the values to the variables listed above
        $equipmentCount = $this->countEquipmentExclude($timeslot, $id);
        foreach ($equipmentCount as $e) {
            $markersCount += $e->quantity_markers;
            $projectorsCount += $e->quantity_projectors;
            $laptopsCount += $e->quantity_laptops;
            $cablesCount += $e->quantity_cables;
        }

        //Use a boolean to know if the status of the equipment remains ok
        //Start the boolean as true
        $eStatus = true;

        //The total amount of equipment available at any given timeslot across all rooms is 3
        //Therefore a check on the current count will be compared to the maximum
        //And the amount requested must be less than what is remaining
        //Check the markers
        if ($markersRequest > (3 - $markersCount)) {
            $eStatus = false;
        }

        //Check the laptops
        if ($laptopsRequest > (3 - $laptopsCount)) {
            $eStatus = false;
        }

        //Check the projectors
        if ($projectorsRequest > (3 - $projectorsCount)) {
            $eStatus = false;
        }

        //Check the cables
        if ($cablesRequest > (3 - $cablesCount)) {
            $eStatus = false;
        }

        return $eStatus;
    }

    /**
     * Method to update the Reservation of a user
     * 
     * @param int $id
     * @param string $description
     */
    public function set(int $id, string $description, int $markers, int $projectors, int $laptops, int $cables, string $timeslot, string $roomName) {
        //Find the reservation that was passed in
        $reservation = $this->find($id);

        //Set all of the new variables
        $reservation->setDescription($description);
        $reservation->setMarkers($markers);
        $reservation->setProjectors($projectors);
        $reservation->setLaptops($laptops);
        $reservation->setCables($cables);

        $date = substr($reservation->getTimeslot()->toDateTimeString(), 0, 10);
        $newTimeslot = $date . " " . $timeslot . ":00:00";

        $reservation->setTimeslot(new Carbon($newTimeslot));
        $reservation->setRoomName($roomName);

        // we've modified something in the object so we register the instance as dirty in the UoW
        // This will be intercepted by the ReservationUoWAspect class when running the system on a Linux based web-server
        ReservationUoW::getInstance()->registerDirty($reservation);
    }

    /**
     * Method to update the Wait-list Position of a user's Reservation
     * 
     * @param int $id
     * @param string $description
     */
    public function setNewWaitlist(int $id, int $newPosition) {
        //Find the reservation that was passed in
        $reservation = $this->find($id);

        //Set the new position
        $reservation->setPosition($newPosition);

        // we've modified something in the object so we register the instance as dirty in the UoW
        // This will be intercepted by the ReservationUoWAspect class when running the system on a Linux based web-server
        ReservationUoW::getInstance()->registerDirty($reservation);
    }

    /**
     * Method to move a user down in the Wait-list for a specific Time-slot
     * 
     * @param int $id
     * @param string $description
     */
    public function moveDown(Reservation $reservation) {
        //Get the old position of the Reservation being passed in
        $old = $reservation->getPosition();

        //Set the new position of the Reservation to the old + 1
        $reservation->setPosition($old + 1);

        // we've modified something in the object so we register the instance as dirty in the UoW
        // This will be intercepted by the ReservationUoWAspect class when running the system on a Linux based web-server
        ReservationUoW::getInstance()->registerDirty($reservation);
    }

    /**
     * Method to Delete a Reservation
     * 
     * @param int $id
     */
    public function delete(int $id) {
        //Find the reservation that was passed in
        $reservation = $this->find($id);

        //If the identity map returned the object, then remove it from the IdentityMap
        if ($reservation !== null) {
            $this->identityMap->delete($reservation);

            // we want to delete this object from out DB, so we simply register it as deleted in the UoW
            // This will be intercepted by the ReservationUoWAspect class when running the system on a Linux based web-server
            ReservationUoW::getInstance()->registerDeleted($reservation);
        }
    }

    /**
     * Finalize changes
     */
    public function done() {
        // This will be intercepted by the ReservationUoWAspect class when running the system on a Linux based web-server
        ReservationUoW::getInstance()->commit();
    }

    /**
     * Pass the list of Reservations to add to DB to the TDG
     *
     * @param array $newList
     */
    public function addMany(array $newList) {
        $this->tdg->addMany($newList);
    }

    /**
     * Pass the list of Reservations to update in the DB to the TDG
     *
     * @param array $updateList
     */
    public function updateMany(array $updateList) {
        $this->tdg->updateMany($updateList);
    }

    /**
     * Pass the list of Reservations to remove from DB to the TDG
     *
     * @param array $deleteList
     */
    public function deleteMany(array $deleteList) {
        $this->tdg->deleteMany($deleteList);
    }

}
