<?php
namespace Phalcon\Tag;

use Phalcon\Tag\Exception;
use Phalcon\Tag as BaseTag;
use Phalcon\EscaperInterface;
abstract 
class Select
{
	public static function selectField($parameters, $data = null)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters, $data];

		}

		if (!(function() { if(isset($params[0])) {$id = $params[0]; return $id; } else { return false; } }()))
		{
			$params[0] = $params["id"];

		}

		if (!(memstr($id, "[")))
		{
			if (!(isset($params["id"])))
			{
				$params["id"] = $id;

			}

		}

		if (!(function() { if(isset($params["name"])) {$name = $params["name"]; return $name; } else { return false; } }()))
		{
			$params["name"] = $id;

		}

		if (!(function() { if(isset($params["value"])) {$value = $params["value"]; return $value; } else { return false; } }()))
		{
			$value = BaseTag::getValue($id, $params);

		}

		if (function() { if(isset($params["useEmpty"])) {$useEmpty = $params["useEmpty"]; return $useEmpty; } else { return false; } }())
		{
			if (!(function() { if(isset($params["emptyValue"])) {$emptyValue = $params["emptyValue"]; return $emptyValue; } else { return false; } }()))
			{
				$emptyValue = "";

			}

			if (!(function() { if(isset($params["emptyText"])) {$emptyText = $params["emptyText"]; return $emptyText; } else { return false; } }()))
			{
				$emptyText = "Choose...";

			}

			unset($params["useEmpty"]);

		}

		if (!(function() { if(isset($params[1])) {$options = $params[1]; return $options; } else { return false; } }()))
		{
			$options = $data;

		}

		if (typeof($options) == "object")
		{
			if (!(function() { if(isset($params["using"])) {$using = $params["using"]; return $using; } else { return false; } }()))
			{
				throw new Exception("The 'using' parameter is required");
			}

		}

		unset($params["using"]);

		$code = BaseTag::renderAttributes("<select", $params) . ">" . PHP_EOL;

		if ($useEmpty)
		{
			$code .= "\t<option value=\"" . $emptyValue . "\">" . $emptyText . "</option>" . PHP_EOL;

		}

		if (typeof($options) == "object")
		{
			$code .= self::_optionsFromResultset($options, $using, $value, "</option>" . PHP_EOL);

		}

		$code .= "</select>";

		return $code;
	}

	private static function _optionsFromResultset($resultset, $using, $value, $closeOption)
	{

		$code = "";

		$params = null;

		if (typeof($using) == "array")
		{
			if (count($using) <> 2)
			{
				throw new Exception("Parameter 'using' requires two values");
			}

			$usingZero = $using[0];
			$usingOne = $using[1];

		}

		$escaper = BaseTag::getEscaperService();

		foreach (iterator($resultset) as $option) {
			if (typeof($using) == "array")
			{
				if (typeof($option) == "object")
				{
					if (method_exists($option, "readAttribute"))
					{
						$optionValue = $option->readAttribute($usingZero);

						$optionText = $option->readAttribute($usingOne);

					}

				}

				$optionValue = $escaper->escapeHtmlAttr($optionValue);

				$optionText = $escaper->escapeHtml($optionText);

				if (typeof($value) == "array")
				{
					if (in_array($optionValue, $value))
					{
						$code .= "\t<option selected=\"selected\" value=\"" . $optionValue . "\">" . $optionText . $closeOption;

					}

				}

			}
		}

		return $code;
	}

	private static function _optionsFromArray($data, $value, $closeOption)
	{

		$code = "";

		foreach ($data as $optionValue => $optionText) {
			$escaped = htmlspecialchars($optionValue);
			if (typeof($optionText) == "array")
			{
				$code .= "\t<optgroup label=\"" . $escaped . "\">" . PHP_EOL . self::_optionsFromArray($optionText, $value, $closeOption) . "\t</optgroup>" . PHP_EOL;

				continue;

			}
			if (typeof($value) == "array")
			{
				if (in_array($optionValue, $value))
				{
					$code .= "\t<option selected=\"selected\" value=\"" . $escaped . "\">" . $optionText . $closeOption;

				}

			}
		}

		return $code;
	}


}