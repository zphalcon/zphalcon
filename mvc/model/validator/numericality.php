<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class Numericality extends Validator
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

		if (!(is_numeric($value)))
		{
			$message = $this->getOption("message");

			if (empty($message))
			{
				$message = "Value of field :field must be numeric";

			}

			$this->appendMessage(strtr($message, [":field" => $field]), $field, "Numericality");

			return false;
		}

		return true;
	}


}