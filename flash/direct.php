<?php
namespace Phalcon\Flash;

use Phalcon\Flash as FlashBase;

class Direct extends FlashBase
{
	public function message($type, $message)
	{
		return $this->outputMessage($type, $message);
	}

	public function output($remove = true)
	{

		$messages = $this->_messages;

		if (typeof($messages) == "array")
		{
			foreach ($messages as $message) {
				echo($message);
			}

		}

		if ($remove)
		{
			parent::clear();

		}

	}


}