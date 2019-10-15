<?php
namespace Phalcon\Events;

use Phalcon\Events\Event;
use SplPriorityQueue;

class Manager implements ManagerInterface
{
	protected $_events = null;
	protected $_collect = false;
	protected $_enablePriorities = false;
	protected $_responses;

	public function attach($eventType, $handler, $priority = 100)
	{

		if (typeof($handler) <> "object")
		{
			throw new Exception("Event handler must be an Object");
		}

		if (!(function() { if(isset($this->_events[$eventType])) {$priorityQueue = $this->_events[$eventType]; return $priorityQueue; } else { return false; } }()))
		{
			if ($this->_enablePriorities)
			{
				$priorityQueue = new SplPriorityQueue();

				$priorityQueue->setExtractFlags(SplPriorityQueue::EXTR_DATA);

				$this[$eventType] = $priorityQueue;

			}

		}

		if (typeof($priorityQueue) == "object")
		{
			$priorityQueue->insert($handler, $priority);

		}

	}

	public function detach($eventType, $handler)
	{

		if (typeof($handler) <> "object")
		{
			throw new Exception("Event handler must be an Object");
		}

		if (function() { if(isset($this->_events[$eventType])) {$priorityQueue = $this->_events[$eventType]; return $priorityQueue; } else { return false; } }())
		{
			if (typeof($priorityQueue) == "object")
			{
				$newPriorityQueue = new SplPriorityQueue();

				$newPriorityQueue->setExtractFlags(SplPriorityQueue::EXTR_DATA);

				$priorityQueue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

				$priorityQueue->top();

				while ($priorityQueue->valid()) {
					$data = $priorityQueue->current();
					$priorityQueue->next();
					if ($data["data"] !== $handler)
					{
						$newPriorityQueue->insert($data["data"], $data["priority"]);

					}
				}

				$this[$eventType] = $newPriorityQueue;

			}

		}

	}

	public function enablePriorities($enablePriorities)
	{
		$this->_enablePriorities = $enablePriorities;

	}

	public function arePrioritiesEnabled()
	{
		return $this->_enablePriorities;
	}

	public function collectResponses($collect)
	{
		$this->_collect = $collect;

	}

	public function isCollecting()
	{
		return $this->_collect;
	}

	public function getResponses()
	{
		return $this->_responses;
	}

	public function detachAll($type = null)
	{
		if ($type === null)
		{
			$this->_events = null;

		}

	}

	public final function fireQueue($queue, $event)
	{


		if (typeof($queue) <> "array")
		{
			if (typeof($queue) == "object")
			{
				if (!($queue instanceof $SplPriorityQueue))
				{
					throw new Exception(sprintf("Unexpected value type: expected object of type SplPriorityQueue, %s given", get_class($queue)));
				}

			}

		}

		$status = null;
		$arguments = null;

		$eventName = $event->getType();

		if (typeof($eventName) <> "string")
		{
			throw new Exception("The event type not valid");
		}

		$source = $event->getSource();

		$data = $event->getData();

		$cancelable = (bool) $event->isCancelable();

		$collect = (bool) $this->_collect;

		if (typeof($queue) == "object")
		{
			$iterator = clone $queue;

			$iterator->top();

			while ($iterator->valid()) {
				$handler = $iterator->current();
				$iterator->next();
				if (typeof($handler) == "object")
				{
					if ($handler instanceof $\Closure)
					{
						if ($arguments === null)
						{
							$arguments = [$event, $source, $data];

						}

						$status = call_user_func_array($handler, $arguments);

						if ($collect)
						{
							$this->_responses[] = $status;

						}

						if ($cancelable)
						{
							if ($event->isStopped())
							{
								break;

							}

						}

					}

				}
			}

		}

		return $status;
	}

	public function fire($eventType, $source, $data = null, $cancelable = true)
	{

		$events = $this->_events;

		if (typeof($events) <> "array")
		{
			return null;
		}

		if (!(memstr($eventType, ":")))
		{
			throw new Exception("Invalid event type " . $eventType);
		}

		$eventParts = explode(":", $eventType);
		$type = $eventParts[0];
		$eventName = $eventParts[1];

		$status = null;

		if ($this->_collect)
		{
			$this->_responses = null;

		}

		$event = null;

		if (function() { if(isset($events[$type])) {$fireEvents = $events[$type]; return $fireEvents; } else { return false; } }())
		{
			if (typeof($fireEvents) == "object" || typeof($fireEvents) == "array")
			{
				$event = new Event($eventName, $source, $data, $cancelable);

				$status = $this->fireQueue($fireEvents, $event);

			}

		}

		if (function() { if(isset($events[$eventType])) {$fireEvents = $events[$eventType]; return $fireEvents; } else { return false; } }())
		{
			if (typeof($fireEvents) == "object" || typeof($fireEvents) == "array")
			{
				if ($event === null)
				{
					$event = new Event($eventName, $source, $data, $cancelable);

				}

				$status = $this->fireQueue($fireEvents, $event);

			}

		}

		return $status;
	}

	public function hasListeners($type)
	{
		return isset($this->_events[$type]);
	}

	public function getListeners($type)
	{

		$events = $this->_events;

		if (typeof($events) == "array")
		{
			if (function() { if(isset($events[$type])) {$fireEvents = $events[$type]; return $fireEvents; } else { return false; } }())
			{
				return $fireEvents;
			}

		}

		return [];
	}


}