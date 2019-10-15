<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\Exception;
use Phalcon\Cache\FrontendInterface;

class Mongo extends Backend
{
	protected $_collection = null;

	public function __construct($frontend, $options = null)
	{
		if (!(isset($options["mongo"])))
		{
			if (!(isset($options["server"])))
			{
				throw new Exception("The parameter 'server' is required");
			}

		}

		if (!(isset($options["db"])))
		{
			throw new Exception("The parameter 'db' is required");
		}

		if (!(isset($options["collection"])))
		{
			throw new Exception("The parameter 'collection' is required");
		}

		parent::__construct($frontend, $options);

	}

	protected final function _getCollection()
	{

		$mongoCollection = $this->_collection;

		if (typeof($mongoCollection) <> "object")
		{
			$options = $this->_options;

			if (function() { if(isset($options["mongo"])) {$mongo = $options["mongo"]; return $mongo; } else { return false; } }())
			{
				if (typeof($mongo) <> "object")
				{
					throw new Exception("The 'mongo' parameter must be a valid Mongo instance");
				}

			}

			$database = $options["db"];

			if (!($database) || typeof($database) <> "string")
			{
				throw new Exception("The backend requires a valid MongoDB db");
			}

			$collection = $options["collection"];

			if (!($collection) || typeof($collection) <> "string")
			{
				throw new Exception("The backend requires a valid MongoDB collection");
			}

			$mongoCollection = $mongo->selectDb($database)->selectCollection($collection);
			$this->_collection = $mongoCollection;

		}

		return $mongoCollection;
	}

	public function get($keyName, $lifetime = null)
	{

		$conditions = [];

		$frontend = $this->_frontend;

		$prefixedKey = $this->_prefix . $keyName;

		$this->_lastKey = $prefixedKey;

		$conditions["key"] = $prefixedKey;

		$conditions["time"] = ["$gt" => time()];

		$document = $this->_getCollection()->findOne($conditions);

		if (typeof($document) == "array")
		{
			if (function() { if(isset($document["data"])) {$cachedContent = $document["data"]; return $cachedContent; } else { return false; } }())
			{
				if (is_numeric($cachedContent))
				{
					return $cachedContent;
				}

				return $frontend->afterRetrieve($cachedContent);
			}

		}

		return null;
	}

	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
	{

		$conditions = [];

		$data = [];

		if ($keyName === null)
		{
			$lastkey = $this->_lastKey;

		}

		if (!($lastkey))
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
				$ttl = $frontend->getLifetime();

			}

		}

		$collection = $this->_getCollection();
		$timestamp = time() + intval($ttl);
		$conditions["key"] = $lastkey;
		$document = $collection->findOne($conditions);

		if (typeof($document) == "array")
		{
			$document["time"] = $timestamp;
			$document["data"] = $preparedContent;
			$success = $collection->update(["_id" => $document["_id"]], $document);

		}

		if (!($success))
		{
			throw new Exception("Failed storing data in mongodb");
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
		$this->_getCollection()->remove(["key" => $this->_prefix . $keyName]);

		if ((int) rand() % 100 == 0)
		{
			$this->gc();

		}

		return true;
	}

	public function queryKeys($prefix = null)
	{


		if (!(empty($prefix)))
		{
			$conditions["key"] = new \MongoRegex("/^" . $prefix . "/");

		}

		$conditions["time"] = ["$gt" => time()];

		$collection = $this->_getCollection();
		$items = $collection->find($conditions, ["key" => 1]);

		foreach (iterator($items) as $item) {
			foreach ($item as $key => $value) {
				if ($key == "key")
				{
					$keys = $value;

				}
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
			return $this->_getCollection()->count(["key" => $lastKey, "time" => ["$gt" => time()]]) > 0;
		}

		return false;
	}

	public function gc()
	{
		return $this->_getCollection()->remove(["time" => ["$lt" => time()]]);
	}

	public function increment($keyName, $value = 1)
	{

		$prefixedKey = $this->_prefix . $keyName;
		$this->_lastKey = $prefixedKey;

		$document = $this->_getCollection()->findOne(["key" => $prefixedKey]);

		if (!(function() { if(isset($document["time"])) {$modifiedTime = $document["time"]; return $modifiedTime; } else { return false; } }()))
		{
			throw new Exception("The cache is corrupted");
		}

		if (time() < $modifiedTime)
		{
			if (!(function() { if(isset($document["data"])) {$cachedContent = $document["data"]; return $cachedContent; } else { return false; } }()))
			{
				throw new Exception("The cache is corrupted");
			}

			if (is_numeric($cachedContent))
			{
				$incremented = $cachedContent + $value;

				$this->save($prefixedKey, $incremented);

				return $incremented;
			}

		}

		return null;
	}

	public function decrement($keyName, $value = 1)
	{

		$prefixedKey = $this->_prefix . $keyName;
		$this->_lastKey = $prefixedKey;

		$document = $this->_getCollection()->findOne(["key" => $prefixedKey]);

		if (!(function() { if(isset($document["time"])) {$modifiedTime = $document["time"]; return $modifiedTime; } else { return false; } }()))
		{
			throw new Exception("The cache is corrupted");
		}

		if (time() < $modifiedTime)
		{
			if (!(function() { if(isset($document["data"])) {$cachedContent = $document["data"]; return $cachedContent; } else { return false; } }()))
			{
				throw new Exception("The cache is corrupted");
			}

			if (is_numeric($cachedContent))
			{
				$decremented = $cachedContent - $value;

				$this->save($prefixedKey, $decremented);

				return $decremented;
			}

		}

		return null;
	}

	public function flush()
	{
		$this->_getCollection()->remove();

		return true;
	}


}