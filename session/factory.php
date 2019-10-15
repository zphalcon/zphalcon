<?php
namespace Phalcon\Session;

use Phalcon\Factory as BaseFactory;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Session\\Adapter", $config);
	}


}