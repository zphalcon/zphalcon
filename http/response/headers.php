<?php
namespace Phalcon\Http\Response;

use Phalcon\Http\Response\HeadersInterface;

class Headers implements HeadersInterface
{
	protected $_headers = [];

	public function set($name, $value)
	{
		$this[$name] = $value;

	}

	public function get($name)
	{

		$headers = $this->_headers;

		if (function() { if(isset($headers[$name])) {$headerValue = $headers[$name]; return $headerValue; } else { return false; } }())
		{
			return $headerValue;
		}

		return false;
	}

	public function setRaw($header)
	{
		$this[$header] = null;

	}

	public function remove($header)
	{

		$headers = $this->_headers;

		unset($headers[$header]);

		$this->_headers = $headers;

	}

	public function send()
	{

		if (!(headers_sent()))
		{
			foreach ($this->_headers as $header => $value) {
				if ($value !== null)
				{
					header($header . ": " . $value, true);

				}
			}

			return true;
		}

		return false;
	}

	public function reset()
	{
		$this->_headers = [];

	}

	public function toArray()
	{
		return $this->_headers;
	}

	public static function __set_state($data)
	{

		$headers = new self();

		if (function() { if(isset($data["_headers"])) {$dataHeaders = $data["_headers"]; return $dataHeaders; } else { return false; } }())
		{
			foreach ($dataHeaders as $key => $value) {
				$headers->set($key, $value);
			}

		}

		return $headers;
	}


}