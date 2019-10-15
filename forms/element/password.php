<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Password extends Element
{
	public function render($attributes = null)
	{
		return Tag::passwordField($this->prepareAttributes($attributes));
	}


}