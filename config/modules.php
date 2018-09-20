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

        // Mrcore Packages
        // ---------------------------------------------------------------------
        #'Mrcore\Appstub'         => [],
        #'Mrcore\Auth'            => [],
        #'Mrcore\Components'      => [],
        'Mrcore\Foundation'       => ['type' => 'foundation'],
        #'Mrcore\Mrcore4Legacy'   => [],
        #'Mrcore\Parser'          => [],
        #'Mrcore\TextWiki'        => [], // Not a mrcore module
        #'Mrcore\Wiki'            => [],


        // Bootswatch Themes
        // ---------------------------------------------------------------------
        // default cerulean cosmo cyborg darkly flatly journal lumen paper
        // readable sandstone simplex slate spacelab superhero united yeti
        'Mrcore\Themes\Bootswatch' => [
            'type' => 'basetheme',
            'namespace' => 'Mrcore\Themes\Bootswatch',
            'controller_namespace' => null,
            'provider' => 'Mrcore\Themes\Bootswatch\Providers\ThemeServiceProvider',
            'path' => ['vendor/mrcore/bootswatch-theme'],
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

        #'Subtheme\Example' => [
        #    'type' => 'subtheme',
        #    'css' => [
        #        'css/bootstrap/slate.min.css',
        #    ],
        #],

        // Mreschke Packages
        // ---------------------------------------------------------------------
        #'Mreschke\Api'           => [],
        #'Mreschke\Dbal'          => [],
        #'Mreschke\Helpers'       => ['provider' => null],
        #'Mreschke\Keystone'      => [],
        #'Mreschke\Render'        => [],
        #'Mreschke\Repository'    => [],

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
        #'Subtheme\Example',
        'Mrcore\Themes\Bootswatch',
        'Mrcore\Auth',
        'Mrcore\Wiki',

    ],

    'views' => [
        '%app%',
        #'Subtheme\Example',
        'Mrcore\Themes\Bootswatch',
        'Mrcore\Auth',
        'Mrcore\Wiki',
    ],

    'routes' => [
        'Mrcore\Wiki',
        '%app%',
        'Mrcore\Auth',
        'Mrcore\Foundation',
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
