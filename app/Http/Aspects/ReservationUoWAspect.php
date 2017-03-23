<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * ReservationUoWAspect
 */

namespace App\Http\Aspects;

use Go\Aop\Aspect;
use Go\Aop\Intercept\FieldAccess;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\After;
use Go\Lang\Annotation\Before;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Pointcut;
use App\Data\Mappers\ReservationMapper;
use App\Data\Reservation;

class ReservationUoWAspect implements Aspect {

    private $newList = [];
    private $changedList = [];
    private $deletedList = [];
    private $mapper;

    /**
     * Method that will be called after real method create
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public App\Data\UoWs\ReservationUoW->registerNew(*))")
     */
    public function aroundRegisterNewExecution(MethodInvocation $invocation) {
        var_dump("around New");
        
        $passing = $invocation->getArguments();

        $this->newList[] = $passing[0];
    }

    /**
     * Method that will be called after real method create
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public App\Data\UoWs\ReservationUoW->registerDirty(*))")
     */
    public function aroundRegisterDirtyExecution(MethodInvocation $invocation) {
        var_dump("around Dirty");
        
        $passing = $invocation->getArguments();

        $this->changedList[] = $passing[0];
    }

    /**
     * Method that will be called after real method create
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public App\Data\UoWs\ReservationUoW->registerDeleted(*))")
     */
    public function aroundRegisterDeletedExecution(MethodInvocation $invocation) {
        var_dump("around Delete");
        
        $passing = $invocation->getArguments();

        $this->deletedList[] = $passing[0];
    }

    /**
     * Method that will be called instead of the real method done
     *
     * @param MethodInvocation $invocation Invocation
     * @Around("execution(public App\Data\Mappers\ReservationMapper->done(*))")
     */
    public function aroundDoneExecution(MethodInvocation $invocation) {
        var_dump("around Done");
        
        $this->commit();
    }

    /**
     * 
     * Commit all the changes by passing the lists to the Reservation mapper
     * 
     */
    
    public function commit() {
        var_dump("committing");
        
        $this->mapper = ReservationMapper::getInstance();

        $this->mapper->addMany($this->newList);
        $this->mapper->updateMany($this->changedList);
        $this->mapper->deleteMany($this->deletedList);

        var_dump($this->newList);
        var_dump($this->changedList);
        var_dump($this->deletedList);

        // empty the lists after the commit
        $this->newList = [];
        $this->changedList = [];
        $this->deletedList = [];
        
        die();
    }

}
