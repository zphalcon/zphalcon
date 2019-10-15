<?php
namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\FormatterInterface;

class Blackhole extends Adapter
{
	public function getFormatter()
	{
		if (typeof($this->_formatter) !== "object")
		{
			$this->_formatter = new Line();

		}

		return $this->_formatter;
	}

	public function logInternal($message, $type, $time, $context)
	{
	}

	public function close()
	{
	}


}