<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class Regex extends Validator
{
	public function validate($record)
	{

		$field = $this->getOption("field");

		if (typeof($field) <> "string")
		{
			throw new Exception("Field name must be a string");
		}

		if (!($this->isSetOption("pattern")))
		{
			throw new Exception("Validator requires a perl-compatible regex pattern");
		}

		$value = $record->readAttribute($field);

		if ($this->isSetOption("allowEmpty") && empty($value))
		{
			return true;
		}

		$pattern = $this->getOption("pattern");

		$failed = false;

		$matches = null;

		if (preg_match($pattern, $value, $matches))
		{
			$failed = $matches[0] <> $value;

		}

		if ($failed === true)
		{
			$message = $this->getOption("message");

			if (empty($message))
			{
				$message = "Value of field ':field' doesn't match regular expression";

			}

			$this->appendMessage(strtr($message, [":field" => $field]), $field, "Regex");

			return false;
		}

		return true;
	}


}