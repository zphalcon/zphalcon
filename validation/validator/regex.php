<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Regex extends Validator
{
	public function validate($validation, $field)
	{

		$matches = null;

		$value = $validation->getValue($field);

		$pattern = $this->getOption("pattern");

		if (typeof($pattern) == "array")
		{
			$pattern = $pattern[$field];

		}

		if (preg_match($pattern, $value, $matches))
		{
			$failed = $matches[0] <> $value;

		}

		if ($failed === true)
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Regex");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Regex", $code));

			return false;
		}

		return true;
	}


}