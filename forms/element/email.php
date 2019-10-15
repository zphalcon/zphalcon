<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Email extends Element
{
	public function render($attributes = null)
	{
		return Tag::emailField($this->prepareAttributes($attributes));
	}


}