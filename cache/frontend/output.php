<?php
namespace Phalcon\Cache\Frontend;

use Phalcon\Cache\FrontendInterface;

class Output implements FrontendInterface
{
	protected $_buffering = false;
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
		return $this->_buffering;
	}

	public function start()
	{
		$this->_buffering = true;

		ob_start();

	}

	public function getContent()
	{
		if ($this->_buffering)
		{
			return ob_get_contents();
		}

		return null;
	}

	public function stop()
	{
		if ($this->_buffering)
		{
			ob_end_clean();

		}

		$this->_buffering = false;

	}

	public function beforeStore($data)
	{
		return $data;
	}

	public function afterRetrieve($data)
	{
		return $data;
	}


}