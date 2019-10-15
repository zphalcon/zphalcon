<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class Inclusionin extends Validator
{
	public function validate($record)
	{

		$field = $this->getOption("field");

		if (typeof($field) <> "string")
		{
			throw new Exception("Field name must be a string");
		}

		if ($this->isSetOption("domain") === false)
		{
			throw new Exception("The option 'domain' is required for this validator");
		}

		$domain = $this->getOption("domain");

		if (typeof($domain) <> "array")
		{
			throw new Exception("Option 'domain' must be an array");
		}

		$value = $record->readAttribute($field);

		if ($this->isSetOption("allowEmpty") && empty($value))
		{
			return true;
		}

		$strict = false;

		if ($this->isSetOption("strict"))
		{
			if (typeof($strict) <> "boolean")
			{
				throw new Exception("Option 'strict' must be a boolean");
			}

			$strict = $this->getOption("strict");

		}

		if (!(in_array($value, $domain, $strict)))
		{
			$message = $this->getOption("message");

			if (empty($message))
			{
				$message = "Value of field ':field' must be part of list: :domain";

			}

			$this->appendMessage(strtr($message, [":field" => $field, ":domain" => join(", ", $domain)]), $field, "Inclusion");

			return false;
		}

		return true;
	}


}