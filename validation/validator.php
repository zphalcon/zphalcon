<?php
namespace Phalcon\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Exception;
use Phalcon\Validation\ValidatorInterface;
abstract 
class Validator implements ValidatorInterface
{
	protected $_options;

	public function __construct($options = null)
	{
		$this->_options = $options;

	}

	deprecated public function isSetOption($key)
	{
		return isset($this->_options[$key]);
	}

	public function hasOption($key)
	{
		return isset($this->_options[$key]);
	}

	public function getOption($key, $defaultValue = null)
	{

		$options = $this->_options;

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options[$key])) {$value = $options[$key]; return $value; } else { return false; } }())
			{
				if ($key == "attribute" && typeof($value) == "array")
				{
					if (function() { if(isset($value[$key])) {$fieldValue = $value[$key]; return $fieldValue; } else { return false; } }())
					{
						return $fieldValue;
					}

				}

				return $value;
			}

		}

		return $defaultValue;
	}

	public function setOption($key, $value)
	{
		$this[$key] = $value;

	}

	abstract public function validate($validation, $attribute)

	protected function prepareLabel($validation, $field)
	{

		$label = $this->getOption("label");

		if (typeof($label) == "array")
		{
			$label = $label[$field];

		}

		if (empty($label))
		{
			$label = $validation->getLabel($field);

		}

		return $label;
	}

	protected function prepareMessage($validation, $field, $type, $option = "message")
	{

		$message = $this->getOption($option);

		if (typeof($message) == "array")
		{
			$message = $message[$field];

		}

		if (empty($message))
		{
			$message = $validation->getDefaultMessage($type);

		}

		return $message;
	}

	protected function prepareCode($field)
	{

		$code = $this->getOption("code");

		if (typeof($code) == "array")
		{
			$code = $code[$field];

		}

		return $code;
	}


}