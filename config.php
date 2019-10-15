<?php
namespace Phalcon;

use Phalcon\Config\Exception;

class Config implements \ArrayAccess, \Countable
{
	const DEFAULT_PATH_DELIMITER = ".";

	protected static $_pathDelimiter;

	public function __construct($arrayConfig = null)
	{

		foreach ($arrayConfig as $key => $value) {
			$this->offsetSet($key, $value);
		}

	}

	public function offsetExists($index)
	{
		$index = strval($index);

		return isset($this->$index);
	}

	public function path($path, $defaultValue = null, $delimiter = null)
	{

		if (isset($this->$path))
		{
			return $this->$path;
		}

		if (empty($delimiter))
		{
			$delimiter = self::getPathDelimiter();

		}

		$config = $this;
		$keys = explode($delimiter, $path);

		while (!(empty($keys))) {
			$key = array_shift($keys);
			if (!(isset($config->$key)))
			{
				break;

			}
			if (empty($keys))
			{
				return $config->$key;
			}
			$config = $config->$key;
			if (empty($config))
			{
				break;

			}
		}

		return $defaultValue;
	}

	public function get($index, $defaultValue = null)
	{
		$index = strval($index);

		if (isset($this->$index))
		{
			return $this->$index;
		}

		return $defaultValue;
	}

	public function offsetGet($index)
	{
		$index = strval($index);

		return $this->$index;
	}

	public function offsetSet($index, $value)
	{
		$index = strval($index);

		if (typeof($value) === "array")
		{
			$this->{$index} = new self($value);

		}

	}

	public function offsetUnset($index)
	{
		$index = strval($index);

		$this->{$index} = null;

	}

	public function merge($config)
	{
		return $this->_merge($config);
	}

	public function toArray()
	{

		$arrayConfig = [];

		foreach (get_object_vars($this) as $key => $value) {
			if (typeof($value) === "object")
			{
				if (method_exists($value, "toArray"))
				{
					$arrayConfig[$key] = $value->toArray();

				}

			}
		}

		return $arrayConfig;
	}

	public function count()
	{
		return count(get_object_vars($this));
	}

	public static function __set_state($data)
	{
		return new self($data);
	}

	public static function setPathDelimiter($delimiter = null)
	{
		self::_pathDelimiter = $delimiter;

	}

	public static function getPathDelimiter()
	{

		$delimiter = self::_pathDelimiter;

		if (!($delimiter))
		{
			$delimiter = self::DEFAULT_PATH_DELIMITER;

		}

		return $delimiter;
	}

	protected final function _merge($config, $instance = null)
	{

		if (typeof($instance) !== "object")
		{
			$instance = $this;

		}

		$number = $instance->count();

		foreach (get_object_vars($config) as $key => $value) {
			$property = strval($key);
			if (function() { if(isset($instance->$property)) {$localObject = $instance->$property; return $localObject; } else { return false; } }())
			{
				if (typeof($localObject) === "object" && typeof($value) === "object")
				{
					if ($localObject instanceof $Config && $value instanceof $Config)
					{
						$this->_merge($value, $localObject);

						continue;

					}

				}

			}
			if (is_numeric($key))
			{
				$key = strval($key);

				while ($instance->offsetExists($key)) {
					$key = strval($number);
					$number++;
				}

			}
			$instance->{$key} = $value;
		}

		return $instance;
	}


}