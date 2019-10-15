<?php
namespace Phalcon\Queue\Beanstalk;

use Phalcon\Queue\Beanstalk;
use Phalcon\Queue\Beanstalk\Exception;

class Job
{
	protected $_id;
	protected $_body;
	protected $_queue;

	public function __construct($queue, $id, $body)
	{
		$this->_queue = $queue;

		$this->_id = $id;

		$this->_body = $body;

	}

	public function delete()
	{

		$queue = $this->_queue;

		$queue->write("delete " . $this->_id);

		return $queue->readStatus()[0] == "DELETED";
	}

	public function release($priority = 100, $delay = 0)
	{

		$queue = $this->_queue;

		$queue->write("release " . $this->_id . " " . $priority . " " . $delay);

		return $queue->readStatus()[0] == "RELEASED";
	}

	public function bury($priority = 100)
	{

		$queue = $this->_queue;

		$queue->write("bury " . $this->_id . " " . $priority);

		return $queue->readStatus()[0] == "BURIED";
	}

	public function touch()
	{

		$queue = $this->_queue;

		$queue->write("touch " . $this->_id);

		return $queue->readStatus()[0] == "TOUCHED";
	}

	public function kick()
	{

		$queue = $this->_queue;

		$queue->write("kick-job " . $this->_id);

		return $queue->readStatus()[0] == "KICKED";
	}

	public function stats()
	{

		$queue = $this->_queue;

		$queue->write("stats-job " . $this->_id);

		$response = $queue->readYaml();

		if ($response[0] == "NOT_FOUND")
		{
			return false;
		}

		return $response[2];
	}

	public function __wakeup()
	{
		if (typeof($this->_id) <> "string")
		{
			throw new Exception("Unexpected inconsistency in Phalcon\\Queue\\Beanstalk\\Job::__wakeup() - possible break-in attempt!");
		}

	}


}