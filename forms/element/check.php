<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Check extends Element
{
	public function render($attributes = null)
	{
		return Tag::checkField($this->prepareAttributes($attributes, true));
	}


}