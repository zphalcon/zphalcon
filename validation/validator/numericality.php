<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Numericality extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		if (!(preg_match("/^-?\d+(?:[\.,]\d+)?$/", $value)) || !(is_numeric($value)))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Numericality");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Numericality", $code));

			return false;
		}

		return true;
	}


}