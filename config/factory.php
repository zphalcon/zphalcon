<?php
namespace Phalcon\Config;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Config;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Config\\Adapter", $config);
	}

	protected static function loadClass($namespace, $config)
	{

		if (typeof($config) == "string")
		{
			$oldConfig = $config;

			$extension = substr(strrchr($config, "."), 1);

			if (empty($extension))
			{
				throw new Exception("You need to provide extension in file path");
			}

			$config = ["adapter" => $extension, "filePath" => $oldConfig];

		}

		if (typeof($config) == "object" && $config instanceof $Config)
		{
			$config = $config->toArray();

		}

		if (typeof($config) <> "array")
		{
			throw new Exception("Config must be array or Phalcon\\Config object");
		}

		if (!(function() { if(isset($config["filePath"])) {$filePath = $config["filePath"]; return $filePath; } else { return false; } }()))
		{
			throw new Exception("You must provide 'filePath' option in factory config parameter.");
		}

		if (function() { if(isset($config["adapter"])) {$adapter = $config["adapter"]; return $adapter; } else { return false; } }())
		{
			$className = $namespace . "\\" . camelize($adapter);

			if (!(strpos($filePath, ".")))
			{
				$filePath = $filePath . "." . lcfirst($adapter);

			}

			if ($className == "Phalcon\\Config\\Adapter\\Ini")
			{
				if (function() { if(isset($config["mode"])) {$mode = $config["mode"]; return $mode; } else { return false; } }())
				{
					return new $className($filePath, $mode);
				}

			}

			return new $className($filePath);
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}


}