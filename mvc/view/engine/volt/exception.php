<?php
namespace Phalcon\Mvc\View\Engine\Volt;

use Phalcon\Mvc\View\Exception as BaseException;

class Exception extends BaseException
{
	protected $statement;

	public function __construct($message = "", $statement = [], $code = 0, $previous = null)
	{
		$this->statement = $statement;

		parent::__construct($message, $code, $previous);

	}

	public function getStatement()
	{

		$statement = $this->statement;

		if (typeof($statement) !== "array")
		{
			$statement = [];

		}

		return $statement;
	}


}