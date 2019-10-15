<?php
namespace Phalcon\Translate\Interpolator;

use Phalcon\Translate\InterpolatorInterface;

class IndexedArray implements InterpolatorInterface
{
	public function replacePlaceholders($translation, $placeholders = null)
	{
		if (typeof($placeholders) === "array" && count($placeholders))
		{
			array_unshift($placeholders, $translation);

			return call_user_func_array("sprintf", $placeholders);
		}

		return $translation;
	}


}