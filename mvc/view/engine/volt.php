<?php
namespace Phalcon\Mvc\View\Engine;

use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\Engine\Volt\Compiler;
use Phalcon\Mvc\View\Exception;

class Volt extends Engine
{
	protected $_options;
	protected $_compiler;
	protected $_macros;

	public function setOptions($options)
	{
		$this->_options = $options;

	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function getCompiler()
	{

		$compiler = $this->_compiler;

		if (typeof($compiler) <> "object")
		{
			$compiler = new Compiler($this->_view);

			$dependencyInjector = $this->_dependencyInjector;

			if (typeof($dependencyInjector) == "object")
			{
				$compiler->setDi($dependencyInjector);

			}

			$options = $this->_options;

			if (typeof($options) == "array")
			{
				$compiler->setOptions($options);

			}

			$this->_compiler = $compiler;

		}

		return $compiler;
	}

	public function render($templatePath, $params, $mustClean = false)
	{

		if ($mustClean)
		{
			ob_clean();

		}

		$compiler = $this->getCompiler();

		$compiler->compile($templatePath);

		$compiledTemplatePath = $compiler->getCompiledTemplatePath();

		if (typeof($params) == "array")
		{
			foreach ($params as $key => $value) {
				$$key = $value;
			}

		}

		require($compiledTemplatePath);

		if ($mustClean)
		{
			$this->_view->setContent(ob_get_contents());

		}

	}

	public function length($item)
	{
		if (typeof($item) == "object" || typeof($item) == "array")
		{
			return count($item);
		}

		if (function_exists("mb_strlen"))
		{
			return mb_strlen($item);
		}

		return strlen($item);
	}

	public function isIncluded($needle, $haystack)
	{
		if (typeof($haystack) == "array")
		{
			return in_array($needle, $haystack);
		}

		if (typeof($haystack) == "string")
		{
			if (function_exists("mb_strpos"))
			{
				return mb_strpos($haystack, $needle) !== false;
			}

			return strpos($haystack, $needle) !== false;
		}

		throw new Exception("Invalid haystack");
	}

	public function convertEncoding($text, $from, $to)
	{
		if ($from == "latin1" || $to == "utf8")
		{
			return utf8_encode($text);
		}

		if ($to == "latin1" || $from == "utf8")
		{
			return utf8_decode($text);
		}

		if (function_exists("mb_convert_encoding"))
		{
			return mb_convert_encoding($text, $from, $to);
		}

		if (function_exists("iconv"))
		{
			return iconv($from, $to, $text);
		}

		throw new Exception("Any of 'mbstring' or 'iconv' is required to perform the charset conversion");
	}

	public function slice($value, $start = 0, $end = null)
	{


		if (typeof($value) == "object")
		{
			if ($end === null)
			{
				$end = count($value) - 1;

			}

			$position = 0;
			$slice = [];

			$value->rewind();

			while ($value->valid()) {
				if ($position >= $start && $position <= $end)
				{
					$slice = $value->current();

				}
				$value->next();
				$position++;
			}

			return $slice;
		}

		if ($end !== null)
		{
			$length = $end - $start + 1;

		}

		if (typeof($value) == "array")
		{
			return array_slice($value, $start, $length);
		}

		if (function_exists("mb_substr"))
		{
			if ($length !== null)
			{
				return mb_substr($value, $start, $length);
			}

			return mb_substr($value, $start);
		}

		if ($length !== null)
		{
			return substr($value, $start, $length);
		}

		return substr($value, $start);
	}

	public function sort($value)
	{
		asort($value);

		return $value;
	}

	public function callMacro($name, $arguments = [])
	{

		if (!(function() { if(isset($this->_macros[$name])) {$macro = $this->_macros[$name]; return $macro; } else { return false; } }()))
		{
			throw new Exception("Macro '" . $name . "' does not exist");
		}

		return call_user_func($macro, $arguments);
	}


}