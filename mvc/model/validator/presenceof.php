<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class PresenceOf extends Validator
{
	public function validate($record)
	{

		$field = $this->getOption("field");

		if (typeof($field) <> "string")
		{
			throw new Exception("Field name must be a string");
		}

		$value = $record->readAttribute($field);

		if (is_null($value) || is_string($value) && !(strlen($value)))
		{
			$message = $this->getOption("message");

			if (empty($message))
			{
				$message = "':field' is required";

			}

			$this->appendMessage(strtr($message, [":field" => $field]), $field, "PresenceOf");

			return false;
		}

		return true;
	}


}