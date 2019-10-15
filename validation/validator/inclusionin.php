<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message;

class InclusionIn extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		$domain = $this->getOption("domain");

		if (function() { if(isset($domain[$field])) {$fieldDomain = $domain[$field]; return $fieldDomain; } else { return false; } }())
		{
			if (typeof($fieldDomain) == "array")
			{
				$domain = $fieldDomain;

			}

		}

		if (typeof($domain) <> "array")
		{
			throw new Exception("Option 'domain' must be an array");
		}

		$strict = false;

		if ($this->hasOption("strict"))
		{
			$strict = $this->getOption("strict");

			if (typeof($strict) == "array")
			{
				$strict = $strict[$field];

			}

			if (typeof($strict) <> "boolean")
			{
				throw new Exception("Option 'strict' must be a boolean");
			}

		}

		if (!(in_array($value, $domain, $strict)))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "InclusionIn");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label, ":domain" => join(", ", $domain)];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "InclusionIn", $code));

			return false;
		}

		return true;
	}


}