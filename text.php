<?php
namespace Phalcon;

abstract 
class Text
{
	const RANDOM_ALNUM = 0;
	const RANDOM_ALPHA = 1;
	const RANDOM_HEXDEC = 2;
	const RANDOM_NUMERIC = 3;
	const RANDOM_NOZERO = 4;
	const RANDOM_DISTINCT = 5;

	public static function camelize($str, $delimiter = null)
	{
		return $str->camelize($delimiter);
	}

	public static function uncamelize($str, $delimiter = null)
	{
		return $str->uncamelize($delimiter);
	}

	public static function increment($str, $separator = "_")
	{

		$parts = explode($separator, $str);

		if (function() { if(isset($parts[1])) {$number = $parts[1]; return $number; } else { return false; } }())
		{
			$number++;

		}

		return $parts[0] . $separator . $number;
	}

	public static function random($type = 0, $length = 8)
	{


		switch ($type) {
			case Text::RANDOM_ALPHA:
				$pool = array_merge(range("a", "z"), range("A", "Z"));
				break;
			case Text::RANDOM_HEXDEC:
				$pool = array_merge(range(0, 9), range("a", "f"));
				break;
			case Text::RANDOM_NUMERIC:
				$pool = range(0, 9);
				break;
			case Text::RANDOM_NOZERO:
				$pool = range(1, 9);
				break;
			case Text::RANDOM_DISTINCT:
				$pool = str_split("2345679ACDEFHJKLMNPRSTUVWXYZ");
				break;
			default:
				$pool = array_merge(range(0, 9), range("a", "z"), range("A", "Z"));
				break;

		}

		$end = count($pool) - 1;

		while (strlen($str) < $length) {
			$str .= $pool[mt_rand(0, $end)];
		}

		return $str;
	}

	public static function startsWith($str, $start, $ignoreCase = true)
	{
		return starts_with($str, $start, $ignoreCase);
	}

	public static function endsWith($str, $end, $ignoreCase = true)
	{
		return ends_with($str, $end, $ignoreCase);
	}

	public static function lower($str, $encoding = "UTF-8")
	{
		if (function_exists("mb_strtolower"))
		{
			return mb_strtolower($str, $encoding);
		}

		return strtolower($str);
	}

	public static function upper($str, $encoding = "UTF-8")
	{
		if (function_exists("mb_strtoupper"))
		{
			return mb_strtoupper($str, $encoding);
		}

		return strtoupper($str);
	}

	public static function reduceSlashes($str)
	{
		return preg_replace("#(?<!:)//+#", "/", $str);
	}

	public static function concat()
	{

		$separator = func_get_arg(0);
		$a = func_get_arg(1);
		$b = func_get_arg(2);


		if (func_num_args() > 3)
		{
			foreach (array_slice(func_get_args(), 3) as $c) {
				$b = rtrim($b, $separator) . $separator . ltrim($c, $separator);
			}

		}

		return rtrim($a, $separator) . $separator . ltrim($b, $separator);
	}

	public static function dynamic($text, $leftDelimiter = "{", $rightDelimiter = "}", $separator = "|")
	{

		if (substr_count($text, $leftDelimiter) !== substr_count($text, $rightDelimiter))
		{
			throw new \RuntimeException("Syntax error in string \"" . $text . "\"");
		}

		$ldS = preg_quote($leftDelimiter);
		$rdS = preg_quote($rightDelimiter);
		$pattern = "/" . $ldS . "([^" . $ldS . $rdS . "]+)" . $rdS . "/";
		$matches = [];

		if (!(preg_match_all($pattern, $text, $matches, 2)))
		{
			return $text;
		}

		if (typeof($matches) == "array")
		{
			foreach ($matches as $match) {
				if (!(isset($match[0])) || !(isset($match[1])))
				{
					continue;

				}
				$words = explode($separator, $match[1]);
				$word = $words[array_rand($words)];
				$sub = preg_quote($match[0], $separator);
				$text = preg_replace("/" . $sub . "/", $word, $text, 1);
			}

		}

		return $text;
	}

	public static function underscore($text)
	{
		return preg_replace("#\s+#", "_", trim($text));
	}

	public static function humanize($text)
	{
		return preg_replace("#[_-]+#", " ", trim($text));
	}


}