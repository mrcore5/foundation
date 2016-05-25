<?php

use Mrcore\Foundation\Support\Assets;

/*
|--------------------------------------------------------------------------
| Mrcore Foundation
|--------------------------------------------------------------------------
|
| Fire up the mrcore foundation to allow asset handling
| and other foundation support bootstraping.
|
| Include this from laravels ./public/index.php and ./artisan, FIRST THING!
|
*/

if (!isset($runningInConsole)) $runningInConsole = false;

if ($runningInConsole) {
	// Running from artisan console


} else {
	// Running from web public/index.php

	// Stream all /assets/* files from the defined theme folders
	if (isset($_SERVER['REQUEST_URI'])) {
		if (substr($_SERVER['REQUEST_URI'], 0, 7) == '/assets') {
			require __DIR__.'/../Support/Assets.php';
			$assets = new Assets($basePath, $_SERVER['REQUEST_URI']);
			exit();
		}
	}

}

// Helpers functions
require __DIR__.'/../Support/Helpers.php';
