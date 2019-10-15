<?php
namespace Phalcon\Cache\Frontend;

use Phalcon\Cache\FrontendInterface;

class Base64 implements FrontendInterface
{
	protected $_frontendOptions;

	public function __construct($frontendOptions = null)
	{
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
		return base64_encode($data);
	}

	public function afterRetrieve($data)
	{
		return base64_decode($data);
	}


}