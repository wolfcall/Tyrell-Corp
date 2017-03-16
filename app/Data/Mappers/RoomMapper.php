<?php

namespace App\Data\Mappers;

use App\Data\IdentityMaps\RoomIdentityMap;
use App\Data\TDGs\RoomTDG;
use App\Data\Room;
use App\Singleton;

/**
 * @method static RoomMapper getInstance()
 */
class RoomMapper extends Singleton {

    /**
     * @var RoomTDG
     */
    private $tdg;

    /**
     * @var RoomIdentityMap
     */
    private $identityMap;

    /**
     * UserMapper constructor
     * Obtain the instance of both the Room TDG and Identity Map
     */
    protected function __construct() {
        parent::__construct();

        $this->tdg = RoomTDG::getInstance();
        $this->identityMap = RoomIdentityMap::getInstance();
    }

    /**
     * Set the Room to busy when a user is in it
     * 
     * @param String $roomName
     * @param int $student
     * @param String $timestamp
     */
    public function setBusy(string $roomName, $student, $timestamp) {
        $this->tdg->setBusy($roomName, $student, $timestamp);
    }

    /**
     * Set the Room to free when a user had left the room
     * 
     * @param String $roomName
     */
    public function setFree(string $roomName) {
        $this->tdg->setFree($roomName);
    }

    /**
     * Set the Room to free when it is free
     * If a browser crashes, but a student was in a room at the time, this method will remove them after
     * the allocated 60 seconds for their reservation has expired
     * 
     * @param int $student
     */
    public function clearStudent($student) {
        $this->tdg->clearStudent($student);
    }

    /**
     * Get the status of a Room
     * 
     * @param String $roomName
     * @return boolean
     */
    public function getStatus($roomName) {
        return $this->tdg->getStatus($roomName);
    }

    /**
     * Fetch message for retrieving a Room with the given name
     *
     * @param string $name
     * @return Room
     */
    public function find(string $name): Room {
        //Obtain the room from the Identity Map
        $room = $this->identityMap->get($name);
        $result = null;

        // If Identity Map doesn't have it then use TDG.
        if ($room === null) {
            $result = $this->tdg->find($name);
        }

        // If TDG doesn't have it then it doens't exist.
        if ($result !== null) {
            //We got the client from the TDG who got it from the DB and now the mapper must add it to the ClientIdentityMap
            $room = new Room((string) $result->name);
            $this->identityMap->add($room);
        }

        return $room;
    }

    /**
     * Fetch message for retrieving all Rooms
     * 
     * @return array
     */
    public function findAll(): array {
        $results = $this->tdg->findAll();
        $rooms = [];

        foreach ($results as $result) {
            if ($room = $this->identityMap->get($result->name)) {
                $rooms[] = $room;
            } else {
                $room = new Room((string) $result->name);
                $this->identityMap->add($room);
                $rooms[] = $room;
            }
        }

        return $rooms;
    }

}
