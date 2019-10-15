<?php
namespace Phalcon\Config\Adapter;

use Phalcon\Config;
use Phalcon\Config\Exception;

class Yaml extends Config
{
	public function __construct($filePath, $callbacks = null)
	{


		if (!(extension_loaded("yaml")))
		{
			throw new Exception("Yaml extension not loaded");
		}

		if ($callbacks !== null)
		{
			$yamlConfig = yaml_parse_file($filePath, 0, $ndocs, $callbacks);

		}

		if ($yamlConfig === false)
		{
			throw new Exception("Configuration file " . basename($filePath) . " can't be loaded");
		}

		parent::__construct($yamlConfig);

	}


}