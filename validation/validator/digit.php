<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Digit extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		if (is_int($value) || ctype_digit($value))
		{
			return true;
		}

		$label = $this->prepareLabel($validation, $field);
		$message = $this->prepareMessage($validation, $field, "Digit");
		$code = $this->prepareCode($field);

		$replacePairs = [":field" => $label];

		$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Digit", $code));

		return false;
	}


}