<?php
namespace Phalcon\Session\Adapter;

use Phalcon\Session\Adapter;
use Phalcon\Session\Exception;
use Phalcon\Cache\Backend\Libmemcached;
use Phalcon\Cache\Frontend\Data as FrontendData;

class Libmemcached extends Adapter
{
	protected $_libmemcached = null;
	protected $_lifetime = 8600;

	public function __construct($options)
	{

		if (!(function() { if(isset($options["servers"])) {$servers = $options["servers"]; return $servers; } else { return false; } }()))
		{
			throw new Exception("No servers given in options");
		}

		if (!(function() { if(isset($options["client"])) {$client = $options["client"]; return $client; } else { return false; } }()))
		{
			$client = null;

		}

		if (!(function() { if(isset($options["lifetime"])) {$lifetime = $options["lifetime"]; return $lifetime; } else { return false; } }()))
		{
			$lifetime = 8600;

		}

		$this->_lifetime = min($lifetime, 2592000);

		if (!(function() { if(isset($options["prefix"])) {$prefix = $options["prefix"]; return $prefix; } else { return false; } }()))
		{
			$prefix = null;

		}

		if (!(function() { if(isset($options["statsKey"])) {$statsKey = $options["statsKey"]; return $statsKey; } else { return false; } }()))
		{
			$statsKey = "";

		}

		if (!(function() { if(isset($options["persistent_id"])) {$persistentId = $options["persistent_id"]; return $persistentId; } else { return false; } }()))
		{
			$persistentId = "phalcon-session";

		}

		$this->_libmemcached = new Libmemcached(new FrontendData(["lifetime" => $this->_lifetime]), ["servers" => $servers, "client" => $client, "prefix" => $prefix, "statsKey" => $statsKey, "persistent_id" => $persistentId]);

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
		return (string) $this->_libmemcached->get($sessionId, $this->_lifetime);
	}

	public function write($sessionId, $data)
	{
		return $this->_libmemcached->save($sessionId, $data, $this->_lifetime);
	}

	public function destroy($sessionId = null)
	{

		if ($sessionId === null)
		{
			$id = $this->getId();

		}

		$this->removeSessionData();

		if (!(empty($id)) && $this->_libmemcached->exists($id))
		{
			return (bool) $this->_libmemcached->delete($id);
		}

		return true;
	}

	public function gc()
	{
		return true;
	}


}