<?php

namespace App\Data\UoWs;

use App\Data\Mappers\ReservationMapper;
use App\Data\Reservation;
use App\Singleton;

/**
 * @method static ReservationUoW getInstance()
 */
class ReservationUoW extends Singleton {

    private $newList = [];
    private $changedList = [];
    private $deletedList = [];

    /**
     * @var ReservationMapper
     */
    private $mapper;

    protected function __construct() {
        parent::__construct();

        $this->mapper = ReservationMapper::getInstance();
    }

    public function registerNew(Reservation $reservation) {
        $this->newList[] = $reservation;
    }

    public function registerDirty(Reservation $reservation) {
        $this->changedList[] = $reservation;
    }

    public function registerDeleted(Reservation $reservation) {
        $this->deletedList[] = $reservation;
    }

    public function commit() {
        $this->mapper->addMany($this->newList);
        $this->mapper->updateMany($this->changedList);
        $this->mapper->deleteMany($this->deletedList);

        // empty the lists after the commit
        $this->newList = [];
        $this->changedList = [];
        $this->deletedList = [];
    }

}
