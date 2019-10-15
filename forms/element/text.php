<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Text extends Element
{
	public function render($attributes = null)
	{
		return Tag::textField($this->prepareAttributes($attributes));
	}


}