<?php
namespace Phalcon\Cache\Frontend;

use Phalcon\Cache\FrontendInterface;

class None implements FrontendInterface
{
	public function getLifetime()
	{
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
	}

	public function stop()
	{
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