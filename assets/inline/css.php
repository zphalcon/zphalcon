<?php
namespace Phalcon\Assets\Inline;

use Phalcon\Assets\Inline as InlineBase;

class Css extends InlineBase
{
	public function __construct($content, $filter = true, $attributes = null)
	{
		if ($attributes == null)
		{
			$attributes = ["type" => "text/css"];

		}

		parent::__construct("css", $content, $filter, $attributes);

	}


}