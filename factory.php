<?php
namespace Phalcon;

use Phalcon\Factory\Exception;
use Phalcon\Config;
abstract 
class Factory implements FactoryInterface
{
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

			$className = $namespace . "\\" . $adapter;

			return new $className($config);
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}


}