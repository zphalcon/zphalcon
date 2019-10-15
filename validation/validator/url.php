<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Url extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		if (!(filter_var($value, FILTER_VALIDATE_URL)))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Url");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Url", $code));

			return false;
		}

		return true;
	}


}