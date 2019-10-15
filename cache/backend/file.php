<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Exception;
use Phalcon\Cache\Backend;
use Phalcon\Cache\FrontendInterface;

class File extends Backend
{
	private $_useSafeKey = false;

	public function __construct($frontend, $options)
	{

		if (!(isset($options["cacheDir"])))
		{
			throw new Exception("Cache directory must be specified with the option cacheDir");
		}

		if (function() { if(isset($options["safekey"])) {$safekey = $options["safekey"]; return $safekey; } else { return false; } }())
		{
			if (typeof($safekey) !== "boolean")
			{
				throw new Exception("safekey option should be a boolean.");
			}

			$this->_useSafeKey = $safekey;

		}

		if (function() { if(isset($options["prefix"])) {$prefix = $options["prefix"]; return $prefix; } else { return false; } }())
		{
			if ($this->_useSafeKey && preg_match("/[^a-zA-Z0-9_.-]+/", $prefix))
			{
				throw new Exception("FileCache prefix should only use alphanumeric characters.");
			}

		}

		parent::__construct($frontend, $options);

	}

	public function get($keyName, $lifetime = null)
	{

		$prefixedKey = $this->_prefix . $this->getKey($keyName);

		$this->_lastKey = $prefixedKey;

		if (!(function() { if(isset($this->_options["cacheDir"])) {$cacheDir = $this->_options["cacheDir"]; return $cacheDir; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		$cacheFile = $cacheDir . $prefixedKey;

		if (file_exists($cacheFile) === true)
		{
			$frontend = $this->_frontend;

			if (!($lifetime))
			{
				$lastLifetime = $this->_lastLifetime;

				if (!($lastLifetime))
				{
					$ttl = (int) $frontend->getLifeTime();

				}

			}

			clearstatcache(true, $cacheFile);

			$modifiedTime = (int) filemtime($cacheFile);

			if ($modifiedTime + $ttl > time())
			{
				$cachedContent = file_get_contents($cacheFile);

				if ($cachedContent === false)
				{
					throw new Exception("Cache file " . $cacheFile . " could not be opened");
				}

				if (is_numeric($cachedContent))
				{
					return $cachedContent;
				}

			}

		}

		return null;
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

		if (!(function() { if(isset($this->_options["cacheDir"])) {$cacheDir = $this->_options["cacheDir"]; return $cacheDir; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		$cacheFile = $cacheDir . $lastKey;

		if ($content === null)
		{
			$cachedContent = $frontend->getContent();

		}

		if (!(is_numeric($cachedContent)))
		{
			$preparedContent = $frontend->beforeStore($cachedContent);

		}

		$status = file_put_contents($cacheFile, $preparedContent);

		if ($status === false)
		{
			throw new Exception("Cache file " . $cacheFile . " could not be written");
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

		return $status !== false;
	}

	public function delete($keyName)
	{

		if (!(function() { if(isset($this->_options["cacheDir"])) {$cacheDir = $this->_options["cacheDir"]; return $cacheDir; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		$cacheFile = $cacheDir . $this->_prefix . $this->getKey($keyName);

		if (file_exists($cacheFile))
		{
			return unlink($cacheFile);
		}

		return false;
	}

	public function queryKeys($prefix = null)
	{


		if (!(function() { if(isset($this->_options["cacheDir"])) {$cacheDir = $this->_options["cacheDir"]; return $cacheDir; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		if (!(empty($prefix)))
		{
			$prefixedKey = $this->_prefix . $this->getKey($prefix);

		}

		foreach (iterator(new \DirectoryIterator($cacheDir)) as $item) {
			if ($item->isDir() === false)
			{
				$key = $item->getFileName();

				if (!(empty($prefix)))
				{
					if (starts_with($key, $prefixedKey))
					{
						$keys = $key;

					}

				}

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
			$cacheFile = $this->_options["cacheDir"] . $lastKey;

			if (file_exists($cacheFile))
			{
				if (!($lifetime))
				{
					$ttl = (int) $this->_frontend->getLifeTime();

				}

				clearstatcache(true, $cacheFile);

				$modifiedTime = (int) filemtime($cacheFile);

				if ($modifiedTime + $ttl > time())
				{
					return true;
				}

			}

		}

		return false;
	}

	public function increment($keyName = null, $value = 1)
	{

		$prefixedKey = $this->_prefix . $this->getKey($keyName);
		$this->_lastKey = $prefixedKey;
		$cacheFile = $this->_options["cacheDir"] . $prefixedKey;

		if (file_exists($cacheFile))
		{
			$frontend = $this->_frontend;

			$lifetime = $this->_lastLifetime;

			if (!($lifetime))
			{
				$ttl = $frontend->getLifeTime();

			}

			clearstatcache(true, $cacheFile);

			$modifiedTime = (int) filemtime($cacheFile);

			if ($modifiedTime + $ttl > time())
			{
				$cachedContent = file_get_contents($cacheFile);

				if ($cachedContent === false)
				{
					throw new Exception("Cache file " . $cacheFile . " could not be opened");
				}

				if (is_numeric($cachedContent))
				{
					$result = $cachedContent + $value;

					if (file_put_contents($cacheFile, $result) === false)
					{
						throw new Exception("Cache directory could not be written");
					}

					return $result;
				}

			}

		}

		return null;
	}

	public function decrement($keyName = null, $value = 1)
	{

		$prefixedKey = $this->_prefix . $this->getKey($keyName);
		$this->_lastKey = $prefixedKey;
		$cacheFile = $this->_options["cacheDir"] . $prefixedKey;

		if (file_exists($cacheFile))
		{
			$lifetime = $this->_lastLifetime;

			if (!($lifetime))
			{
				$ttl = $this->_frontend->getLifeTime();

			}

			clearstatcache(true, $cacheFile);

			$modifiedTime = (int) filemtime($cacheFile);

			if ($modifiedTime + $ttl > time())
			{
				$cachedContent = file_get_contents($cacheFile);

				if ($cachedContent === false)
				{
					throw new Exception("Cache file " . $cacheFile . " could not be opened");
				}

				if (is_numeric($cachedContent))
				{
					$result = $cachedContent - $value;

					if (file_put_contents($cacheFile, $result) === false)
					{
						throw new Exception("Cache directory can't be written");
					}

					return $result;
				}

			}

		}

		return null;
	}

	public function flush()
	{

		$prefix = $this->_prefix;

		if (!(function() { if(isset($this->_options["cacheDir"])) {$cacheDir = $this->_options["cacheDir"]; return $cacheDir; } else { return false; } }()))
		{
			throw new Exception("Unexpected inconsistency in options");
		}

		foreach (iterator(new \DirectoryIterator($cacheDir)) as $item) {
			if ($item->isFile() == true)
			{
				$key = $item->getFileName();
				$cacheFile = $item->getPathName();

				if (empty($prefix) || starts_with($key, $prefix))
				{
					if (!(unlink($cacheFile)))
					{
						return false;
					}

				}

			}
		}

		return true;
	}

	public function getKey($key)
	{
		if ($this->_useSafeKey === true)
		{
			return md5($key);
		}

		return $key;
	}

	public function useSafeKey($useSafeKey)
	{
		$this->_useSafeKey = $useSafeKey;

		return $this;
	}


}