<?php

namespace App\Data\TDGs;

use App\Data\Reservation;
use App\Singleton;
use DB;

/**
 * @method static ReservationTDG getInstance()
 */
class ReservationTDG extends Singleton
{
    /**
     * Adds a list of Reservations to the database
     *
     * @param array $newList
     */
    public function addMany(array $newList)
    {
        foreach ($newList as $reservation) {
            $id = $this->create($reservation);
            $reservation->setId($id);
        }
    }
    
    /**
     * Updates a list of Reservations in the database
     *
     * @param array $updateList
     */
    public function updateMany(array $updateList)
    {
        foreach ($updateList as $user) {
            $this->update($user);
        }
    }

    /**
     * Removes a list of Reservations in the database
     *
     * @param array $deleteList
     */
    public function deleteMany(array $deleteList)
    {
        foreach ($deleteList as $reservation) {
            $this->remove($reservation);
        }
    }

    /**
     * SQL statement to create a new Reservation row
     *
     * @param Reservation $reservation
     * @return int
     */
    public function create(Reservation $reservation)
    {
        $id = DB::table('reservations')->insertGetId([
            'user_id' => $reservation->getUserId(),
            'room_name' => $reservation->getRoomName(),
            'timeslot' => $reservation->getTimeslot(),
            'description' => $reservation->getDescription()
        ]);

        return $id;
    }

    /**
     * SQL statement to update a new Reservation row
     *
     * @param Reservation $reservation
     */
    public function update(Reservation $reservation)
    {
        DB::update('UPDATE reservations SET description = :description WHERE id = :id', [
            'id' => $reservation->getId(),
            'description' => $reservation->getDescription()
        ]);
    }

    /**
     * SQL statement to delete a new Reservation row
     *
     * @param Reservation $reservation
     */
    public function remove(Reservation $reservation)
    {
        DB::delete('DELETE FROM reservations WHERE id = :id', [
            'id' => $reservation->getId()
        ]);
    }

    /**
     * SQL statement to find a Reservation by its id
     *
     * @param int $id
     * @return null
     */
    public function find(int $id)
    {
        $reservations = DB::select('SELECT * FROM reservations WHERE id = ?', [$id]);

        if (empty($reservations)) {
            return null;
        }

        return $reservations[0];
    }

    /**
     * Returns a list of all Reservations for a given room-timeslot, ordered by id
     *
     * @param string $roomName
     * @param \DateTime $timeslot
     * @return array
     */
    public function findForTimeslot(string $roomName, \DateTime $timeslot)
    {
        return DB::select('SELECT *
            FROM reservations
            WHERE timeslot = :timeslot AND room_name = :room_name
            ORDER BY id', ['timeslot' => $timeslot, 'room_name' => $roomName]);
    }

    /**
     * Returns a list of all active (eg. not waitlisted) reservations for a user
     *
     * @param \DateTime $date
     * @return array
     */
    public function findAllActive(\DateTime $date)
    {
        return DB::select('SELECT r1.*
            FROM reservations r1
            JOIN (SELECT min(id) AS id
	            FROM reservations
	            GROUP BY room_name, timeslot) r2 ON r1.id = r2.id
	        WHERE DATE(timeslot) = DATE(?)
            ORDER BY timeslot;', [$date]);
    }

    /**
     * Returns a list of all Reservations and their waiting list positions for a user
     *
     * @param int $user_id
     * @return array
     */
    public function findPositionsForUser(int $user_id)
    {
        return DB::select('SELECT t.* FROM (
                SELECT r.*,
                    @rank_count := CASE WHEN @prev_room_name <> room_name OR @prev_timeslot <> timeslot THEN 0 ELSE @rank_count + 1 END AS position,
                    @prev_timeslot := timeslot AS _prev_timeslot,
                    @prev_room_name := room_name AS _prev_room_name
                FROM 
                    (SELECT @prev_room_name := -1, @prev_timeslot := -1, @rank_count := -1) v,
                    reservations r
                ORDER BY room_name, timeslot, id) t
            WHERE user_id = ? AND timeslot >= CURDATE()
            ORDER BY timeslot;', [$user_id]);
    }
}
