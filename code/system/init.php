<?php

/**
* WizyTówka 5
* Basic configuration and WT() function.
*/
namespace WizyTowka;


const VERSION        = '5.00';
const VERSION_DATE   = '2018-08-05';
const VERSION_STABLE = false;
const VERSION_NAME   = 'WizyTówka ' . VERSION . ' ALFA';

if (PHP_VERSION_ID < 70100) {
	include __DIR__ . '/defaults/incompatible.php';
}

require SYSTEM_DIR . '/classes/_Private/System.php';

function WT($controllerName = null)
{
	static $system;

	if (!$system) {
		$system = new _Private\System;
	}

	if ($controllerName) {
		return $system($controllerName);
	}

	return $system;
}

WT();