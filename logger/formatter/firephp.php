<?php
namespace Phalcon\Logger\Formatter;

use Phalcon\Logger;
use Phalcon\Logger\Formatter;

class Firephp extends Formatter
{
	protected $_showBacktrace = true;
	protected $_enableLabels = true;

	public function getTypeString($type)
	{
		switch ($type) {
			case Logger::EMERGENCY:
			case Logger::CRITICAL:
			case Logger::ERROR:
				return "ERROR";			case Logger::ALERT:
			case Logger::WARNING:
				return "WARN";			case Logger::INFO:
			case Logger::NOTICE:
			case Logger::CUSTOM:
				return "INFO";			case Logger::DEBUG:
			case Logger::SPECIAL:
				return "LOG";
		}

		return "CUSTOM";
	}

	public function setShowBacktrace($isShow = null)
	{
		$this->_showBacktrace = $isShow;

		return $this;
	}

	public function getShowBacktrace()
	{
		return $this->_showBacktrace;
	}

	public function enableLabels($isEnable = null)
	{
		$this->_enableLabels = $isEnable;

		return $this;
	}

	public function labelsEnabled()
	{
		return $this->_enableLabels;
	}

	public function format($message, $type, $timestamp, $context = null)
	{

		if (typeof($context) === "array")
		{
			$message = $this->interpolate($message, $context);

		}

		$meta = ["Type" => $this->getTypeString($type)];

		if ($this->_showBacktrace)
		{

			$param = DEBUG_BACKTRACE_IGNORE_ARGS;

			$backtrace = debug_backtrace($param);
			$lastTrace = end($backtrace);

			if (isset($lastTrace["file"]))
			{
				$meta["File"] = $lastTrace["file"];

			}

			if (isset($lastTrace["line"]))
			{
				$meta["Line"] = $lastTrace["line"];

			}

			foreach ($backtrace as $key => $backtraceItem) {
				unset($backtraceItem["object"]);
				unset($backtraceItem["args"]);
				$backtrace[$key] = $backtraceItem;
			}

		}

		if ($this->_enableLabels)
		{
			$meta["Label"] = $message;

		}

		if (!($this->_enableLabels) && !($this->_showBacktrace))
		{
			$body = $message;

		}

		$encoded = json_encode([$meta, $body]);
		$len = strlen($encoded);

		return $len . "|" . $encoded . "|";
	}


}