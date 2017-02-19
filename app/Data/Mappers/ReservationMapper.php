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
class ReservationMapper extends Singleton
{

    /**
     * @var ReservationTDG
     */
    private $tdg;

    /**
     * @var ReservationIdentityMap
     */
    private $identityMap;

    /**
     * ReservationMapper constructor.
     */
    protected function __construct()
    {
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
     * @return Reservation
     */
    public function create(int $userId, string $roomName, \DateTime $timeslot, string $description, string $uuid, int $position, int $markers, int $projectors, int $laptops, int $cables): Reservation
    {
        $reservation = new Reservation($userId, $roomName, $timeslot, $description, $uuid, null, $position, $markers, $projectors, $laptops, $cables);

        // add the new Reservation to the list of existing objects in live memory
        $this->identityMap->add($reservation);

        // add to UoW registry so that we create it in the DB once the reservation is ready to commit everything
        ReservationUoW::getInstance()->registerNew($reservation);

        return $reservation;
    }

    /**
     * Fetch message for retrieving a Reservation with the given ID
     *
     * @param int $id
     * @return Reservation|null
     */
    public function find(int $id)
    {
        $reservation = $this->identityMap->get($id);
        $result = null;

        // if Identity Map doesn't have it, use TDG
        if ($reservation === null) {
            $result = $this->tdg->find($id);
        }
		
		$markers = $result->quantity_markers;
		$projectors = $result->quantity_projectors;
		$laptops = $result->quantity_laptops;
		$cables = $result->quantity_cables;
		
        // if TDG doesn't have it, it doesn't exist
        if ($result !== null) {
            // we got the Reservation from the TDG who got it from the DB and now the mapper must add it to the ReservationIdentityMap
            $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position,
				$markers,$projectors,$laptops,$cables);
            $this->identityMap->add($reservation);
        }
        return $reservation;
    }

    /**
	 * Returns a list of all Reservations for a given room-timeslot, ordered by id
     * @param string $roomName
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findForTimeslot(string $roomName, \DateTime $timeslot): array
    {
        $results = $this->tdg->findForTimeslot($roomName, $timeslot);
        $reservations = [];

        foreach ($results as $result) {
            if ($reservation = $this->identityMap->get($result->id)) {
                $reservations[] = $reservation;
            } else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position,
					$result->wait_position, $result->quantity_markers, $result->quantity_projectors, $result->quantity_laptops, $result->quantity_cables);
                $this->identityMap->add($reservation);
                $reservations[] = $reservation;
            }
        }

        return $reservations;
    }
	
	/**
	 * Returns a list of all active Reservations (if any) for a given timeslot by the user passed in
	 * @param int $id
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findAllTimeslotActive(\DateTime $timeslot, $id)
    {
        return  $this->tdg->findAllTimeslotActive($timeslot, $id);
    }
	
	/**
	 * Returns a list of all waitlisted Reservations (if any) for a given timeslot by the user passed in
	 * @param int $id
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findAllTimeslotWaitlisted(\DateTime $timeslot, $id, $roomName)
    {
        return  $this->tdg->findAllTimeslotWaitlisted($timeslot, $id, $roomName);
    }
	
	/**
	 * Returns who has the reservation for the timeslot
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findTimeslotWinner(\DateTime $timeslot, $roomName)
    {
        return  $this->tdg->findTimeslotWinner($timeslot, $roomName);
    }
	

    /**
     * @param Reservation $reservation
     * @return int
     */
    public function findPosition(Reservation $reservation): int
    {
        // get a list of all the other reservations for the same room-timeslot
        $reservations = $this->findForTimeslot($reservation->getRoomName(), $reservation->getTimeslot());

        // find which position we're in the waitlist
        $position = 0;
        foreach ($reservations as $r) {
            if ($r->getId() === $reservation->getId()) {
                break;
            }

            ++$position;
        }

        return $position;
    }

    /**
     * @param \DateTime $date
     * @return Reservation[]|array
     */
    public function findAllActive(\DateTime $date): array
    {
        $results = $this->tdg->findAllActive($date);
        $reservations = [];

        foreach ($results as $result) {
            if ($reservation = $this->identityMap->get($result->id)) {
                $reservations[] = $reservation;
            } else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id),
					$result->wait_position, $result->quantity_markers, $result->quantity_projectors, $result->quantity_laptops, $result->quantity_cables);
                $this->identityMap->add($reservation);
                $reservations[] = $reservation;
            }
        }

        return $reservations;
    }

    /**
     * @param int $user_id
     * @return array[]
     */
    public function findPositionsForUser(int $user_id): array
    {
        $results = $this->tdg->findPositionsForUser($user_id);
        $reservations = [];

        foreach ($results as $result) {
            if ($reservation = $this->identityMap->get($result->id)) {
                $reservations[] = [$reservation, $result->position];
            } else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, $result->recur_id, intval($result->id), $result->wait_position,
					$result->wait_position, $result->quantity_markers, $result->quantity_projectors, $result->quantity_laptops, $result->quantity_cables);
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
    public function countInRange(int $userId, \DateTime $start, \DateTime $end)
    {
        return $this->tdg->countInRange($userId, $start, $end);
    }
	
	/**
     * SQL statement to count all wait-listed reservations for a certain user within a date range
     *
     * @param int $userId
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countAll(int $userId, \DateTime $start, \DateTime $end)
    {
        return $this->tdg->countAll($userId, $start, $end);
    }
	
	/**
     * SQL statement to count all Equipment in active reservations for a certain user within a date range
     *
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countEquipment(\DateTime $timeslot)
    {
        return $this->tdg->countEquipment($timeslot);
    }
	
	/**
     * Confirm the status of all the equipment that is requested by the user
     *
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function statusEquipment(\DateTime $timeslot, int $markersRequest, int $laptopsRequest, int $projectorsRequest, int $cablesRequest)
    {
        //Count all equipment already being used
		$markersCount = 0;
		$projectorsCount = 0;
		$laptopsCount = 0;
		$cablesCount = 0;
		
		//Check all the equipment that is being used during that timeslot
		$equipmentCount = $this->countEquipment($timeslot);
		foreach($equipmentCount as $e)
		{
			$markersCount += $e->quantity_markers;
			$projectorsCount += $e->quantity_projectors;
			$laptopsCount += $e->quantity_laptops;
			$cablesCount += $e->quantity_cables;
		}			
		
		//Use a boolean to know if the status of the equipment is ok
		//Start the boolean as true
		$eStatus = true;
		
		//Check the markers
		if($markersRequest > (3-$markersCount))
		{
			$eStatus = false;
		}

		//Check the laptops
		if($laptopsRequest > (3-$laptopsCount))
		{
			$eStatus = false;
		}

		//Check the projectors
		if($projectorsRequest > (3-$projectorsCount))
		{
			$eStatus = false;
		}

		//Check the cables
		if($cablesRequest > (3-$cablesCount))
		{
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
    public function set(int $id, string $description, int $markers, int $projectors, int $laptops, int $cables, string $timeslot, string $roomName)
    {
        $reservation = $this->find($id);

        $reservation->setDescription($description);
        $reservation->setMarkers($markers);
        $reservation->setProjectors($projectors);
        $reservation->setLaptops($laptops);
        $reservation->setCables($cables);

        $date = substr($reservation->getTimeslot()->toDateTimeString(), 0, 10);
        $newTimeslot = $date." ".$timeslot.":00:00";
        
        $reservation->setTimeslot(new Carbon($newTimeslot));
        $reservation->setRoomName($roomName);

        // we've modified something in the object so we register the instance as dirty in the UoW
        ReservationUoW::getInstance()->registerDirty($reservation);
    }
	
	/**
     * Method to update the Waitlist Position of a user's Reservation
	 * 
	 * @param int $id
     * @param string $description
     */
    public function setNewWaitlist(int $id, int $newPosition)
    {
        $reservation = $this->find($id);

        $reservation->setPosition($newPosition);

        // we've modified something in the object so we register the instance as dirty in the UoW
        ReservationUoW::getInstance()->registerDirty($reservation);
    }
	
	/**
	* Method to move a user down in the Waitlist for a specific Timeslot
	* @param int $id
	* @param string $description
	*/
    public function moveDown(Reservation $reservation)
    {
        $old = $reservation->getPosition();
		$reservation->setPosition($old+1);

        // we've modified something in the object so we register the instance as dirty in the UoW
        ReservationUoW::getInstance()->registerDirty($reservation);
    }
	

    /**
	 * Method to Delete a Reservation
	 * 
     * @param int $id
     */
    public function delete(int $id)
    {
        // first we fetch the client by checking the identity map
        $reservation = $this->find($id);

        // if the identity map returned the object, then remove it from the IdentityMap
        if ($reservation !== null) {
            $this->identityMap->delete($reservation);

            // we want to delete this object from out DB, so we simply register it as deleted in the UoW
            ReservationUoW::getInstance()->registerDeleted($reservation);
        }
    }

    /**
     * Finalize changes
     */
    public function done()
    {
        ReservationUoW::getInstance()->commit();
    }

    /**
     * Pass the list of Reservations to add to DB to the TDG
     *
     * @param array $newList
     */
    public function addMany(array $newList)
    {
        $this->tdg->addMany($newList);
    }

    /**
     * Pass the list of Reservations to update in the DB to the TDG
     *
     * @param array $updateList
     */
    public function updateMany(array $updateList)
    {
        $this->tdg->updateMany($updateList);
    }

    /**
     * Pass the list of Reservations to remove from DB to the TDG
     *
     * @param array $deleteList
     */
    public function deleteMany(array $deleteList)
    {
        $this->tdg->deleteMany($deleteList);
    }
}
