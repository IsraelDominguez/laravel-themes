<?php namespace Genetsis\Themes;

use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('themes.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('theme', function ($app) {
            $theme = new Theme($app, $this->app['view']->getFinder(), $this->app['translator']);
            return $theme;
        });

        $this->app->singleton('asset', function($app){
            return new Asset();
        });
    }

}
