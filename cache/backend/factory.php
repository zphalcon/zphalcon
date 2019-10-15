<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\Frontend\Factory as FrontendFactory;
use Phalcon\Config;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Cache\\Backend", $config);
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

		if (!(function() { if(isset($config["frontend"])) {$frontend = $config["frontend"]; return $frontend; } else { return false; } }()))
		{
			throw new Exception("You must provide 'frontend' option in factory config parameter.");
		}

		if (function() { if(isset($config["adapter"])) {$adapter = $config["adapter"]; return $adapter; } else { return false; } }())
		{
			unset($config["adapter"]);

			unset($config["frontend"]);

			if (typeof($frontend) == "array" || $frontend instanceof $Config)
			{
				$frontend = FrontendFactory::load($frontend);

			}

			$className = $namespace . "\\" . camelize($adapter);

			return new $className($frontend, $config);
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}


}