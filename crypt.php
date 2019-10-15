<?php
namespace Phalcon;

use Phalcon\CryptInterface;
use Phalcon\Crypt\Exception;
use Phalcon\Crypt\Mismatch;

class Crypt implements CryptInterface
{
	const PADDING_DEFAULT = 0;
	const PADDING_ANSI_X_923 = 1;
	const PADDING_PKCS7 = 2;
	const PADDING_ISO_10126 = 3;
	const PADDING_ISO_IEC_7816_4 = 4;
	const PADDING_ZERO = 5;
	const PADDING_SPACE = 6;

	protected $_key;
	protected $_padding = 0;
	protected $_cipher = "aes-256-cfb";
	protected $availableCiphers;
	protected $ivLength = 16;
	protected $hashAlgo = "sha256";
	protected $useSigning = false;

	public function __construct($cipher = "aes-256-cfb", $useSigning = false)
	{
		$this->initializeAvailableCiphers();

		$this->setCipher($cipher);

		$this->useSigning($useSigning);

	}

	public function setPadding($scheme)
	{
		$this->_padding = $scheme;

		return $this;
	}

	public function setCipher($cipher)
	{
		$this->assertCipherIsAvailable($cipher);

		$this->ivLength = $this->getIvLength($cipher);
		$this->_cipher = $cipher;

		return $this;
	}

	public function getCipher()
	{
		return $this->_cipher;
	}

	public function setKey($key)
	{
		$this->_key = $key;

		return $this;
	}

	public function getKey()
	{
		return $this->_key;
	}

	public function setHashAlgo($hashAlgo)
	{
		$this->assertHashAlgorithmAvailable($hashAlgo);

		$this->hashAlgo = $hashAlgo;

		return $this;
	}

	public function getHashAlgo()
	{
		return $this->hashAlgo;
	}

	public function useSigning($useSigning)
	{
		$this->useSigning = $useSigning;

		return $this;
	}

	protected function _cryptPadText($text, $mode, $blockSize, $paddingType)
	{


		if ($mode == "cbc" || $mode == "ecb")
		{
			$paddingSize = $blockSize - strlen($text) % $blockSize;

			if ($paddingSize >= 256)
			{
				throw new Exception("Block size is bigger than 256");
			}

			switch ($paddingType) {
				case self::PADDING_ANSI_X_923:
					$padding = str_repeat(chr(0), $paddingSize - 1) . chr($paddingSize);
					break;
				case self::PADDING_PKCS7:
					$padding = str_repeat(chr($paddingSize), $paddingSize);
					break;
				case self::PADDING_ISO_10126:
					$padding = "";
					foreach (range(0, $paddingSize - 2) as $i) {
						$padding .= chr(rand());
					}
					$padding .= chr($paddingSize);
					break;
				case self::PADDING_ISO_IEC_7816_4:
					$padding = chr(0x80) . str_repeat(chr(0), $paddingSize - 1);
					break;
				case self::PADDING_ZERO:
					$padding = str_repeat(chr(0), $paddingSize);
					break;
				case self::PADDING_SPACE:
					$padding = str_repeat(" ", $paddingSize);
					break;
				default:
					$paddingSize = 0;
					break;

			}

		}

		if (!($paddingSize))
		{
			return $text;
		}

		if ($paddingSize > $blockSize)
		{
			throw new Exception("Invalid padding size");
		}

		return $text . substr($padding, 0, $paddingSize);
	}

	protected function _cryptUnpadText($text, $mode, $blockSize, $paddingType)
	{



		$length = strlen($text);

		if ($length > 0 && $length % $blockSize == 0 && $mode == "cbc" || $mode == "ecb")
		{
			switch ($paddingType) {
				case self::PADDING_ANSI_X_923:
					$last = substr($text, $length - 1, 1);
					$ord = (int) ord($last);
					if ($ord <= $blockSize)
					{
						$paddingSize = $ord;

						$padding = str_repeat(chr(0), $paddingSize - 1) . $last;

						if (substr($text, $length - $paddingSize) <> $padding)
						{
							$paddingSize = 0;

						}

					}
					break;
				case self::PADDING_PKCS7:
					$last = substr($text, $length - 1, 1);
					$ord = (int) ord($last);
					if ($ord <= $blockSize)
					{
						$paddingSize = $ord;

						$padding = str_repeat(chr($paddingSize), $paddingSize);

						if (substr($text, $length - $paddingSize) <> $padding)
						{
							$paddingSize = 0;

						}

					}
					break;
				case self::PADDING_ISO_10126:
					$last = substr($text, $length - 1, 1);
					$paddingSize = (int) ord($last);
					break;
				case self::PADDING_ISO_IEC_7816_4:
					$i = $length - 1;
					while ($i > 0 && $text[$i] == 0x00 && $paddingSize < $blockSize) {
						$paddingSize++;
						$i--;
					}
					if ($text[$i] == 0x80)
					{
						$paddingSize++;

					}
					break;
				case self::PADDING_ZERO:
					$i = $length - 1;
					while ($i >= 0 && $text[$i] == 0x00 && $paddingSize <= $blockSize) {
						$paddingSize++;
						$i--;
					}
					break;
				case self::PADDING_SPACE:
					$i = $length - 1;
					while ($i >= 0 && $text[$i] == 0x20 && $paddingSize <= $blockSize) {
						$paddingSize++;
						$i--;
					}
					break;
				default:
					break;

			}

			if ($paddingSize && $paddingSize <= $blockSize)
			{
				if ($paddingSize < $length)
				{
					return substr($text, 0, $length - $paddingSize);
				}

				return "";
			}

		}

		if (!($paddingSize))
		{
			return $text;
		}

	}

	public function encrypt($text, $key = null)
	{

		if (empty($key))
		{
			$encryptKey = $this->_key;

		}

		if (empty($encryptKey))
		{
			throw new Exception("Encryption key cannot be empty");
		}

		$cipher = $this->_cipher;

		$mode = strtolower(substr($cipher, strrpos($cipher, "-") - strlen($cipher)));

		$this->assertCipherIsAvailable($cipher);

		$ivLength = $this->ivLength;

		if ($ivLength > 0)
		{
			$blockSize = $ivLength;

		}

		$iv = openssl_random_pseudo_bytes($ivLength);

		$paddingType = $this->_padding;

		if ($paddingType <> 0 && $mode == "cbc" || $mode == "ecb")
		{
			$padded = $this->_cryptPadText($text, $mode, $blockSize, $paddingType);

		}

		$encrypted = openssl_encrypt($padded, $cipher, $encryptKey, OPENSSL_RAW_DATA, $iv);

		if ($this->useSigning)
		{

			$hashAlgo = $this->getHashAlgo();

			$digest = hash_hmac($hashAlgo, $padded, $encryptKey, true);

			return $iv . $digest . $encrypted;
		}

		return $iv . $encrypted;
	}

	public function decrypt($text, $key = null)
	{

		if (empty($key))
		{
			$decryptKey = $this->_key;

		}

		if (empty($decryptKey))
		{
			throw new Exception("Decryption key cannot be empty");
		}

		$cipher = $this->_cipher;

		$mode = strtolower(substr($cipher, strrpos($cipher, "-") - strlen($cipher)));

		$this->assertCipherIsAvailable($cipher);

		$ivLength = $this->ivLength;

		if ($ivLength > 0)
		{
			$blockSize = $ivLength;

		}

		$iv = mb_substr($text, 0, $ivLength, "8bit");

		if ($this->useSigning)
		{
			$hashAlgo = $this->getHashAlgo();

			$hashLength = strlen(hash($hashAlgo, "", true));

			$hash = mb_substr($text, $ivLength, $hashLength, "8bit");

			$ciphertext = mb_substr($text, $ivLength + $hashLength, null, "8bit");

			$decrypted = openssl_decrypt($ciphertext, $cipher, $decryptKey, OPENSSL_RAW_DATA, $iv);

			if ($mode == "cbc" || $mode == "ecb")
			{
				$decrypted = $this->_cryptUnpadText($decrypted, $mode, $blockSize, $this->_padding);

			}

			if (hash_hmac($hashAlgo, $decrypted, $decryptKey, true) !== $hash)
			{
				throw new Mismatch("Hash does not match.");
			}

			return $decrypted;
		}

		$ciphertext = mb_substr($text, $ivLength, null, "8bit");

		$decrypted = openssl_decrypt($ciphertext, $cipher, $decryptKey, OPENSSL_RAW_DATA, $iv);

		if ($mode == "cbc" || $mode == "ecb")
		{
			$decrypted = $this->_cryptUnpadText($decrypted, $mode, $blockSize, $this->_padding);

		}

		return $decrypted;
	}

	public function encryptBase64($text, $key = null, $safe = false)
	{
		if ($safe == true)
		{
			return rtrim(strtr(base64_encode($this->encrypt($text, $key)), "+/", "-_"), "=");
		}

		return base64_encode($this->encrypt($text, $key));
	}

	public function decryptBase64($text, $key = null, $safe = false)
	{
		if ($safe == true)
		{
			return $this->decrypt(base64_decode(strtr($text, "-_", "+/") . substr("===", strlen($text) + 3 % 4)), $key);
		}

		return $this->decrypt(base64_decode($text), $key);
	}

	public function getAvailableCiphers()
	{

		$availableCiphers = $this->availableCiphers;

		if (typeof($availableCiphers) !== "array")
		{
			$this->initializeAvailableCiphers();

			$availableCiphers = $this->availableCiphers;

		}

		return $availableCiphers;
	}

	public function getAvailableHashAlgos()
	{

		if (function_exists("hash_hmac_algos"))
		{
			$algos = hash_hmac_algos();

		}

		return $algos;
	}

	protected function assertCipherIsAvailable($cipher)
	{

		$availableCiphers = $this->getAvailableCiphers();

		if (!(in_array($cipher, $availableCiphers)))
		{
			throw new Exception(sprintf("The cipher algorithm \"%s\" is not supported on this system.", $cipher));
		}

	}

	protected function assertHashAlgorithmAvailable($hashAlgo)
	{

		$availableAlgorithms = $this->getAvailableHashAlgos();

		if (!(in_array($hashAlgo, $availableAlgorithms)))
		{
			throw new Exception(sprintf("The hash algorithm \"%s\" is not supported on this system.", $hashAlgo));
		}

	}

	protected function getIvLength($cipher)
	{
		if (!(function_exists("openssl_cipher_iv_length")))
		{
			throw new Exception("openssl extension is required");
		}

		return openssl_cipher_iv_length($cipher);
	}

	protected function initializeAvailableCiphers()
	{
		if (!(function_exists("openssl_get_cipher_methods")))
		{
			throw new Exception("openssl extension is required");
		}

		$this->availableCiphers = openssl_get_cipher_methods(true);

	}


}