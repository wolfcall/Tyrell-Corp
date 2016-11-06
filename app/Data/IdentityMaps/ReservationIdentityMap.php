<?php

namespace App\Data\IdentityMaps;

use App\Data\Reservation;
use App\Singleton;

/**
 * @method static ReservationIdentityMap getInstance()
 */
class ReservationIdentityMap extends Singleton
{
    /**
     * @var Reservation[]
     */
    private $memory = [];

    /**
     * @param int $id
     * @return Reservation|null
     */
    public function find(int $id)
    {
        foreach ($this->memory as $r) {
            if ($r->getId() === $id) {
                return $r;
            }
        }

        return null;
    }

    /**
     * @param Reservation $reservation
     */
    public function add(Reservation $reservation)
    {
        $memory[spl_object_hash($reservation)] = $reservation;
    }

    /**
     * @param Reservation $reservation
     */
    public function remove(Reservation $reservation)
    {
        $key = spl_object_hash($reservation);

        if (isset($this->memory[$key])) {
            unset($this->memory[$key]);
        }
    }
}
