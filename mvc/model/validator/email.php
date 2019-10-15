<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class Email extends Validator
{
	public function validate($record)
	{

		$field = $this->getOption("field");

		if (typeof($field) <> "string")
		{
			throw new Exception("Field name must be a string");
		}

		$value = $record->readAttribute($field);

		if ($this->isSetOption("allowEmpty") && empty($value))
		{
			return true;
		}

		if (!(filter_var($value, FILTER_VALIDATE_EMAIL)))
		{
			$message = $this->getOption("message");

			if (empty($message))
			{
				$message = "Value of field ':field' must have a valid e-mail format";

			}

			$this->appendMessage(strtr($message, [":field" => $field]), $field, "Email");

			return false;
		}

		return true;
	}


}