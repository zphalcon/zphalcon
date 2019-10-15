<?php
namespace Phalcon\Events;


class Event implements EventInterface
{
	protected $_type;
	protected $_source;
	protected $_data;
	protected $_stopped = false;
	protected $_cancelable = true;

	public function __construct($type, $source, $data = null, $cancelable = true)
	{
		$this->_type = $type;
		$this->_source = $source;

		if ($data !== null)
		{
			$this->_data = $data;

		}

		if ($cancelable !== true)
		{
			$this->_cancelable = $cancelable;

		}

	}

	public function setData($data = null)
	{
		$this->_data = $data;

		return $this;
	}

	public function setType($type)
	{
		$this->_type = $type;

		return $this;
	}

	public function stop()
	{
		if (!($this->_cancelable))
		{
			throw new Exception("Trying to cancel a non-cancelable event");
		}

		$this->_stopped = true;

		return $this;
	}

	public function isStopped()
	{
		return $this->_stopped;
	}

	public function isCancelable()
	{
		return $this->_cancelable;
	}


}