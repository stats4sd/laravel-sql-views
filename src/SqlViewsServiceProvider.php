<?php

namespace Stats4SD\SqlViews;

use Illuminate\Support\ServiceProvider;

class SqlViewsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'stats4sd');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'stats4sd');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->publishes([
            __DIR__.'/../config/sqlviews.php' => config_path('sqlviews.php'),
        ]);

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sqlviews.php', 'sqlviews');

        //create the default folder for storing mysql view definitions
        if (! is_dir(base_path('database/views'))) {
            mkdir(base_path('database/views'));
            copy(__DIR__.'/database/views/example.sql', base_path('database/views/example.sql'));
        }

        // Register the service the package provides.
        $this->app->singleton('sqlviews', function ($app) {
            return new SqlViews;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['sqlviews'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/sqlviews.php' => config_path('sqlviews.php'),
        ], 'sqlviews.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/stats4sd'),
        ], 'sqlviews.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/stats4sd'),
        ], 'sqlviews.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/stats4sd'),
        ], 'sqlviews.views');*/

        // Registering package commands.
        $this->commands([
            Commands\UpdateSqlViews::class,
        ]);
    }
}
