<?php
namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Exception;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\Formatter\Syslog as SyslogFormatter;

class Syslog extends Adapter
{
	protected $_opened = false;

	public function __construct($name, $options = null)
	{

		if ($name)
		{
			if (!(function() { if(isset($options["option"])) {$option = $options["option"]; return $option; } else { return false; } }()))
			{
				$option = LOG_ODELAY;

			}

			if (!(function() { if(isset($options["facility"])) {$facility = $options["facility"]; return $facility; } else { return false; } }()))
			{
				$facility = LOG_USER;

			}

			openlog($name, $option, $facility);

			$this->_opened = true;

		}

	}

	public function getFormatter()
	{
		if (typeof($this->_formatter) !== "object")
		{
			$this->_formatter = new SyslogFormatter();

		}

		return $this->_formatter;
	}

	public function logInternal($message, $type, $time, $context)
	{

		$appliedFormat = $this->getFormatter()->format($message, $type, $time, $context);

		if (typeof($appliedFormat) !== "array")
		{
			throw new Exception("The formatted message is not valid");
		}

		syslog($appliedFormat[0], $appliedFormat[1]);

	}

	public function close()
	{
		if (!($this->_opened))
		{
			return true;
		}

		return closelog();
	}


}