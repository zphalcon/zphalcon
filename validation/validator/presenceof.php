<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class PresenceOf extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		if ($value === null || $value === "")
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "PresenceOf");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "PresenceOf", $code));

			return false;
		}

		return true;
	}


}