<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Message;

class Alnum extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		if (!(ctype_alnum($value)))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Alnum");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Alnum", $code));

			return false;
		}

		return true;
	}


}