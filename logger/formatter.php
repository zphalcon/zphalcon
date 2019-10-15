<?php
namespace Phalcon\Logger;

use Phalcon\Logger;
abstract 
class Formatter implements FormatterInterface
{
	public function getTypeString($type)
	{
		switch ($type) {
			case Logger::DEBUG:
				return "DEBUG";
			case Logger::ERROR:
				return "ERROR";
			case Logger::WARNING:
				return "WARNING";
			case Logger::CRITICAL:
				return "CRITICAL";
			case Logger::CUSTOM:
				return "CUSTOM";
			case Logger::ALERT:
				return "ALERT";
			case Logger::NOTICE:
				return "NOTICE";
			case Logger::INFO:
				return "INFO";
			case Logger::EMERGENCY:
				return "EMERGENCY";
			case Logger::SPECIAL:
				return "SPECIAL";

		}

		return "CUSTOM";
	}

	public function interpolate($message, $context = null)
	{

		if (typeof($context) == "array" && count($context) > 0)
		{
			$replace = [];

			foreach ($context as $key => $value) {
				$replace["{" . $key . "}"] = $value;
			}

			return strtr($message, $replace);
		}

		return $message;
	}


}