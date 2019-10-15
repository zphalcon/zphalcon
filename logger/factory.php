<?php
namespace Phalcon\Logger;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Config;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Logger\\Adapter", $config);
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
			$className = $namespace . "\\" . camelize($adapter);

			if ($className <> "Phalcon\\Logger\\Adapter\\Firephp")
			{
				unset($config["adapter"]);

				if (!(function() { if(isset($config["name"])) {$name = $config["name"]; return $name; } else { return false; } }()))
				{
					throw new Exception("You must provide 'name' option in factory config parameter.");
				}

				unset($config["name"]);

				return new $className($name, $config);
			}

			return new $className();
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}


}