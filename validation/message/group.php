<?php
namespace Phalcon\Validation\Message;

use Phalcon\Validation\Message;
use Phalcon\Validation\Exception;
use Phalcon\Validation\MessageInterface;
use Phalcon\Validation\Message\Group;

class Group implements \Countable, \ArrayAccess, \Iterator
{
	protected $_position = 0;
	protected $_messages = [];

	public function __construct($messages = null)
	{
		if (typeof($messages) == "array")
		{
			$this->_messages = $messages;

		}

	}

	public function offsetGet($index)
	{

		if (function() { if(isset($this->_messages[$index])) {$message = $this->_messages[$index]; return $message; } else { return false; } }())
		{
			return $message;
		}

		return false;
	}

	public function offsetSet($index, $message)
	{
		if (typeof($message) <> "object")
		{
			throw new Exception("The message must be an object");
		}

		$this[$index] = $message;

	}

	public function offsetExists($index)
	{
		return isset($this->_messages[$index]);
	}

	public function offsetUnset($index)
	{
		if (isset($this->_messages[$index]))
		{
			array_splice($this->_messages, $index, 1);

		}

	}

	public function appendMessage($message)
	{
		$this->_messages[] = $message;

	}

	public function appendMessages($messages)
	{

		if (typeof($messages) <> "array" && typeof($messages) <> "object")
		{
			throw new Exception("The messages must be array or object");
		}

		$currentMessages = $this->_messages;

		if (typeof($messages) == "array")
		{
			if (typeof($currentMessages) == "array")
			{
				$finalMessages = array_merge($currentMessages, $messages);

			}

			$this->_messages = $finalMessages;

		}

	}

	public function filter($fieldName)
	{

		$filtered = [];
		$messages = $this->_messages;

		if (typeof($messages) == "array")
		{
			foreach ($messages as $message) {
				if (method_exists($message, "getField"))
				{
					if ($fieldName == $message->getField())
					{
						$filtered = $message;

					}

				}
			}

		}

		return $filtered;
	}

	public function count()
	{
		return count($this->_messages);
	}

	public function rewind()
	{
		$this->_position = 0;

	}

	public function current()
	{
		return $this->_messages[$this->_position];
	}

	public function key()
	{
		return $this->_position;
	}

	public function next()
	{
		$this->_position++;
	}

	public function valid()
	{
		return isset($this->_messages[$this->_position]);
	}

	public static function __set_state($group)
	{
		return new self($group["_messages"]);
	}


}