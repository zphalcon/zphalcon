<?php
namespace Phalcon\Mvc\Model\Transaction;

use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\Transaction\ManagerInterface;
use Phalcon\Mvc\Model\Transaction\Exception;
use Phalcon\Mvc\Model\Transaction;
use Phalcon\Mvc\Model\TransactionInterface;

class Manager implements ManagerInterface, InjectionAwareInterface
{
	protected $_dependencyInjector;
	protected $_initialized = false;
	protected $_rollbackPendent = true;
	protected $_number = 0;
	protected $_service = "db";
	protected $_transactions;

	public function __construct($dependencyInjector = null)
	{
		if (!($dependencyInjector))
		{
			$dependencyInjector = \Phalcon\Di::getDefault();

		}

		$this->_dependencyInjector = $dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
		}

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function setDbService($service)
	{
		$this->_service = $service;

		return $this;
	}

	public function getDbService()
	{
		return $this->_service;
	}

	public function setRollbackPendent($rollbackPendent)
	{
		$this->_rollbackPendent = $rollbackPendent;

		return $this;
	}

	public function getRollbackPendent()
	{
		return $this->_rollbackPendent;
	}

	public function has()
	{
		return $this->_number > 0;
	}

	public function get($autoBegin = true)
	{
		if (!($this->_initialized))
		{
			if ($this->_rollbackPendent)
			{
				register_shutdown_function([$this, "rollbackPendent"]);

			}

			$this->_initialized = true;

		}

		return $this->getOrCreateTransaction($autoBegin);
	}

	public function getOrCreateTransaction($autoBegin = true)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
		}

		if ($this->_number)
		{
			$transactions = $this->_transactions;

			if (typeof($transactions) == "array")
			{
				foreach ($transactions as $transaction) {
					if (typeof($transaction) == "object")
					{
						$transaction->setIsNewTransaction(false);

						return $transaction;
					}
				}

			}

		}

		$transaction = new Transaction($dependencyInjector, $autoBegin, $this->_service);

		$transaction->setTransactionManager($this);

		$this->_transactions[] = $transaction;
		$this->_number++;
		return $transaction;
	}

	public function rollbackPendent()
	{
		$this->rollback();

	}

	public function commit()
	{

		$transactions = $this->_transactions;

		if (typeof($transactions) == "array")
		{
			foreach ($transactions as $transaction) {
				$connection = $transaction->getConnection();
				if ($connection->isUnderTransaction())
				{
					$connection->commit();

				}
			}

		}

	}

	public function rollback($collect = true)
	{

		$transactions = $this->_transactions;

		if (typeof($transactions) == "array")
		{
			foreach ($transactions as $transaction) {
				$connection = $transaction->getConnection();
				if ($connection->isUnderTransaction())
				{
					$connection->rollback();

					$connection->close();

				}
				if ($collect)
				{
					$this->_collectTransaction($transaction);

				}
			}

		}

	}

	public function notifyRollback($transaction)
	{
		$this->_collectTransaction($transaction);

	}

	public function notifyCommit($transaction)
	{
		$this->_collectTransaction($transaction);

	}

	protected function _collectTransaction($transaction)
	{

		$transactions = $this->_transactions;

		if (count($transactions))
		{
			$newTransactions = [];

			foreach ($transactions as $managedTransaction) {
				if ($managedTransaction <> $transaction)
				{
					$newTransactions = $transaction;

				}
			}

			$this->_transactions = $newTransactions;

		}

	}

	public function collectTransactions()
	{

		$transactions = $this->_transactions;

		if (count($transactions))
		{
			foreach ($transactions as $_) {
				$this->_number--;			}

			$this->_transactions = null;

		}

	}


}