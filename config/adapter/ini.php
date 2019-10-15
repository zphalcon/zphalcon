<?php
namespace Phalcon\Config\Adapter;

use Phalcon\Config;
use Phalcon\Config\Exception;

class Ini extends Config
{
	public function __construct($filePath, $mode = null)
	{

		if (null === $mode)
		{
			$mode = INI_SCANNER_RAW;

		}

		$iniConfig = parse_ini_file($filePath, true, $mode);

		if ($iniConfig === false)
		{
			throw new Exception("Configuration file " . basename($filePath) . " can't be loaded");
		}


		$config = [];

		foreach ($iniConfig as $section => $directives) {
			if (typeof($directives) == "array")
			{
				$sections = [];

				foreach ($directives as $path => $lastValue) {
					$sections = $this->_parseIniString((string) $path, $lastValue);
				}

				if (count($sections))
				{
					$config[$section] = call_user_func_array("array_replace_recursive", $sections);

				}

			}
		}

		parent::__construct($config);

	}

	protected function _parseIniString($path, $value)
	{

		$value = $this->_cast($value);

		$pos = strpos($path, ".");

		if ($pos === false)
		{
			return [$path => $value];
		}

		$key = substr($path, 0, $pos);

		$path = substr($path, $pos + 1);

		return [$key => $this->_parseIniString($path, $value)];
	}

	protected function _cast($ini)
	{

		if (typeof($ini) == "array")
		{
			foreach ($ini as $key => $val) {
				$ini[$key] = $this->_cast($val);
			}

		}

		if (typeof($ini) == "string")
		{
			if ($ini === "true" || $ini === "yes" || strtolower($ini) === "on")
			{
				return true;
			}

			if ($ini === "false" || $ini === "no" || strtolower($ini) === "off")
			{
				return false;
			}

			if ($ini === "null")
			{
				return null;
			}

			if (is_numeric($ini))
			{
				if (preg_match("/[.]+/", $ini))
				{
					return (double) $ini;
				}

			}

		}

		return $ini;
	}


}