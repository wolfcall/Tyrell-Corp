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
     * Method that will be called after real method
     *
     * @param MethodInvocation $invocation Invocation
     * @After("execution(public App\Data\Mappers\ReservationMapper->create(*))")
     */
    public function afterMethodExecution(MethodInvocation $invocation) {
        
        $passing = $invocation->getArguments();
        
        $reservation = new Reservation($passing[0], $passing[1], $passing[2], $passing[3], $passing[4], null, $passing[5], $passing[6], $passing[7], $passing[8], $passing[9]);
        var_dump($reservation);
        echo '<br>';
        $this->registerNew($reservation);
        
        var_dump($newList);
        die();
    }
    
    
    

}
