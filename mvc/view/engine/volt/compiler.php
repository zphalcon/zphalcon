<?php
namespace Phalcon\Mvc\View\Engine\Volt;

use Phalcon\DiInterface;
use Phalcon\Mvc\ViewBaseInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\View\Engine\Volt\Exception;

class Compiler implements InjectionAwareInterface
{
	protected $_dependencyInjector;
	protected $_view;
	protected $_options;
	protected $_arrayHelpers;
	protected $_level = 0;
	protected $_foreachLevel = 0;
	protected $_blockLevel = 0;
	protected $_exprLevel = 0;
	protected $_extended = false;
	protected $_autoescape = false;
	protected $_extendedBlocks;
	protected $_currentBlock;
	protected $_blocks;
	protected $_forElsePointers;
	protected $_loopPointers;
	protected $_extensions;
	protected $_functions;
	protected $_filters;
	protected $_macros;
	protected $_prefix;
	protected $_currentPath;
	protected $_compiledTemplatePath;

	public function __construct($view = null)
	{
		if (typeof($view) == "object")
		{
			$this->_view = $view;

		}

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function setOptions($options)
	{
		$this->_options = $options;

	}

	public function setOption($option, $value)
	{
		$this[$option] = $value;

	}

	public function getOption($option)
	{

		if (function() { if(isset($this->_options[$option])) {$value = $this->_options[$option]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return null;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public final function fireExtensionEvent($name, $arguments = null)
	{

		$extensions = $this->_extensions;

		if (typeof($extensions) == "array")
		{
			foreach ($extensions as $extension) {
				if (method_exists($extension, $name))
				{
					if (typeof($arguments) == "array")
					{
						$status = call_user_func_array([$extension, $name], $arguments);

					}

					if (typeof($status) == "string")
					{
						return $status;
					}

				}
			}

		}

	}

	public function addExtension($extension)
	{
		if (typeof($extension) <> "object")
		{
			throw new Exception("The extension is not valid");
		}

		if (method_exists($extension, "initialize"))
		{
			$extension->initialize($this);

		}

		$this->_extensions[] = $extension;

		return $this;
	}

	public function getExtensions()
	{
		return $this->_extensions;
	}

	public function addFunction($name, $definition)
	{
		$this[$name] = $definition;

		return $this;
	}

	public function getFunctions()
	{
		return $this->_functions;
	}

	public function addFilter($name, $definition)
	{
		$this[$name] = $definition;

		return $this;
	}

	public function getFilters()
	{
		return $this->_filters;
	}

	public function setUniquePrefix($prefix)
	{
		$this->_prefix = $prefix;

		return $this;
	}

	public function getUniquePrefix()
	{
		if (!($this->_prefix))
		{
			$this->_prefix = unique_path_key($this->_currentPath);

		}

		if (typeof($this->_prefix) == "object")
		{
			if ($this->_prefix instanceof $\Closure)
			{
				$this->_prefix = call_user_func_array($this->_prefix, [$this]);

			}

		}

		if (typeof($this->_prefix) <> "string")
		{
			throw new Exception("The unique compilation prefix is invalid");
		}

		return $this->_prefix;
	}

	public function attributeReader($expr)
	{

		$exprCode = null;

		$left = $expr["left"];

		if ($left["type"] == PHVOLT_T_IDENTIFIER)
		{
			$variable = $left["value"];

			if ($variable == "loop")
			{
				$level = $this->_foreachLevel;
				$exprCode .= "$" . $this->getUniquePrefix() . $level . "loop";
				$this[$level] = $level;

			}

		}

		$exprCode .= "->";

		$right = $expr["right"];

		if ($right["type"] == PHVOLT_T_IDENTIFIER)
		{
			$exprCode .= $right["value"];

		}

		return $exprCode;
	}

	public function functionCall($expr)
	{

		$code = null;

		$funcArguments = null;

		if (function() { if(isset($expr["arguments"])) {$funcArguments = $expr["arguments"]; return $funcArguments; } else { return false; } }())
		{
			$arguments = $this->expression($funcArguments);

		}

		$nameExpr = $expr["name"];
		$nameType = $nameExpr["type"];

		if ($nameType == PHVOLT_T_IDENTIFIER)
		{
			$name = $nameExpr["value"];

			$extensions = $this->_extensions;

			if (typeof($extensions) == "array")
			{
				$code = $this->fireExtensionEvent("compileFunction", [$name, $arguments, $funcArguments]);

				if (typeof($code) == "string")
				{
					return $code;
				}

			}

			$functions = $this->_functions;

			if (typeof($functions) == "array")
			{
				if (function() { if(isset($functions[$name])) {$definition = $functions[$name]; return $definition; } else { return false; } }())
				{
					if (typeof($definition) == "string")
					{
						return $definition . "(" . $arguments . ")";
					}

					if (typeof($definition) == "object")
					{
						if ($definition instanceof $\Closure)
						{
							return call_user_func_array($definition, [$arguments, $funcArguments]);
						}

					}

					throw new Exception("Invalid definition for user function '" . $name . "' in " . $expr["file"] . " on line " . $expr["line"]);
				}

			}

			if ($name == "get_content" || $name == "content")
			{
				return "$this->getContent()";
			}

			if ($name == "partial")
			{
				return "$this->partial(" . $arguments . ")";
			}

			if ($name == "super")
			{
				$extendedBlocks = $this->_extendedBlocks;

				if (typeof($extendedBlocks) == "array")
				{
					$currentBlock = $this->_currentBlock;

					if (function() { if(isset($extendedBlocks[$currentBlock])) {$block = $extendedBlocks[$currentBlock]; return $block; } else { return false; } }())
					{
						$exprLevel = $this->_exprLevel;

						if (typeof($block) == "array")
						{
							$code = $this->_statementListOrExtends($block);

							if ($exprLevel == 1)
							{
								$escapedCode = $code;

							}

						}

						if ($exprLevel == 1)
						{
							return $escapedCode;
						}

						return "'" . $escapedCode . "'";
					}

				}

				return "''";
			}

			$method = lcfirst(camelize($name));
			$className = "Phalcon\\Tag";

			if (method_exists($className, $method))
			{
				$arrayHelpers = $this->_arrayHelpers;

				if (typeof($arrayHelpers) <> "array")
				{
					$arrayHelpers = ["check_field" => true, "color_field" => true, "date_field" => true, "date_time_field" => true, "date_time_local_field" => true, "email_field" => true, "file_field" => true, "form" => true, "hidden_field" => true, "image" => true, "image_input" => true, "link_to" => true, "month_field" => true, "numeric_field" => true, "password_field" => true, "radio_field" => true, "range_field" => true, "search_field" => true, "select" => true, "select_static" => true, "submit_button" => true, "tel_field" => true, "text_area" => true, "text_field" => true, "time_field" => true, "url_field" => true, "week_field" => true];

					$this->_arrayHelpers = $arrayHelpers;

				}

				if (isset($arrayHelpers[$name]))
				{
					return "$this->tag->" . $method . "([" . $arguments . "])";
				}

				return "$this->tag->" . $method . "(" . $arguments . ")";
			}

			if ($name == "url")
			{
				return "$this->url->get(" . $arguments . ")";
			}

			if ($name == "static_url")
			{
				return "$this->url->getStatic(" . $arguments . ")";
			}

			if ($name == "date")
			{
				return "date(" . $arguments . ")";
			}

			if ($name == "time")
			{
				return "time()";
			}

			if ($name == "dump")
			{
				return "var_dump(" . $arguments . ")";
			}

			if ($name == "version")
			{
				return "Phalcon\\Version::get()";
			}

			if ($name == "version_id")
			{
				return "Phalcon\\Version::getId()";
			}

			if ($name == "constant")
			{
				return "constant(" . $arguments . ")";
			}

			return "$this->callMacro('" . $name . "', [" . $arguments . "])";
		}

		return $this->expression($nameExpr) . "(" . $arguments . ")";
	}

	public function resolveTest($test, $left)
	{

		$type = $test["type"];

		if ($type == PHVOLT_T_IDENTIFIER)
		{
			$name = $test["value"];

			if ($name == "empty")
			{
				return "empty(" . $left . ")";
			}

			if ($name == "even")
			{
				return "(((" . $left . ") % 2) == 0)";
			}

			if ($name == "odd")
			{
				return "(((" . $left . ") % 2) != 0)";
			}

			if ($name == "numeric")
			{
				return "is_numeric(" . $left . ")";
			}

			if ($name == "scalar")
			{
				return "is_scalar(" . $left . ")";
			}

			if ($name == "iterable")
			{
				return "(is_array(" . $left . ") || (" . $left . ") instanceof Traversable)";
			}

		}

		if ($type == PHVOLT_T_FCALL)
		{
			$testName = $test["name"];

			if (function() { if(isset($testName["value"])) {$name = $testName["value"]; return $name; } else { return false; } }())
			{
				if ($name == "divisibleby")
				{
					return "(((" . $left . ") % (" . $this->expression($test["arguments"]) . ")) == 0)";
				}

				if ($name == "sameas")
				{
					return "(" . $left . ") === (" . $this->expression($test["arguments"]) . ")";
				}

				if ($name == "type")
				{
					return "gettype(" . $left . ") === (" . $this->expression($test["arguments"]) . ")";
				}

			}

		}

		return $left . " == " . $this->expression($test);
	}

	final protected function resolveFilter($filter, $left)
	{

		$code = null;
		$type = $filter["type"];

		if ($type == PHVOLT_T_IDENTIFIER)
		{
			$name = $filter["value"];

		}

		$funcArguments = null;
		$arguments = null;

		if (function() { if(isset($filter["arguments"])) {$funcArguments = $filter["arguments"]; return $funcArguments; } else { return false; } }())
		{
			if ($name <> "default")
			{
				$file = $filter["file"];
				$line = $filter["line"];

				array_unshift($funcArguments, ["expr" => ["type" => 364, "value" => $left, "file" => $file, "line" => $line], "file" => $file, "line" => $line]);

			}

			$arguments = $this->expression($funcArguments);

		}

		$extensions = $this->_extensions;

		if (typeof($extensions) == "array")
		{
			$code = $this->fireExtensionEvent("compileFilter", [$name, $arguments, $funcArguments]);

			if (typeof($code) == "string")
			{
				return $code;
			}

		}

		$filters = $this->_filters;

		if (typeof($filters) == "array")
		{
			if (function() { if(isset($filters[$name])) {$definition = $filters[$name]; return $definition; } else { return false; } }())
			{
				if (typeof($definition) == "string")
				{
					return $definition . "(" . $arguments . ")";
				}

				if (typeof($definition) == "object")
				{
					if ($definition instanceof $\Closure)
					{
						return call_user_func_array($definition, [$arguments, $funcArguments]);
					}

				}

				throw new Exception("Invalid definition for user filter '" . $name . "' in " . $filter["file"] . " on line " . $filter["line"]);
			}

		}

		if ($name == "length")
		{
			return "$this->length(" . $arguments . ")";
		}

		if ($name == "e" || $name == "escape")
		{
			return "$this->escaper->escapeHtml(" . $arguments . ")";
		}

		if ($name == "escape_css")
		{
			return "$this->escaper->escapeCss(" . $arguments . ")";
		}

		if ($name == "escape_js")
		{
			return "$this->escaper->escapeJs(" . $arguments . ")";
		}

		if ($name == "escape_attr")
		{
			return "$this->escaper->escapeHtmlAttr(" . $arguments . ")";
		}

		if ($name == "trim")
		{
			return "trim(" . $arguments . ")";
		}

		if ($name == "left_trim")
		{
			return "ltrim(" . $arguments . ")";
		}

		if ($name == "right_trim")
		{
			return "rtrim(" . $arguments . ")";
		}

		if ($name == "striptags")
		{
			return "strip_tags(" . $arguments . ")";
		}

		if ($name == "url_encode")
		{
			return "urlencode(" . $arguments . ")";
		}

		if ($name == "slashes")
		{
			return "addslashes(" . $arguments . ")";
		}

		if ($name == "stripslashes")
		{
			return "stripslashes(" . $arguments . ")";
		}

		if ($name == "nl2br")
		{
			return "nl2br(" . $arguments . ")";
		}

		if ($name == "keys")
		{
			return "array_keys(" . $arguments . ")";
		}

		if ($name == "join")
		{
			return "join(" . $arguments . ")";
		}

		if ($name == "lower" || $name == "lowercase")
		{
			return "Phalcon\\Text::lower(" . $arguments . ")";
		}

		if ($name == "upper" || $name == "uppercase")
		{
			return "Phalcon\\Text::upper(" . $arguments . ")";
		}

		if ($name == "capitalize")
		{
			return "ucwords(" . $arguments . ")";
		}

		if ($name == "sort")
		{
			return "$this->sort(" . $arguments . ")";
		}

		if ($name == "json_encode")
		{
			return "json_encode(" . $arguments . ")";
		}

		if ($name == "json_decode")
		{
			return "json_decode(" . $arguments . ")";
		}

		if ($name == "format")
		{
			return "sprintf(" . $arguments . ")";
		}

		if ($name == "abs")
		{
			return "abs(" . $arguments . ")";
		}

		if ($name == "slice")
		{
			return "$this->slice(" . $arguments . ")";
		}

		if ($name == "default")
		{
			return "(empty(" . $left . ") ? (" . $arguments . ") : (" . $left . "))";
		}

		if ($name == "convert_encoding")
		{
			return "$this->convertEncoding(" . $arguments . ")";
		}

		throw new Exception("Unknown filter \"" . $name . "\" in " . $filter["file"] . " on line " . $filter["line"]);
	}

	final public function expression($expr)
	{

		$exprCode = null;
		$this->_exprLevel++;
		$extensions = $this->_extensions;

		while (true) {
			if (typeof($extensions) == "array")
			{
				$exprCode = $this->fireExtensionEvent("resolveExpression", [$expr]);

				if (typeof($exprCode) == "string")
				{
					break;

				}

			}
			if (!(function() { if(isset($expr["type"])) {$type = $expr["type"]; return $type; } else { return false; } }()))
			{
				$items = [];

				foreach ($expr as $singleExpr) {
					$singleExprCode = $this->expression($singleExpr["expr"]);
					if (function() { if(isset($singleExpr["name"])) {$name = $singleExpr["name"]; return $name; } else { return false; } }())
					{
						$items = "'" . $name . "' => " . $singleExprCode;

					}
				}

				$exprCode = join(", ", $items);

				break;

			}
			if ($type == PHVOLT_T_DOT)
			{
				$exprCode = $this->attributeReader($expr);

				break;

			}
			if (function() { if(isset($expr["left"])) {$left = $expr["left"]; return $left; } else { return false; } }())
			{
				$leftCode = $this->expression($left);

			}
			if ($type == PHVOLT_T_IS)
			{
				$exprCode = $this->resolveTest($expr["right"], $leftCode);

				break;

			}
			if ($type == 124)
			{
				$exprCode = $this->resolveFilter($expr["right"], $leftCode);

				break;

			}
			if (function() { if(isset($expr["right"])) {$right = $expr["right"]; return $right; } else { return false; } }())
			{
				$rightCode = $this->expression($right);

			}
			$exprCode = null;
			switch ($type) {
				case PHVOLT_T_NOT:
					$exprCode = "!" . $rightCode;
					break;
				case PHVOLT_T_MUL:
					$exprCode = $leftCode . " * " . $rightCode;
					break;
				case PHVOLT_T_ADD:
					$exprCode = $leftCode . " + " . $rightCode;
					break;
				case PHVOLT_T_SUB:
					$exprCode = $leftCode . " - " . $rightCode;
					break;
				case PHVOLT_T_DIV:
					$exprCode = $leftCode . " / " . $rightCode;
					break;
				case 37:
					$exprCode = $leftCode . " % " . $rightCode;
					break;
				case PHVOLT_T_LESS:
					$exprCode = $leftCode . " < " . $rightCode;
					break;
				case 61:
					$exprCode = $leftCode . " > " . $rightCode;
					break;
				case 62:
					$exprCode = $leftCode . " > " . $rightCode;
					break;
				case 126:
					$exprCode = $leftCode . " . " . $rightCode;
					break;
				case 278:
					$exprCode = "pow(" . $leftCode . ", " . $rightCode . ")";
					break;
				case PHVOLT_T_ARRAY:
					if (isset($expr["left"]))
					{
						$exprCode = "[" . $leftCode . "]";

					}
					break;
				case 258:
					$exprCode = $expr["value"];
					break;
				case 259:
					$exprCode = $expr["value"];
					break;
				case PHVOLT_T_STRING:
					$exprCode = "'" . str_replace("'", "\\'", $expr["value"]) . "'";
					break;
				case PHVOLT_T_NULL:
					$exprCode = "null";
					break;
				case PHVOLT_T_FALSE:
					$exprCode = "false";
					break;
				case PHVOLT_T_TRUE:
					$exprCode = "true";
					break;
				case PHVOLT_T_IDENTIFIER:
					$exprCode = "$" . $expr["value"];
					break;
				case PHVOLT_T_AND:
					$exprCode = $leftCode . " && " . $rightCode;
					break;
				case 267:
					$exprCode = $leftCode . " || " . $rightCode;
					break;
				case PHVOLT_T_LESSEQUAL:
					$exprCode = $leftCode . " <= " . $rightCode;
					break;
				case 271:
					$exprCode = $leftCode . " >= " . $rightCode;
					break;
				case 272:
					$exprCode = $leftCode . " == " . $rightCode;
					break;
				case 273:
					$exprCode = $leftCode . " != " . $rightCode;
					break;
				case 274:
					$exprCode = $leftCode . " === " . $rightCode;
					break;
				case 275:
					$exprCode = $leftCode . " !== " . $rightCode;
					break;
				case PHVOLT_T_RANGE:
					$exprCode = "range(" . $leftCode . ", " . $rightCode . ")";
					break;
				case PHVOLT_T_FCALL:
					$exprCode = $this->functionCall($expr);
					break;
				case PHVOLT_T_ENCLOSED:
					$exprCode = "(" . $leftCode . ")";
					break;
				case PHVOLT_T_ARRAYACCESS:
					$exprCode = $leftCode . "[" . $rightCode . "]";
					break;
				case PHVOLT_T_SLICE:
					if (function() { if(isset($expr["start"])) {$start = $expr["start"]; return $start; } else { return false; } }())
					{
						$startCode = $this->expression($start);

					}
					if (function() { if(isset($expr["end"])) {$end = $expr["end"]; return $end; } else { return false; } }())
					{
						$endCode = $this->expression($end);

					}
					$exprCode = "$this->slice(" . $leftCode . ", " . $startCode . ", " . $endCode . ")";
					break;
				case PHVOLT_T_NOT_ISSET:
					$exprCode = "!isset(" . $leftCode . ")";
					break;
				case PHVOLT_T_ISSET:
					$exprCode = "isset(" . $leftCode . ")";
					break;
				case PHVOLT_T_NOT_ISEMPTY:
					$exprCode = "!empty(" . $leftCode . ")";
					break;
				case PHVOLT_T_ISEMPTY:
					$exprCode = "empty(" . $leftCode . ")";
					break;
				case PHVOLT_T_NOT_ISEVEN:
					$exprCode = "!(((" . $leftCode . ") % 2) == 0)";
					break;
				case PHVOLT_T_ISEVEN:
					$exprCode = "(((" . $leftCode . ") % 2) == 0)";
					break;
				case PHVOLT_T_NOT_ISODD:
					$exprCode = "!(((" . $leftCode . ") % 2) != 0)";
					break;
				case PHVOLT_T_ISODD:
					$exprCode = "(((" . $leftCode . ") % 2) != 0)";
					break;
				case PHVOLT_T_NOT_ISNUMERIC:
					$exprCode = "!is_numeric(" . $leftCode . ")";
					break;
				case PHVOLT_T_ISNUMERIC:
					$exprCode = "is_numeric(" . $leftCode . ")";
					break;
				case PHVOLT_T_NOT_ISSCALAR:
					$exprCode = "!is_scalar(" . $leftCode . ")";
					break;
				case PHVOLT_T_ISSCALAR:
					$exprCode = "is_scalar(" . $leftCode . ")";
					break;
				case PHVOLT_T_NOT_ISITERABLE:
					$exprCode = "!(is_array(" . $leftCode . ") || (" . $leftCode . ") instanceof Traversable)";
					break;
				case PHVOLT_T_ISITERABLE:
					$exprCode = "(is_array(" . $leftCode . ") || (" . $leftCode . ") instanceof Traversable)";
					break;
				case PHVOLT_T_IN:
					$exprCode = "$this->isIncluded(" . $leftCode . ", " . $rightCode . ")";
					break;
				case PHVOLT_T_NOT_IN:
					$exprCode = "!$this->isIncluded(" . $leftCode . ", " . $rightCode . ")";
					break;
				case PHVOLT_T_TERNARY:
					$exprCode = "(" . $this->expression($expr["ternary"]) . " ? " . $leftCode . " : " . $rightCode . ")";
					break;
				case PHVOLT_T_MINUS:
					$exprCode = "-" . $rightCode;
					break;
				case PHVOLT_T_PLUS:
					$exprCode = "+" . $rightCode;
					break;
				case PHVOLT_T_RESOLVED_EXPR:
					$exprCode = $expr["value"];
					break;
				default:
					throw new Exception("Unknown expression " . $type . " in " . $expr["file"] . " on line " . $expr["line"]);
			}
			break;
		}

		$this->_exprLevel--;
		return $exprCode;
	}

	final protected function _statementListOrExtends($statements)
	{


		if (typeof($statements) <> "array")
		{
			return $statements;
		}

		$isStatementList = true;

		if (!(isset($statements["type"])))
		{
			foreach ($statements as $statement) {
				if (typeof($statement) <> "array")
				{
					$isStatementList = false;

					break;

				}
			}

		}

		if ($isStatementList === true)
		{
			return $this->_statementList($statements);
		}

		return $statements;
	}

	public function compileForeach($statement, $extendsMode = false)
	{

		if (!(isset($statement["expr"])))
		{
			throw new Exception("Corrupted statement");
		}

		$compilation = "";
		$forElse = null;

		$this->_foreachLevel++;
		$prefix = $this->getUniquePrefix();

		$level = $this->_foreachLevel;

		$prefixLevel = $prefix . $level;

		$expr = $statement["expr"];

		$exprCode = $this->expression($expr);

		$blockStatements = $statement["block_statements"];

		$forElse = false;

		if (typeof($blockStatements) == "array")
		{
			foreach ($blockStatements as $bstatement) {
				if (typeof($bstatement) <> "array")
				{
					break;

				}
				if (!(function() { if(isset($bstatement["type"])) {$type = $bstatement["type"]; return $type; } else { return false; } }()))
				{
					break;

				}
				if ($type == PHVOLT_T_ELSEFOR)
				{
					$compilation .= "<?php $" . $prefixLevel . "iterated = false; ?>";

					$forElse = $prefixLevel;

					$this[$level] = $forElse;

					break;

				}
			}

		}

		$code = $this->_statementList($blockStatements, $extendsMode);

		$loopContext = $this->_loopPointers;

		if (isset($loopContext[$level]))
		{
			$compilation .= "<?php $" . $prefixLevel . "iterator = " . $exprCode . "; ";

			$compilation .= "$" . $prefixLevel . "incr = 0; ";

			$compilation .= "$" . $prefixLevel . "loop = new stdClass(); ";

			$compilation .= "$" . $prefixLevel . "loop->self = &$" . $prefixLevel . "loop; ";

			$compilation .= "$" . $prefixLevel . "loop->length = count($" . $prefixLevel . "iterator); ";

			$compilation .= "$" . $prefixLevel . "loop->index = 1; ";

			$compilation .= "$" . $prefixLevel . "loop->index0 = 1; ";

			$compilation .= "$" . $prefixLevel . "loop->revindex = $" . $prefixLevel . "loop->length; ";

			$compilation .= "$" . $prefixLevel . "loop->revindex0 = $" . $prefixLevel . "loop->length - 1; ?>";

			$iterator = "$" . $prefixLevel . "iterator";

		}

		$variable = $statement["variable"];

		if (function() { if(isset($statement["key"])) {$key = $statement["key"]; return $key; } else { return false; } }())
		{
			$compilation .= "<?php foreach (" . $iterator . " as $" . $key . " => $" . $variable . ") { ";

		}

		if (function() { if(isset($statement["if_expr"])) {$ifExpr = $statement["if_expr"]; return $ifExpr; } else { return false; } }())
		{
			$compilation .= "if (" . $this->expression($ifExpr) . ") { ?>";

		}

		if (isset($loopContext[$level]))
		{
			$compilation .= "<?php $" . $prefixLevel . "loop->first = ($" . $prefixLevel . "incr == 0); ";

			$compilation .= "$" . $prefixLevel . "loop->index = $" . $prefixLevel . "incr + 1; ";

			$compilation .= "$" . $prefixLevel . "loop->index0 = $" . $prefixLevel . "incr; ";

			$compilation .= "$" . $prefixLevel . "loop->revindex = $" . $prefixLevel . "loop->length - $" . $prefixLevel . "incr; ";

			$compilation .= "$" . $prefixLevel . "loop->revindex0 = $" . $prefixLevel . "loop->length - ($" . $prefixLevel . "incr + 1); ";

			$compilation .= "$" . $prefixLevel . "loop->last = ($" . $prefixLevel . "incr == ($" . $prefixLevel . "loop->length - 1)); ?>";

		}

		if (typeof($forElse) == "string")
		{
			$compilation .= "<?php $" . $forElse . "iterated = true; ?>";

		}

		$compilation .= $code;

		if (isset($statement["if_expr"]))
		{
			$compilation .= "<?php } ?>";

		}

		if (typeof($forElse) == "string")
		{
			$compilation .= "<?php } ?>";

		}

		$this->_foreachLevel--;
		return $compilation;
	}

	public function compileForElse()
	{

		$level = $this->_foreachLevel;

		if (function() { if(isset($this->_forElsePointers[$level])) {$prefix = $this->_forElsePointers[$level]; return $prefix; } else { return false; } }())
		{
			if (isset($this->_loopPointers[$level]))
			{
				return "<?php $" . $prefix . "incr++; } if (!$" . $prefix . "iterated) { ?>";
			}

			return "<?php } if (!$" . $prefix . "iterated) { ?>";
		}

		return "";
	}

	public function compileIf($statement, $extendsMode = false)
	{

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupt statement", $statement);
		}

		$compilation = "<?php if (" . $this->expression($expr) . ") { ?>" . $this->_statementList($statement["true_statements"], $extendsMode);

		if (function() { if(isset($statement["false_statements"])) {$blockStatements = $statement["false_statements"]; return $blockStatements; } else { return false; } }())
		{
			$compilation .= "<?php } else { ?>" . $this->_statementList($blockStatements, $extendsMode);

		}

		$compilation .= "<?php } ?>";

		return $compilation;
	}

	public function compileSwitch($statement, $extendsMode = false)
	{

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupt statement", $statement);
		}

		$compilation = "<?php switch (" . $this->expression($expr) . "): ?>";

		if (function() { if(isset($statement["case_clauses"])) {$caseClauses = $statement["case_clauses"]; return $caseClauses; } else { return false; } }())
		{
			$lines = $this->_statementList($caseClauses, $extendsMode);

			if (strlen($lines) !== 0)
			{
				$lines = preg_replace("/(*ANYCRLF)^\h+|\h+$|(\h){2,}/mu", "", $lines);

			}

			$compilation .= $lines;

		}

		$compilation .= "<?php endswitch ?>";

		return $compilation;
	}

	public function compileCase($statement, $caseClause = true)
	{

		if ($caseClause === false)
		{
			return "<?php default: ?>";
		}

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupt statement", $statement);
		}

		return "<?php case " . $this->expression($expr) . ": ?>";
	}

	public function compileElseIf($statement)
	{

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupt statement", $statement);
		}

		return "<?php } elseif (" . $this->expression($expr) . ") { ?>";
	}

	public function compileCache($statement, $extendsMode = false)
	{

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupt statement", $statement);
		}

		$exprCode = $this->expression($expr);

		$compilation = "<?php $_cache[" . $this->expression($expr) . "] = $this->di->get('viewCache'); ";

		if (function() { if(isset($statement["lifetime"])) {$lifetime = $statement["lifetime"]; return $lifetime; } else { return false; } }())
		{
			$compilation .= "$_cacheKey[" . $exprCode . "]";

			if ($lifetime["type"] == PHVOLT_T_IDENTIFIER)
			{
				$compilation .= " = $_cache[" . $exprCode . "]->start(" . $exprCode . ", $" . $lifetime["value"] . "); ";

			}

		}

		$compilation .= "if ($_cacheKey[" . $exprCode . "] === null) { ?>";

		$compilation .= $this->_statementList($statement["block_statements"], $extendsMode);

		if (function() { if(isset($statement["lifetime"])) {$lifetime = $statement["lifetime"]; return $lifetime; } else { return false; } }())
		{
			if ($lifetime["type"] == PHVOLT_T_IDENTIFIER)
			{
				$compilation .= "<?php $_cache[" . $exprCode . "]->save(" . $exprCode . ", null, $" . $lifetime["value"] . "); ";

			}

			$compilation .= "} else { echo $_cacheKey[" . $exprCode . "]; } ?>";

		}

		return $compilation;
	}

	public function compileSet($statement)
	{

		if (!(function() { if(isset($statement["assignments"])) {$assignments = $statement["assignments"]; return $assignments; } else { return false; } }()))
		{
			throw new Exception("Corrupted statement");
		}

		$compilation = "<?php";

		foreach ($assignments as $assignment) {
			$exprCode = $this->expression($assignment["expr"]);
			$target = $this->expression($assignment["variable"]);
			switch ($assignment["op"]) {
				case PHVOLT_T_ADD_ASSIGN:
					$compilation .= " " . $target . " += " . $exprCode . ";";
					break;
				case PHVOLT_T_SUB_ASSIGN:
					$compilation .= " " . $target . " -= " . $exprCode . ";";
					break;
				case PHVOLT_T_MUL_ASSIGN:
					$compilation .= " " . $target . " *= " . $exprCode . ";";
					break;
				case PHVOLT_T_DIV_ASSIGN:
					$compilation .= " " . $target . " /= " . $exprCode . ";";
					break;
				default:
					$compilation .= " " . $target . " = " . $exprCode . ";";
					break;

			}
		}

		$compilation .= " ?>";

		return $compilation;
	}

	public function compileDo($statement)
	{

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupted statement");
		}

		return "<?php " . $this->expression($expr) . "; ?>";
	}

	public function compileReturn($statement)
	{

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupted statement");
		}

		return "<?php return " . $this->expression($expr) . "; ?>";
	}

	public function compileAutoEscape($statement, $extendsMode)
	{

		if (!(function() { if(isset($statement["enable"])) {$autoescape = $statement["enable"]; return $autoescape; } else { return false; } }()))
		{
			throw new Exception("Corrupted statement");
		}

		$oldAutoescape = $this->_autoescape;
		$this->_autoescape = $autoescape;

		$compilation = $this->_statementList($statement["block_statements"], $extendsMode);
		$this->_autoescape = $oldAutoescape;

		return $compilation;
	}

	public function compileEcho($statement)
	{

		if (!(function() { if(isset($statement["expr"])) {$expr = $statement["expr"]; return $expr; } else { return false; } }()))
		{
			throw new Exception("Corrupt statement", $statement);
		}

		$exprCode = $this->expression($expr);

		if ($expr["type"] == PHVOLT_T_FCALL)
		{
			$name = $expr["name"];

			if ($name["type"] == PHVOLT_T_IDENTIFIER)
			{
				if ($name["value"] == "super")
				{
					return $exprCode;
				}

			}

		}

		if ($this->_autoescape)
		{
			return "<?= $this->escaper->escapeHtml(" . $exprCode . ") ?>";
		}

		return "<?= " . $exprCode . " ?>";
	}

	public function compileInclude($statement)
	{

		if (!(function() { if(isset($statement["path"])) {$pathExpr = $statement["path"]; return $pathExpr; } else { return false; } }()))
		{
			throw new Exception("Corrupted statement");
		}

		if ($pathExpr["type"] == 260)
		{
			if (!(isset($statement["params"])))
			{
				$path = $pathExpr["value"];

				$finalPath = $this->getFinalPath($path);

				$subCompiler = clone $this;

				$compilation = $subCompiler->compile($finalPath, false);

				if (typeof($compilation) == "null")
				{
					$compilation = file_get_contents($subCompiler->getCompiledTemplatePath());

				}

				return $compilation;
			}

		}

		$path = $this->expression($pathExpr);

		if (!(function() { if(isset($statement["params"])) {$params = $statement["params"]; return $params; } else { return false; } }()))
		{
			return "<?php $this->partial(" . $path . "); ?>";
		}

		return "<?php $this->partial(" . $path . ", " . $this->expression($params) . "); ?>";
	}

	public function compileMacro($statement, $extendsMode)
	{

		if (!(function() { if(isset($statement["name"])) {$name = $statement["name"]; return $name; } else { return false; } }()))
		{
			throw new Exception("Corrupted statement");
		}

		if (isset($this->_macros[$name]))
		{
			throw new Exception("Macro '" . $name . "' is already defined");
		}

		$this[$name] = $name;

		$macroName = "$this->_macros['" . $name . "']";

		$code = "<?php ";

		if (!(function() { if(isset($statement["parameters"])) {$parameters = $statement["parameters"]; return $parameters; } else { return false; } }()))
		{
			$code .= $macroName . " = function() { ?>";

		}

		if (function() { if(isset($statement["block_statements"])) {$blockStatements = $statement["block_statements"]; return $blockStatements; } else { return false; } }())
		{
			$code .= $this->_statementList($blockStatements, $extendsMode) . "<?php }; ";

		}

		$code .= $macroName . " = \\Closure::bind(" . $macroName . ", $this); ?>";

		return $code;
	}

	public function compileCall($statement, $extendsMode)
	{
	}

	final protected function _statementList($statements, $extendsMode = false)
	{

		if (!(count($statements)))
		{
			return "";
		}

		$extended = $this->_extended;

		$blockMode = $extended || $extendsMode;

		if ($blockMode === true)
		{
			$this->_blockLevel++;
		}

		$this->_level++;
		$compilation = null;

		$extensions = $this->_extensions;

		foreach ($statements as $statement) {
			if (typeof($statement) <> "array")
			{
				throw new Exception("Corrupted statement");
			}
			if (!(isset($statement["type"])))
			{
				throw new Exception("Invalid statement in " . $statement["file"] . " on line " . $statement["line"], $statement);
			}
			if (typeof($extensions) == "array")
			{
				$tempCompilation = $this->fireExtensionEvent("compileStatement", [$statement]);

				if (typeof($tempCompilation) == "string")
				{
					$compilation .= $tempCompilation;

					continue;

				}

			}
			$type = $statement["type"];
			switch ($type) {
				case PHVOLT_T_RAW_FRAGMENT:
					$compilation .= $statement["value"];
					break;
				case PHVOLT_T_IF:
					$compilation .= $this->compileIf($statement, $extendsMode);
					break;
				case PHVOLT_T_ELSEIF:
					$compilation .= $this->compileElseIf($statement);
					break;
				case PHVOLT_T_SWITCH:
					$compilation .= $this->compileSwitch($statement, $extendsMode);
					break;
				case PHVOLT_T_CASE:
					$compilation .= $this->compileCase($statement);
					break;
				case PHVOLT_T_DEFAULT:
					$compilation .= $this->compileCase($statement, false);
					break;
				case PHVOLT_T_FOR:
					$compilation .= $this->compileForeach($statement, $extendsMode);
					break;
				case PHVOLT_T_SET:
					$compilation .= $this->compileSet($statement);
					break;
				case PHVOLT_T_ECHO:
					$compilation .= $this->compileEcho($statement);
					break;
				case PHVOLT_T_BLOCK:
					$blockName = $statement["name"];
					$blockStatements = $statement["block_statements"]					$blocks = $this->_blocks;
					if ($blockMode)
					{
						if (typeof($blocks) <> "array")
						{
							$blocks = [];

						}

						if (typeof($compilation) <> "null")
						{
							$blocks = $compilation;

							$compilation = null;

						}

						$blocks[$blockName] = $blockStatements;

						$this->_blocks = $blocks;

					}
					break;
				case PHVOLT_T_EXTENDS:
					$path = $statement["path"];
					$finalPath = $this->getFinalPath($path["value"]);
					$extended = true;
					$subCompiler = clone $this;
					$tempCompilation = $subCompiler->compile($finalPath, $extended);
					if (typeof($tempCompilation) == "null")
					{
						$tempCompilation = file_get_contents($subCompiler->getCompiledTemplatePath());

					}
					$this->_extended = true;
					$this->_extendedBlocks = $tempCompilation;
					$blockMode = $extended;
					break;
				case PHVOLT_T_INCLUDE:
					$compilation .= $this->compileInclude($statement);
					break;
				case PHVOLT_T_CACHE:
					$compilation .= $this->compileCache($statement, $extendsMode);
					break;
				case PHVOLT_T_DO:
					$compilation .= $this->compileDo($statement);
					break;
				case PHVOLT_T_RETURN:
					$compilation .= $this->compileReturn($statement);
					break;
				case PHVOLT_T_AUTOESCAPE:
					$compilation .= $this->compileAutoEscape($statement, $extendsMode);
					break;
				case PHVOLT_T_CONTINUE:
					$compilation .= "<?php continue; ?>";
					break;
				case PHVOLT_T_BREAK:
					$compilation .= "<?php break; ?>";
					break;
				case 321:
					$compilation .= $this->compileForElse();
					break;
				case PHVOLT_T_MACRO:
					$compilation .= $this->compileMacro($statement, $extendsMode);
					break;
				case 325:
					$compilation .= $this->compileCall($statement, $extendsMode);
					break;
				case 358:
					break;
				default:
					throw new Exception("Unknown statement " . $type . " in " . $statement["file"] . " on line " . $statement["line"]);
			}
		}

		if ($blockMode === true)
		{
			$level = $this->_blockLevel;

			if ($level == 1)
			{
				if (typeof($compilation) <> "null")
				{
					$this->_blocks[] = $compilation;

				}

			}

			$this->_blockLevel--;
		}

		$this->_level--;
		return $compilation;
	}

	protected function _compileSource($viewCode, $extendsMode = false)
	{

		$currentPath = $this->_currentPath;

		$options = $this->_options;

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["autoescape"])) {$autoescape = $options["autoescape"]; return $autoescape; } else { return false; } }())
			{
				if (typeof($autoescape) <> "bool")
				{
					throw new Exception("'autoescape' must be boolean");
				}

				$this->_autoescape = $autoescape;

			}

		}

		$intermediate = phvolt_parse_view($viewCode, $currentPath);

		if (typeof($intermediate) <> "array")
		{
			throw new Exception("Invalid intermediate representation");
		}

		$compilation = $this->_statementList($intermediate, $extendsMode);

		$extended = $this->_extended;

		if ($extended === true)
		{
			if ($extendsMode === true)
			{
				$finalCompilation = [];

			}

			$blocks = $this->_blocks;

			$extendedBlocks = $this->_extendedBlocks;

			foreach ($extendedBlocks as $name => $block) {
				if (typeof($name) == "string")
				{
					if (isset($blocks[$name]))
					{
						$localBlock = $blocks[$name];
						$this->_currentBlock = $name;
						$blockCompilation = $this->_statementList($localBlock);

					}

					if ($extendsMode === true)
					{
						$finalCompilation[$name] = $blockCompilation;

					}

				}
			}

			return $finalCompilation;
		}

		if ($extendsMode === true)
		{
			return $this->_blocks;
		}

		return $compilation;
	}

	public function compileString($viewCode, $extendsMode = false)
	{
		$this->_currentPath = "eval code";

		return $this->_compileSource($viewCode, $extendsMode);
	}

	public function compileFile($path, $compiledPath, $extendsMode = false)
	{

		if ($path == $compiledPath)
		{
			throw new Exception("Template path and compilation template path cannot be the same");
		}

		if (!(file_exists($path)))
		{
			throw new Exception("Template file " . $path . " does not exist");
		}

		$viewCode = file_get_contents($path);

		if ($viewCode === false)
		{
			throw new Exception("Template file " . $path . " could not be opened");
		}

		$this->_currentPath = $path;

		$compilation = $this->_compileSource($viewCode, $extendsMode);

		if (typeof($compilation) == "array")
		{
			$finalCompilation = serialize($compilation);

		}

		if (file_put_contents($compiledPath, $finalCompilation) === false)
		{
			throw new Exception("Volt directory can't be written");
		}

		return $compilation;
	}

	public function compile($templatePath, $extendsMode = false)
	{

		$this->_extended = false;

		$this->_extendedBlocks = false;

		$this->_blocks = null;

		$this->_level = 0;

		$this->_foreachLevel = 0;

		$this->_blockLevel = 0;

		$this->_exprLevel = 0;

		$stat = true;

		$compileAlways = false;

		$compiledPath = "";

		$prefix = null;

		$compiledSeparator = "%%";

		$compiledExtension = ".php";

		$compilation = null;

		$options = $this->_options;

		if (typeof($options) == "array")
		{
			if (isset($options["compileAlways"]))
			{
				$compileAlways = $options["compileAlways"];

				if (typeof($compileAlways) <> "boolean")
				{
					throw new Exception("'compileAlways' must be a bool value");
				}

			}

			if (isset($options["prefix"]))
			{
				$prefix = $options["prefix"];

				if (typeof($prefix) <> "string")
				{
					throw new Exception("'prefix' must be a string");
				}

			}

			if (isset($options["compiledPath"]))
			{
				$compiledPath = $options["compiledPath"];

				if (typeof($compiledPath) <> "string")
				{
					if (typeof($compiledPath) <> "object")
					{
						throw new Exception("'compiledPath' must be a string or a closure");
					}

				}

			}

			if (isset($options["compiledSeparator"]))
			{
				$compiledSeparator = $options["compiledSeparator"];

				if (typeof($compiledSeparator) <> "string")
				{
					throw new Exception("'compiledSeparator' must be a string");
				}

			}

			if (isset($options["compiledExtension"]))
			{
				$compiledExtension = $options["compiledExtension"];

				if (typeof($compiledExtension) <> "string")
				{
					throw new Exception("'compiledExtension' must be a string");
				}

			}

			if (isset($options["stat"]))
			{
				$stat = $options["stat"];

			}

		}

		if (typeof($compiledPath) == "string")
		{
			if (!(empty($compiledPath)))
			{
				$templateSepPath = prepare_virtual_path(realpath($templatePath), $compiledSeparator);

			}

			if ($extendsMode === true)
			{
				$compiledTemplatePath = $compiledPath . $prefix . $templateSepPath . $compiledSeparator . "e" . $compiledSeparator . $compiledExtension;

			}

		}

		$realCompiledPath = $compiledTemplatePath;

		if ($compileAlways)
		{
			$compilation = $this->compileFile($templatePath, $realCompiledPath, $extendsMode);

		}

		$this->_compiledTemplatePath = $realCompiledPath;

		return $compilation;
	}

	public function getTemplatePath()
	{
		return $this->_currentPath;
	}

	public function getCompiledTemplatePath()
	{
		return $this->_compiledTemplatePath;
	}

	public function parse($viewCode)
	{

		return phvolt_parse_view($viewCode, $currentPath);
	}

	protected function getFinalPath($path)
	{

		$view = $this->_view;

		if (typeof($view) == "object")
		{
			$viewsDirs = $view->getViewsDir();

			if (typeof($viewsDirs) == "array")
			{
				foreach ($viewsDirs as $viewsDir) {
					if (file_exists($viewsDir . $path))
					{
						return $viewsDir . $path;
					}
				}

				return $viewsDir . $path;
			}

		}

		return $path;
	}


}