<?php
namespace Phalcon\Http;

use Phalcon\DiInterface;
use Phalcon\Http\Response\Exception;
use Phalcon\Http\Response\HeadersInterface;
use Phalcon\Http\Response\CookiesInterface;
use Phalcon\Mvc\UrlInterface;
use Phalcon\Mvc\ViewInterface;
use Phalcon\Http\Response\Headers;
use Phalcon\Di\InjectionAwareInterface;

class Response implements ResponseInterface, InjectionAwareInterface
{
	protected $_sent = false;
	protected $_content;
	protected $_headers;
	protected $_cookies;
	protected $_file;
	protected $_dependencyInjector;

	public function __construct($content = null, $code = null, $status = null)
	{
		$this->_headers = new Headers();

		if ($content !== null)
		{
			$this->_content = $content;

		}

		if ($code !== null)
		{
			$this->setStatusCode($code, $status);

		}

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = \Phalcon\Di::getDefault();

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injection object is required to access the 'url' service");
			}

			$this->_dependencyInjector = $dependencyInjector;

		}

		return $dependencyInjector;
	}

	public function setStatusCode($code, $message = null)
	{

		$headers = $this->getHeaders();
		$currentHeadersRaw = $headers->toArray();

		if (typeof($currentHeadersRaw) == "array")
		{
			foreach ($currentHeadersRaw as $key => $_) {
				if (typeof($key) == "string" && strstr($key, "HTTP/"))
				{
					$headers->remove($key);

				}
			}

		}

		if ($message === null)
		{
			$statusCodes = [100 => "Continue", 101 => "Switching Protocols", 102 => "Processing", 103 => "Early Hints", 200 => "OK", 201 => "Created", 202 => "Accepted", 203 => "Non-Authoritative Information", 204 => "No Content", 205 => "Reset Content", 206 => "Partial Content", 207 => "Multi-status", 208 => "Already Reported", 226 => "IM Used", 300 => "Multiple Choices", 301 => "Moved Permanently", 302 => "Found", 303 => "See Other", 304 => "Not Modified", 305 => "Use Proxy", 306 => "Switch Proxy", 307 => "Temporary Redirect", 308 => "Permanent Redirect", 400 => "Bad Request", 401 => "Unauthorized", 402 => "Payment Required", 403 => "Forbidden", 404 => "Not Found", 405 => "Method Not Allowed", 406 => "Not Acceptable", 407 => "Proxy Authentication Required", 408 => "Request Time-out", 409 => "Conflict", 410 => "Gone", 411 => "Length Required", 412 => "Precondition Failed", 413 => "Request Entity Too Large", 414 => "Request-URI Too Large", 415 => "Unsupported Media Type", 416 => "Requested range not satisfiable", 417 => "Expectation Failed", 418 => "I'm a teapot", 421 => "Misdirected Request", 422 => "Unprocessable Entity", 423 => "Locked", 424 => "Failed Dependency", 425 => "Unordered Collection", 426 => "Upgrade Required", 428 => "Precondition Required", 429 => "Too Many Requests", 431 => "Request Header Fields Too Large", 451 => "Unavailable For Legal Reasons", 499 => "Client Closed Request", 500 => "Internal Server Error", 501 => "Not Implemented", 502 => "Bad Gateway", 503 => "Service Unavailable", 504 => "Gateway Time-out", 505 => "HTTP Version not supported", 506 => "Variant Also Negotiates", 507 => "Insufficient Storage", 508 => "Loop Detected", 510 => "Not Extended", 511 => "Network Authentication Required"];

			if (!(isset($statusCodes[$code])))
			{
				throw new Exception("Non-standard statuscode given without a message");
			}

			$defaultMessage = $statusCodes[$code];
			$message = $defaultMessage;

		}

		$headers->setRaw("HTTP/1.1 " . $code . " " . $message);

		$headers->set("Status", $code . " " . $message);

		return $this;
	}

	public function getStatusCode()
	{

		$statusCode = substr($this->getHeaders()->get("Status"), 0, 3);

		return $statusCode ? (int) $statusCode : null;
	}

	public function getReasonPhrase()
	{

		$statusReasonPhrase = substr($this->getHeaders()->get("Status"), 4);

		return $statusReasonPhrase ? $statusReasonPhrase : null;
	}

	public function setHeaders($headers)
	{
		$this->_headers = $headers;

		return $this;
	}

	public function getHeaders()
	{
		return $this->_headers;
	}

	public function setCookies($cookies)
	{
		$this->_cookies = $cookies;

		return $this;
	}

	public function getCookies()
	{
		return $this->_cookies;
	}

	public function setHeader($name, $value)
	{

		$headers = $this->getHeaders();

		$headers->set($name, $value);

		return $this;
	}

	public function setRawHeader($header)
	{

		$headers = $this->getHeaders();

		$headers->setRaw($header);

		return $this;
	}

	public function resetHeaders()
	{

		$headers = $this->getHeaders();

		$headers->reset();

		return $this;
	}

	public function setExpires($datetime)
	{

		$date = clone $datetime;

		$date->setTimezone(new \DateTimeZone("UTC"));

		$this->setHeader("Expires", $date->format("D, d M Y H:i:s") . " GMT");

		return $this;
	}

	public function setLastModified($datetime)
	{

		$date = clone $datetime;

		$date->setTimezone(new \DateTimeZone("UTC"));

		$this->setHeader("Last-Modified", $date->format("D, d M Y H:i:s") . " GMT");

		return $this;
	}

	public function setCache($minutes)
	{

		$date = new \DateTime();

		$date->modify("+" . $minutes . " minutes");

		$this->setExpires($date);

		$this->setHeader("Cache-Control", "max-age=" . $minutes * 60);

		return $this;
	}

	public function setNotModified()
	{
		$this->setStatusCode(304, "Not modified");

		return $this;
	}

	public function setContentType($contentType, $charset = null)
	{
		if ($charset === null)
		{
			$this->setHeader("Content-Type", $contentType);

		}

		return $this;
	}

	public function setContentLength($contentLength)
	{
		$this->setHeader("Content-Length", $contentLength);

		return $this;
	}

	public function setEtag($etag)
	{
		$this->setHeader("Etag", $etag);

		return $this;
	}

	public function redirect($location = null, $externalRedirect = false, $statusCode = 302)
	{

		if (!($location))
		{
			$location = "";

		}

		if ($externalRedirect)
		{
			$header = $location;

		}

		$dependencyInjector = $this->getDI();

		if (!($header))
		{
			$url = $dependencyInjector->getShared("url");
			$header = $url->get($location);

		}

		if ($dependencyInjector->has("view"))
		{
			$view = $dependencyInjector->getShared("view");

			if ($view instanceof $ViewInterface)
			{
				$view->disable();

			}

		}

		if ($statusCode < 300 || $statusCode > 308)
		{
			$statusCode = 302;

		}

		$this->setStatusCode($statusCode);

		$this->setHeader("Location", $header);

		return $this;
	}

	public function setContent($content)
	{
		$this->_content = $content;

		return $this;
	}

	public function setJsonContent($content, $jsonOptions = 0, $depth = 512)
	{
		$this->setContentType("application/json", "UTF-8");

		$this->setContent(json_encode($content, $jsonOptions, $depth));

		return $this;
	}

	public function appendContent($content)
	{
		$this->_content = $this->getContent() . $content;

		return $this;
	}

	public function getContent()
	{
		return $this->_content;
	}

	public function isSent()
	{
		return $this->_sent;
	}

	public function sendHeaders()
	{
		$this->_headers->send();

		return $this;
	}

	public function sendCookies()
	{

		$cookies = $this->_cookies;

		if (typeof($cookies) == "object")
		{
			$cookies->send();

		}

		return $this;
	}

	public function send()
	{

		if ($this->_sent)
		{
			throw new Exception("Response was already sent");
		}

		$this->sendHeaders();

		$this->sendCookies();

		$content = $this->_content;

		if ($content <> null)
		{
			echo($content);

		}

		$this->_sent = true;

		return $this;
	}

	public function setFileToSend($filePath, $attachmentName = null, $attachment = true)
	{

		if (typeof($attachmentName) <> "string")
		{
			$basePath = basename($filePath);

		}

		if ($attachment)
		{
			$this->setRawHeader("Content-Description: File Transfer");

			$this->setRawHeader("Content-Type: application/octet-stream");

			$this->setRawHeader("Content-Disposition: attachment; filename=" . $basePath . ";");

			$this->setRawHeader("Content-Transfer-Encoding: binary");

		}

		$this->_file = $filePath;

		return $this;
	}

	public function removeHeader($name)
	{

		$headers = $this->getHeaders();

		$headers->remove($name);

		return $this;
	}


}