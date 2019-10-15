<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Message;

class Date extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		$format = $this->getOption("format");

		if (typeof($format) == "array")
		{
			$format = $format[$field];

		}

		if (empty($format))
		{
			$format = "Y-m-d";

		}

		if (!($this->checkDate($value, $format)))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Date");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Date", $code));

			return false;
		}

		return true;
	}

	private function checkDate($value, $format)
	{

		if (!(is_string($value)))
		{
			return false;
		}

		$date = \DateTime::createFromFormat($format, $value);

		$errors = \DateTime::getLastErrors();

		if ($errors["warning_count"] > 0 || $errors["error_count"] > 0)
		{
			return false;
		}

		return true;
	}


}