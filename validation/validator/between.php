<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Between extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);
		$minimum = $this->getOption("minimum");
		$maximum = $this->getOption("maximum");

		if (typeof($minimum) == "array")
		{
			$minimum = $minimum[$field];

		}

		if (typeof($maximum) == "array")
		{
			$maximum = $maximum[$field];

		}

		if ($value < $minimum || $value > $maximum)
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Between");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label, ":min" => $minimum, ":max" => $maximum];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Between", $code));

			return false;
		}

		return true;
	}


}