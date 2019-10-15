<?php
namespace Phalcon\Acl;

use Phalcon\Acl\Exception;

class Resource implements ResourceInterface
{
	protected $_name;
	protected $_description;

	public function __construct($name, $description = null)
	{
		if ($name == "*")
		{
			throw new Exception("Resource name cannot be '*'");
		}

		$this->_name = $name;

		if ($description)
		{
			$this->_description = $description;

		}

	}


}