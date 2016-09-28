<?php

/**
 * Mrcore Modules Configuration File
 *
 * Do NOT use laravels env() function here as this is used by the asset manager.
 * This config does not allow partial overrides. You must publish
 * the entire script: ./artisan vendor:publish --tag="mrcore.modules.configs"
 * Access with Config::get('mrcore.xyz')
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Mrcore Modules
    |--------------------------------------------------------------------------
    |
    | Define all mrcore modules.  Order does not matter here.  Path is relative
    | to your laravel root (no leading or trailing /).
    |
    */

    'modules' => [

        'Foundation' => [
            'type' => 'foundation',
            'namespace' => 'Mrcore\Foundation',
            'controller_namespace' => 'Mrcore\Foundation\Http\Controllers',
            'provider' => 'Mrcore\Foundation\Providers\FoundationServiceProvider',
            'path' => ['vendor/mrcore/foundation', '../Modules/Foundation'],
            'routes' => 'Http/routes.php',
        ],

        'Auth' =>  [
            'type' => 'module',
            'namespace' => 'Mrcore\Auth',
            'controller_namespace' => 'Mrcore\Auth\Http\Controllers',
            'provider' => 'Mrcore\Auth\Providers\AuthServiceProvider',
            'path' => ['vendor/mrcore/auth', '../Modules/Auth'],
            'routes' => 'Http/routes.php',
            'route_prefix' => null,
            'views' => 'Views',
            'view_prefix' => null,
            'assets' => 'Assets',
            'enabled' => false,
        ],

        'Wiki' => [
            'type' => 'module',
            'namespace' => 'Mrcore\Wiki',
            'controller_namespace' => 'Mrcore\Wiki\Http\Controllers',
            'provider' => 'Mrcore\Wiki\Providers\WikiServiceProvider',
            'path' => ['vendor/mrcore/wiki', '../Modules/Wiki'],
            'routes' => 'Http/routes.php',
            'route_prefix' => null,
            'views' => 'Views',
            'view_prefix' => null,
            'assets' => 'Assets',
            'enabled' => false,
        ],

        'Parser' => [
            'type' => 'module',
            'namespace' => 'Mrcore\Parser',
            'provider' => 'Mrcore\Parser\Providers\ParserServiceProvider',
            'path' => ['vendor/mrcore/parser', '../Modules/Parser'],
            'enabled' => false,
        ],

        // Bootswatch Themes
        // default cerulean cosmo cyborg darkly flatly journal lumen paper
        // readable sandstone simplex slate spacelab superhero united yeti
        'BaseTheme' => [
            'type' => 'basetheme',
            'namespace' => 'Mrcore\Themes\Bootswatch',
            'controller_namespace' => null,
            'provider' => 'Mrcore\Themes\Bootswatch\Providers\ThemeServiceProvider',
            'path' => ['vendor/mrcore/bootswatch-theme', '../Themes/Bootswatch'],
            'routes' => null,
            'route_prefix' => null,
            'views' => 'Views',
            'view_prefix' => null,
            'assets' => 'Assets',
            'css' => [
                'css/bootstrap/simplex.min.css',
                'css/bootstrap/override/theme-simplex.css',
                'css/prism-light.css',
            ],
            'container' => [
                'header' => true,
                'body' => true,
                'footer' => true,
            ],
            'enabled' => false,
        ],

        'SubTheme' => [
            'type' => 'subtheme',
            'namespace' => 'Mrcore\Themes\Example',
            'controller_namespace' => null,
            'provider' => 'Mrcore\Themes\Example\Providers\ThemeServiceProvider',
            'path' => ['vendor/dynatron/example-theme', '../Themes/Example'],
            'routes' => null,
            'route_prefix' => null,
            'views' => 'Views',
            'view_prefix' => null,
            'assets' => 'Assets',
            'css' => [
                'css/bootstrap/slate.min.css',
                #'css/bootstrap/override/example.css',
                #'css/prism-light.css',
            ],
            'enabled' => false,
        ],

        'Mreschke\Helpers' => [
            'type' => 'module',
            'namespace' => 'Mreschke\Helpers',
            'path' => ['vendor/mreschke/helpers', '../Apps/Mreschke/Helpers'],
            'enabled' => false,
        ],

        /*'Mreschke\Dbal' => [
            'type' => 'module',
            'namespace' => 'Mreschke\Dbal',
            'provider' => 'Mreschke\Dbal\Providers\DbalServiceProvider',
            'path' => ['vendor/mreschke/dbal', '../Apps/Mreschke/Dbal'],
            'console_only' => false,
            'enabled' => false,
        ],

        'Mreschke\Repository' => [
            'type' => 'module',
            'namespace' => 'Mreschke\Repository',
            'path' => ['vendor/mreschke/repository', '../Apps/Mreschke/Repository'],
        ],

        'Mreschke\Mrcore4Legacy' => [
            'type' => 'module',
            'namespace' => 'Mreschke\Mrcore4Legacy',
            'provider' => 'Mreschke\Mrcore4Legacy\Providers\Mrcore4LegacyServiceProvider',
            'path' => ['vendor/mreschke/mrcore4-legacy', '../Apps/Mreschke/Mrcore4Legacy'],
        ],

        'Mreschke\Keystone' => [
            'type' => 'module',
            'namespace' => 'Mreschke\Keystone',
            'provider' => 'Mreschke\Keystone\Providers\KeystoneServiceProvider',
            'path' => ['vendor/mreschke/keystone', '../Apps/Mreschke/Keystone'],
        ],

        'Mreschke\Api' => [
            'type' => 'module',
            'namespace' => 'Mreschke\Api',
            'provider' => 'Mreschke\Api\Providers\ApiServiceProvider',
            'path' => ['vendor/mreschke/api', '../Apps/Mreschke/Api'],
        ],
        */

    ],

    /*
    |--------------------------------------------------------------------------
    | Loading Order / Override Management
    |--------------------------------------------------------------------------
    |
    | Defines the modules loading order for each item (assets, views, routes).
    | First item found wins. This fine grained control control over module
    | overrides giving you the control.
    | If you have the Mrcore\Wiki module enabled, then use %app% to define
    | the order of the dynamically loaded wiki application.  Usually apps are first
    | which allows an app to be primary override.  Your actual laravel app is not
    | listed, but always comes first (even above %app%) for assets, views and routes.
    |
    */

    'assets' => [
        '%app%',
        'SubTheme',
        'Auth',
        'Wiki',
        'BaseTheme',
    ],

    'views' => [
        '%app%',
        'SubTheme',
        'Auth',
        'Wiki',
        'BaseTheme',
    ],

    'routes' => [
        'Wiki',
        '%app%',
        'Auth',
        'Foundation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug and Trace
    |--------------------------------------------------------------------------
    |
    | If enabled, each modules boot and register is added to a IoC array for
    | further dump and analysis using dd(Module::trace())
    |
    */

    'debug' => false,

];
