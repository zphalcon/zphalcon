<?php
namespace Phalcon\Cache;

use Phalcon\Cache\Exception;
use Phalcon\Cache\BackendInterface;

class Multiple
{
	protected $_backends;

	public function __construct($backends = null)
	{
		if (typeof($backends) <> "null")
		{
			if (typeof($backends) <> "array")
			{
				throw new Exception("The backends must be an array");
			}

			$this->_backends = $backends;

		}

	}

	public function push($backend)
	{
		$this->_backends[] = $backend;

		return $this;
	}

	public function get($keyName, $lifetime = null)
	{

		foreach ($this->_backends as $backend) {
			$content = $backend->get($keyName, $lifetime);
			if ($content <> null)
			{
				return $content;
			}
		}

		return null;
	}

	public function start($keyName, $lifetime = null)
	{

		foreach ($this->_backends as $backend) {
			$backend->start($keyName, $lifetime);
		}

	}

	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
	{

		foreach ($this->_backends as $backend) {
			$backend->save($keyName, $content, $lifetime, $stopBuffer);
		}

	}

	public function delete($keyName)
	{

		foreach ($this->_backends as $backend) {
			$backend->delete($keyName);
		}

		return true;
	}

	public function exists($keyName = null, $lifetime = null)
	{

		foreach ($this->_backends as $backend) {
			if ($backend->exists($keyName, $lifetime) == true)
			{
				return true;
			}
		}

		return false;
	}

	public function flush()
	{

		foreach ($this->_backends as $backend) {
			$backend->flush();
		}

		return true;
	}


}