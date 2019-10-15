<?php
namespace Phalcon\Http;

use Phalcon\DiInterface;
use Phalcon\FilterInterface;
use Phalcon\Http\Request\File;
use Phalcon\Http\Request\Exception;
use Phalcon\Events\ManagerInterface;
use Phalcon\Di\InjectionAwareInterface;

class Request implements RequestInterface, InjectionAwareInterface
{
	protected $_dependencyInjector;
	protected $_rawBody;
	protected $_filter;
	protected $_putCache;
	protected $_httpMethodParameterOverride = false;
	protected $_strictHostCheck = false;

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function get($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_REQUEST, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	public function getPost($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_POST, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	public function getPut($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{

		$put = $this->_putCache;

		if (typeof($put) <> "array")
		{
			$contentType = $this->getContentType();

			if (typeof($contentType) == "string" && stripos($contentType, "json") <> false)
			{
				$put = $this->getJsonRawBody(true);

				if (typeof($put) <> "array")
				{
					$put = [];

				}

			}

			$this->_putCache = $put;

		}

		return $this->getHelper($put, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	public function getQuery($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		return $this->getHelper($_GET, $name, $filters, $defaultValue, $notAllowEmpty, $noRecursive);
	}

	protected final function getHelper($source, $name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{

		if ($name === null)
		{
			return $source;
		}

		if (!(function() { if(isset($source[$name])) {$value = $source[$name]; return $value; } else { return false; } }()))
		{
			return $defaultValue;
		}

		if ($filters !== null)
		{
			$filter = $this->_filter;

			if (typeof($filter) <> "object")
			{
				$dependencyInjector = $this->_dependencyInjector;

				if (typeof($dependencyInjector) <> "object")
				{
					throw new Exception("A dependency injection object is required to access the 'filter' service");
				}

				$filter = $dependencyInjector->getShared("filter");

				$this->_filter = $filter;

			}

			$value = $filter->sanitize($value, $filters, $noRecursive);

		}

		if (empty($value) && $notAllowEmpty === true)
		{
			return $defaultValue;
		}

		return $value;
	}

	public function getServer($name)
	{

		if (function() { if(isset($_SERVER[$name])) {$serverValue = $_SERVER[$name]; return $serverValue; } else { return false; } }())
		{
			return $serverValue;
		}

		return null;
	}

	public function has($name)
	{
		return isset($_REQUEST[$name]);
	}

	public function hasPost($name)
	{
		return isset($_POST[$name]);
	}

	public function hasPut($name)
	{

		$put = $this->getPut();

		return isset($put[$name]);
	}

	public function hasQuery($name)
	{
		return isset($_GET[$name]);
	}

	public final function hasServer($name)
	{
		return isset($_SERVER[$name]);
	}

	public final function hasHeader($header)
	{

		$name = strtoupper(strtr($header, "-", "_"));

		if (isset($_SERVER[$name]))
		{
			return true;
		}

		if (isset($_SERVER["HTTP_" . $name]))
		{
			return true;
		}

		return false;
	}

	public final function getHeader($header)
	{

		$name = strtoupper(strtr($header, "-", "_"));

		if (function() { if(isset($_SERVER[$name])) {$value = $_SERVER[$name]; return $value; } else { return false; } }())
		{
			return $value;
		}

		if (function() { if(isset($_SERVER["HTTP_" . $name])) {$value = $_SERVER["HTTP_" . $name]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return "";
	}

	public function getScheme()
	{

		$https = $this->getServer("HTTPS");

		if ($https)
		{
			if ($https == "off")
			{
				$scheme = "http";

			}

		}

		return $scheme;
	}

	public function isAjax()
	{
		return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
	}

	public function isSoap()
	{

		if (isset($_SERVER["HTTP_SOAPACTION"]))
		{
			return true;
		}

		return false;
	}

	deprecated public function isSoapRequested()
	{
		return $this->isSoap();
	}

	public function isSecure()
	{
		return $this->getScheme() === "https";
	}

	deprecated public function isSecureRequest()
	{
		return $this->isSecure();
	}

	public function getRawBody()
	{

		$rawBody = $this->_rawBody;

		if (empty($rawBody))
		{
			$contents = file_get_contents("php://input");

			$this->_rawBody = $contents;

			return $contents;
		}

		return $rawBody;
	}

	public function getJsonRawBody($associative = false)
	{

		$rawBody = $this->getRawBody();

		if (empty($rawBody))
		{
			return false;
		}

		$data = json_decode($rawBody, $associative);

		if (json_last_error() !== JSON_ERROR_NONE)
		{
			return false;
		}

		return $data;
	}

	public function getServerAddress()
	{

		if (function() { if(isset($_SERVER["SERVER_ADDR"])) {$serverAddr = $_SERVER["SERVER_ADDR"]; return $serverAddr; } else { return false; } }())
		{
			return $serverAddr;
		}

		return gethostbyname("localhost");
	}

	public function getServerName()
	{

		if (function() { if(isset($_SERVER["SERVER_NAME"])) {$serverName = $_SERVER["SERVER_NAME"]; return $serverName; } else { return false; } }())
		{
			return $serverName;
		}

		return "localhost";
	}

	public function getHttpHost()
	{

		$strict = $this->_strictHostCheck;

		$host = $this->getServer("HTTP_HOST");

		if (!($host))
		{
			$host = $this->getServer("SERVER_NAME");

			if (!($host))
			{
				$host = $this->getServer("SERVER_ADDR");

			}

		}

		if ($host && $strict)
		{
			$host = strtolower(trim($host));

			if (memstr($host, ":"))
			{
				$host = preg_replace("/:[[:digit:]]+$/", "", $host);

			}

			if ("" !== preg_replace("/[a-z0-9-]+\.?/", "", $host))
			{
				throw new \UnexpectedValueException("Invalid host " . $host);
			}

		}

		return (string) $host;
	}

	public function setStrictHostCheck($flag = true)
	{
		$this->_strictHostCheck = $flag;

		return $this;
	}

	public function isStrictHostCheck()
	{
		return $this->_strictHostCheck;
	}

	public function getPort()
	{

		$host = $this->getServer("HTTP_HOST");

		if ($host)
		{
			if (memstr($host, ":"))
			{
				$pos = strrpos($host, ":");

				if (false !== $pos)
				{
					return (int) substr($host, $pos + 1);
				}

			}

			return "https" === $this->getScheme() ? 443 : 80;
		}

		return (int) $this->getServer("SERVER_PORT");
	}

	public final function getURI()
	{

		if (function() { if(isset($_SERVER["REQUEST_URI"])) {$requestURI = $_SERVER["REQUEST_URI"]; return $requestURI; } else { return false; } }())
		{
			return $requestURI;
		}

		return "";
	}

	public function getClientAddress($trustForwardedHeader = false)
	{

		if ($trustForwardedHeader)
		{
			$address = $_SERVER["HTTP_X_FORWARDED_FOR"]
			if ($address === null)
			{
				$address = $_SERVER["HTTP_CLIENT_IP"]
			}

		}

		if ($address === null)
		{
			$address = $_SERVER["REMOTE_ADDR"]
		}

		if (typeof($address) == "string")
		{
			if (memstr($address, ","))
			{
				return explode(",", $address)[0];
			}

			return $address;
		}

		return false;
	}

	public final function getMethod()
	{


		if (function() { if(isset($_SERVER["REQUEST_METHOD"])) {$requestMethod = $_SERVER["REQUEST_METHOD"]; return $requestMethod; } else { return false; } }())
		{
			$returnMethod = strtoupper($requestMethod);

		}

		if ("POST" === $returnMethod)
		{
			$overridedMethod = $this->getHeader("X-HTTP-METHOD-OVERRIDE");

			if (!(empty($overridedMethod)))
			{
				$returnMethod = strtoupper($overridedMethod);

			}

		}

		if (!($this->isValidHttpMethod($returnMethod)))
		{
			return "GET";
		}

		return $returnMethod;
	}

	public function getUserAgent()
	{

		if (function() { if(isset($_SERVER["HTTP_USER_AGENT"])) {$userAgent = $_SERVER["HTTP_USER_AGENT"]; return $userAgent; } else { return false; } }())
		{
			return $userAgent;
		}

		return "";
	}

	public function isValidHttpMethod($method)
	{
		switch (strtoupper($method)) {
			case "GET":
			case "POST":
			case "PUT":
			case "DELETE":
			case "HEAD":
			case "OPTIONS":
			case "PATCH":
			case "PURGE":
			case "TRACE":
			case "CONNECT":
				return true;
		}

		return false;
	}

	public function isMethod($methods, $strict = false)
	{

		$httpMethod = $this->getMethod();

		if (typeof($methods) == "string")
		{
			if ($strict && !($this->isValidHttpMethod($methods)))
			{
				throw new Exception("Invalid HTTP method: " . $methods);
			}

			return $methods == $httpMethod;
		}

		if (typeof($methods) == "array")
		{
			foreach ($methods as $method) {
				if ($this->isMethod($method, $strict))
				{
					return true;
				}
			}

			return false;
		}

		if ($strict)
		{
			throw new Exception("Invalid HTTP method: non-string");
		}

		return false;
	}

	public function isPost()
	{
		return $this->getMethod() === "POST";
	}

	public function isGet()
	{
		return $this->getMethod() === "GET";
	}

	public function isPut()
	{
		return $this->getMethod() === "PUT";
	}

	public function isPatch()
	{
		return $this->getMethod() === "PATCH";
	}

	public function isHead()
	{
		return $this->getMethod() === "HEAD";
	}

	public function isDelete()
	{
		return $this->getMethod() === "DELETE";
	}

	public function isOptions()
	{
		return $this->getMethod() === "OPTIONS";
	}

	public function isPurge()
	{
		return $this->getMethod() === "PURGE";
	}

	public function isTrace()
	{
		return $this->getMethod() === "TRACE";
	}

	public function isConnect()
	{
		return $this->getMethod() === "CONNECT";
	}

	public function hasFiles($onlySuccessful = false)
	{


		$files = $_FILES;

		if (typeof($files) <> "array")
		{
			return 0;
		}

		foreach ($files as $file) {
			if (function() { if(isset($file["error"])) {$error = $file["error"]; return $error; } else { return false; } }())
			{
				if (typeof($error) <> "array")
				{
					if (!($error) || !($onlySuccessful))
					{
						$numberFiles++;

					}

				}

				if (typeof($error) == "array")
				{
					$numberFiles += $this->hasFileHelper($error, $onlySuccessful);

				}

			}
		}

		return $numberFiles;
	}

	protected final function hasFileHelper($data, $onlySuccessful)
	{


		if (typeof($data) <> "array")
		{
			return 1;
		}

		foreach ($data as $value) {
			if (typeof($value) <> "array")
			{
				if (!($value) || !($onlySuccessful))
				{
					$numberFiles++;

				}

			}
			if (typeof($value) == "array")
			{
				$numberFiles += $this->hasFileHelper($value, $onlySuccessful);

			}
		}

		return $numberFiles;
	}

	public function getUploadedFiles($onlySuccessful = false)
	{


		$superFiles = $_FILES;

		if (count($superFiles) > 0)
		{
			foreach ($superFiles as $prefix => $input) {
				if (typeof($input["name"]) == "array")
				{
					$smoothInput = $this->smoothFiles($input["name"], $input["type"], $input["tmp_name"], $input["size"], $input["error"], $prefix);

					foreach ($smoothInput as $file) {
						if ($onlySuccessful == false || $file["error"] == UPLOAD_ERR_OK)
						{
							$dataFile = ["name" => $file["name"], "type" => $file["type"], "tmp_name" => $file["tmp_name"], "size" => $file["size"], "error" => $file["error"]];

							$files = new File($dataFile, $file["key"]);

						}
					}

				}
			}

		}

		return $files;
	}

	protected final function smoothFiles($names, $types, $tmp_names, $sizes, $errors, $prefix)
	{

		$files = [];

		foreach ($names as $idx => $name) {
			$p = $prefix . "." . $idx;
			if (typeof($name) == "string")
			{
				$files = ["name" => $name, "type" => $types[$idx], "tmp_name" => $tmp_names[$idx], "size" => $sizes[$idx], "error" => $errors[$idx], "key" => $p];

			}
			if (typeof($name) == "array")
			{
				$parentFiles = $this->smoothFiles($names[$idx], $types[$idx], $tmp_names[$idx], $sizes[$idx], $errors[$idx], $p);

				foreach ($parentFiles as $file) {
					$files = $file;
				}

			}
		}

		return $files;
	}

	public function getHeaders()
	{



		foreach ($_SERVER as $name => $value) {
			if (starts_with($name, "HTTP_"))
			{
				$name = ucwords(strtolower(str_replace("_", " ", substr($name, 5))));
				$name = str_replace(" ", "-", $name);

				$headers[$name] = $value;

				continue;

			}
			$name = strtoupper($name);
			if (isset($contentHeaders[$name]))
			{
				$name = ucwords(strtolower(str_replace("_", " ", $name)));
				$name = str_replace(" ", "-", $name);

				$headers[$name] = $value;

			}
		}

		$authHeaders = $this->resolveAuthorizationHeaders();

		if (typeof($authHeaders) === "array")
		{
			$headers = array_merge($headers, $authHeaders);

		}

		return $headers;
	}

	protected function resolveAuthorizationHeaders()
	{


		$dependencyInjector = $this->getDI();

		if (typeof($dependencyInjector) === "object")
		{
			$hasEventsManager = (bool) $dependencyInjector->has("eventsManager");

			if ($hasEventsManager)
			{
				$eventsManager = $dependencyInjector->getShared("eventsManager");

			}

		}

		if ($hasEventsManager && typeof($eventsManager) === "object")
		{
			$resolved = $eventsManager->fire("request:beforeAuthorizationResolve", $this, ["server" => $_SERVER]);

			if (typeof($resolved) === "array")
			{
				$headers = array_merge($headers, $resolved);

			}

		}

		if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"]))
		{
			$headers["Php-Auth-User"] = $_SERVER["PHP_AUTH_USER"];
			$headers["Php-Auth-Pw"] = $_SERVER["PHP_AUTH_PW"];

		}

		if (!(isset($headers["Authorization"])))
		{
			if (isset($headers["Php-Auth-User"]))
			{
				$headers["Authorization"] = "Basic " . base64_encode($headers["Php-Auth-User"] . ":" . $headers["Php-Auth-Pw"]);

			}

		}

		if ($hasEventsManager && typeof($eventsManager) === "object")
		{
			$resolved = $eventsManager->fire("request:afterAuthorizationResolve", $this, ["headers" => $headers, "server" => $_SERVER]);

			if (typeof($resolved) === "array")
			{
				$headers = array_merge($headers, $resolved);

			}

		}

		return $headers;
	}

	public function getHTTPReferer()
	{

		if (function() { if(isset($_SERVER["HTTP_REFERER"])) {$httpReferer = $_SERVER["HTTP_REFERER"]; return $httpReferer; } else { return false; } }())
		{
			return $httpReferer;
		}

		return "";
	}

	protected final function _getBestQuality($qualityParts, $name)
	{



		$i = 0;
		$quality = 0.0;
		$selectedName = "";

		foreach ($qualityParts as $accept) {
			if ($i == 0)
			{
				$quality = (double) $accept["quality"];
				$selectedName = $accept[$name];

			}
			$i++;
		}

		return $selectedName;
	}

	public function getContentType()
	{

		if (function() { if(isset($_SERVER["CONTENT_TYPE"])) {$contentType = $_SERVER["CONTENT_TYPE"]; return $contentType; } else { return false; } }())
		{
			return $contentType;
		}

		return null;
	}

	public function getAcceptableContent()
	{
		return $this->_getQualityHeader("HTTP_ACCEPT", "accept");
	}

	public function getBestAccept()
	{
		return $this->_getBestQuality($this->getAcceptableContent(), "accept");
	}

	public function getClientCharsets()
	{
		return $this->_getQualityHeader("HTTP_ACCEPT_CHARSET", "charset");
	}

	public function getBestCharset()
	{
		return $this->_getBestQuality($this->getClientCharsets(), "charset");
	}

	public function getLanguages()
	{
		return $this->_getQualityHeader("HTTP_ACCEPT_LANGUAGE", "language");
	}

	public function getBestLanguage()
	{
		return $this->_getBestQuality($this->getLanguages(), "language");
	}

	public function getBasicAuth()
	{

		if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"]))
		{
			$auth = [];

			$auth["username"] = $_SERVER["PHP_AUTH_USER"];

			$auth["password"] = $_SERVER["PHP_AUTH_PW"];

			return $auth;
		}

		return null;
	}

	public function getDigestAuth()
	{


		$auth = [];

		if (function() { if(isset($_SERVER["PHP_AUTH_DIGEST"])) {$digest = $_SERVER["PHP_AUTH_DIGEST"]; return $digest; } else { return false; } }())
		{
			$matches = [];

			if (!(preg_match_all("#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $digest, $matches, 2)))
			{
				return $auth;
			}

			if (typeof($matches) == "array")
			{
				foreach ($matches as $match) {
					$auth[$match[1]] = $match[3];
				}

			}

		}

		return $auth;
	}

	protected final function _getQualityHeader($serverIndex, $name)
	{

		$returnedParts = [];

		foreach (preg_split("/,\\s*/", $this->getServer($serverIndex), -1, PREG_SPLIT_NO_EMPTY) as $part) {
			$headerParts = [];
			foreach (preg_split("/\s*;\s*/", trim($part), -1, PREG_SPLIT_NO_EMPTY) as $headerPart) {
				if (strpos($headerPart, "=") !== false)
				{
					$split = explode("=", $headerPart, 2);

					if ($split[0] === "q")
					{
						$headerParts["quality"] = (double) $split[1];

					}

				}
			}
			$returnedParts = $headerParts;
		}

		return $returnedParts;
	}


}