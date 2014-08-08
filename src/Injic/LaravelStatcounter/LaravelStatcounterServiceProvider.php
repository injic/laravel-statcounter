<?php

namespace Injic\LaravelStatcounter;

use Illuminate\Support\ServiceProvider;

class LaravelStatcounterServiceProvider extends ServiceProvider {

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
  public function boot() {
    $this->package( 'injic/laravel-statcounter' );
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register() {
    $this->app['statcounter'] = $this->app->share( function ($app) {
      return new Stat();
    } );
    
    $this->app->booting( function () {
      $loader = \Illuminate\Foundation\AliasLoader::getInstance();
    } );
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides() {
    return array (
        'statcounter' 
    );
  }
}
