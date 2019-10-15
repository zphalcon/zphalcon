<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Validator;

class Confirmation extends Validator
{
	public function validate($validation, $field)
	{

		$fieldWith = $this->getOption("with");

		if (typeof($fieldWith) == "array")
		{
			$fieldWith = $fieldWith[$field];

		}

		$value = $validation->getValue($field);
		$valueWith = $validation->getValue($fieldWith);

		if (!($this->compare($value, $valueWith)))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "Confirmation");
			$code = $this->prepareCode($field);

			$labelWith = $this->getOption("labelWith");

			if (typeof($labelWith) == "array")
			{
				$labelWith = $labelWith[$fieldWith];

			}

			if (empty($labelWith))
			{
				$labelWith = $validation->getLabel($fieldWith);

			}

			$replacePairs = [":field" => $label, ":with" => $labelWith];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Confirmation", $code));

			return false;
		}

		return true;
	}

	protected final function compare($a, $b)
	{
		if ($this->getOption("ignoreCase", false))
		{
			if (!(function_exists("mb_strtolower")))
			{
				throw new Exception("Extension 'mbstring' is required");
			}

			$a = mb_strtolower($a, "utf-8");

			$b = mb_strtolower($b, "utf-8");

		}

		return $a == $b;
	}


}