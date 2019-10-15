<?php
require_once(__DIR__ . '/core.php');

spl_autoload_register(function ($class) {
	if (strpos($class, "Phalcon") === 0 || strpos($class, "\Phalcon") === 0) {
		include (__DIR__ . '/' . uncamelize($class) . '.php');
	}
})
