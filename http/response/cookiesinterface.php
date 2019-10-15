<?php
namespace Phalcon\Http\Response;


interface CookiesInterface
{
	public function useEncryption($useEncryption)
	{
	}

	public function isUsingEncryption()
	{
	}

	public function set($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null)
	{
	}

	public function get($name)
	{
	}

	public function has($name)
	{
	}

	public function delete($name)
	{
	}

	public function send()
	{
	}

	public function reset()
	{
	}


}