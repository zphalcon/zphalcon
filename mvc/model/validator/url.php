<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class Url extends Validator
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

		if (!(filter_var($value, FILTER_VALIDATE_URL)))
		{
			$message = $this->getOption("message");

			if (empty($message))
			{
				$message = ":field does not have a valid url format";

			}

			$this->appendMessage(strtr($message, [":field" => $field]), $field, "Url");

			return false;
		}

		return true;
	}


}