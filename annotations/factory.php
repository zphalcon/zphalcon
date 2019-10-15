<?php
namespace Phalcon\Annotations;

use Phalcon\Factory as BaseFactory;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Annotations\\Adapter", $config);
	}


}