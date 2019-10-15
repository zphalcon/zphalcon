<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class Submit extends Element
{
	public function render($attributes = null)
	{
		return Tag::submitButton($this->prepareAttributes($attributes));
	}


}