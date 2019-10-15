<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class Ip extends Validator
{
	const VERSION_4 = FILTER_FLAG_IPV4;
	const VERSION_6 = FILTER_FLAG_IPV6;

	public function validate($record)
	{

		$field = $this->getOption("field");

		if (typeof($field) <> "string")
		{
			throw new Exception("Field name must be a string");
		}

		$value = $record->readAttribute($field);

		$version = $this->getOption("version", FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);

		$allowPrivate = $this->getOption("allowPrivate") ? 0 : FILTER_FLAG_NO_PRIV_RANGE;

		$allowReserved = $this->getOption("allowReserved") ? 0 : FILTER_FLAG_NO_RES_RANGE;

		if ($this->getOption("allowEmpty", false) && empty($value))
		{
			return true;
		}

		$options = ["options" => ["default" => false], "flags" => $version | $allowPrivate | $allowReserved];

		if (!(filter_var($value, FILTER_VALIDATE_IP, $options)))
		{
			$message = $this->getOption("message", "IP address is incorrect");

			$this->appendMessage(strtr($message, [":field" => $field]), $field, "IP");

			return false;
		}

		return true;
	}


}