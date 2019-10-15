<?php
namespace Phalcon\Validation;

use Phalcon\Validation\Message;

interface MessageInterface
{
	public function setType($type)
	{
	}

	public function getType()
	{
	}

	public function setMessage($message)
	{
	}

	public function getMessage()
	{
	}

	public function setField($field)
	{
	}

	public function getField()
	{
	}

	public function __toString()
	{
	}

	public static function __set_state($message)
	{
	}


}