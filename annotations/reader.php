<?php
namespace Phalcon\Annotations;

use Phalcon\Annotations\ReaderInterface;

class Reader implements ReaderInterface
{
	public function parse($className)
	{

		$annotations = [];

		$reflection = new \ReflectionClass($className);

		$comment = $reflection->getDocComment();

		if (typeof($comment) == "string")
		{
			$classAnnotations = phannot_parse_annotations($comment, $reflection->getFileName(), $reflection->getStartLine());

			if (typeof($classAnnotations) == "array")
			{
				$annotations["class"] = $classAnnotations;

			}

		}

		$properties = $reflection->getProperties();

		if (count($properties))
		{
			$line = 1;

			$annotationsProperties = [];

			foreach ($properties as $property) {
				$comment = $property->getDocComment();
				if (typeof($comment) == "string")
				{
					$propertyAnnotations = phannot_parse_annotations($comment, $reflection->getFileName(), $line);

					if (typeof($propertyAnnotations) == "array")
					{
						$annotationsProperties[$property->name] = $propertyAnnotations;

					}

				}
			}

			if (count($annotationsProperties))
			{
				$annotations["properties"] = $annotationsProperties;

			}

		}

		$methods = $reflection->getMethods();

		if (count($methods))
		{
			$annotationsMethods = [];

			foreach ($methods as $method) {
				$comment = $method->getDocComment();
				if (typeof($comment) == "string")
				{
					$methodAnnotations = phannot_parse_annotations($comment, $method->getFileName(), $method->getStartLine());

					if (typeof($methodAnnotations) == "array")
					{
						$annotationsMethods[$method->name] = $methodAnnotations;

					}

				}
			}

			if (count($annotationsMethods))
			{
				$annotations["methods"] = $annotationsMethods;

			}

		}

		return $annotations;
	}

	public static function parseDocBlock($docBlock, $file = null, $line = null)
	{
		if (typeof($file) <> "string")
		{
			$file = "eval code";

		}

		return phannot_parse_annotations($docBlock, $file, $line);
	}


}