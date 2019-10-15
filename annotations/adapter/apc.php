<?php
namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Reflection;

class Apc extends Adapter
{
	protected $_prefix = "";
	protected $_ttl = 172800;

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
		return apc_fetch(strtolower("_PHAN" . $this->_prefix . $key));
	}

	public function write($key, $data)
	{
		return apc_store(strtolower("_PHAN" . $this->_prefix . $key), $data, $this->_ttl);
	}


}