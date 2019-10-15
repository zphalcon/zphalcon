<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Validator;
use Phalcon\Mvc\Model\Exception;

class StringLength extends Validator
{
	public function validate($record)
	{

		$field = $this->getOption("field");

		if (typeof($field) <> "string")
		{
			throw new Exception("Field name must be a string");
		}

		$isSetMin = $this->isSetOption("min");

		$isSetMax = $this->isSetOption("max");

		if (!($isSetMin) && !($isSetMax))
		{
			throw new Exception("A minimum or maximum must be set");
		}

		$value = $record->readAttribute($field);

		if ($this->isSetOption("allowEmpty") && empty($value))
		{
			return true;
		}

		if (function_exists("mb_strlen"))
		{
			$length = mb_strlen($value);

		}

		if ($isSetMax)
		{
			$maximum = $this->getOption("max");

			if ($length > $maximum)
			{
				$message = $this->getOption("messageMaximum");

				if (empty($message))
				{
					$message = "Value of field ':field' exceeds the maximum :max characters";

				}

				$this->appendMessage(strtr($message, [":field" => $field, ":max" => $maximum]), $field, "TooLong");

				return false;
			}

		}

		if ($isSetMin)
		{
			$minimum = $this->getOption("min");

			if ($length < $minimum)
			{
				$message = $this->getOption("messageMinimum");

				if (empty($message))
				{
					$message = "Value of field ':field' is less than the minimum :min characters";

				}

				$this->appendMessage(strtr($message, [":field" => $field, ":min" => $minimum]), $field, "TooShort");

				return false;
			}

		}

		return true;
	}


}