<?php
namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Exception;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Formatter\Firephp as FirePhpFormatter;

class Firephp extends Adapter
{
	private $_initialized = false;
	private $_index = 1;

	public function getFormatter()
	{
		if (typeof($this->_formatter) !== "object")
		{
			$this->_formatter = new FirePhpFormatter();

		}

		return $this->_formatter;
	}

	public function logInternal($message, $type, $time, $context)
	{

		if (!($this->_initialized))
		{
			header("X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2");

			header("X-Wf-1-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3");

			header("X-Wf-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1");

			$this->_initialized = true;

		}

		$format = $this->getFormatter()->format($message, $type, $time, $context);
		$chunk = str_split($format, 4500);
		$index = $this->_index;

		foreach ($chunk as $key => $chString) {
			$content = "X-Wf-1-1-1-" . (string) $index . ": " . $chString;
			if (isset($chunk[$key + 1]))
			{
				$content .= "|\\";

			}
			header($content);
			$index++;
		}

		$this->_index = $index;

	}

	public function close()
	{
		return true;
	}


}