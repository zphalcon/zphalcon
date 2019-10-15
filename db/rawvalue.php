<?php
namespace Phalcon\Db;


class RawValue
{
	protected $_value;

	public function __construct($value)
	{
		if (typeof($value) == "string" && $value == "")
		{
			$this->_value = "''";

			return ;
		}

		if ($value === null)
		{
			$this->_value = "NULL";

			return ;
		}

		$this->_value = (string) $value;

	}


}