<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

class Callback extends Validator
{
	public function validate($validation, $field)
	{

		$callback = $this->getOption("callback");

		if (is_callable($callback))
		{
			$data = $validation->getEntity();

			if (empty($data))
			{
				$data = $validation->getData();

			}

			$returnedValue = call_user_func($callback, $data);

			if (typeof($returnedValue) == "boolean")
			{
				if (!($returnedValue))
				{
					$label = $this->prepareLabel($validation, $field);
					$message = $this->prepareMessage($validation, $field, "Callback");
					$code = $this->prepareCode($field);

					$replacePairs = [":field" => $label];

					$validation->appendMessage(new Message(strtr($message, $replacePairs), $field, "Callback", $code));

					return false;
				}

				return true;
			}

			throw new Exception("Callback must return boolean or Phalcon\\Validation\\Validator object");
		}

		return true;
	}


}