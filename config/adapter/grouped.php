<?php
namespace Phalcon\Config\Adapter;

use Phalcon\Config;
use Phalcon\Factory\Exception;
use Phalcon\Config\Factory;

class Grouped extends Config
{
	public function __construct($arrayConfig, $defaultAdapter = "php")
	{

		parent::__construct([]);

		foreach ($arrayConfig as $configName) {
			$configInstance = $configName;
			if (typeof($configName) === "string")
			{
				$configInstance = ["filePath" => $configName, "adapter" => $defaultAdapter];

			}
			if ($configInstance["adapter"] === "array")
			{
				if (!(isset($configInstance["config"])))
				{
					throw new Exception("To use 'array' adapter you have to specify the 'config' as an array.");
				}

			}
			$this->_merge($configInstance);
		}

	}


}