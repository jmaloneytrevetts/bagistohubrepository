<?php

namespace jmaloneytrevetts\bagistohubexport;

use Illuminate\Support\ServiceProvider;

class BagistoHubExportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    protected $commands = [
        'jmaloneytrevetts\bagistohubexport\HubExportCommand',
        'jmaloneytrevetts\bagistohubexport\SlackTestCommand',
    ];


    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
       $this->app->make('jmaloneytrevetts\bagistohubexport\OrderHubController');
       $this->commands($this->commands);
    }
}
