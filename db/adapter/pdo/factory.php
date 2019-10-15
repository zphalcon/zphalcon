<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Factory as BaseFactory;
use Phalcon\Db\AdapterInterface;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Db\\Adapter\\Pdo", $config);
	}


}