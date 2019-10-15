<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\Model;

class ValidationFailed extends \Phalcon\Mvc\Model\Exception
{
	protected $_model;
	protected $_messages;

	public function __construct($model, $validationMessages)
	{

		if (count($validationMessages) > 0)
		{
			$message = $validationMessages[0];

			$messageStr = $message->getMessage();

		}

		$this->_model = $model;

		$this->_messages = $validationMessages;

		parent::__construct($messageStr);

	}

	public function getModel()
	{
		return $this->_model;
	}

	public function getMessages()
	{
		return $this->_messages;
	}


}