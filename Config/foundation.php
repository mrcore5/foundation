<?php

/**
 * Mrcore Foundation Configuration File
 *
 * All configs use env() so you can override in your own .env
 * You can also publish the entire configuration with
 * ./artisan vendor:publish --tag="mrcore.foundation.configs"
 * This config is merged, meaning it handles partial overrides
 * Access with Config::get('mrcore.foundation.xyz')
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Internal Laravel Caching
    |--------------------------------------------------------------------------
    |
    | Cache posts and various other queries to help speed things up
    | Cache provider is set in app/config/cache.php (redis is a good choice)
    | cache_expires is in minutes
    |
    */

    'use_cache' => env('MRCORE_FOUNDATION_CACHE', true),
    'cache_prefix' => env('MRCORE_FOUNDATION_CACHE_PREFIX', 'mrcore/cache'),
    'cache_expires' => env('MRCORE_FOUNDATION_CACHE_EXPIRES', 60),

    // If null, will disable any ?v= addition
    'asset_cache_version' => env('MRCORE_FOUNDATION_ASSET_CACHE_VERSION', null),

    // These app paths for module and console command usage
    'paths' => [
        'psr4' => '',
        'assets' => null,
        'public' => null,
        'config' => 'Config',
        'database' => null,
        'migrations' => null,
        'factories' => null,
        'seeds' => null,
        'tests' => null,
        'routes' => 'Http/routes.php',
        'route_prefix' => null,
        'views' => null,
        'view_prefix' => null,
    ],

];
