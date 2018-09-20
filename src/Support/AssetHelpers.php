<?php

// This is to satisfy the asset manager while including a config that has
// pre-laravel functions like env() and config()
// It is OK if these functions don't actually work, the asset manager
// only needs the 'paths' array for mrcore/modules

function env() {}
function config() { return []; }

function base_path($path = '')
{
    $basePath = $GLOBALS['basePath'];
    if ($path) return "$basePath/$path";
    return $basePath;
}

function dd()
{
    array_map(function ($items) {
        echo "<pre>";
        var_dump($items);
        echo "</pre>";
    }, func_get_args());
    exit();
}

function kebab_case($value)
{
    $delimiter = '-';
    if (! ctype_lower($value)) {
        $value = preg_replace('/\s+/u', '', ucwords($value));

        $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value), 'UTF-8');
    }
    return $value;
}

function studly_case($value)
{
    $value = ucwords(str_replace(array('-', '_'), ' ', $value));
    return str_replace(' ', '', $value);
}
