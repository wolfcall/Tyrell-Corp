<?php

namespace App\Data\IdentityMaps;

use App\Data\Room;
use App\Singleton;

/**
 * @method static RoomIdentityMap getInstance()
 */
class RoomIdentityMap extends Singleton
{
    private $memory = [];

    /**
     * @param string $name
     * @return Room|null
     */
    public function find(string $name)
    {
        if (isset($this->memory[$name])) {
            return $this->memory[$name];
        }

        return null;
    }

    /**
     * @param Room $room
     */
    public function add(Room $room)
    {
        $memory[$room->getName()] = $room;
    }

    /**
     * @param Room $room
     */
    public function remove(Room $room)
    {
        $id = $room->getName();

        if (isset($this->memory[$id])) {
            unset($this->memory[$id]);
        }
    }
}
