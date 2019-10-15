<?php
namespace Phalcon\Logger;


class Item
{
	protected $_type;
	protected $_message;
	protected $_time;
	protected $_context;

	public function __construct($message, $type, $time = 0, $context = null)
	{
		$this->_message = $message;
		$this->_type = $type;
		$this->_time = $time;

		if (typeof($context) == "array")
		{
			$this->_context = $context;

		}

	}


}