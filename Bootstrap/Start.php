<?php

use Mrcore\Modules\Foundation\Support\Assets;

//=========================================================================
// Place this in laravels public/index.php, FIRST THING!
//=========================================================================

/*
|--------------------------------------------------------------------------
| Mrcore Foundation
|--------------------------------------------------------------------------
|
| Fire up the mrcore foundation to allow asset handling
| and other foundation support bootstraping.
|
*/

#$basePath = realpath(__DIR__.'/../');
#require __DIR__.'/../vendors/mrcore/modules/foundation/Foundation/Bootstrap/Start.php';

//=========================================================================



// Stream all /assets/* files from the defined theme folders
if (substr($_SERVER['REQUEST_URI'], 0, 7) == '/assets') {
	require __DIR__.'/../Support/Assets.php';
	$assets = new Assets($basePath, $_SERVER['REQUEST_URI']);
	exit();
}

// Helpers functions
require __DIR__.'/../Support/Helpers.php';