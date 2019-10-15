<?php
namespace Phalcon\Mvc\Model;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Phalcon\Mvc\Model\Transaction\ManagerInterface;
use Phalcon\Mvc\Model\TransactionInterface;

class Transaction implements TransactionInterface
{
	protected $_connection;
	protected $_activeTransaction = false;
	protected $_isNewTransaction = true;
	protected $_rollbackOnAbort = false;
	protected $_manager;
	protected $_messages;
	protected $_rollbackRecord;

	public function __construct($dependencyInjector, $autoBegin = false, $service = null)
	{

		$this->_messages = [];

		if ($service)
		{
			$connection = $dependencyInjector->get($service);

		}

		$this->_connection = $connection;

		if ($autoBegin)
		{
			$connection->begin();

		}

	}

	public function setTransactionManager($manager)
	{
		$this->_manager = $manager;

	}

	public function begin()
	{
		return $this->_connection->begin();
	}

	public function commit()
	{

		$manager = $this->_manager;

		if (typeof($manager) == "object")
		{
			$manager->notifyCommit($this);

		}

		return $this->_connection->commit();
	}

	public function rollback($rollbackMessage = null, $rollbackRecord = null)
	{

		$manager = $this->_manager;

		if (typeof($manager) == "object")
		{
			$manager->notifyRollback($this);

		}

		$connection = $this->_connection;

		if ($connection->rollback())
		{
			if (!($rollbackMessage))
			{
				$rollbackMessage = "Transaction aborted";

			}

			if (typeof($rollbackRecord) == "object")
			{
				$this->_rollbackRecord = $rollbackRecord;

			}

			throw new TxFailed($rollbackMessage, $this->_rollbackRecord);
		}

		return true;
	}

	public function getConnection()
	{
		if ($this->_rollbackOnAbort)
		{
			if (connection_aborted())
			{
				$this->rollback("The request was aborted");

			}

		}

		return $this->_connection;
	}

	public function setIsNewTransaction($isNew)
	{
		$this->_isNewTransaction = $isNew;

	}

	public function setRollbackOnAbort($rollbackOnAbort)
	{
		$this->_rollbackOnAbort = $rollbackOnAbort;

	}

	public function isManaged()
	{
		return typeof($this->_manager) == "object";
	}

	public function getMessages()
	{
		return $this->_messages;
	}

	public function isValid()
	{
		return $this->_connection->isUnderTransaction();
	}

	public function setRollbackedRecord($record)
	{
		$this->_rollbackRecord = $record;

	}


}