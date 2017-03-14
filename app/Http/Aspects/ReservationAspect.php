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

    /**
     * Method that will be called before real method
     *
     * @param MethodInvocation $invocation Invocation
     * @Before("execution(public App\Http\Controllers\ReservationController->create(*))")
     */
    public function beforeMethodExecution(MethodInvocation $invocation) {
 
        $passing = $invocation->getArguments();
        
        foreach($passing as $p)
        {
            
        var_dump($passing);
        
        echo '<br>';
        
        }
        die();
    }

}
