<?php

// This file is registered first thing from Mrcore\Foundation\Bootstrap\Start
// So we can override any Laravel Foundation/helpers.php or Support/helpers.php functions

if ( ! function_exists('asset'))
{
	/**
	 * Generate an asset path for the application.
	 *
	 * @param  string  $path
	 * @param  bool    $secure
	 * @return string
	 */
	function asset($path, $secure = null)
	{
		$path = "/assets/$path";
		return app('url')->asset($path, $secure);
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