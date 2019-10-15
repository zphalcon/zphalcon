<?php
namespace Phalcon\Security;


class Random
{
	public function bytes($len = 16)
	{

		if ($len <= 0)
		{
			$len = 16;

		}

		if (function_exists("random_bytes"))
		{
			return random_bytes($len);
		}

		if (function_exists("\\Sodium\\randombytes_buf"))
		{
			return \Sodium\randombytes_buf($len);
		}

		if (function_exists("openssl_random_pseudo_bytes"))
		{
			return openssl_random_pseudo_bytes($len);
		}

		if (file_exists("/dev/urandom"))
		{
			$handle = fopen("/dev/urandom", "rb");

			if ($handle !== false)
			{
				stream_set_read_buffer($handle, 0);

				$ret = fread($handle, $len);

				fclose($handle);

				if (strlen($ret) <> $len)
				{
					throw new Exception("Unexpected partial read from random device");
				}

				return $ret;
			}

		}

		throw new Exception("No random device available");
	}

	public function hex($len = null)
	{
		return array_shift(unpack("H*", $this->bytes($len)));
	}

	public function base58($len = null)
	{
		return $this->base("123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz", 58, $len);
	}

	public function base62($len = null)
	{
		return $this->base("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz", 62, $len);
	}

	public function base64($len = null)
	{
		return base64_encode($this->bytes($len));
	}

	public function base64Safe($len = null, $padding = false)
	{

		$s = strtr(base64_encode($this->base64($len)), "+/", "-_");

		$s = preg_replace("#[^a-z0-9_=-]+#i", "", $s);

		if (!($padding))
		{
			return rtrim($s, "=");
		}

		return $s;
	}

	public function uuid()
	{

		$ary = array_values(unpack("N1a/n1b/n1c/n1d/n1e/N1f", $this->bytes(16)));

		$ary[2] = $ary[2] & 0x0fff | 0x4000;
		$ary[3] = $ary[3] & 0x3fff | 0x8000;

		array_unshift($ary, "%08x-%04x-%04x-%04x-%04x%08x");

		return call_user_func_array("sprintf", $ary);
	}

	public function number($len)
	{


		if ($len <= 0)
		{
			throw new Exception("Require a positive integer > 0");
		}

		if (function_exists("random_int"))
		{
			return random_int(0, $len);
		}

		if (function_exists("\\Sodium\\randombytes_uniform"))
		{
			return \Sodium\randombytes_uniform($len) + 1;
		}

		$hex = dechex($len);

		if (strlen($hex) & 1 == 1)
		{
			$hex = "0" . $hex;

		}

		$bin .= pack("H*", $hex);

		$mask = ord($bin[0]);

		$mask = $mask | $mask >> 1;

		$mask = $mask | $mask >> 2;

		$mask = $mask | $mask >> 4;

		do {
			$rnd = $this->bytes(strlen($bin));
			$rnd = substr_replace($rnd, chr(ord(substr($rnd, 0, 1)) & $mask), 0, 1);
		} while ($bin < $rnd)
		$ret = unpack("H*", $rnd);

		return hexdec(array_shift($ret));
	}

	protected function base($alphabet, $base, $n = null)
	{


		$bytes = unpack("C*", $this->bytes($n));

		foreach ($bytes as $idx) {
			$idx = $idx % 64;
			if ($idx >= $base)
			{
				$idx = $this->number($base - 1);

			}
			$byteString .= $alphabet[(int) $idx];
		}

		return $byteString;
	}


}