<?php
namespace Phalcon\Cache\Frontend;

use Phalcon\Cache\FrontendInterface;
use Phalcon\Cache\Exception;

class Msgpack extends Data implements FrontendInterface
{
	public function __construct($frontendOptions = null)
	{

		if (function() { if(isset($frontendOptions["lifetime"])) {$lifetime = $frontendOptions["lifetime"]; return $lifetime; } else { return false; } }())
		{
			if (typeof($lifetime) !== "integer")
			{
				throw new Exception("Option 'lifetime' must be an integer");
			}

		}

		$this->_frontendOptions = $frontendOptions;

	}

	public function getLifetime()
	{

		$options = $this->_frontendOptions;

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["lifetime"])) {$lifetime = $options["lifetime"]; return $lifetime; } else { return false; } }())
			{
				return $lifetime;
			}

		}

		return 1;
	}

	public function isBuffering()
	{
		return false;
	}

	public function start()
	{
	}

	public function getContent()
	{
		return null;
	}

	public function stop()
	{
	}

	public function beforeStore($data)
	{
		return msgpack_pack($data);
	}

	public function afterRetrieve($data)
	{
		if (is_numeric($data))
		{
			return $data;
		}

		return msgpack_unpack($data);
	}


}