<?php
namespace Phalcon\Http\Response;

use Phalcon\DiInterface;
use Phalcon\Http\CookieInterface;
use Phalcon\Http\Response\CookiesInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Http\Cookie\Exception;

class Cookies implements CookiesInterface, InjectionAwareInterface
{
	protected $_dependencyInjector;
	protected $_registered = false;
	protected $_useEncryption = true;
	protected $_cookies;
	protected $signKey = null;

	public function __construct($useEncryption = true, $signKey = null)
	{
		$this->_useEncryption = $useEncryption;

		$this->_cookies = [];

		$this->setSignKey($signKey);

	}

	public function setSignKey($signKey = null)
	{
		$this->signKey = $signKey;

		return $this;
	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function useEncryption($useEncryption)
	{
		$this->_useEncryption = $useEncryption;

		return $this;
	}

	public function isUsingEncryption()
	{
		return $this->_useEncryption;
	}

	public function set($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null)
	{

		$encryption = $this->_useEncryption;

		if (!(function() { if(isset($this->_cookies[$name])) {$cookie = $this->_cookies[$name]; return $cookie; } else { return false; } }()))
		{
			$cookie = $this->_dependencyInjector->get("Phalcon\\Http\\Cookie", [$name, $value, $expire, $path, $secure, $domain, $httpOnly]);

			$cookie->setDi($this->_dependencyInjector);

			if ($encryption)
			{
				$cookie->useEncryption($encryption);

				$cookie->setSignKey($this->signKey);

			}

			$this[$name] = $cookie;

		}

		if ($this->_registered === false)
		{
			$dependencyInjector = $this->_dependencyInjector;

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injection object is required to access the 'response' service");
			}

			$response = $dependencyInjector->getShared("response");

			$response->setCookies($this);

			$this->_registered = true;

		}

		return $this;
	}

	public function get($name)
	{

		if (function() { if(isset($this->_cookies[$name])) {$cookie = $this->_cookies[$name]; return $cookie; } else { return false; } }())
		{
			return $cookie;
		}

		$cookie = $this->_dependencyInjector->get("Phalcon\\Http\\Cookie", [$name]);
		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) == "object")
		{
			$cookie->setDi($dependencyInjector);

			$encryption = $this->_useEncryption;

			if ($encryption)
			{
				$cookie->useEncryption($encryption);

				$cookie->setSignKey($this->signKey);

			}

		}

		return $cookie;
	}

	public function has($name)
	{
		if (isset($this->_cookies[$name]))
		{
			return true;
		}

		if (isset($_COOKIE[$name]))
		{
			return true;
		}

		return false;
	}

	public function delete($name)
	{

		if (function() { if(isset($this->_cookies[$name])) {$cookie = $this->_cookies[$name]; return $cookie; } else { return false; } }())
		{
			$cookie->delete();

			return true;
		}

		return false;
	}

	public function send()
	{

		if (!(headers_sent()))
		{
			foreach ($this->_cookies as $cookie) {
				$cookie->send();
			}

			return true;
		}

		return false;
	}

	public function reset()
	{
		$this->_cookies = [];

		return $this;
	}


}