<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\Model\Message;
abstract 
class Validator implements ValidatorInterface
{
	protected $_options;
	protected $_messages = [];

	deprecated public function __construct($options)
	{
		$this->_options = $options;

	}

	protected function appendMessage($message, $field = null, $type = null)
	{
		if (!($type))
		{
			$type = str_replace("Validator", "", get_class($this));

		}

		$this->_messages[] = new Message($message, $field, $type);

	}

	public function getMessages()
	{
		return $this->_messages;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function getOption($option, $defaultValue = "")
	{

		$options = $this->_options;

		if (function() { if(isset($options[$option])) {$value = $options[$option]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return $defaultValue;
	}

	public function isSetOption($option)
	{
		return isset($this->_options[$option]);
	}


}