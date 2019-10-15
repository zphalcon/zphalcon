<?php
namespace Phalcon\Logger\Formatter;

use Phalcon\Logger\Formatter;

class Json extends Formatter
{
	public function format($message, $type, $timestamp, $context = null)
	{
		if (typeof($context) === "array")
		{
			$message = $this->interpolate($message, $context);

		}

		return json_encode(["type" => $this->getTypeString($type), "message" => $message, "timestamp" => $timestamp]) . PHP_EOL;
	}


}