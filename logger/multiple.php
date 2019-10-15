<?php
namespace Phalcon\Logger;

use Phalcon\Logger;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Exception;

class Multiple
{
	protected $_loggers;
	protected $_formatter;
	protected $_logLevel;

	public function push($logger)
	{
		$this->_loggers[] = $logger;

	}

	public function setFormatter($formatter)
	{

		$loggers = $this->_loggers;

		if (typeof($loggers) == "array")
		{
			foreach ($loggers as $logger) {
				$logger->setFormatter($formatter);
			}

		}

		$this->_formatter = $formatter;

	}

	public function setLogLevel($level)
	{

		$loggers = $this->_loggers;

		if (typeof($loggers) == "array")
		{
			foreach ($loggers as $logger) {
				$logger->setLogLevel($level);
			}

		}

		$this->_logLevel = $level;

	}

	public function log($type, $message = null, $context = null)
	{

		$loggers = $this->_loggers;

		if (typeof($loggers) == "array")
		{
			foreach ($loggers as $logger) {
				$logger->log($type, $message, $context);
			}

		}

	}

	public function critical($message, $context = null)
	{
		$this->log(Logger::CRITICAL, $message, $context);

	}

	public function emergency($message, $context = null)
	{
		$this->log(Logger::EMERGENCY, $message, $context);

	}

	public function debug($message, $context = null)
	{
		$this->log(Logger::DEBUG, $message, $context);

	}

	public function error($message, $context = null)
	{
		$this->log(Logger::ERROR, $message, $context);

	}

	public function info($message, $context = null)
	{
		$this->log(Logger::INFO, $message, $context);

	}

	public function notice($message, $context = null)
	{
		$this->log(Logger::NOTICE, $message, $context);

	}

	public function warning($message, $context = null)
	{
		$this->log(Logger::WARNING, $message, $context);

	}

	public function alert($message, $context = null)
	{
		$this->log(Logger::ALERT, $message, $context);

	}


}