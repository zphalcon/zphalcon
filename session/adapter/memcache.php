<?php
namespace Phalcon\Session\Adapter;

use Phalcon\Session\Adapter;
use Phalcon\Cache\Backend\Memcache;
use Phalcon\Cache\Frontend\Data as FrontendData;

class Memcache extends Adapter
{
	protected $_memcache = null;
	protected $_lifetime = 8600;

	public function __construct($options = [])
	{

		if (!(isset($options["host"])))
		{
			$options["host"] = "127.0.0.1";

		}

		if (!(isset($options["port"])))
		{
			$options["port"] = 11211;

		}

		if (!(isset($options["persistent"])))
		{
			$options["persistent"] = 0;

		}

		if (function() { if(isset($options["lifetime"])) {$lifetime = $options["lifetime"]; return $lifetime; } else { return false; } }())
		{
			$this->_lifetime = $lifetime;

		}

		$this->_memcache = new Memcache(new FrontendData(["lifetime" => $this->_lifetime]), $options);

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
		return (string) $this->_memcache->get($sessionId, $this->_lifetime);
	}

	public function write($sessionId, $data)
	{
		return $this->_memcache->save($sessionId, $data, $this->_lifetime);
	}

	public function destroy($sessionId = null)
	{

		if ($sessionId === null)
		{
			$id = $this->getId();

		}

		$this->removeSessionData();

		if (!(empty($id)) && $this->_memcache->exists($id))
		{
			return (bool) $this->_memcache->delete($id);
		}

		return true;
	}

	public function gc()
	{
		return true;
	}


}