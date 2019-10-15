<?php
namespace Phalcon\Logger\Formatter;

use Phalcon\Logger\Formatter;

class Syslog extends Formatter
{
	public function format($message, $type, $timestamp, $context = null)
	{
		if (typeof($context) === "array")
		{
			$message = $this->interpolate($message, $context);

		}

		return [$type, $message];
	}


}