<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\EntityInterface;

interface ValidatorInterface
{
	public function getMessages()
	{
	}

	public function validate($record)
	{
	}


}