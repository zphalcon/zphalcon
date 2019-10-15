<?php
namespace Phalcon\Session\Adapter;

use Phalcon\Session\Adapter;
use Phalcon\Cache\Backend\Redis;
use Phalcon\Cache\Frontend\None as FrontendNone;

class Redis extends Adapter
{
	protected $_redis = null;
	protected $_lifetime = 8600;

	public function __construct($options = [])
	{

		if (!(isset($options["host"])))
		{
			$options["host"] = "127.0.0.1";

		}

		if (!(isset($options["port"])))
		{
			$options["port"] = 6379;

		}

		if (!(isset($options["persistent"])))
		{
			$options["persistent"] = false;

		}

		if (function() { if(isset($options["lifetime"])) {$lifetime = $options["lifetime"]; return $lifetime; } else { return false; } }())
		{
			$this->_lifetime = $lifetime;

		}

		$this->_redis = new Redis(new FrontendNone(["lifetime" => $this->_lifetime]), $options);

		session_set_save_handler([$this, "open"], [$this, "close"], [$this, "read"], [$this, "write"], [$this, "destroy"], [$this, "gc"]);

		parent::__construct($options);

	}

	public function open()
	{
		return true;
	}

	public function close()
	{
		return true;
	}

	public function read($sessionId)
	{
		return (string) $this->_redis->get($sessionId, $this->_lifetime);
	}

	public function write($sessionId, $data)
	{
		return $this->_redis->save($sessionId, $data, $this->_lifetime);
	}

	public function destroy($sessionId = null)
	{

		if ($sessionId === null)
		{
			$id = $this->getId();

		}

		$this->removeSessionData();

		return $this->_redis->exists($id) ? $this->_redis->delete($id) : true;
	}

	public function gc()
	{
		return true;
	}


}