<?php namespace Mrcore\Foundation\Support;

use Config;
use Illuminate\Support\Str;
use Illuminate\Routing\UrlGenerator as LaravelUrlGenerator;

/**
 * Override laravels UrlGenerator
 */
class UrlGenerator extends LaravelUrlGenerator
{
    /*
    When laravel asset() helper generates full URLS it determine http:// or https:// based on
    what laravel sees as your actual schema.  The problem comes in when you use SSL termination
    behind a loadbalancer.  Laravel thinkgs you are http:// but really you are https://
    So these overrides help the asset() helper pick the proper http method based
    on your APP_URL env variable and NOT your actual protocol
    */

    /**
     * Laravel 5.3 and below has getRootUrl in vendor/laravel/framework/src/Illuminate/Routing/UrlGenerator.php
     */
    protected function getRootUrl($scheme, $root = null)
    {
        $root = $root ?: $this->request->root();

        $start = starts_with($root, 'http://') ? 'http://' : 'https://';

        // mReschke override, force https if https is in the app.url config
        // This gets around loadbalancer ssl termination detection
        if (str_contains(Config::get('app.url'), 'https://')) {
            $scheme = 'https://';
        }

        return preg_replace('~'.$start.'~', $scheme, $root, 1);
    }

    /**
     * Laravel 5.4 and above use formatRoot in vendor/laravel/framework/src/Illuminate/Routing/UrlGenerator.php
     */
    public function formatRoot($scheme, $root = null)
    {
        return $this->getRootUrl($scheme, $root);
    }
}
