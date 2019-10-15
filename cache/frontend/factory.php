<?php
namespace Phalcon\Cache\Frontend;

use Phalcon\Cache\FrontendInterface;
use Phalcon\Factory\Exception;
use Phalcon\Factory as BaseFactory;
use Phalcon\Config;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Cache\\Frontend", $config);
	}

	protected static function loadClass($namespace, $config)
	{

		if (typeof($config) == "object" && $config instanceof $Config)
		{
			$config = $config->toArray();

		}

		if (typeof($config) <> "array")
		{
			throw new Exception("Config must be array or Phalcon\\Config object");
		}

		if (function() { if(isset($config["adapter"])) {$adapter = $config["adapter"]; return $adapter; } else { return false; } }())
		{
			unset($config["adapter"]);

			$className = $namespace . "\\" . camelize($adapter);

			if ($className == "Phalcon\\Cache\\Frontend\\None")
			{
				return new $className();
			}

		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}


}