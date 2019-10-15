<?php
namespace Phalcon\Paginator;

use Phalcon\Factory as BaseFactory;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Paginator\\Adapter", $config);
	}


}