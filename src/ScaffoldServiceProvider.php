<?php

namespace Ramosmerino\Scaffold;

use Illuminate\Support\ServiceProvider;

class ScaffoldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCreate();
    }

    /**
     * Register the scaffold:create generator.
     */
    private function registerCreate()
    {
        $this->app->singleton('command.ramosmerino.create', function ($app) {
            return $app['Ramosmerino\Scaffold\Commands\ScaffoldCreateCommand'];
        });
        $this->commands('command.ramosmerino.create');
    }

	/**
     * Register the scaffold:flush command.
     */
    private function registerCreate()
    {
        $this->app->singleton('command.ramosmerino.flush', function ($app) {
            return $app['Ramosmerino\Scaffold\Commands\ScaffoldFlushCommand'];
        });
        $this->commands('command.ramosmerino.flush');
    }

}
