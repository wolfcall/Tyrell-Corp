<?php

namespace App\Data\TDGs;

use App\Singleton;
use DB;

/**
 * @method static RoomTDG getInstance()
 */
class RoomTDG extends Singleton {

    /**
     * Gets a specific Room from the database by name
     *
     * @param string $roomName
     * @return \stdClass|null
     */
    public function find(string $roomName) {
        $rooms = DB::select('SELECT * FROM rooms WHERE name = ?', [$roomName]);

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
    public function findAll() {
        $rooms = DB::select('SELECT * FROM rooms');

        return $rooms;
    }

    /**
     * Set the Room Passed in to busy
     */
    public function setBusy($roomName, $student, $timestamp) {
        DB::update('UPDATE rooms SET busy = :id, dateTime = :time WHERE name = :room', [
            'id' => $student,
            'room' => $roomName,
            'time' => $timestamp
        ]);
    }

    /**
     * Set the Room Passed in to free
     */
    public function setFree($roomName) {
        DB::update('UPDATE rooms SET busy = :id WHERE name = :room', [
            'id' => 0,
            'room' => $roomName
        ]);
    }

    /**
     * Remove the student any rooms they are in
     */
    public function clearStudent($student) {
        $roomName = DB::select('SELECT name FROM rooms WHERE busy = ?', [$student]);

        foreach ($roomName as $room) {
            $this->setFree($room->name);
        }
    }

    /**
     * Check if the Room Passed in is busy
     *
     * @return boolean
     */
    public function getStatus($roomName) {
        return DB::select('SELECT * FROM rooms WHERE name = ?', [$roomName]);
    }

}
