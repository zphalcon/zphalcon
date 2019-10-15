<?php
namespace Phalcon\Validation;

use Phalcon\Validation\MessageInterface;

class Message implements MessageInterface
{
	protected $_type;
	protected $_message;
	protected $_field;
	protected $_code;

	public function __construct($message, $field = null, $type = null, $code = null)
	{
		$this->_message = $message;
		$this->_field = $field;
		$this->_type = $type;
		$this->_code = $code;

	}

	public function setType($type)
	{
		$this->_type = $type;

		return $this;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function setMessage($message)
	{
		$this->_message = $message;

		return $this;
	}

	public function getMessage()
	{
		return $this->_message;
	}

	public function setField($field)
	{
		$this->_field = $field;

		return $this;
	}

	public function getField()
	{
		return $this->_field;
	}

	public function setCode($code)
	{
		$this->_code = $code;

		return $this;
	}

	public function getCode()
	{
		return $this->_code;
	}

	public function __toString()
	{
		return $this->_message;
	}

	public static function __set_state($message)
	{
		return new self($message["_message"], $message["_field"], $message["_type"]);
	}


}