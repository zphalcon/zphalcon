<?php
namespace Phalcon;

use Phalcon\DiInterface;
use Phalcon\Security\Random;
use Phalcon\Security\Exception;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Session\AdapterInterface as SessionInterface;

class Security implements InjectionAwareInterface
{
	const CRYPT_DEFAULT = 0;
	const CRYPT_STD_DES = 1;
	const CRYPT_EXT_DES = 2;
	const CRYPT_MD5 = 3;
	const CRYPT_BLOWFISH = 4;
	const CRYPT_BLOWFISH_A = 5;
	const CRYPT_BLOWFISH_X = 6;
	const CRYPT_BLOWFISH_Y = 7;
	const CRYPT_SHA256 = 8;
	const CRYPT_SHA512 = 9;

	protected $_dependencyInjector;
	protected $_workFactor = 8;
	protected $_numberBytes = 16;
	protected $_tokenKeySessionID = "$PHALCON/CSRF/KEY$";
	protected $_tokenValueSessionID = "$PHALCON/CSRF$";
	protected $_token;
	protected $_tokenKey;
	protected $_random;
	protected $_defaultHash;

	public function __construct()
	{
		$this->_random = new Random();

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function setRandomBytes($randomBytes)
	{
		$this->_numberBytes = $randomBytes;

		return $this;
	}

	public function getRandomBytes()
	{
		return $this->_numberBytes;
	}

	public function getRandom()
	{
		return $this->_random;
	}

	public function getSaltBytes($numberBytes = 0)
	{

		if (!($numberBytes))
		{
			$numberBytes = (int) $this->_numberBytes;

		}

		while (true) {
			$safeBytes = $this->_random->base64Safe($numberBytes);
			if (!($safeBytes) || strlen($safeBytes) < $numberBytes)
			{
				continue;

			}
			break;
		}

		return $safeBytes;
	}

	public function hash($password, $workFactor = 0)
	{



		if (!($workFactor))
		{
			$workFactor = (int) $this->_workFactor;

		}

		$hash = (int) $this->_defaultHash;

		switch ($hash) {
			case self::CRYPT_BLOWFISH_A:
				$variant = "a";
				break;
			case self::CRYPT_BLOWFISH_X:
				$variant = "x";
				break;
			case self::CRYPT_BLOWFISH_Y:
				$variant = "y";
				break;
			case self::CRYPT_MD5:
				$variant = "1";
				break;
			case self::CRYPT_SHA256:
				$variant = "5";
				break;
			case self::CRYPT_SHA512:
				$variant = "6";
				break;
			case self::CRYPT_DEFAULT:
			default:
				$variant = "y";
				break;

		}

		switch ($hash) {
			case self::CRYPT_STD_DES:
			case self::CRYPT_EXT_DES:
				if ($hash == self::CRYPT_EXT_DES)
				{
					$saltBytes = "_" . $this->getSaltBytes(8);

				}
				if (typeof($saltBytes) <> "string")
				{
					throw new Exception("Unable to get random bytes for the salt");
				}
				return crypt($password, $saltBytes);			case self::CRYPT_MD5:
			case self::CRYPT_SHA256:
			case self::CRYPT_SHA512:
				$saltBytes = $this->getSaltBytes($hash == self::CRYPT_MD5 ? 12 : 16);
				if (typeof($saltBytes) <> "string")
				{
					throw new Exception("Unable to get random bytes for the salt");
				}
				return crypt($password, "$" . $variant . "$" . $saltBytes . "$");			case self::CRYPT_DEFAULT:
			case self::CRYPT_BLOWFISH:
			case self::CRYPT_BLOWFISH_X:
			case self::CRYPT_BLOWFISH_Y:
			default:
				$saltBytes = $this->getSaltBytes(22);
				if (typeof($saltBytes) <> "string")
				{
					throw new Exception("Unable to get random bytes for the salt");
				}
				if ($workFactor < 4)
				{
					$workFactor = 4;

				}
				return crypt($password, "$2" . $variant . "$" . sprintf("%02s", $workFactor) . "$" . $saltBytes . "$");
		}

		return "";
	}

	public function checkHash($password, $passwordHash, $maxPassLength = 0)
	{



		if ($maxPassLength)
		{
			if ($maxPassLength > 0 && strlen($password) > $maxPassLength)
			{
				return false;
			}

		}

		$cryptedHash = (string) crypt($password, $passwordHash);

		$cryptedLength = strlen($cryptedHash);
		$passwordLength = strlen($passwordHash);

		$cryptedHash .= $passwordHash;

		$sum = $cryptedLength - $passwordLength;

		foreach ($passwordHash as $i => $ch) {
			$sum = $sum | $cryptedHash[$i] ^ $ch;
		}

		return 0 === $sum;
	}

	public function isLegacyHash($passwordHash)
	{
		return starts_with($passwordHash, "$2a$");
	}

	public function getTokenKey()
	{

		if (null === $this->_tokenKey)
		{
			$dependencyInjector = $this->_dependencyInjector;

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injection container is required to access the 'session' service");
			}

			$this->_tokenKey = $this->_random->base64Safe($this->_numberBytes);

			$session = $dependencyInjector->getShared("session");

			$session->set($this->_tokenKeySessionID, $this->_tokenKey);

		}

		return $this->_tokenKey;
	}

	public function getToken()
	{

		if (null === $this->_token)
		{
			$this->_token = $this->_random->base64Safe($this->_numberBytes);

			$dependencyInjector = $this->_dependencyInjector;

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injection container is required to access the 'session' service");
			}

			$session = $dependencyInjector->getShared("session");

			$session->set($this->_tokenValueSessionID, $this->_token);

		}

		return $this->_token;
	}

	public function checkToken($tokenKey = null, $tokenValue = null, $destroyIfValid = true)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection container is required to access the 'session' service");
		}

		$session = $dependencyInjector->getShared("session");

		if (!($tokenKey))
		{
			$tokenKey = $session->get($this->_tokenKeySessionID);

		}

		if (!($tokenKey))
		{
			return false;
		}

		if (!($tokenValue))
		{
			$request = $dependencyInjector->getShared("request");

			$userToken = $request->getPost($tokenKey);

		}

		$knownToken = $session->get($this->_tokenValueSessionID);

		$equals = hash_equals($knownToken, $userToken);

		if ($equals && $destroyIfValid)
		{
			$this->destroyToken();

		}

		return $equals;
	}

	public function getSessionToken()
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection container is required to access the 'session' service");
		}

		$session = $dependencyInjector->getShared("session");

		return $session->get($this->_tokenValueSessionID);
	}

	public function destroyToken()
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection container is required to access the 'session' service");
		}

		$session = $dependencyInjector->getShared("session");

		$session->remove($this->_tokenKeySessionID);

		$session->remove($this->_tokenValueSessionID);

		$this->_token = null;

		$this->_tokenKey = null;

		return $this;
	}

	public function computeHmac($data, $key, $algo, $raw = false)
	{

		$hmac = hash_hmac($algo, $data, $key, $raw);

		if (!($hmac))
		{
			throw new Exception("Unknown hashing algorithm: %s" . $algo);
		}

		return $hmac;
	}

	public function setDefaultHash($defaultHash)
	{
		$this->_defaultHash = $defaultHash;

		return $this;
	}

	public function getDefaultHash()
	{
		return $this->_defaultHash;
	}

	public function hasLibreSsl()
	{
		if (!(defined("OPENSSL_VERSION_TEXT")))
		{
			return false;
		}

		return strpos(OPENSSL_VERSION_TEXT, "LibreSSL") === 0;
	}

	public function getSslVersionNumber()
	{

		if (!(defined("OPENSSL_VERSION_TEXT")))
		{
			return 0;
		}

		preg_match("#(?:Libre|Open)SSL ([\d]+)\.([\d]+)(?:\.([\d]+))?#", OPENSSL_VERSION_TEXT, $matches);

		if (!(isset($matches[2])))
		{
			return 0;
		}

		$major = (int) $matches[1];
		$minor = (int) $matches[2];

		if (isset($matches[3]))
		{
			$patch = (int) $matches[3];

		}

		return 10000 * $major + 100 * $minor + $patch;
	}


}