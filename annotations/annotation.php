<?php
namespace Phalcon\Annotations;

use Phalcon\Annotations\Annotation;
use Phalcon\Annotations\Exception;

class Annotation
{
	protected $_name;
	protected $_arguments;
	protected $_exprArguments;

	public function __construct($reflectionData)
	{

		$this->_name = $reflectionData["name"];

		if (function() { if(isset($reflectionData["arguments"])) {$exprArguments = $reflectionData["arguments"]; return $exprArguments; } else { return false; } }())
		{
			$arguments = [];

			foreach ($exprArguments as $argument) {
				$resolvedArgument = $this->getExpression($argument["expr"]);
				if (function() { if(isset($argument["name"])) {$name = $argument["name"]; return $name; } else { return false; } }())
				{
					$arguments[$name] = $resolvedArgument;

				}
			}

			$this->_arguments = $arguments;

			$this->_exprArguments = $exprArguments;

		}

	}

	public function getName()
	{
		return $this->_name;
	}

	public function getExpression($expr)
	{

		$type = $expr["type"];

		switch ($type) {
			case PHANNOT_T_INTEGER:
			case PHANNOT_T_DOUBLE:
			case PHANNOT_T_STRING:
			case PHANNOT_T_IDENTIFIER:
				$value = $expr["value"];
				break;
			case PHANNOT_T_NULL:
				$value = null;
				break;
			case PHANNOT_T_FALSE:
				$value = false;
				break;
			case PHANNOT_T_TRUE:
				$value = true;
				break;
			case PHANNOT_T_ARRAY:
				$arrayValue = [];
				foreach ($expr["items"] as $item) {
					$resolvedItem = $this->getExpression($item["expr"]);
					if (function() { if(isset($item["name"])) {$name = $item["name"]; return $name; } else { return false; } }())
					{
						$arrayValue[$name] = $resolvedItem;

					}
				}
				return $arrayValue;			case PHANNOT_T_ANNOTATION:
				return new Annotation($expr);			default:
				throw new Exception("The expression " . $type . " is unknown");
		}

		return $value;
	}

	public function getExprArguments()
	{
		return $this->_exprArguments;
	}

	public function getArguments()
	{
		return $this->_arguments;
	}

	public function numberArguments()
	{
		return count($this->_arguments);
	}

	public function getArgument($position)
	{

		if (function() { if(isset($this->_arguments[$position])) {$argument = $this->_arguments[$position]; return $argument; } else { return false; } }())
		{
			return $argument;
		}

	}

	public function hasArgument($position)
	{
		return isset($this->_arguments[$position]);
	}

	public function getNamedArgument($name)
	{

		if (function() { if(isset($this->_arguments[$name])) {$argument = $this->_arguments[$name]; return $argument; } else { return false; } }())
		{
			return $argument;
		}

	}

	public function getNamedParameter($name)
	{
		return $this->getNamedArgument($name);
	}


}