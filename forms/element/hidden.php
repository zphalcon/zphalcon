<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Hidden extends Element
{
	public function render($attributes = null)
	{
		return Tag::hiddenField($this->prepareAttributes($attributes));
	}


}