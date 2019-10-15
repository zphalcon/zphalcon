<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\FrontendInterface;
use Phalcon\Cache\Exception;

class Libmemcached extends Backend
{
	protected $_memcache = null;

	public function __construct($frontend, $options = null)
	{

		if (typeof($options) <> "array")
		{
			$options = [];

		}

		if (!(isset($options["servers"])))
		{
			$servers = [0 => ["host" => "127.0.0.1", "port" => 11211, "weight" => 1]];

			$options["servers"] = $servers;

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

		if (!(function() { if(isset($options["persistent_id"])) {$persistentId = $options["persistent_id"]; return $persistentId; } else { return false; } }()))
		{
			$persistentId = "phalcon_cache";

		}

		$memcache = new \Memcached($persistentId);

		if (empty($memcache->getServerList()))
		{
			if (!(function() { if(isset($options["servers"])) {$servers = $options["servers"]; return $servers; } else { return false; } }()))
			{
				throw new Exception("Servers must be an array");
			}

			if (typeof($servers) <> "array")
			{
				throw new Exception("Servers must be an array");
			}

			if (!(function() { if(isset($options["client"])) {$client = $options["client"]; return $client; } else { return false; } }()))
			{
				$client = [];

			}

			if (typeof($client) !== "array")
			{
				throw new Exception("Client options must be instance of array");
			}

			if (!($memcache->setOptions($client)))
			{
				throw new Exception("Cannot set to Memcached options");
			}

			if (!($memcache->addServers($servers)))
			{
				throw new Exception("Cannot connect to Memcached server");
			}

		}

		$this->_memcache = $memcache;

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

		if (!($cachedContent))
		{
			return null;
		}

		if (is_numeric($cachedContent))
		{
			return $cachedContent;
		}

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
				$tt1 = $frontend->getLifetime();

			}

		}

		$success = $memcache->set($lastKey, $preparedContent, $tt1);

		if (!($success))
		{
			throw new Exception("Failed storing data in memcached, error code: " . $memcache->getResultCode());
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
				$keys[$lastKey] = $tt1;

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

			$value = $memcache->get($lastKey);

			if (!($value))
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

		if (!($value))
		{
			$value = 1;

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