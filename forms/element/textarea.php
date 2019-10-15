<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag;
use Phalcon\Forms\Element;

class TextArea extends Element
{
	public function render($attributes = null)
	{
		return Tag::textArea($this->prepareAttributes($attributes));
	}


}