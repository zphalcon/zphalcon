<?php
namespace Phalcon\Session;

abstract 
class Adapter implements AdapterInterface
{
	const SESSION_ACTIVE = 2;
	const SESSION_NONE = 1;
	const SESSION_DISABLED = 0;

	protected $_uniqueId;
	protected $_started = false;
	protected $_options;

	public function __construct($options = null)
	{
		if (typeof($options) == "array")
		{
			$this->setOptions($options);

		}

	}

	public function start()
	{
		if (!(headers_sent()))
		{
			if (!($this->_started) && $this->status() !== self::SESSION_ACTIVE)
			{
				session_start();

				$this->_started = true;

				return true;
			}

		}

		return false;
	}

	public function setOptions($options)
	{

		if (function() { if(isset($options["uniqueId"])) {$uniqueId = $options["uniqueId"]; return $uniqueId; } else { return false; } }())
		{
			$this->_uniqueId = $uniqueId;

		}

		$this->_options = $options;

	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function setName($name)
	{
		session_name($name);

	}

	public function getName()
	{
		return session_name();
	}

	public function regenerateId($deleteOldSession = true)
	{
		session_regenerate_id($deleteOldSession);

		return $this;
	}

	public function get($index, $defaultValue = null, $remove = false)
	{

		$uniqueId = $this->_uniqueId;

		if (!(empty($uniqueId)))
		{
			$key = $uniqueId . "#" . $index;

		}

		if (function() { if(isset($_SESSION[$key])) {$value = $_SESSION[$key]; return $value; } else { return false; } }())
		{
			if ($remove)
			{
				unset($_SESSION[$key]);

			}

			return $value;
		}

		return $defaultValue;
	}

	public function set($index, $value)
	{

		$uniqueId = $this->_uniqueId;

		if (!(empty($uniqueId)))
		{
			$_SESSION[$uniqueId . "#" . $index] = $value;

			return ;
		}

		$_SESSION[$index] = $value;

	}

	public function has($index)
	{

		$uniqueId = $this->_uniqueId;

		if (!(empty($uniqueId)))
		{
			return isset($_SESSION[$uniqueId . "#" . $index]);
		}

		return isset($_SESSION[$index]);
	}

	public function remove($index)
	{

		$uniqueId = $this->_uniqueId;

		if (!(empty($uniqueId)))
		{
			unset($_SESSION[$uniqueId . "#" . $index]);

			return ;
		}

		unset($_SESSION[$index]);

	}

	public function getId()
	{
		return session_id();
	}

	public function setId($id)
	{
		session_id($id);

	}

	public function isStarted()
	{
		return $this->_started;
	}

	public function destroy($removeData = null)
	{
		if ($removeData && $removeData !== null)
		{
			$this->removeSessionData();

		}

		$this->_started = false;

		return session_destroy();
	}

	public function status()
	{

		$status = session_status();

		switch ($status) {
			case PHP_SESSION_DISABLED:
				return self::SESSION_DISABLED;			case PHP_SESSION_ACTIVE:
				return self::SESSION_ACTIVE;
		}

		return self::SESSION_NONE;
	}

	public function __get($index)
	{
		return $this->get($index);
	}

	public function __set($index, $value)
	{
		return $this->set($index, $value);
	}

	public function __isset($index)
	{
		return $this->has($index);
	}

	public function __unset($index)
	{
		$this->remove($index);

	}

	public function __destruct()
	{
		if ($this->_started)
		{
			session_write_close();

			$this->_started = false;

		}

	}

	protected function removeSessionData()
	{

		$uniqueId = $this->_uniqueId;

		if (empty($_SESSION))
		{
			return ;
		}

		if (!(empty($uniqueId)))
		{
			foreach ($_SESSION as $key => $_) {
				if (starts_with($key, $uniqueId . "#"))
				{
					unset($_SESSION[$key]);

				}
			}

		}

	}


}