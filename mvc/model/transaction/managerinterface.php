<?php
namespace Phalcon\Mvc\Model\Transaction;


interface ManagerInterface
{
	public function has()
	{
	}

	public function get($autoBegin = true)
	{
	}

	public function rollbackPendent()
	{
	}

	public function commit()
	{
	}

	public function rollback($collect = false)
	{
	}

	public function notifyRollback($transaction)
	{
	}

	public function notifyCommit($transaction)
	{
	}

	public function collectTransactions()
	{
	}


}