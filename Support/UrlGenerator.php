<?php namespace Mrcore\Foundation\Support;

use Config;
use Illuminate\Routing\UrlGenerator as LaravelUrlGenerator;

/**
 * Override laravels UrlGenerator
 */
class UrlGenerator extends LaravelUrlGenerator
{

    /**
     * Get the base URL for the request.
     *
     * @param  string  $scheme
     * @param  string  $root
     * @return string
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
}
