<?php
namespace Phalcon\Debug;

use Phalcon\Di;

class Dump
{
	protected $_detailed = false;
	protected $_methods = [];
	protected $_styles;

	public function __construct($styles = [], $detailed = false)
	{
		$this->setStyles($styles);

		$this->_detailed = $detailed;

	}

	public function all()
	{
		return call_user_func_array([$this, "variables"], func_get_args());
	}

	protected function getStyle($type)
	{

		if (function() { if(isset($this->_styles[$type])) {$style = $this->_styles[$type]; return $style; } else { return false; } }())
		{
			return $style;
		}

	}

	public function setStyles($styles = [])
	{

		if (typeof($styles) == "null")
		{
			$styles = [];

		}

		if (typeof($styles) <> "array")
		{
			throw new Exception("The styles must be an array");
		}

		$defaultStyles = ["pre" => "background-color:#f3f3f3; font-size:11px; padding:10px; border:1px solid #ccc; text-align:left; color:#333", "arr" => "color:red", "bool" => "color:green", "float" => "color:fuchsia", "int" => "color:blue", "null" => "color:black", "num" => "color:navy", "obj" => "color:purple", "other" => "color:maroon", "res" => "color:lime", "str" => "color:teal"];

		$this->_styles = array_merge($defaultStyles, $styles);

		return $this->_styles;
	}

	public function one($variable, $name = null)
	{
		return $this->variable($variable, $name);
	}

	protected function output($variable, $name = null, $tab = 1)
	{

		$space = "  ";
		$output = "";

		if ($name)
		{
			$output = $name . " ";

		}

		if (typeof($variable) == "array")
		{
			$output .= strtr("<b style =':style'>Array</b> (<span style =':style'>:count</span>) (\n", [":style" => $this->getStyle("arr"), ":count" => count($variable)]);

			foreach ($variable as $key => $value) {
				$output .= str_repeat($space, $tab) . strtr("[<span style=':style'>:key</span>] => ", [":style" => $this->getStyle("arr"), ":key" => $key]);
				if ($tab == 1 && $name <> "" && !(is_int($key)) && $name == $key)
				{
					continue;

				}
			}

			return $output . str_repeat($space, $tab - 1) . ")";
		}

		if (typeof($variable) == "object")
		{
			$output .= strtr("<b style=':style'>Object</b> :class", [":style" => $this->getStyle("obj"), ":class" => get_class($variable)]);

			if (get_parent_class($variable))
			{
				$output .= strtr(" <b style=':style'>extends</b> :parent", [":style" => $this->getStyle("obj"), ":parent" => get_parent_class($variable)]);

			}

			$output .= " (\n";

			if ($variable instanceof $Di)
			{
				$output .= str_repeat($space, $tab) . "[skipped]\n";

			}

			$attr = get_class_methods($variable);

			$output .= str_repeat($space, $tab) . strtr(":class <b style=':style'>methods</b>: (<span style=':style'>:count</span>) (\n", [":style" => $this->getStyle("obj"), ":class" => get_class($variable), ":count" => count($attr)]);

			if (in_array(get_class($variable), $this->_methods))
			{
				$output .= str_repeat($space, $tab) . "[already listed]\n";

			}

			return $output . str_repeat($space, $tab - 1) . ")";
		}

		if (is_int($variable))
		{
			return $output . strtr("<b style=':style'>Integer</b> (<span style=':style'>:var</span>)", [":style" => $this->getStyle("int"), ":var" => $variable]);
		}

		if (is_float($variable))
		{
			return $output . strtr("<b style=':style'>Float</b> (<span style=':style'>:var</span>)", [":style" => $this->getStyle("float"), ":var" => $variable]);
		}

		if (is_numeric($variable))
		{
			return $output . strtr("<b style=':style'>Numeric string</b> (<span style=':style'>:length</span>) \"<span style=':style'>:var</span>\"", [":style" => $this->getStyle("num"), ":length" => strlen($variable), ":var" => $variable]);
		}

		if (is_string($variable))
		{
			return $output . strtr("<b style=':style'>String</b> (<span style=':style'>:length</span>) \"<span style=':style'>:var</span>\"", [":style" => $this->getStyle("str"), ":length" => strlen($variable), ":var" => nl2br(htmlentities($variable, ENT_IGNORE, "utf-8"))]);
		}

		if (is_bool($variable))
		{
			return $output . strtr("<b style=':style'>Boolean</b> (<span style=':style'>:var</span>)", [":style" => $this->getStyle("bool"), ":var" => $variable ? "TRUE" : "FALSE"]);
		}

		if (is_null($variable))
		{
			return $output . strtr("<b style=':style'>NULL</b>", [":style" => $this->getStyle("null")]);
		}

		return $output . strtr("(<span style=':style'>:var</span>)", [":style" => $this->getStyle("other"), ":var" => $variable]);
	}

	public function variable($variable, $name = null)
	{
		return strtr("<pre style=':style'>:output</pre>", [":style" => $this->getStyle("pre"), ":output" => $this->output($variable, $name)]);
	}

	public function variables()
	{

		$output = "";

		foreach (func_get_args() as $key => $value) {
			$output .= $this->one($value, "var " . $key);
		}

		return $output;
	}

	public function toJson($variable)
	{
		return json_encode($variable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}


}