<?php
namespace Phalcon\Http;

use Phalcon\Http\Response\HeadersInterface;

interface ResponseInterface
{
	public function setStatusCode($code, $message = null)
	{
	}

	public function getHeaders()
	{
	}

	public function setHeader($name, $value)
	{
	}

	public function setRawHeader($header)
	{
	}

	public function resetHeaders()
	{
	}

	public function setExpires($datetime)
	{
	}

	public function setNotModified()
	{
	}

	public function setContentType($contentType, $charset = null)
	{
	}

	public function setContentLength($contentLength)
	{
	}

	public function redirect($location = null, $externalRedirect = false, $statusCode = 302)
	{
	}

	public function setContent($content)
	{
	}

	public function setJsonContent($content)
	{
	}

	public function appendContent($content)
	{
	}

	public function getContent()
	{
	}

	public function sendHeaders()
	{
	}

	public function sendCookies()
	{
	}

	public function send()
	{
	}

	public function setFileToSend($filePath, $attachmentName = null)
	{
	}


}