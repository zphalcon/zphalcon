<?php
namespace Phalcon\Translate;

use Phalcon\Factory as BaseFactory;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Translate\\Adapter", $config);
	}


}