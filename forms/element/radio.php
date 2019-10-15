<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Radio extends Element
{
	public function render($attributes = null)
	{
		return Tag::radioField($this->prepareAttributes($attributes, true));
	}


}