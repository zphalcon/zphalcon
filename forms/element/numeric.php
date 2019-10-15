<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Numeric extends Element
{
	public function render($attributes = null)
	{
		return Tag::numericField($this->prepareAttributes($attributes));
	}


}