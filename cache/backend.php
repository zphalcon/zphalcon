<?php
namespace Phalcon\Cache;

use Phalcon\Cache\FrontendInterface;
abstract 
class Backend implements BackendInterface
{
	protected $_frontend;
	protected $_options;
	protected $_prefix = "";
	protected $_lastKey = "";
	protected $_lastLifetime = null;
	protected $_fresh = false;
	protected $_started = false;

	public function __construct($frontend, $options = null)
	{

		if (function() { if(isset($options["prefix"])) {$prefix = $options["prefix"]; return $prefix; } else { return false; } }())
		{
			$this->_prefix = $prefix;

		}

		$this->_frontend = $frontend;
		$this->_options = $options;

	}

	public function start($keyName, $lifetime = null)
	{

		$existingCache = $this->get($keyName, $lifetime);

		if ($existingCache === null)
		{
			$fresh = true;

			$this->_frontend->start();

		}

		$this->_fresh = $fresh;
		$this->_started = true;

		if (typeof($lifetime) <> "null")
		{
			$this->_lastLifetime = $lifetime;

		}

		return $existingCache;
	}

	public function stop($stopBuffer = true)
	{
		if ($stopBuffer === true)
		{
			$this->_frontend->stop();

		}

		$this->_started = false;

	}

	public function isFresh()
	{
		return $this->_fresh;
	}

	public function isStarted()
	{
		return $this->_started;
	}

	public function getLifetime()
	{
		return $this->_lastLifetime;
	}


}