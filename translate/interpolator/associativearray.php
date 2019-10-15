<?php
namespace Phalcon\Translate\Interpolator;

use Phalcon\Translate\InterpolatorInterface;

class AssociativeArray implements InterpolatorInterface
{
	public function replacePlaceholders($translation, $placeholders = null)
	{

		if (typeof($placeholders) === "array" && count($placeholders))
		{
			foreach ($placeholders as $key => $value) {
				$translation = str_replace("%" . $key . "%", $value, $translation);
			}

		}

		return $translation;
	}


}