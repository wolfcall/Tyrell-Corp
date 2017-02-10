<?php

namespace App\Data\TDGs;

use App\Data\Reservation;
use App\Singleton;
use DB;
use Illuminate\Database\QueryException;

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
            if (($id = $this->create($reservation)) !== null) {
                $reservation->setId($id);
            }
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
        $id = null;

        try {
            $id = DB::table('reservations')->insertGetId([
                'user_id' => $reservation->getUserId(),
                'wait_position' => $reservation->getPosition(),
				'room_name' => $reservation->getRoomName(),
                'timeslot' => $reservation->getTimeslot(),
                'description' => $reservation->getDescription(),
                'recur_id' => $reservation->getRecurId(),
				'quantity_markers' => $reservation->getMarkers(),
				'quantity_projectors' => $reservation->getProjectors(),
				'quantity_laptops' => $reservation->getLaptops(),
				'quantity_cables' => $reservation->getCables()
            ]);
        } catch (QueryException $e) {
            // error inserting, duplicate row
        }

        return $id;
    }

    /**
     * SQL statement to update a new Reservation row
     *
     * @param Reservation $reservation
     */
    public function update(Reservation $reservation)
    {
        DB::update('UPDATE reservations SET description = :description, wait_position = :wait_position,
        quantity_markers = :markers, quantity_projectors = :projectors, quantity_laptops = :laptops, quantity_cables = :cables,
        timeslot = :timeslot, room_name = :roomName WHERE id = :id', [
            'id' => $reservation->getId(),
			'wait_position' => $reservation->getPosition(),
            'description' => $reservation->getDescription(),
            'markers' => $reservation->getMarkers(),
            'projectors' => $reservation->getProjectors(),
            'laptops' => $reservation->getLaptops(),
            'cables' => $reservation->getCables(),
            'timeslot' => $reservation->getTimeslot(),
            'roomName' => $reservation->getRoomName()
        ]);
    }

    /**
     * SQL statement to delete Reservation rows based on the Reservation id, or the recurrence id
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
     * @return \stdClass|null
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
     * Returns a list of all Reservations (waitlist and active) for a given room-timeslot, ordered by id
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
     * Returns a list of all active Reservations (if any) for a given timeslot by the user passed in
     *
	 * @param int $id
     * @param \DateTime $timeslot
     * @return array
     */
    public function findAllTimeslotActive(\DateTime $timeslot, $id)
    {
        return DB::select('SELECT *
            FROM reservations
            WHERE timeslot = :timeslot AND user_id = :id AND wait_position = 0
            ORDER BY id', ['timeslot' => $timeslot, 'id' => $id]);
    }
	
	/**
     * Returns who has the reservation for the timeslot
     *
	 * @param String $roomName
     * @param \DateTime $timeslot
     * @return array
     */
    public function findTimeslotWinner(\DateTime $timeslot, $roomName)
    {
        return DB::select('SELECT *
            FROM reservations
            WHERE timeslot = :timeslot AND room_name = :room AND wait_position = 0
            ORDER BY id', ['timeslot' => $timeslot, 'room' => $roomName]);
    }
	
	/**
     * Returns a list of all waitlisted Reservations (if any) for a given timeslot by the user passed in
     *
	 * @param int $id
     * @param \DateTime $timeslot
     * @return array
     */
    public function findAllTimeslotWaitlisted(\DateTime $timeslot, $id, $roomName)
    {
        return DB::select('SELECT *
            FROM reservations
            WHERE timeslot = :timeslot AND user_id = :id AND wait_position != 0 AND room_name != :roomName
            ORDER BY id', ['timeslot' => $timeslot, 'id' => $id, 'roomName' => $roomName]);
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

    /**
     * SQL statement to count the active reservations for a certain user within a date range
     *
     * @param int $user_id
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countInRange(int $user_id, \DateTime $start, \DateTime $end)
    {
        return DB::select('SELECT *
            FROM reservations
            WHERE user_id = :user AND timeslot >= :start AND timeslot < :end AND wait_position = 0', 
			['user' => $user_id, 'start' => $start, 'end' => $end]);
    }
	
	/**
     * SQL statement to count all wait-listed reservations for a certain user within a date range
     *
     * @param int $user_id
     * @param \DateTime $start Start date, inclusive
     * @param \DateTime $end End date, exclusive
     * @return int
     */
    public function countAll(int $user_id, \DateTime $start, \DateTime $end)
    {
        return DB::select('SELECT *
            FROM reservations
            WHERE user_id = :user AND timeslot >= :start AND timeslot < :end AND wait_position != 0', 
			['user' => $user_id, 'start' => $start, 'end' => $end]);
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
        return DB::select('SELECT quantity_markers, quantity_projectors, quantity_laptops, quantity_cables
            FROM reservations
            WHERE timeslot = :time AND wait_position = 0', 
			['time' => $timeslot]);
    }
}
