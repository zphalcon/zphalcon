<?php
namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\Exception;

class Session extends MetaData
{
	protected $_prefix = "";

	public function __construct($options = null)
	{

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["prefix"])) {$prefix = $options["prefix"]; return $prefix; } else { return false; } }())
			{
				$this->_prefix = $prefix;

			}

		}

	}

	public function read($key)
	{

		if (function() { if(isset($_SESSION["$PMM$" . $this->_prefix][$key])) {$metaData = $_SESSION["$PMM$" . $this->_prefix][$key]; return $metaData; } else { return false; } }())
		{
			return $metaData;
		}

		return null;
	}

	public function write($key, $data)
	{
		$_SESSION["$PMM$" . $this->_prefix] = $data;

	}


}