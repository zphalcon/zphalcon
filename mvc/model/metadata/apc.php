<?php
namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\Exception;

class Apc extends MetaData
{
	protected $_prefix = "";
	protected $_ttl = 172800;
	protected $_metaData = [];

	public function __construct($options = null)
	{

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["prefix"])) {$prefix = $options["prefix"]; return $prefix; } else { return false; } }())
			{
				$this->_prefix = $prefix;

			}

			if (function() { if(isset($options["lifetime"])) {$ttl = $options["lifetime"]; return $ttl; } else { return false; } }())
			{
				$this->_ttl = $ttl;

			}

		}

	}

	public function read($key)
	{

		$data = apc_fetch("$PMM$" . $this->_prefix . $key);

		if (typeof($data) == "array")
		{
			return $data;
		}

		return null;
	}

	public function write($key, $data)
	{
		apc_store("$PMM$" . $this->_prefix . $key, $data, $this->_ttl);

	}


}