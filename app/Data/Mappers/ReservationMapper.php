<?php

namespace App\Data\Mappers;

use App\Data\IdentityMaps\ReservationIdentityMap;
use App\Data\TDGs\ReservationTDG;
use App\Data\UnitsOfWork\ReservationUnitOfWork;
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
     * @return Reservation
     */
    public function create(int $userId, string $roomName, \DateTime $timeslot, string $description): Reservation
    {
        $reservation = new Reservation($userId, $roomName, $timeslot, $description);

        //Add the new Client to the list of existing objects in Live memory
        $this->identityMap->add($reservation);

        //Add to UoW registry so that we create it in the DB once the reservation is ready to commit everything.
        ReservationUnitOfWork::getInstance()->registerNew($reservation);

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
        $reservation = $this->identityMap->find($id);
        $result = null;

        // If Identity Map doesn't have it then use TDG.
        if ($reservation === null) {
            $result = $this->tdg->find($id);
        }

        // If TDG doesn't have it then it doens't exist.
        if ($result !== null) {
            //We got the client from the TDG who got it from the DB and now the mapper must add it to the ClientIdentityMap
            $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, intval($result->id));
            $this->identityMap->add($reservation);
        }

        return $reservation;
    }

    /**
     * @param string $roomName
     * @param \DateTime $timeslot
     * @return Reservation[]
     */
    public function findForTimeslot(string $roomName, \DateTime $timeslot): array
    {
        $results = $this->tdg->findForTimeslot($roomName, $timeslot);
        $reservations = [];

        foreach ($results as $result) {
            if ($reservation = $this->identityMap->find($result->id)) {
                $reservations[] = $reservation;
            } else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, intval($result->id));
                $this->identityMap->add($reservation);
                $reservations[] = $reservation;
            }
        }

        return $reservations;
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
     * @return Reservation[]
     */
    public function findAllActive(): array
    {
        $results = $this->tdg->findAllActive();
        $reservations = [];

        foreach ($results as $result) {
            if ($reservation = $this->identityMap->find($result->id)) {
                $reservations[] = $reservation;
            } else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, intval($result->id));
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
            if ($reservation = $this->identityMap->find($result->id)) {
                $reservations[] = [$reservation, $result->position];
            } else {
                $reservation = new Reservation(intval($result->user_id), $result->room_name, new Carbon($result->timeslot), $result->description, intval($result->id));
                $this->identityMap->add($reservation);
                $reservations[] = [$reservation, intval($result->position)];
            }
        }

        return $reservations;
    }

    /**
     * @param int $id
     * @param string $description
     */
    public function set(int $id, string $description)
    {
        $reservation = $this->find($id);

        $reservation->setDescription($description);

        // We've modified something in the object so we Register the instance as Dirty in the UoW.
        ReservationUnitOfWork::getInstance()->registerDirty($reservation);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        //Fire we fetch the client by checking the identity map
        $reservation = $this->find($id);

        // If the identity map returned the object, then remove it from the IdentityMap
        if ($reservation !== null) {
            $this->identityMap->remove($reservation);

            // We want to delete this object from out DB, so we simply register it as Deleted in the UoW
            ReservationUnitOfWork::getInstance()->registerDeleted($reservation);
        }
    }

    /**
     * Finalize changes
     */
    public function done()
    {
        ReservationUnitOfWork::getInstance()->commit();
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
