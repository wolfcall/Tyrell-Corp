<?php

namespace App\Data\IdentityMaps;

use App\Data\Reservation;
use App\Singleton;

/**
 * @method static ReservationIdentityMap getInstance()
 */
class ReservationIdentityMap extends Singleton {

    /**
     * @var Reservation[]
     */
    private $memory = [];

    /**
     * Obtain a reservation from the Identity Map
     * 
     * @param int $id
     * @return Reservation|null
     */
    public function get(int $id) {
        foreach ($this->memory as $r) {
            if ($r->getId() === $id) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Add a reservation to the Identity Map
     * 
     * @param Reservation $reservation
     */
    public function add(Reservation $reservation) {
        $memory[spl_object_hash($reservation)] = $reservation;
    }

    /**
     * Remove a reservation from the Identity map
     * 
     * @param Reservation $reservation
     */
    public function delete(Reservation $reservation) {
        $key = spl_object_hash($reservation);

        if (isset($this->memory[$key])) {
            unset($this->memory[$key]);
        }
    }

}
