<?php

namespace App\Data\TDGs;

use App\Data\Room;
use App\Singleton;
use DB;

/**
 * @method static RoomTDG getInstance()
 */
class RoomTDG extends Singleton
{
    /**
     * Gets a specific Room from the database by name
     *
     * @param string $name
     * @return \stdClass|null
     */
    public function find(string $name)
    {
        $rooms = DB::select('SELECT * FROM rooms WHERE name = ?', [$name]);

        if (empty($rooms)) {
            return null;
        }

        return $rooms[0];
    }

    /**
     * Gets all Rooms from the database
     *
     * @return array
     */
    public function findAll()
    {
        $rooms = DB::select('SELECT * FROM rooms');

        return $rooms;
    }
}
