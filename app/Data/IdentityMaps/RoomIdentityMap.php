<?php

namespace App\Data\IdentityMaps;

use App\Data\Room;
use App\Singleton;

/**
 * @method static RoomIdentityMap getInstance()
 */
class RoomIdentityMap extends Singleton {

    private $memory = [];

    /**
     * Obtain a room from the Identity Map
     * 
     * @param string $name
     * @return Room|null
     */
    public function get(string $name) {
        if (isset($this->memory[$name])) {
            return $this->memory[$name];
        }

        return null;
    }

    /**
     * Add a room to the Identity Map
     * 
     * @param Room $room
     */
    public function add(Room $room) {
        $memory[$room->getName()] = $room;
    }

    /**
     * Remove a user from the Identity Map
     * 
     * @param Room $room
     */
    public function delete(Room $room) {
        $id = $room->getName();

        if (isset($this->memory[$id])) {
            unset($this->memory[$id]);
        }
    }

}
