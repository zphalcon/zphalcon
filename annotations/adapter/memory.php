<?php
namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Reflection;

class Memory extends Adapter
{
	protected $_data;

	public function read($key)
	{

		if (function() { if(isset($this->_data[strtolower($key)])) {$data = $this->_data[strtolower($key)]; return $data; } else { return false; } }())
		{
			return $data;
		}

		return false;
	}

	public function write($key, $data)
	{

		$lowercasedKey = strtolower($key);

		$this[$lowercasedKey] = $data;

	}


}