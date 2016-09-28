<?php

use Mreschke\Helpers\Other;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

// This file is registered first thing from Mrcore\Foundation\Bootstrap\Start
// So we can override any Laravel Foundation/helpers.php or Support/helpers.php functions

if (! function_exists('asset')) {
    /**
     * Generate an asset path for the application
     * @param  string  $path
     * @param  bool    $secure
     * @return string
     */
    function asset($path, $secure = null)
    {
        $assetCacheVersion = config('mrcore.foundation.asset_cache_version');
        if (isset($assetCacheVersion)) {
            $path = "/assets/$path?v=$assetCacheVersion";
        } else {
            $path = "/assets/$path";
        }
        return app('url')->asset($path, $secure);
    }
}

if (! function_exists('dumpt')) {
    /**
     * Dump the passed variables table style
     * @return void
     */
    function dumpt()
    {
        // Dump simple data
        $dump = function ($data) {
            dump($data);
        };

        // Dump complex array
        $dumpArray = function ($data) {
            if (!isset($data)) {
                return;
            }
            if (empty($data)) {
                return;
            }
            $data = json_decode(json_encode($data), true);
            $data = Other::collapse($data);

            // Build table output
            $headers = array_keys(head($data));
            $table = new Table(new ConsoleOutput);
            $table->setHeaders($headers)->setRows($data);
            $table->render();
            echo count($data)." Rows\n\n";
        };

        $args = func_get_args();
        foreach ($args as $items) {
            if (isset($items)) {
                if (is_numeric($items) || is_string($items)) {
                    // Basic strings and numbers
                    echo $items;
                } elseif ($items instanceof Collection) {
                    $dumpArray($items->flatten()->toArray());
                } elseif (is_array($items)) {
                    if (is_array(head($items))) {
                        $dumpArray($items);
                    } else {
                        // Simple single level array (can be single assoc too)
                        $dump($items);
                    }
                } elseif (is_object($items)) {
                    // Single object
                    dump($items);
                }
            }
        }
    }
}

if (! function_exists('ddt')) {
    /**
     * Dump the passed variables table style and die
     * @return void
     */
    function ddt()
    {
        array_map(function ($items) {
            dumpt($items);
        }, func_get_args());
        exit();
    }
}


if (! function_exists('value')) {
    /**
    * Return the default value of the given value
    * @param  mixed $value|array
    * @param  mixed $default|array key
    * @param  mixed $default
    * @return mixed
    */
    function value()
    {
        // NOTICE, value() is an existing Laravel function
        // This is simply an extended version to allow objects,
        // arrays and default values.

        $args = func_get_args();
        $numArgs = count($args);

        if ($numArgs == 1) {
            // Calling as function($itemOrClosure)
            // This is identical to Laravels built in value() method
            $item = $args[0];
            return $item instanceof Closure ? $item() : $item;
        } elseif ($numArgs == 2 || $numArgs == 3) {
            // Calling as function($arrayOrObjectOrClosure, $key)
            // Calling as function($arrayOrObjectOrClosure, $key, $default)
            $item = $args[0];
            $default = $numArgs == 3 ? $args[2] : null;
            if ($default instanceof Closure) {
                $default = $default();
            }

            if (is_array($item)) {
                // If array, the second arg is key, and 3rd is default
                $key = $args[1];
                return (isset($item[$key])) ? $item[$key] : $default;
            } elseif (is_object($item)) {
                // If object, the second arg is property, and 3rd is default
                $property = $args[1];
                return (isset($item->$property)) ? $item->$property : $default;
            } elseif ($item instanceof Closure) {
                $return = $item();
                return isset($return) ? $return : $default;
            } else {
                // If string or numeric, second arg is default
                $default = $args[1];
                if ($default instanceof Closure) {
                    $default = $default();
                }
                return isset($item) ? $item : $default;
            }

            if (isset($item)) {
                return $item instanceof Closure ? $item() : $item;
            } else {
                return $default;
            }
        } elseif ($numArts == 3) {
        }
    }
}


/*if ( ! function_exists('dump'))
{
    function dump($data)
    {
        // Symfonys var-dummper
        foreach (func_get_args() as $var) {
            \Symfony\Component\VarDumper\VarDumper::dump($var);
        }
    }
}

if ( ! function_exists('dd'))
{
    function dd($data)
    {
        // Laravels (from Illuminate\Support\helpers.php)
        array_map(function($x) { (new \Illuminate\Support\Debug\Dumper)->dump($x); }, func_get_args());
        die;
    }
}

if ( ! function_exists('kdump'))
{
    function kdump($data)
    {
        // Kint
        Kint::dump($data);
    }
}

if ( ! function_exists('kdd'))
{
    function kdd($data)
    {
        kdump($data);
        die;
    }
}

if ( ! function_exists('vdd'))
{
    function vdd($data)
    {
        echo "<pre>";
        var_dump($data);
        die;
    }
}*/
