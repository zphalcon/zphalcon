<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message;

class StringLength extends Validator
{
	public function validate($validation, $field)
	{

		$isSetMin = $this->hasOption("min");
		$isSetMax = $this->hasOption("max");

		if (!($isSetMin) && !($isSetMax))
		{
			throw new Exception("A minimum or maximum must be set");
		}

		$value = $validation->getValue($field);
		$label = $this->prepareLabel($validation, $field);
		$code = $this->prepareCode($field);

		if (function_exists("mb_strlen"))
		{
			$length = mb_strlen($value);

		}

		if ($isSetMax)
		{
			$maximum = $this->getOption("max");

			if (typeof($maximum) == "array")
			{
				$maximum = $maximum[$field];

			}

			if ($length > $maximum)
			{
				$message = $this->prepareMessage($validation, $field, "TooLong", "messageMaximum");
				$replacePairs = [":field" => $label, ":max" => $maximum];

				$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "TooLong", $code));

				return false;
			}

		}

		if ($isSetMin)
		{
			$minimum = $this->getOption("min");

			if (typeof($minimum) == "array")
			{
				$minimum = $minimum[$field];

			}

			if ($length < $minimum)
			{
				$message = $this->prepareMessage($validation, $field, "TooShort", "messageMinimum");
				$replacePairs = [":field" => $label, ":min" => $minimum];

				$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "TooShort", $code));

				return false;
			}

		}

		return true;
	}


}