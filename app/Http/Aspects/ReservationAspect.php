<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of MonitorAspect
 *
 * @author
 */

namespace App\Http\Aspects;

use Go\Aop\Aspect;
use Go\Aop\Intercept\FieldAccess;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\After;
use Go\Lang\Annotation\Before;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Pointcut;
use App\Data\IdentityMaps\ReservationIdentityMap;
use App\Data\TDGs\ReservationTDG;
use App\Data\UoWs\ReservationUoW;
use App\Data\Reservation;

class ReservationAspect implements Aspect {

    private $newList = [];
    private $changedList = [];
    private $deletedList = [];
    private $mapper;

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
        $this->mapper = ReservationMapper::getInstance();

        $this->mapper->addMany($this->newList);
        $this->mapper->updateMany($this->changedList);
        $this->mapper->deleteMany($this->deletedList);

        // empty the lists after the commit
        $this->newList = [];
        $this->changedList = [];
        $this->deletedList = [];
    }

    /**
     * Method that will be called after real method create
     *
     * @param MethodInvocation $invocation Invocation
     * @After("execution(public App\Data\Mappers\ReservationMapper->create(*))")
     */
    public function afterCreatedExecution(MethodInvocation $invocation) {
        $passing = $invocation->getArguments();
        $reservation = new Reservation($passing[0], $passing[1], $passing[2], $passing[3], $passing[4], null, $passing[5], $passing[6], $passing[7], $passing[8], $passing[9]);

        $this->registerNew($reservation);
    }
    
    /**
     * Method that will be called instead of the real method set
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public App\Data\Mappers\ReservationMapper->set(*))")
     */
    public function aroundSetExecution(MethodInvocation $invocation) {
        $this->mapper = ReservationMapper::getInstance();
        
        $passing = $invocation->getArguments();
        
        $reservation = $this->mapper->find($passing[0]);

        $reservation->setDescription($passing[1]);
        $reservation->setMarkers($passing[2]);
        $reservation->setProjectors($passing[3]);
        $reservation->setLaptops($passing[4]);
        $reservation->setCables($passing[5]);

        $date = substr($reservation->getTimeslot()->toDateTimeString(), 0, 10);
        $newTimeslot = $date . " " . $timeslot . ":00:00";

        $reservation->setTimeslot(new Carbon($newTimeslot));
        $reservation->setRoomName($passing[7]);

        // we've modified something in the object so we register the instance as dirty in the UoW
        $this->registerDirty($reservation);
    }
    
    /**
     * Method that will be called instead of the real method setNewWaitlist
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public App\Data\Mappers\ReservationMapper->setNewWaitlist(*))")
     */
    public function aroundSetNewWaitlistExecution(MethodInvocation $invocation) {
        $this->mapper = ReservationMapper::getInstance();
        
        $passing = $invocation->getArguments();
        
        $reservation = $this->find($passing[0]);

        $reservation->setPosition($passing[1]);

        // we've modified something in the object so we register the instance as dirty in the UoW
        $this->registerDirty($reservation);
    }
    
    /**
     * Method that will be called instead of the real method moveDown
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public App\Data\Mappers\ReservationMapper->moveDown(*))")
     */
    public function aroundMoveDownExecution(MethodInvocation $invocation) {
        $passing = $invocation->getArguments();
        
        $old = $passing[0]->getPosition();
        $passing[0]->setPosition($old + 1);

        // we've modified something in the object so we register the instance as dirty in the UoW
        $this->registerDirty($passing[0]);
    }
    
    /**
     * Method that will be called after the real method delete
     *
     * @param MethodInvocation $invocation Invocation
     * @After("execution(public App\Data\Mappers\ReservationMapper->delete(*))")
     */
    public function afterDeleteExecution(MethodInvocation $invocation) {
        $this->mapper = ReservationMapper::getInstance();
        
        $passing = $invocation->getArguments();        

        // first we fetch the client by checking the identity map
        $reservation = $this->mapper->find($passing[0]);

        // if the identity map returned the object, then remove it from the IdentityMap
        if ($reservation !== null) {
            // we want to delete this object from out DB, so we simply register it as deleted in the UoW
            $this->registerDeleted($reservation);
        }
    }
}
