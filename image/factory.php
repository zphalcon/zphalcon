<?php
namespace Phalcon\Image;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Config;

class Factory extends BaseFactory
{
	public static function load($config)
	{
		return self::loadClass("Phalcon\\Image\\Adapter", $config);
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

		if (!(function() { if(isset($config["file"])) {$file = $config["file"]; return $file; } else { return false; } }()))
		{
			throw new Exception("You must provide 'file' option in factory config parameter.");
		}

		if (function() { if(isset($config["adapter"])) {$adapter = $config["adapter"]; return $adapter; } else { return false; } }())
		{
			$className = $namespace . "\\" . camelize($adapter);

			if (function() { if(isset($config["width"])) {$width = $config["width"]; return $width; } else { return false; } }())
			{
				if (function() { if(isset($config["height"])) {$height = $config["height"]; return $height; } else { return false; } }())
				{
					return new $className($file, $width, $height);
				}

				return new $className($file, $width);
			}

			return new $className($file);
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}


}