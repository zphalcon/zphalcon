<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Transaction\ManagerInterface;

interface TransactionInterface
{
	public function setTransactionManager($manager)
	{
	}

	public function begin()
	{
	}

	public function commit()
	{
	}

	public function rollback($rollbackMessage = null, $rollbackRecord = null)
	{
	}

	public function getConnection()
	{
	}

	public function setIsNewTransaction($isNew)
	{
	}

	public function setRollbackOnAbort($rollbackOnAbort)
	{
	}

	public function isManaged()
	{
	}

	public function getMessages()
	{
	}

	public function isValid()
	{
	}

	public function setRollbackedRecord($record)
	{
	}


}