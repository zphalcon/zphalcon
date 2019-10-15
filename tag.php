<?php
namespace Phalcon;

use Phalcon\Tag\Select;
use Phalcon\Tag\Exception;
use Phalcon\Mvc\UrlInterface;

class Tag
{
	const HTML32 = 1;
	const HTML401_STRICT = 2;
	const HTML401_TRANSITIONAL = 3;
	const HTML401_FRAMESET = 4;
	const HTML5 = 5;
	const XHTML10_STRICT = 6;
	const XHTML10_TRANSITIONAL = 7;
	const XHTML10_FRAMESET = 8;
	const XHTML11 = 9;
	const XHTML20 = 10;
	const XHTML5 = 11;

	protected static $_displayValues;
	protected static $_documentTitle = null;
	protected static $_documentAppendTitle = null;
	protected static $_documentPrependTitle = null;
	protected static $_documentTitleSeparator = null;
	protected static $_documentType = 11;
	protected static $_dependencyInjector;
	protected static $_urlService = null;
	protected static $_dispatcherService = null;
	protected static $_escaperService = null;
	protected static $_autoEscape = true;

	public static function getEscaper($params)
	{

		if (!(function() { if(isset($params["escape"])) {$autoescape = $params["escape"]; return $autoescape; } else { return false; } }()))
		{
			$autoescape = self::_autoEscape;

		}

		if (!($autoescape))
		{
			return null;
		}

		return self::getEscaperService();
	}

	public static function renderAttributes($code, $attributes)
	{

		$order = ["rel" => null, "type" => null, "for" => null, "src" => null, "href" => null, "action" => null, "id" => null, "name" => null, "value" => null, "class" => null];

		$attrs = [];

		foreach ($order as $key => $value) {
			if (function() { if(isset($attributes[$key])) {$attribute = $attributes[$key]; return $attribute; } else { return false; } }())
			{
				$attrs[$key] = $attribute;

			}
		}

		foreach ($attributes as $key => $value) {
			if (!(isset($attrs[$key])))
			{
				$attrs[$key] = $value;

			}
		}

		$escaper = self::getEscaper($attributes);

		unset($attrs["escape"]);

		$newCode = $code;

		foreach ($attrs as $key => $value) {
			if (typeof($key) == "string" && $value !== null)
			{
				if (typeof($value) == "array" || typeof($value) == "resource")
				{
					throw new Exception("Value at index: '" . $key . "' type: '" . gettype($value) . "' cannot be rendered");
				}

				if ($escaper)
				{
					$escaped = $escaper->escapeHtmlAttr($value);

				}

				$newCode .= " " . $key . "=\"" . $escaped . "\"";

			}
		}

		return $newCode;
	}

	public static function setDI($dependencyInjector)
	{
		self::_dependencyInjector = $dependencyInjector;

	}

	public static function getDI()
	{

		$di = self::_dependencyInjector;

		if (typeof($di) <> "object")
		{
			$di = Di::getDefault();

		}

		return $di;
	}

	public static function getUrlService()
	{

		$url = self::_urlService;

		if (typeof($url) <> "object")
		{
			$dependencyInjector = self::getDI();

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injector container is required to obtain the 'url' service");
			}

			$url = $dependencyInjector->getShared("url");
			self::_urlService = $url;

		}

		return $url;
	}

	public static function getEscaperService()
	{

		$escaper = self::_escaperService;

		if (typeof($escaper) <> "object")
		{
			$dependencyInjector = self::getDI();

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injector container is required to obtain the 'escaper' service");
			}

			$escaper = $dependencyInjector->getShared("escaper");
			self::_escaperService = $escaper;

		}

		return $escaper;
	}

	public static function setAutoescape($autoescape)
	{
		self::_autoEscape = $autoescape;

	}

	public static function setDefault($id, $value)
	{
		if ($value !== null)
		{
			if (typeof($value) == "array" || typeof($value) == "object")
			{
				throw new Exception("Only scalar values can be assigned to UI components");
			}

		}

		self::$id = $value;

	}

	public static function setDefaults($values, $merge = false)
	{
		if ($merge && typeof(self::_displayValues) == "array")
		{
			self::_displayValues = array_merge(self::_displayValues, $values);

		}

	}

	public static function displayTo($id, $value)
	{
		self::setDefault($id, $value);

	}

	public static function hasValue($name)
	{
		return isset(self::_displayValues[$name]) || isset($_POST[$name]);
	}

	public static function getValue($name, $params = null)
	{

		if (!($params) || !(function() { if(isset($params["value"])) {$value = $params["value"]; return $value; } else { return false; } }()))
		{
			if (!(function() { if(isset(self::_displayValues[$name])) {$value = self::_displayValues[$name]; return $value; } else { return false; } }()))
			{
				if (!(function() { if(isset($_POST[$name])) {$value = $_POST[$name]; return $value; } else { return false; } }()))
				{
					return null;
				}

			}

		}

		return $value;
	}

	deprecated public static function resetInput()
	{
		self::_displayValues = [];
		self::_documentTitle = null;
		self::_documentAppendTitle = [];
		self::_documentPrependTitle = [];
		self::_documentTitleSeparator = null;

	}

	public static function linkTo($parameters, $text = null, $local = true)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters, $text, $local];

		}

		if (!(function() { if(isset($params[0])) {$action = $params[0]; return $action; } else { return false; } }()))
		{
			if (!(function() { if(isset($params["action"])) {$action = $params["action"]; return $action; } else { return false; } }()))
			{
				$action = "";

			}

		}

		if (!(function() { if(isset($params[1])) {$text = $params[1]; return $text; } else { return false; } }()))
		{
			if (!(function() { if(isset($params["text"])) {$text = $params["text"]; return $text; } else { return false; } }()))
			{
				$text = "";

			}

		}

		if (!(function() { if(isset($params[2])) {$local = $params[2]; return $local; } else { return false; } }()))
		{
			if (!(function() { if(isset($params["local"])) {$local = $params["local"]; return $local; } else { return false; } }()))
			{
				$local = true;

			}

		}

		if (function() { if(isset($params["query"])) {$query = $params["query"]; return $query; } else { return false; } }())
		{
			unset($params["query"]);

		}

		$url = self::getUrlService();
		$params["href"] = $url->get($action, $query, $local);
		$code = self::renderAttributes("<a", $params);
		$code .= ">" . $text . "</a>";

		return $code;
	}

	static protected final function _inputField($type, $parameters, $asValue = false)
	{

		$params = [];

		if (typeof($parameters) <> "array")
		{
			$params = $parameters;

		}

		if ($asValue == false)
		{
			if (!(function() { if(isset($params[0])) {$id = $params[0]; return $id; } else { return false; } }()))
			{
				$params[0] = $params["id"];

			}

			if (function() { if(isset($params["name"])) {$name = $params["name"]; return $name; } else { return false; } }())
			{
				if (empty($name))
				{
					$params["name"] = $id;

				}

			}

			if (typeof($id) == "string")
			{
				if (!(memstr($id, "[")) && !(isset($params["id"])))
				{
					$params["id"] = $id;

				}

			}

			$params["value"] = self::getValue($id, $params);

		}

		$params["type"] = $type;
		$code = self::renderAttributes("<input", $params);

		if (self::_documentType > self::HTML5)
		{
			$code .= " />";

		}

		return $code;
	}

	static protected final function _inputFieldChecked($type, $parameters)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters];

		}

		if (!(isset($params[0])))
		{
			$params[0] = $params["id"];

		}

		$id = $params[0];

		if (!(isset($params["name"])))
		{
			$params["name"] = $id;

		}

		if (!(strpos($id, "[")))
		{
			if (!(isset($params["id"])))
			{
				$params["id"] = $id;

			}

		}

		if (function() { if(isset($params["value"])) {$currentValue = $params["value"]; return $currentValue; } else { return false; } }())
		{
			unset($params["value"]);

			$value = self::getValue($id, $params);

			if ($value <> null && $currentValue == $value)
			{
				$params["checked"] = "checked";

			}

			$params["value"] = $currentValue;

		}

		$params["type"] = $type;
		$code = self::renderAttributes("<input", $params);

		if (self::_documentType > self::HTML5)
		{
			$code .= " />";

		}

		return $code;
	}

	public static function colorField($parameters)
	{
		return self::_inputField("color", $parameters);
	}

	public static function textField($parameters)
	{
		return self::_inputField("text", $parameters);
	}

	public static function numericField($parameters)
	{
		return self::_inputField("number", $parameters);
	}

	public static function rangeField($parameters)
	{
		return self::_inputField("range", $parameters);
	}

	public static function emailField($parameters)
	{
		return self::_inputField("email", $parameters);
	}

	public static function dateField($parameters)
	{
		return self::_inputField("date", $parameters);
	}

	public static function dateTimeField($parameters)
	{
		return self::_inputField("datetime", $parameters);
	}

	public static function dateTimeLocalField($parameters)
	{
		return self::_inputField("datetime-local", $parameters);
	}

	public static function monthField($parameters)
	{
		return self::_inputField("month", $parameters);
	}

	public static function timeField($parameters)
	{
		return self::_inputField("time", $parameters);
	}

	public static function weekField($parameters)
	{
		return self::_inputField("week", $parameters);
	}

	public static function passwordField($parameters)
	{
		return self::_inputField("password", $parameters);
	}

	public static function hiddenField($parameters)
	{
		return self::_inputField("hidden", $parameters);
	}

	public static function fileField($parameters)
	{
		return self::_inputField("file", $parameters);
	}

	public static function searchField($parameters)
	{
		return self::_inputField("search", $parameters);
	}

	public static function telField($parameters)
	{
		return self::_inputField("tel", $parameters);
	}

	public static function urlField($parameters)
	{
		return self::_inputField("url", $parameters);
	}

	public static function checkField($parameters)
	{
		return self::_inputFieldChecked("checkbox", $parameters);
	}

	public static function radioField($parameters)
	{
		return self::_inputFieldChecked("radio", $parameters);
	}

	public static function imageInput($parameters)
	{
		return self::_inputField("image", $parameters, true);
	}

	public static function submitButton($parameters)
	{
		return self::_inputField("submit", $parameters, true);
	}

	public static function selectStatic($parameters, $data = null)
	{
		return Select::selectField($parameters, $data);
	}

	public static function select($parameters, $data = null)
	{
		return Select::selectField($parameters, $data);
	}

	public static function textArea($parameters)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters];

		}

		if (!(isset($params[0])))
		{
			if (isset($params["id"]))
			{
				$params[0] = $params["id"];

			}

		}

		$id = $params[0];

		if (!(isset($params["name"])))
		{
			$params["name"] = $id;

		}

		if (!(isset($params["id"])))
		{
			$params["id"] = $id;

		}

		if (isset($params["value"]))
		{
			$content = $params["value"];

			unset($params["value"]);

		}

		$code = self::renderAttributes("<textarea", $params);
		$code .= ">" . $content . "</textarea>";

		return $code;
	}

	public static function form($parameters)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters];

		}

		if (!(function() { if(isset($params[0])) {$paramsAction = $params[0]; return $paramsAction; } else { return false; } }()))
		{
			$paramsAction = $params["action"]
		}

		if (!(isset($params["method"])))
		{
			$params["method"] = "post";

		}

		$action = null;

		if (!(empty($paramsAction)))
		{
			$action = self::getUrlService()->get($paramsAction);

		}

		if (function() { if(isset($params["parameters"])) {$parameters = $params["parameters"]; return $parameters; } else { return false; } }())
		{
			$action .= "?" . $parameters;

			unset($params["parameters"]);

		}

		if (!(empty($action)))
		{
			$params["action"] = $action;

		}

		$code = self::renderAttributes("<form", $params);
		$code .= ">";

		return $code;
	}

	public static function endForm()
	{
		return "</form>";
	}

	public static function setTitle($title)
	{
		self::_documentTitle = $title;

	}

	public static function setTitleSeparator($titleSeparator)
	{
		self::_documentTitleSeparator = $titleSeparator;

	}

	public static function appendTitle($title)
	{
		if (typeof(self::_documentAppendTitle) == "null")
		{
			self::_documentAppendTitle = [];

		}

		if (typeof($title) == "array")
		{
			self::_documentAppendTitle = $title;

		}

	}

	public static function prependTitle($title)
	{
		if (typeof(self::_documentPrependTitle) == "null")
		{
			self::_documentPrependTitle = [];

		}

		if (typeof($title) == "array")
		{
			self::_documentPrependTitle = $title;

		}

	}

	public static function getTitle($tags = true)
	{

		$escaper = self::getEscaper(["escape" => true]);

		$items = [];

		$output = "";

		$documentTitle = $escaper->escapeHtml(self::_documentTitle);

		$documentTitleSeparator = $escaper->escapeHtml(self::_documentTitleSeparator);

		if (typeof(self::_documentAppendTitle) == "null")
		{
			self::_documentAppendTitle = [];

		}

		$documentAppendTitle = self::_documentAppendTitle;

		if (typeof(self::_documentPrependTitle) == "null")
		{
			self::_documentPrependTitle = [];

		}

		$documentPrependTitle = self::_documentPrependTitle;

		if (!(empty($documentPrependTitle)))
		{

			foreach ($tmp as $title) {
				$items = $escaper->escapeHtml($title);
			}

		}

		if (!(empty($documentTitle)))
		{
			$items = $documentTitle;

		}

		if (!(empty($documentAppendTitle)))
		{
			foreach ($documentAppendTitle as $title) {
				$items = $escaper->escapeHtml($title);
			}

		}

		if (empty($documentTitleSeparator))
		{
			$documentTitleSeparator = "";

		}

		if (!(empty($items)))
		{
			$output = implode($documentTitleSeparator, $items);

		}

		if ($tags)
		{
			return "<title>" . $output . "</title>" . PHP_EOL;
		}

		return $output;
	}

	public static function getTitleSeparator()
	{
		return self::_documentTitleSeparator;
	}

	public static function stylesheetLink($parameters = null, $local = true)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters, $local];

		}

		if (isset($params[1]))
		{
			$local = (bool) $params[1];

		}

		if (!(isset($params["type"])))
		{
			$params["type"] = "text/css";

		}

		if (!(isset($params["href"])))
		{
			if (isset($params[0]))
			{
				$params["href"] = $params[0];

			}

		}

		if ($local === true)
		{
			$params["href"] = self::getUrlService()->getStatic($params["href"]);

		}

		if (!(isset($params["rel"])))
		{
			$params["rel"] = "stylesheet";

		}

		$code = self::renderAttributes("<link", $params);

		if (self::_documentType > self::HTML5)
		{
			$code .= " />" . PHP_EOL;

		}

		return $code;
	}

	public static function javascriptInclude($parameters = null, $local = true)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters, $local];

		}

		if (isset($params[1]))
		{
			$local = (bool) $params[1];

		}

		if (!(isset($params["type"])))
		{
			$params["type"] = "text/javascript";

		}

		if (!(isset($params["src"])))
		{
			if (isset($params[0]))
			{
				$params["src"] = $params[0];

			}

		}

		if ($local === true)
		{
			$params["src"] = self::getUrlService()->getStatic($params["src"]);

		}

		$code = self::renderAttributes("<script", $params);
		$code .= "></script>" . PHP_EOL;

		return $code;
	}

	public static function image($parameters = null, $local = true)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters];

		}

		if (!(isset($params["src"])))
		{
			if (function() { if(isset($params[0])) {$src = $params[0]; return $src; } else { return false; } }())
			{
				$params["src"] = $src;

			}

		}

		if ($local)
		{
			$params["src"] = self::getUrlService()->getStatic($params["src"]);

		}

		$code = self::renderAttributes("<img", $params);

		if (self::_documentType > self::HTML5)
		{
			$code .= " />";

		}

		return $code;
	}

	public static function friendlyTitle($text, $separator = "-", $lowercase = true, $replace = null)
	{

		if (extension_loaded("iconv"))
		{
			$locale = setlocale(LC_ALL, "en_US.UTF-8");
			$text = iconv("UTF-8", "ASCII//TRANSLIT", $text);

		}

		if ($replace)
		{
			if (typeof($replace) <> "array" && typeof($replace) <> "string")
			{
				throw new Exception("Parameter replace must be an array or a string");
			}

			if (typeof($replace) == "array")
			{
				foreach ($replace as $search) {
					$text = str_replace($search, " ", $text);
				}

			}

		}

		$friendly = preg_replace("/[^a-zA-Z0-9\\/_|+ -]/", "", $text);

		if ($lowercase)
		{
			$friendly = strtolower($friendly);

		}

		$friendly = preg_replace("/[\\/_|+ -]+/", $separator, $friendly);
		$friendly = trim($friendly, $separator);

		if (extension_loaded("iconv"))
		{
			setlocale(LC_ALL, $locale);

		}

		return $friendly;
	}

	public static function setDocType($doctype)
	{
		if ($doctype < self::HTML32 || $doctype > self::XHTML5)
		{
			self::_documentType = self::HTML5;

		}

	}

	public static function getDocType()
	{
		switch (self::_documentType) {
			case 1:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2 Final//EN\">" . PHP_EOL;			case 2:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/html4/strict.dtd\">" . PHP_EOL;			case 3:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/html4/loose.dtd\">" . PHP_EOL;			case 4:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/html4/frameset.dtd\">" . PHP_EOL;			case 6:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">" . PHP_EOL;			case 7:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">" . PHP_EOL;			case 8:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">" . PHP_EOL;			case 9:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">" . PHP_EOL;			case 10:
				return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 2.0//EN\"" . PHP_EOL . "\t\"http://www.w3.org/MarkUp/DTD/xhtml2.dtd\">" . PHP_EOL;			case 5:
			case 11:
				return "<!DOCTYPE html>" . PHP_EOL;
		}

		return "";
	}

	public static function tagHtml($tagName, $parameters = null, $selfClose = false, $onlyStart = false, $useEol = false)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [$parameters];

		}

		$localCode = self::renderAttributes("<" . $tagName, $params);

		if (self::_documentType > self::HTML5)
		{
			if ($selfClose)
			{
				$localCode .= " />";

			}

		}

		if ($useEol)
		{
			$localCode .= PHP_EOL;

		}

		return $localCode;
	}

	public static function tagHtmlClose($tagName, $useEol = false)
	{
		if ($useEol)
		{
			return "</" . $tagName . ">" . PHP_EOL;
		}

		return "</" . $tagName . ">";
	}


}