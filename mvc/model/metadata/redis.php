<?php
namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Cache\Backend\Redis;
use Phalcon\Cache\Frontend\Data as FrontendData;

class Redis extends MetaData
{
	protected $_ttl = 172800;
	protected $_redis = null;
	protected $_metaData = [];

	public function __construct($options = null)
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

		if (!(isset($options["persistent"])))
		{
			$options["persistent"] = 0;

		}

		if (!(isset($options["statsKey"])))
		{
			$options["statsKey"] = "_PHCM_MM";

		}

		if (function() { if(isset($options["lifetime"])) {$ttl = $options["lifetime"]; return $ttl; } else { return false; } }())
		{
			$this->_ttl = $ttl;

		}

		$this->_redis = new Redis(new FrontendData(["lifetime" => $this->_ttl]), $options);

	}

	public function read($key)
	{

		$data = $this->_redis->get($key);

		if (typeof($data) == "array")
		{
			return $data;
		}

		return null;
	}

	public function write($key, $data)
	{
		$this->_redis->save($key, $data);

	}

	public function reset()
	{

		$meta = $this->_metaData;

		if (typeof($meta) == "array")
		{
			foreach ($meta as $key => $_) {
				$realKey = "meta-" . $key;
				$this->_redis->delete($realKey);
			}

		}

		parent::reset();

	}


}