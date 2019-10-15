<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Alpha extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		if (preg_match("/[^[:alpha:]]/imu", $value))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Alpha");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Alpha", $code));

			return false;
		}

		return true;
	}


}