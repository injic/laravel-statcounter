<?php

namespace Injic\LaravelStatcounter;

use Illuminate\Support\ServiceProvider;

class LaravelStatcounterServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'statcounter');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bindStat();
        $this->handleConfig();
    }

    protected function bindStat()
    {
        $this->app->bind('statcounter', function () {
            return new LaravelStatcounter(app('config')->get('statcounter'));
        });
    }

    protected function handleConfig()
    {
        $packageConfig = __DIR__ . '/config/statcounter.php';
        $destinationConfig = config_path('statcounter.php');

        $this->publishes([
            $packageConfig => $destinationConfig,
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'statcounter'
        );
    }
}
