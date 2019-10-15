<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Date extends Element
{
	public function render($attributes = null)
	{
		return Tag::dateField($this->prepareAttributes($attributes));
	}


}