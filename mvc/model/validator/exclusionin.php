<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Validator;
use Phalcon\Mvc\Model\Exception;

class Exclusionin extends Validator
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
			throw new Exception("The option 'domain' is required by this validator");
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

		if (in_array($value, $domain))
		{
			$message = $this->getOption("message");

			if (empty($message))
			{
				$message = "Value of field ':field' must not be part of list: :domain";

			}

			$this->appendMessage(strtr($message, [":field" => $field, ":domain" => join(", ", $domain)]), $field, "Exclusion");

			return false;
		}

		return true;
	}


}