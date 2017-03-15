<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http;

use Go\Core\AspectKernel;
use Go\Core\AspectContainer;
use App\Http\Aspects\MonitorAspect;

/**
 * Description of aspectKernel
 *
 * @author Georges
 */
class ApplicationAspectKernel extends AspectKernel {

    /**
     * Configure an AspectContainer with advisors, aspects and pointcuts
     *
     * @param AspectContainer $container
     *
     * @return void
     */
    protected function configureAop(AspectContainer $container) {
        $container->registerAspect(new Aspects\ReservationUoWAspect() );
        $container->registerAspect(new Aspects\UserUoWAspect() );
    }

}
