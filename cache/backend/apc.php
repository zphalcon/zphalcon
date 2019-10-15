<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Exception;
use Phalcon\Cache\Backend;

class Apc extends Backend
{
	public function get($keyName, $lifetime = null)
	{

		$prefixedKey = "_PHCA" . $this->_prefix . $keyName;
		$this->_lastKey = $prefixedKey;

		$cachedContent = apc_fetch($prefixedKey);

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

		$success = apc_store($lastKey, $preparedContent, $ttl);

		if (!($success))
		{
			throw new Exception("Failed storing data in apc");
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

		if (function_exists("apc_inc"))
		{
			$result = apc_inc($prefixedKey, $value);

			return $result;
		}

		return false;
	}

	public function decrement($keyName = null, $value = 1)
	{

		$lastKey = "_PHCA" . $this->_prefix . $keyName;
		$this->_lastKey = $lastKey;

		if (function_exists("apc_dec"))
		{
			return apc_dec($lastKey, $value);
		}

		return false;
	}

	public function delete($keyName)
	{
		return apc_delete("_PHCA" . $this->_prefix . $keyName);
	}

	public function queryKeys($prefix = null)
	{

		if (empty($prefix))
		{
			$prefixPattern = "/^_PHCA/";

		}

		$keys = [];
		$apc = new \APCIterator("user", $prefixPattern);

		foreach (iterator($apc) as $key => $_) {
			$keys = substr($key, 5);
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
			if (apc_exists($lastKey) !== false)
			{
				return true;
			}

		}

		return false;
	}

	public function flush()
	{

		$prefixPattern = "/^_PHCA" . $this->_prefix . "/";

		foreach (iterator(new \APCIterator("user", $prefixPattern)) as $item) {
			apc_delete($item["key"]);
		}

		return true;
	}


}