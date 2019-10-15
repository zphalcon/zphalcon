<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\Exception;

class Memory extends Backend implements \Serializable
{
	protected $_data;

	public function get($keyName, $lifetime = null)
	{

		if ($keyName === null)
		{
			$lastKey = $this->_lastKey;

		}

		if (!(function() { if(isset($this->_data[$lastKey])) {$cachedContent = $this->_data[$lastKey]; return $cachedContent; } else { return false; } }()))
		{
			return null;
		}

		if ($cachedContent === null)
		{
			return null;
		}

		return $this->_frontend->afterRetrieve($cachedContent);
	}

	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
	{

		if ($keyName === null)
		{
			$lastKey = $this->_lastKey;

		}

		if (!($lastKey))
		{
			throw new Exception("Cache must be started first");
		}

		$frontend = $this->_frontend;

		if ($content === null)
		{
			$cachedContent = $frontend->getContent();

		}

		if (!(is_numeric($cachedContent)))
		{
			$preparedContent = $frontend->beforeStore($cachedContent);

		}

		$this[$lastKey] = $preparedContent;
		$isBuffering = $frontend->isBuffering();

		if ($stopBuffer === true)
		{
			$frontend->stop();

		}

		if ($isBuffering === true)
		{
			echo($cachedContent);

		}

		$this->_started = false;

		return true;
	}

	public function delete($keyName)
	{

		$key = $this->_prefix . $keyName;
		$data = $this->_data;

		if (isset($data[$key]))
		{
			unset($data[$key]);

			$this->_data = $data;

			return true;
		}

		return false;
	}

	public function queryKeys($prefix = null)
	{

		$data = $this->_data;

		if (typeof($data) <> "array")
		{
			return [];
		}

		$keys = array_keys($data);

		foreach ($keys as $idx => $key) {
			if (!(empty($prefix)) && !(starts_with($key, $prefix)))
			{
				unset($keys[$idx]);

			}
		}

		return $keys;
	}

	public function exists($keyName = null, $lifetime = null)
	{

		if ($keyName === null)
		{
			$lastKey = $this->_lastKey;

		}

		if ($lastKey)
		{
			if (isset($this->_data[$lastKey]))
			{
				return true;
			}

		}

		return false;
	}

	public function increment($keyName = null, $value = 1)
	{

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		if (!(function() { if(isset($this->_data[$lastKey])) {$cachedContent = $this->_data[$lastKey]; return $cachedContent; } else { return false; } }()))
		{
			return null;
		}

		if (!($cachedContent))
		{
			return null;
		}

		$result = $cachedContent + $value;

		$this[$lastKey] = $result;

		return $result;
	}

	public function decrement($keyName = null, $value = 1)
	{

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		if (!(function() { if(isset($this->_data[$lastKey])) {$cachedContent = $this->_data[$lastKey]; return $cachedContent; } else { return false; } }()))
		{
			return null;
		}

		if (!($cachedContent))
		{
			return null;
		}

		$result = $cachedContent - $value;

		$this[$lastKey] = $result;

		return $result;
	}

	public function flush()
	{
		$this->_data = null;

		return true;
	}

	public function serialize()
	{
		return serialize(["frontend" => $this->_frontend]);
	}

	public function unserialize($data)
	{

		$unserialized = unserialize($data);

		if (typeof($unserialized) <> "array")
		{
			throw new \Exception("Unserialized data must be an array");
		}

		$this->_frontend = $unserialized["frontend"];

	}


}