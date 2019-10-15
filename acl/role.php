<?php
namespace Phalcon\Acl;

use Phalcon\Acl\Exception;

class Role implements RoleInterface
{
	protected $_name;
	protected $_description;

	public function __construct($name, $description = null)
	{
		if ($name == "*")
		{
			throw new Exception("Role name cannot be '*'");
		}

		$this->_name = $name;

		if ($description)
		{
			$this->_description = $description;

		}

	}


}