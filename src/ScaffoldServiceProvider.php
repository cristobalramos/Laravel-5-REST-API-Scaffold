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
            return $app['Ramosmerino\Scaffold\Commands\ScaffoldCreate'];
        });

        $this->commands('command.ramosmerino.create');
    }
}
