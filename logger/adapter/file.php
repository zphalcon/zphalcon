<?php
namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Exception;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Formatter\Line as LineFormatter;

class File extends Adapter
{
	protected $_fileHandler;
	protected $_path;
	protected $_options;

	public function __construct($name, $options = null)
	{

		if (typeof($options) === "array")
		{
			if (function() { if(isset($options["mode"])) {$mode = $options["mode"]; return $mode; } else { return false; } }())
			{
				if (memstr($mode, "r"))
				{
					throw new Exception("Logger must be opened in append or write mode");
				}

			}

		}

		if ($mode === null)
		{
			$mode = "ab";

		}

		$handler = fopen($name, $mode);

		if (typeof($handler) <> "resource")
		{
			throw new Exception("Can't open log file at '" . $name . "'");
		}

		$this->_path = $name;
		$this->_options = $options;
		$this->_fileHandler = $handler;

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

		$fileHandler = $this->_fileHandler;

		if (typeof($fileHandler) !== "resource")
		{
			throw new Exception("Cannot send message to the log because it is invalid");
		}

		fwrite($fileHandler, $this->getFormatter()->format($message, $type, $time, $context));

	}

	public function close()
	{
		return fclose($this->_fileHandler);
	}

	public function __wakeup()
	{

		$path = $this->_path;

		if (typeof($path) !== "string")
		{
			throw new Exception("Invalid data passed to Phalcon\\Logger\\Adapter\\File::__wakeup()");
		}

		if (!(function() { if(isset($this->_options["mode"])) {$mode = $this->_options["mode"]; return $mode; } else { return false; } }()))
		{
			$mode = "ab";

		}

		if (typeof($mode) !== "string")
		{
			throw new Exception("Invalid data passed to Phalcon\\Logger\\Adapter\\File::__wakeup()");
		}

		if (memstr($mode, "r"))
		{
			throw new Exception("Logger must be opened in append or write mode");
		}

		$this->_fileHandler = fopen($path, $mode);

	}


}