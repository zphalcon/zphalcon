<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\Exception;
use Phalcon\Cache\FrontendInterface;

class Redis extends Backend
{
	protected $_redis = null;

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
			$options["port"] = 6379;

		}

		if (!(isset($options["index"])))
		{
			$options["index"] = 0;

		}

		if (!(isset($options["persistent"])))
		{
			$options["persistent"] = false;

		}

		if (!(isset($options["statsKey"])))
		{
			$options["statsKey"] = "";

		}

		if (!(isset($options["auth"])))
		{
			$options["auth"] = "";

		}

		if (!(isset($options["timeout"])))
		{
			$options["timeout"] = 0;

		}

		parent::__construct($frontend, $options);

	}

	public function _connect()
	{

		$options = $this->_options;

		$redis = new \Redis();

		if (!(function() { if(isset($options["host"])) {$host = $options["host"]; return $host; } else { return false; } }()) || !(function() { if(isset($options["port"])) {$port = $options["port"]; return $port; } else { return false; } }()) || !(function() { if(isset($options["persistent"])) {$persistent = $options["persistent"]; return $persistent; } else { return false; } }()) || !(function() { if(isset($options["timeout"])) {$timeout = $options["timeout"]; return $timeout; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($persistent)
		{
			$success = $redis->pconnect($host, $port, $timeout);

		}

		if (!($success))
		{
			throw new Exception("Could not connect to the Redisd server " . $host . ":" . $port);
		}

		if (function() { if(isset($options["auth"])) {$auth = $options["auth"]; return $auth; } else { return false; } }() && !(empty($options["auth"])))
		{
			$success = $redis->auth($auth);

			if (!($success))
			{
				throw new Exception("Failed to authenticate with the Redisd server");
			}

		}

		if (function() { if(isset($options["index"])) {$index = $options["index"]; return $index; } else { return false; } }() && $index > 0)
		{
			$success = $redis->select($index);

			if (!($success))
			{
				throw new Exception("Redis server selected database failed");
			}

		}

		$this->_redis = $redis;

	}

	public function get($keyName, $lifetime = null)
	{

		$redis = $this->_redis;

		if (typeof($redis) <> "object")
		{
			$this->_connect();

			$redis = $this->_redis;

		}

		$frontend = $this->_frontend;

		$prefix = $this->_prefix;

		$lastKey = "_PHCR" . $prefix . $keyName;

		$this->_lastKey = $lastKey;

		$cachedContent = $redis->get($lastKey);

		if ($cachedContent === false)
		{
			return null;
		}

		if (is_numeric($cachedContent))
		{
			return $cachedContent;
		}

		return $frontend->afterRetrieve($cachedContent);
	}

	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
	{

		if ($keyName === null)
		{
			$lastKey = $this->_lastKey;

			$prefixedKey = substr($lastKey, 5);

		}

		if (!($lastKey))
		{
			throw new Exception("The cache must be started first");
		}

		$frontend = $this->_frontend;

		$redis = $this->_redis;

		if (typeof($redis) <> "object")
		{
			$this->_connect();

			$redis = $this->_redis;

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

		$success = $redis->set($lastKey, $preparedContent);

		if (!($success))
		{
			throw new Exception("Failed storing the data in redis");
		}

		if ($tt1 >= 1)
		{
			$redis->settimeout($lastKey, $tt1);

		}

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey <> "")
		{
			$redis->sAdd($specialKey, $prefixedKey);

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

		$redis = $this->_redis;

		if (typeof($redis) <> "object")
		{
			$this->_connect();

			$redis = $this->_redis;

		}

		$prefix = $this->_prefix;

		$prefixedKey = $prefix . $keyName;

		$lastKey = "_PHCR" . $prefixedKey;

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey <> "")
		{
			$redis->sRem($specialKey, $prefixedKey);

		}

		return (bool) $redis->delete($lastKey);
	}

	public function queryKeys($prefix = null)
	{

		$redis = $this->_redis;

		if (typeof($redis) <> "object")
		{
			$this->_connect();

			$redis = $this->_redis;

		}

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey == "")
		{
			throw new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCR')!");
		}

		$keys = $redis->sMembers($specialKey);

		if (typeof($keys) <> "array")
		{
			return [];
		}

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
			$redis = $this->_redis;

			if (typeof($redis) <> "object")
			{
				$this->_connect();

				$redis = $this->_redis;

			}

			return (bool) $redis->exists($lastKey);
		}

		return false;
	}

	public function increment($keyName = null, $value = 1)
	{

		$redis = $this->_redis;

		if (typeof($redis) <> "object")
		{
			$this->_connect();

			$redis = $this->_redis;

		}

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		return $redis->incrBy($lastKey, $value);
	}

	public function decrement($keyName = null, $value = 1)
	{

		$redis = $this->_redis;

		if (typeof($redis) <> "object")
		{
			$this->_connect();

			$redis = $this->_redis;

		}

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		return $redis->decrBy($lastKey, $value);
	}

	public function flush()
	{

		$options = $this->_options;

		if (!(function() { if(isset($options["statsKey"])) {$specialKey = $options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		$redis = $this->_redis;

		if (typeof($redis) <> "object")
		{
			$this->_connect();

			$redis = $this->_redis;

		}

		if ($specialKey == "")
		{
			throw new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCR')!");
		}

		$keys = $redis->sMembers($specialKey);

		if (typeof($keys) == "array")
		{
			foreach ($keys as $key) {
				$lastKey = "_PHCR" . $key;
				$redis->sRem($specialKey, $key);
				$redis->delete($lastKey);
			}

		}

		return true;
	}


}