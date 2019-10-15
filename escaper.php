<?php
namespace Phalcon;

use Phalcon\EscaperInterface;
use Phalcon\Escaper\Exception;

class Escaper implements EscaperInterface
{
	protected $_encoding = "utf-8";
	protected $_htmlEscapeMap = null;
	protected $_htmlQuoteType = 3;
	protected $_doubleEncode = true;

	public function setEncoding($encoding)
	{
		$this->_encoding = $encoding;

	}

	public function getEncoding()
	{
		return $this->_encoding;
	}

	public function setHtmlQuoteType($quoteType)
	{
		$this->_htmlQuoteType = $quoteType;

	}

	public function setDoubleEncode($doubleEncode)
	{
		$this->_doubleEncode = $doubleEncode;

	}

	public final function detectEncoding($str)
	{

		$charset = phalcon_is_basic_charset($str);

		if (typeof($charset) == "string")
		{
			return $charset;
		}

		if (!(function_exists("mb_detect_encoding")))
		{
			return null;
		}

		foreach (["UTF-32", "UTF-8", "ISO-8859-1", "ASCII"] as $charset) {
			if (mb_detect_encoding($str, $charset, true))
			{
				return $charset;
			}
		}

		return mb_detect_encoding($str);
	}

	public final function normalizeEncoding($str)
	{
		if (!(function_exists("mb_convert_encoding")))
		{
			throw new Exception("Extension 'mbstring' is required");
		}

		return mb_convert_encoding($str, "UTF-32", $this->detectEncoding($str));
	}

	public function escapeHtml($text)
	{
		return htmlspecialchars($text, $this->_htmlQuoteType, $this->_encoding, $this->_doubleEncode);
	}

	public function escapeHtmlAttr($attribute)
	{
		return htmlspecialchars($attribute, ENT_QUOTES, $this->_encoding, $this->_doubleEncode);
	}

	public function escapeCss($css)
	{
		return phalcon_escape_css($this->normalizeEncoding($css));
	}

	public function escapeJs($js)
	{
		return phalcon_escape_js($this->normalizeEncoding($js));
	}

	public function escapeUrl($url)
	{
		return rawurlencode($url);
	}


}