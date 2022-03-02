<?php

namespace Danzkefas\Drawverter\Providers;

use Danzkefas\Drawverter\Console\DrawverterInstallPackage;
use Danzkefas\Drawverter\Console\DrawverterProcessERD;
use Illuminate\Support\ServiceProvider;

class DrawverterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
       $this->commands([
           DrawverterInstallPackage::class,
           DrawverterProcessERD::class
       ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(dirname(__DIR__,1).'/routes.php');
    }
}
