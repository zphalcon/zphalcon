<?php
namespace Phalcon;

use Phalcon\FilterInterface;
use Phalcon\Filter\Exception;

class Filter implements FilterInterface
{
	const FILTER_EMAIL = "email";
	const FILTER_ABSINT = "absint";
	const FILTER_INT = "int";
	const FILTER_INT_CAST = "int!";
	const FILTER_STRING = "string";
	const FILTER_FLOAT = "float";
	const FILTER_FLOAT_CAST = "float!";
	const FILTER_ALPHANUM = "alphanum";
	const FILTER_TRIM = "trim";
	const FILTER_STRIPTAGS = "striptags";
	const FILTER_LOWER = "lower";
	const FILTER_UPPER = "upper";
	const FILTER_URL = "url";
	const FILTER_SPECIAL_CHARS = "special_chars";

	protected $_filters;

	public function add($name, $handler)
	{
		if (typeof($handler) <> "object" && !(is_callable($handler)))
		{
			throw new Exception("Filter must be an object or callable");
		}

		$this[$name] = $handler;

		return $this;
	}

	public function sanitize($value, $filters, $noRecursive = false)
	{

		if (typeof($filters) == "array")
		{
			if ($value !== null)
			{
				foreach ($filters as $filter) {
					if (typeof($value) == "array" && !($noRecursive))
					{
						$arrayValue = [];

						foreach ($value as $itemKey => $itemValue) {
							$arrayValue[$itemKey] = $this->_sanitize($itemValue, $filter);
						}

						$value = $arrayValue;

					}
				}

			}

			return $value;
		}

		if (typeof($value) == "array" && !($noRecursive))
		{
			$sanitizedValue = [];

			foreach ($value as $itemKey => $itemValue) {
				$sanitizedValue[$itemKey] = $this->_sanitize($itemValue, $filters);
			}

			return $sanitizedValue;
		}

		return $this->_sanitize($value, $filters);
	}

	protected function _sanitize($value, $filter)
	{

		if (function() { if(isset($this->_filters[$filter])) {$filterObject = $this->_filters[$filter]; return $filterObject; } else { return false; } }())
		{
			if (typeof($filterObject) == "object" && $filterObject instanceof $\Closure || is_callable($filterObject))
			{
				return call_user_func_array($filterObject, [$value]);
			}

			return $filterObject->filter($value);
		}

		switch ($filter) {
			case Filter::FILTER_EMAIL:
				return filter_var($value, constant("FILTER_SANITIZE_EMAIL"));			case Filter::FILTER_INT:
				return filter_var($value, FILTER_SANITIZE_NUMBER_INT);			case Filter::FILTER_INT_CAST:
				return intval($value);			case Filter::FILTER_ABSINT:
				return abs(intval($value));			case Filter::FILTER_STRING:
				return filter_var($value, FILTER_SANITIZE_STRING);			case Filter::FILTER_FLOAT:
				return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, ["flags" => FILTER_FLAG_ALLOW_FRACTION]);			case Filter::FILTER_FLOAT_CAST:
				return doubleval($value);			case Filter::FILTER_ALPHANUM:
				return preg_replace("/[^A-Za-z0-9]/", "", $value);			case Filter::FILTER_TRIM:
				return trim($value);			case Filter::FILTER_STRIPTAGS:
				return strip_tags($value);			case Filter::FILTER_LOWER:
				if (function_exists("mb_strtolower"))
				{
					return mb_strtolower($value);
				}
				return strtolower($value);			case Filter::FILTER_UPPER:
				if (function_exists("mb_strtoupper"))
				{
					return mb_strtoupper($value);
				}
				return strtoupper($value);			case Filter::FILTER_URL:
				return filter_var($value, FILTER_SANITIZE_URL);			case Filter::FILTER_SPECIAL_CHARS:
				return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);			default:
				throw new Exception("Sanitize filter '" . $filter . "' is not supported");
		}

	}

	public function getFilters()
	{
		return $this->_filters;
	}


}