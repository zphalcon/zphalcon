<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Message;

class CreditCard extends Validator
{
	public function validate($validation, $field)
	{

		$value = $validation->getValue($field);

		$valid = $this->verifyByLuhnAlgorithm($value);

		if (!($valid))
		{
			$label = $this->prepareLabel($validation, $field);
			$message = $this->prepareMessage($validation, $field, "CreditCard");
			$code = $this->prepareCode($field);

			$replacePairs = [":field" => $label];

			$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "CreditCard", $code));

			return false;
		}

		return true;
	}

	private function verifyByLuhnAlgorithm($number)
	{

		$digits = (array) str_split($number);


		foreach ($digits->reversed() as $position => $digit) {
			$hash .= $position % 2 ? $digit * 2 : $digit;
		}


		$result = array_sum(str_split($hash));

		return $result % 10 == 0;
	}


}