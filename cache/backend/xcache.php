<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\Exception;
use Phalcon\Cache\FrontendInterface;

class Xcache extends Backend
{
	public function __construct($frontend, $options = null)
	{
		if (typeof($options) <> "array")
		{
			$options = [];

		}

		if (!(isset($options["statsKey"])))
		{
			$options["statsKey"] = "";

		}

		parent::__construct($frontend, $options);

	}

	public function get($keyName, $lifetime = null)
	{

		$frontend = $this->_frontend;

		$prefixedKey = "_PHCX" . $this->_prefix . $keyName;

		$this->_lastKey = $prefixedKey;

		$cachedContent = xcache_get($prefixedKey);

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

		$success = xcache_set($lastKey, $preparedContent, $tt1);

		if (!($success))
		{
			throw new Exception("Failed storing the data in xcache");
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

		if ($success)
		{
			$options = $this->_options;

			if (!(function() { if(isset($this->_options["statsKey"])) {$specialKey = $this->_options["statsKey"]; return $specialKey; } else { return false; } }()))
			{
				throw new Exception("Unexpected inconsistency in options");
			}

			if ($specialKey <> "")
			{
				$keys = xcache_get($specialKey);

				if (typeof($keys) <> "array")
				{
					$keys = [];

				}

				$keys[$lastKey] = $tt1;

				xcache_set($specialKey, $keys);

			}

		}

		return $success;
	}

	public function delete($keyName)
	{

		$prefixedKey = "_PHCX" . $this->_prefix . $keyName;

		if (!(function() { if(isset($this->_options["statsKey"])) {$specialKey = $this->_options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey <> "")
		{
			$keys = xcache_get($specialKey);

			if (typeof($keys) <> "array")
			{
				$keys = [];

			}

			unset($keys[$prefixedKey]);

			xcache_set($specialKey, $keys);

		}

	}

	public function queryKeys($prefix = null)
	{

		if (!($prefix))
		{
			$prefixed = "_PHCX";

		}

		$options = $this->_options;

		if (!(function() { if(isset($this->_options["statsKey"])) {$specialKey = $this->_options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey == "")
		{
			throw new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCX')!");
		}

		$keys = xcache_get($specialKey);

		if (typeof($keys) <> "array")
		{
			return [];
		}

		$retval = [];

		foreach ($keys as $key => $_) {
			if (starts_with($key, $prefixed))
			{
				$realKey = substr($key, 5);

				$retval = $realKey;

			}
		}

		return $retval;
	}

	public function exists($keyName = null, $lifetime = null)
	{

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		if ($lastKey)
		{
			return xcache_isset($lastKey);
		}

		return false;
	}

	public function increment($keyName, $value = 1)
	{

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		if (!($lastKey))
		{
			throw new Exception("Cache must be started first");
		}

		if (function_exists("xcache_inc"))
		{
			$newVal = xcache_inc($lastKey, $value);

		}

		return $newVal;
	}

	public function decrement($keyName, $value = 1)
	{

		if (!($keyName))
		{
			$lastKey = $this->_lastKey;

		}

		if (!($lastKey))
		{
			throw new Exception("Cache must be started first");
		}

		if (function_exists("xcache_dec"))
		{
			$newVal = xcache_dec($lastKey, $value);

		}

		return $newVal;
	}

	public function flush()
	{

		$options = $this->_options;

		if (!(function() { if(isset($this->_options["statsKey"])) {$specialKey = $this->_options["statsKey"]; return $specialKey; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if ($specialKey == "")
		{
			throw new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCM')!");
		}

		$keys = xcache_get($specialKey);

		if (typeof($keys) == "array")
		{
			foreach ($keys as $key => $_) {
				unset($keys[$key]);
				xcache_unset($key);
			}

			xcache_set($specialKey, $keys);

		}

		return true;
	}


}