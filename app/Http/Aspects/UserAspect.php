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
use App\Data\IdentityMaps\UserIdentityMap;
use App\Data\TDGs\UserTDG;
use App\Data\UoWs\UserUoW;
use App\Data\User;

class UserAspect implements Aspect {

    /**
     * Method that will be called before real method
     *
     * @param MethodInvocation $invocation Invocation
     * @Before("execution(public App\Data\Mappers\UserMapper->create(*))")
     */
    public function beforeMethodExecution(MethodInvocation $invocation) {
 
        foreach($p as $passing)
        {
            
        var_dump($passing + '<br>');
        
        }
    }

}