<?php
namespace Phalcon\Flash;

use Phalcon\Flash as FlashBase;
use Phalcon\DiInterface;
use Phalcon\Flash\Exception;
use Phalcon\Session\AdapterInterface as SessionInterface;

class Session extends FlashBase
{
	protected function _getSessionMessages($remove, $type = null)
	{

		$dependencyInjector = $this->getDI();

		$session = $dependencyInjector->getShared("session");
		$messages = $session->get("_flashMessages");

		if (typeof($type) == "string")
		{
			if (function() { if(isset($messages[$type])) {$returnMessages = $messages[$type]; return $returnMessages; } else { return false; } }())
			{
				if ($remove === true)
				{
					unset($messages[$type]);

					$session->set("_flashMessages", $messages);

				}

				return $returnMessages;
			}

			return [];
		}

		if ($remove === true)
		{
			$session->remove("_flashMessages");

		}

		return $messages;
	}

	protected function _setSessionMessages($messages)
	{

		$dependencyInjector = $this->getDI();
		$session = $dependencyInjector->getShared("session");

		$session->set("_flashMessages", $messages);

		return $messages;
	}

	public function message($type, $message)
	{

		$messages = $this->_getSessionMessages(false);

		if (typeof($messages) <> "array")
		{
			$messages = [];

		}

		if (!(isset($messages[$type])))
		{
			$messages[$type] = [];

		}

		$messages[$type][] = $message;

		$this->_setSessionMessages($messages);

	}

	public function has($type = null)
	{

		$messages = $this->_getSessionMessages(false);

		if (typeof($messages) == "array")
		{
			if (typeof($type) == "string")
			{
				return isset($messages[$type]);
			}

			return true;
		}

		return false;
	}

	public function getMessages($type = null, $remove = true)
	{
		return $this->_getSessionMessages($remove, $type);
	}

	public function output($remove = true)
	{

		$messages = $this->_getSessionMessages($remove);

		if (typeof($messages) == "array")
		{
			foreach ($messages as $type => $message) {
				$this->outputMessage($type, $message);
			}

		}

		parent::clear();

	}

	public function clear()
	{
		$this->_getSessionMessages(true);

		parent::clear();

	}


}