<?php
namespace Phalcon;


class Version
{
	const VERSION_MAJOR = 0;
	const VERSION_MEDIUM = 1;
	const VERSION_MINOR = 2;
	const VERSION_SPECIAL = 3;
	const VERSION_SPECIAL_NUMBER = 4;

	protected static function _getVersion()
	{
		return [3, 4, 4, 4, 0];
	}

	protected final static function _getSpecial($special)
	{

		switch ($special) {
			case 1:
				$suffix = "ALPHA";
				break;

			case 2:
				$suffix = "BETA";
				break;

			case 3:
				$suffix = "RC";
				break;


		}

		return $suffix;
	}

	public static function get()
	{

		$version = static::_getVersion();

		$major = $version[self::VERSION_MAJOR];
		$medium = $version[self::VERSION_MEDIUM];
		$minor = $version[self::VERSION_MINOR];
		$special = $version[self::VERSION_SPECIAL];
		$specialNumber = $version[self::VERSION_SPECIAL_NUMBER];

		$result = $major . "." . $medium . "." . $minor . " ";

		$suffix = static::_getSpecial($special);

		if ($suffix <> "")
		{
			$result .= $suffix . " " . $specialNumber;

		}

		return trim($result);
	}

	public static function getId()
	{

		$version = static::_getVersion();

		$major = $version[self::VERSION_MAJOR];
		$medium = $version[self::VERSION_MEDIUM];
		$minor = $version[self::VERSION_MINOR];
		$special = $version[self::VERSION_SPECIAL];
		$specialNumber = $version[self::VERSION_SPECIAL_NUMBER];

		return $major . sprintf("%02s", $medium) . sprintf("%02s", $minor) . $special . $specialNumber;
	}

	public static function getPart($part)
	{

		$version = static::_getVersion();

		switch ($part) {
			case self::VERSION_MAJOR:

			case self::VERSION_MEDIUM:

			case self::VERSION_MINOR:

			case self::VERSION_SPECIAL_NUMBER:
				$result = $version[$part];
				break;

			case self::VERSION_SPECIAL:
				$result = static::_getSpecial($version[self::VERSION_SPECIAL]);
				break;

			default:
				$result = static::get();
				break;


		}

		return $result;
	}


}