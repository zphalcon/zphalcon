<?php
namespace Phalcon\Mvc\Collection;

use Phalcon\Mvc\CollectionInterface;
abstract 
class Behavior implements BehaviorInterface
{
	protected $_options;

	public function __construct($options = null)
	{
		$this->_options = $options;

	}

	protected function mustTakeAction($eventName)
	{
		return isset($this->_options[$eventName]);
	}

	protected function getOptions($eventName = null)
	{

		$options = $this->_options;

		if ($eventName !== null)
		{
			if (function() { if(isset($options[$eventName])) {$eventOptions = $options[$eventName]; return $eventOptions; } else { return false; } }())
			{
				return $eventOptions;
			}

			return null;
		}

		return $options;
	}

	public function notify($type, $model)
	{
		return null;
	}

	public function missingMethod($model, $method, $arguments = null)
	{
		return null;
	}


}