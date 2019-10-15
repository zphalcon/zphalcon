<?php
namespace Phalcon\Logger;

use Phalcon\Logger;
use Phalcon\Logger\Item;
use Phalcon\Logger\Exception;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\FormatterInterface;
abstract 
class Adapter implements AdapterInterface
{
	protected $_transaction = false;
	protected $_queue = [];
	protected $_formatter;
	protected $_logLevel = 9;

	public function setLogLevel($level)
	{
		$this->_logLevel = $level;

		return $this;
	}

	public function getLogLevel()
	{
		return $this->_logLevel;
	}

	public function setFormatter($formatter)
	{
		$this->_formatter = $formatter;

		return $this;
	}

	public function begin()
	{
		$this->_transaction = true;

		return $this;
	}

	public function commit()
	{

		if (!($this->_transaction))
		{
			throw new Exception("There is no active transaction");
		}

		$this->_transaction = false;

		foreach ($this->_queue as $message) {
			$this->logInternal($message->getMessage(), $message->getType(), $message->getTime(), $message->getContext());
		}

		$this->_queue = [];

		return $this;
	}

	public function rollback()
	{

		$transaction = $this->_transaction;

		if (!($transaction))
		{
			throw new Exception("There is no active transaction");
		}

		$this->_transaction = false;
		$this->_queue = [];

		return $this;
	}

	public function isTransaction()
	{
		return $this->_transaction;
	}

	public function critical($message, $context = null)
	{
		return $this->log(Logger::CRITICAL, $message, $context);
	}

	public function emergency($message, $context = null)
	{
		return $this->log(Logger::EMERGENCY, $message, $context);
	}

	public function debug($message, $context = null)
	{
		return $this->log(Logger::DEBUG, $message, $context);
	}

	public function error($message, $context = null)
	{
		return $this->log(Logger::ERROR, $message, $context);
	}

	public function info($message, $context = null)
	{
		return $this->log(Logger::INFO, $message, $context);
	}

	public function notice($message, $context = null)
	{
		return $this->log(Logger::NOTICE, $message, $context);
	}

	public function warning($message, $context = null)
	{
		return $this->log(Logger::WARNING, $message, $context);
	}

	public function alert($message, $context = null)
	{
		return $this->log(Logger::ALERT, $message, $context);
	}

	public function log($type, $message = null, $context = null)
	{

		if (typeof($type) == "string" && typeof($message) == "integer")
		{
			$toggledMessage = $type;
			$toggledType = $message;

		}

		if (typeof($toggledType) == "null")
		{
			$toggledType = Logger::DEBUG;

		}

		if ($this->_logLevel >= $toggledType)
		{
			$timestamp = time();

			if ($this->_transaction)
			{
				$this->_queue[] = new Item($toggledMessage, $toggledType, $timestamp, $context);

			}

		}

		return $this;
	}


}