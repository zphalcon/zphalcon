<?php
namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\Exception;

class Memory extends MetaData
{
	protected $_metaData = [];

	public function __construct($options = null)
	{
	}

	public function read($key)
	{
		return null;
	}

	public function write($key, $data)
	{
		return ;
	}


}