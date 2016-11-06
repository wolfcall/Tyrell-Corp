<?php

namespace App\Data\Mappers;

use App\Data\IdentityMaps\RoomIdentityMap;
use App\Data\TDGs\RoomTDG;
use App\Data\UnitOfWork;
use App\Data\Room;
use App\Singleton;

/**
 * @method static RoomMapper getInstance()
 */
class RoomMapper extends Singleton
{

    /**
     * @var RoomTDG
     */
    private $tdg;

    /**
     * @var RoomIdentityMap
     */
    private $identityMap;

    /**
     * UserMapper constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->tdg = RoomTDG::getInstance();
        $this->identityMap = RoomIdentityMap::getInstance();
    }

    /**
     * Fetch message for retrieving a User with the given ID
     *
     * @param string $name
     * @return Room
     */
    public function find(string $name): Room
    {
        $room = $this->identityMap->find($name);
        $result = null;

        // If Identity Map doesn't have it then use TDG.
        if ($room === null) {
            $result = $this->tdg->find($name);
        }

        // If TDG doesn't have it then it doens't exist.
        if ($result !== null) {
            //We got the client from the TDG who got it from the DB and now the mapper must add it to the ClientIdentityMap
            $room = new Room((string)$result->name);
            $this->identityMap->add($room);
        }

        return $room;
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        $results = $this->tdg->findAll();
        $rooms = [];

        foreach ($results as $result) {
            if ($room = $this->identityMap->find($result->name)) {
                $rooms[] = $room;
            } else {
                $room = new Room((string)$result->name);
                $this->identityMap->add($room);
                $rooms[] = $room;
            }
        }

        return $rooms;
    }
}
