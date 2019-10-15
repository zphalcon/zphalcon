<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\Exception;
use Phalcon\Cache\FrontendInterface;

class Memcache extends Backend
{
	protected $_memcache = null;

	public function __construct($frontend, $options = null)
	{
		if (typeof($options) <> "array")
		{
			$options = [];

		}

		if (!(isset($options["host"])))
		{
			$options["host"] = "127.0.0.1";

		}

		if (!(isset($options["port"])))
		{
			$options["port"] = 11211;

		}

		if (!(isset($options["persistent"])))
		{
			$options["persistent"] = false;

		}

		if (!(isset($options["statsKey"])))
		{
			$options["statsKey"] = "";

		}

		parent::__construct($frontend, $options);

	}

	public function _connect()
	{

		$options = $this->_options;

		$memcache = new \Memcache();

		if (!(function() { if(isset($options["host"])) {$host = $options["host"]; return $host; } else { return false; } }()) || !(function() { if(isset($options["port"])) {$port = $options["port"]; return $port; } else { return false; } }()) || !(function() { if(isset($options["persistent"])) {$persistent = $options["persistent"]; return $persistent; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($persistent)
		{
			$success = $memcache->pconnect($host, $port);

		}

		if (!($success))
		{
			throw new Exception("Cannot connect to Memcached server");
		}

		$this->_memcache = $memcache;

	}

	public function addServers($host, $port, $persistent = false)
	{

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		$success = $memcache->addServer($host, $port, $persistent);

		$this->_memcache = $memcache;

		return $success;
	}

	public function get($keyName, $lifetime = null)
	{

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		$prefixedKey = $this->_prefix . $keyName;

		$this->_lastKey = $prefixedKey;

		$cachedContent = $memcache->get($prefixedKey);

		if ($cachedContent === false)
		{
			return null;
		}

		if (is_numeric($cachedContent))
		{
			return $cachedContent;
		}

		$retrieve = $this->_frontend->afterRetrieve($cachedContent);

		return $retrieve;
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

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		if ($content === null)
		{
			$cachedContent = $frontend->getContent();

		}

		if (!(is_numeric($cachedContent)))
		{
			$preparedContent = $frontend->beforeStore($cachedContent);

		}

		if ($lifetime === null)
		{
			$tmp = $this->_lastLifetime;

			if (!($tmp))
			{
				$ttl = $frontend->getLifetime();

			}

		}

		$success = $memcache->set($lastKey, $preparedContent, 0, $ttl);

		if (!($success))
		{
			throw new Exception("Failed storing data in memcached");
		}

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey <> "")
		{
			$keys = $memcache->get($specialKey);

			if (typeof($keys) <> "array")
			{
				$keys = [];

			}

			if (!(isset($keys[$lastKey])))
			{
				$keys[$lastKey] = $ttl;

				$memcache->set($specialKey, $keys);

			}

		}

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

		return $success;
	}

	public function delete($keyName)
	{

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		$prefixedKey = $this->_prefix . $keyName;

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey <> "")
		{
			$keys = $memcache->get($specialKey);

			if (typeof($keys) == "array")
			{
				unset($keys[$prefixedKey]);

				$memcache->set($specialKey, $keys);

			}

		}

		$ret = $memcache->delete($prefixedKey);

		return $ret;
	}

	public function queryKeys($prefix = null)
	{

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey == "")
		{
			throw new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCM')!");
		}

		$keys = $memcache->get($specialKey);

		if (typeof($keys) <> "array")
		{
			return [];
		}

		$keys = array_keys($keys);

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

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		if ($lastKey)
		{
			$memcache = $this->_memcache;

			if (typeof($memcache) <> "object")
			{
				$this->_connect();

				$memcache = $this->_memcache;

			}

			if (!($memcache->get($lastKey)))
			{
				return false;
			}

			return true;
		}

		return false;
	}

	public function increment($keyName = null, $value = 1)
	{

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		return $memcache->increment($lastKey, $value);
	}

	public function decrement($keyName = null, $value = 1)
	{

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		return $memcache->decrement($lastKey, $value);
	}

	public function flush()
	{

		$memcache = $this->_memcache;

		if (typeof($memcache) <> "object")
		{
			$this->_connect();

			$memcache = $this->_memcache;

		}

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey == "")
		{
			throw new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCM')!");
		}

		$keys = $memcache->get($specialKey);

		if (typeof($keys) <> "array")
		{
			return true;
		}

		foreach ($keys as $key => $_) {
			$memcache->delete($key);
		}

		$memcache->delete($specialKey);

		return true;
	}


}