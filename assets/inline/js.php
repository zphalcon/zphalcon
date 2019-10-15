<?php
namespace Phalcon\Assets\Inline;

use Phalcon\Assets\Inline as InlineBase;

class Js extends InlineBase
{
	public function __construct($content, $filter = true, $attributes = null)
	{
		if ($attributes == null)
		{
			$attributes = ["type" => "text/javascript"];

		}

		parent::__construct("js", $content, $filter, $attributes);

	}


}