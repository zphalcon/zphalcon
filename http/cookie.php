<?php
namespace Phalcon\Http;

use Phalcon\DiInterface;
use Phalcon\CryptInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Http\Response\Exception;
use Phalcon\Http\Cookie\Exception as CookieException;
use Phalcon\Crypt\Mismatch;
use Phalcon\Session\AdapterInterface as SessionInterface;

class Cookie implements CookieInterface, InjectionAwareInterface
{
	protected $_readed = false;
	protected $_restored = false;
	protected $_useEncryption = false;
	protected $_dependencyInjector;
	protected $_filter;
	protected $_name;
	protected $_value;
	protected $_expire;
	protected $_path = "/";
	protected $_domain;
	protected $_secure;
	protected $_httpOnly = true;
	protected $signKey = null;

	public function __construct($name, $value = null, $expire = 0, $path = "/", $secure = null, $domain = null, $httpOnly = null)
	{
		$this->_name = $name;

		if ($value !== null)
		{
			$this->setValue($value);

		}

		$this->_expire = $expire;

		if ($path !== null)
		{
			$this->_path = $path;

		}

		if ($secure !== null)
		{
			$this->_secure = $secure;

		}

		if ($domain !== null)
		{
			$this->_domain = $domain;

		}

		if ($httpOnly !== null)
		{
			$this->_httpOnly = $httpOnly;

		}

	}

	public function setSignKey($signKey = null)
	{
		if ($signKey !== null)
		{
			$this->assertSignKeyIsLongEnough($signKey);

		}

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

	public function setValue($value)
	{
		$this->_value = $value;
		$this->_readed = true;

		return $this;
	}

	public function getValue($filters = null, $defaultValue = null)
	{

		if (!($this->_restored))
		{
			$this->restore();

		}

		$dependencyInjector = null;
		$name = $this->_name;

		if ($this->_readed === false)
		{
			if (function() { if(isset($_COOKIE[$name])) {$value = $_COOKIE[$name]; return $value; } else { return false; } }())
			{
				if ($this->_useEncryption)
				{
					$dependencyInjector = $this->_dependencyInjector;

					if (typeof($dependencyInjector) <> "object")
					{
						throw new Exception("A dependency injection object is required to access the 'filter' and 'crypt' service");
					}

					$crypt = $dependencyInjector->getShared("crypt");

					if (typeof($crypt) <> "object")
					{
						throw new Exception("A dependency which implements CryptInterface is required to use encryption");
					}

					$signKey = $this->signKey;

					if (typeof($signKey) === "string")
					{
						$decryptedValue = $crypt->decryptBase64($value, $signKey);

					}

				}

				$this->_value = $decryptedValue;

				if ($filters !== null)
				{
					$filter = $this->_filter;

					if (typeof($filter) <> "object")
					{
						if ($dependencyInjector === null)
						{
							$dependencyInjector = $this->_dependencyInjector;

							if (typeof($dependencyInjector) <> "object")
							{
								throw new Exception("A dependency injection object is required to access the 'filter' service");
							}

						}

						$filter = $dependencyInjector->getShared("filter");
						$this->_filter = $filter;

					}

					return $filter->sanitize($decryptedValue, $filters);
				}

				return $decryptedValue;
			}

			return $defaultValue;
		}

		return $this->_value;
	}

	public function send()
	{

		$name = $this->_name;
		$value = $this->_value;
		$expire = $this->_expire;
		$domain = $this->_domain;
		$path = $this->_path;
		$secure = $this->_secure;
		$httpOnly = $this->_httpOnly;

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection object is required to access the 'session' service");
		}

		$definition = [];

		if ($expire <> 0)
		{
			$definition["expire"] = $expire;

		}

		if (!(empty($path)))
		{
			$definition["path"] = $path;

		}

		if (!(empty($domain)))
		{
			$definition["domain"] = $domain;

		}

		if (!(empty($secure)))
		{
			$definition["secure"] = $secure;

		}

		if (!(empty($httpOnly)))
		{
			$definition["httpOnly"] = $httpOnly;

		}

		if (count($definition))
		{
			$session = $dependencyInjector->getShared("session");

			if ($session->isStarted())
			{
				$session->set("_PHCOOKIE_" . $name, $definition);

			}

		}

		if ($this->_useEncryption)
		{
			if (!(empty($value)))
			{
				if (typeof($dependencyInjector) <> "object")
				{
					throw new Exception("A dependency injection object is required to access the 'filter' service");
				}

				$crypt = $dependencyInjector->getShared("crypt");

				if (typeof($crypt) <> "object")
				{
					throw new Exception("A dependency which implements CryptInterface is required to use encryption");
				}

				$signKey = $this->signKey;

				if (typeof($signKey) === "string")
				{
					$encryptValue = $crypt->encryptBase64((string) $value, $signKey);

				}

			}

		}

		setcookie($name, $encryptValue, $expire, $path, $domain, $secure, $httpOnly);

		return $this;
	}

	public function restore()
	{

		if (!($this->_restored))
		{
			$dependencyInjector = $this->_dependencyInjector;

			if (typeof($dependencyInjector) == "object")
			{
				$session = $dependencyInjector->getShared("session");

				if ($session->isStarted())
				{
					$definition = $session->get("_PHCOOKIE_" . $this->_name);

					if (typeof($definition) == "array")
					{
						if (function() { if(isset($definition["expire"])) {$expire = $definition["expire"]; return $expire; } else { return false; } }())
						{
							$this->_expire = $expire;

						}

						if (function() { if(isset($definition["domain"])) {$domain = $definition["domain"]; return $domain; } else { return false; } }())
						{
							$this->_domain = $domain;

						}

						if (function() { if(isset($definition["path"])) {$path = $definition["path"]; return $path; } else { return false; } }())
						{
							$this->_path = $path;

						}

						if (function() { if(isset($definition["secure"])) {$secure = $definition["secure"]; return $secure; } else { return false; } }())
						{
							$this->_secure = $secure;

						}

						if (function() { if(isset($definition["httpOnly"])) {$httpOnly = $definition["httpOnly"]; return $httpOnly; } else { return false; } }())
						{
							$this->_httpOnly = $httpOnly;

						}

					}

				}

			}

			$this->_restored = true;

		}

		return $this;
	}

	public function delete()
	{

		$name = $this->_name;
		$domain = $this->_domain;
		$path = $this->_path;
		$secure = $this->_secure;
		$httpOnly = $this->_httpOnly;

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) == "object")
		{
			$session = $dependencyInjector->getShared("session");

			if ($session->isStarted())
			{
				$session->remove("_PHCOOKIE_" . $name);

			}

		}

		$this->_value = null;

		setcookie($name, null, time() - 691200, $path, $domain, $secure, $httpOnly);

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

	public function setExpiration($expire)
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		$this->_expire = $expire;

		return $this;
	}

	public function getExpiration()
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		return $this->_expire;
	}

	public function setPath($path)
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		$this->_path = $path;

		return $this;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function getPath()
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		return $this->_path;
	}

	public function setDomain($domain)
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		$this->_domain = $domain;

		return $this;
	}

	public function getDomain()
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		return $this->_domain;
	}

	public function setSecure($secure)
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		$this->_secure = $secure;

		return $this;
	}

	public function getSecure()
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		return $this->_secure;
	}

	public function setHttpOnly($httpOnly)
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		$this->_httpOnly = $httpOnly;

		return $this;
	}

	public function getHttpOnly()
	{
		if (!($this->_restored))
		{
			$this->restore();

		}

		return $this->_httpOnly;
	}

	public function __toString()
	{
		return (string) $this->getValue();
	}

	protected function assertSignKeyIsLongEnough($signKey)
	{

		$length = mb_strlen($signKey);

		if ($length < 32)
		{
			throw new CookieException(sprintf("The cookie's key should be at least 32 characters long. Current length is %d.", $length));
		}

	}


}