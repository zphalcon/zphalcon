<?php
namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Exception;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Formatter\Line as LineFormatter;

class Stream extends Adapter
{
	protected $_stream;

	public function __construct($name, $options = null)
	{

		if (function() { if(isset($options["mode"])) {$mode = $options["mode"]; return $mode; } else { return false; } }())
		{
			if (memstr($mode, "r"))
			{
				throw new Exception("Stream must be opened in append or write mode");
			}

		}

		$stream = fopen($name, $mode);

		if (!($stream))
		{
			throw new Exception("Can't open stream '" . $name . "'");
		}

		$this->_stream = $stream;

	}

	public function getFormatter()
	{
		if (typeof($this->_formatter) !== "object")
		{
			$this->_formatter = new LineFormatter();

		}

		return $this->_formatter;
	}

	public function logInternal($message, $type, $time, $context)
	{

		$stream = $this->_stream;

		if (typeof($stream) <> "resource")
		{
			throw new Exception("Cannot send message to the log because it is invalid");
		}

		fwrite($stream, $this->getFormatter()->format($message, $type, $time, $context));

	}

	public function close()
	{
		return fclose($this->_stream);
	}


}