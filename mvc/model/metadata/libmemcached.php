<?php
namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Cache\Backend\Libmemcached;
use Phalcon\Cache\Frontend\Data as FrontendData;
use Phalcon\Mvc\Model\Exception;

class Libmemcached extends MetaData
{
	protected $_ttl = 172800;
	protected $_memcache = null;
	protected $_metaData = [];

	public function __construct($options = null)
	{

		if (typeof($options) <> "array")
		{
			$options = [];

		}

		if (!(isset($options["servers"])))
		{
			throw new Exception("No servers given in options");
		}

		if (function() { if(isset($options["lifetime"])) {$ttl = $options["lifetime"]; return $ttl; } else { return false; } }())
		{
			$this->_ttl = $ttl;

		}

		if (!(isset($options["statsKey"])))
		{
			$options["statsKey"] = "_PHCM_MM";

		}

		$this->_memcache = new Libmemcached(new FrontendData(["lifetime" => $this->_ttl]), $options);

	}

	public function read($key)
	{

		$data = $this->_memcache->get($key);

		if (typeof($data) == "array")
		{
			return $data;
		}

		return null;
	}

	public function write($key, $data)
	{
		$this->_memcache->save($key, $data);

	}

	public function reset()
	{

		$meta = $this->_metaData;

		if (typeof($meta) == "array")
		{
			foreach ($meta as $key => $_) {
				$realKey = "meta-" . $key;
				$this->_memcache->delete($realKey);
			}

		}

		parent::reset();

	}


}