<?php namespace Mrcore\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Mrcore\Foundation\Support\UrlGenerator;

/**
 * Provides laravel URL overrides
 */
class UrlServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Override Laravels UrlGenerator with my own
        // Original UrlGenerator class is found in
        // vendor/laravel/framework/src/Illuminate/Routing/UrlGenerator.php
        // And the original service is registered in
        // vendor/laravel/framework/src/Illuminate/Routing/RoutingServiceProvider.php
        // I simply created a new Mrcore/Mrcore/UrlGenerator.php class and copied
        // the register closure below from Illuminate/Routing/RoutingServiceProvider.php
        // I only overwrite one function, the getRootUrl() ffunction.
        // This is responsible for determining if the original request is http or https
        // But because I usually run laravel behind a loadbalancer, all requests are seen
        // internally as http.  So I have to force https if defined in my config
        // ->share means url is a singleton, same as bindShared

        $this->app->singleton('url', function ($app) {
            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();
            return new UrlGenerator($routes, $app->rebinding('request', function ($app, $request) {
                $app['url']->setRequest($request);
            }));
        });
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
