<?php
namespace Phalcon\Mvc\Model\Transaction;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Transaction\Exception;
use Phalcon\Mvc\Model\MessageInterface;

class Failed extends Exception
{
	protected $_record = null;

	public function __construct($message, $record = null)
	{
		$this->_record = $record;

		parent::__construct($message);

	}

	public function getRecordMessages()
	{

		$record = $this->_record;

		if ($record !== null)
		{
			return $record->getMessages();
		}

		return $this->getMessage();
	}

	public function getRecord()
	{
		return $this->_record;
	}


}