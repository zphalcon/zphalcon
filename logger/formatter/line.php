<?php
namespace Phalcon\Logger\Formatter;

use Phalcon\Logger\Formatter;

class Line extends Formatter
{
	protected $_dateFormat = "D, d M y H:i:s O";
	protected $_format = "[%date%][%type%] %message%";

	public function __construct($format = null, $dateFormat = null)
	{
		if ($format)
		{
			$this->_format = $format;

		}

		if ($dateFormat)
		{
			$this->_dateFormat = $dateFormat;

		}

	}

	public function format($message, $type, $timestamp, $context = null)
	{

		$format = $this->_format;

		if (memstr($format, "%date%"))
		{
			$format = str_replace("%date%", date($this->_dateFormat, $timestamp), $format);

		}

		if (memstr($format, "%type%"))
		{
			$format = str_replace("%type%", $this->getTypeString($type), $format);

		}

		$format = str_replace("%message%", $message, $format) . PHP_EOL;

		if (typeof($context) === "array")
		{
			return $this->interpolate($format, $context);
		}

		return $format;
	}


}