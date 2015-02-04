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