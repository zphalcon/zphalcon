<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Exception;
use Phalcon\Cache\Backend;

class Apcu extends Backend
{
	public function get($keyName, $lifetime = null)
	{

		$prefixedKey = "_PHCA" . $this->_prefix . $keyName;
		$this->_lastKey = $prefixedKey;

		$cachedContent = apcu_fetch($prefixedKey);

		if ($cachedContent === false)
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

		if ($lifetime === null)
		{
			$lifetime = $this->_lastLifetime;

			if ($lifetime === null)
			{
				$ttl = $frontend->getLifetime();

			}

		}

		$success = apcu_store($lastKey, $preparedContent, $ttl);

		if (!($success))
		{
			throw new Exception("Failed storing data in APCu");
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

	public function increment($keyName = null, $value = 1)
	{

		$prefixedKey = "_PHCA" . $this->_prefix . $keyName;

		$this->_lastKey = $prefixedKey;

		return apcu_inc($prefixedKey, $value);
	}

	public function decrement($keyName = null, $value = 1)
	{

		$lastKey = "_PHCA" . $this->_prefix . $keyName;
		$this->_lastKey = $lastKey;

		return apcu_dec($lastKey, $value);
	}

	public function delete($keyName)
	{
		return apcu_delete("_PHCA" . $this->_prefix . $keyName);
	}

	public function queryKeys($prefix = null)
	{

		if (empty($prefix))
		{
			$prefixPattern = "/^_PHCA/";

		}

		$keys = [];

		if (class_exists("APCUIterator"))
		{
			$apc = new \APCUIterator($prefixPattern);

		}

		if (typeof($apc) <> "object")
		{
			return [];
		}

		foreach (iterator($apc) as $key => $_) {
			$keys = substr($key, 5);
		}

		return $keys;
	}

	public function exists($keyName = null, $lifetime = null)
	{

		if ($keyName === null)
		{
			$lastKey = (string) $this->_lastKey;

		}

		if (empty($lastKey))
		{
			return false;
		}

		return apcu_exists($lastKey);
	}

	public function flush()
	{

		$prefixPattern = "/^_PHCA" . $this->_prefix . "/";

		if (class_exists("APCUIterator"))
		{
			$apc = new \APCUIterator($prefixPattern);

		}

		if (typeof($apc) <> "object")
		{
			return false;
		}

		foreach (iterator($apc) as $item) {
			apcu_delete($item["key"]);
		}

		return true;
	}


}