<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Identical extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		if ($this->hasOption("accepted"))
		{
			$accepted = $this->getOption("accepted");

			if (typeof($accepted) == "array")
			{
				$accepted = $accepted[$field];

			}

			$valid = $value == $accepted;

		}

		if (!($valid))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Identical");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Identical", $code));

			return false;
		}

		return true;
	}


}