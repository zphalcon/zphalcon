<?php
namespace Phalcon\Validation;


interface ValidatorInterface
{
	public function hasOption($key)
	{
	}

	public function getOption($key, $defaultValue = null)
	{
	}

	public function validate($validation, $attribute)
	{
	}


}