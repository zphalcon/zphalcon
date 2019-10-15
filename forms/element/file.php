<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class File extends Element
{
	public function render($attributes = null)
	{
		return Tag::fileField($this->prepareAttributes($attributes));
	}


}