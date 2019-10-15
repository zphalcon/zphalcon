<?php
namespace Phalcon\Mvc\Model\Query;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Query\StatusInterface;

class Status implements StatusInterface
{
	protected $_success;
	protected $_model;

	public function __construct($success, $model = null)
	{
		$this->_success = $success;
		$this->_model = $model;

	}

	public function getModel()
	{
		return $this->_model;
	}

	public function getMessages()
	{

		$model = $this->_model;

		if (typeof($model) <> "object")
		{
			return [];
		}

		return $model->getMessages();
	}

	public function success()
	{
		return $this->_success;
	}


}