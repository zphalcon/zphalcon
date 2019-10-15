<?php
namespace Phalcon\Http\Request;

use Phalcon\Http\Request\FileInterface;

class File implements FileInterface
{
	protected $_name;
	protected $_tmp;
	protected $_size;
	protected $_type;
	protected $_realType;
	protected $_error;
	protected $_key;
	protected $_extension;

	public function __construct($file, $key = null)
	{

		if (function() { if(isset($file["name"])) {$name = $file["name"]; return $name; } else { return false; } }())
		{
			$this->_name = $name;

			if (defined("PATHINFO_EXTENSION"))
			{
				$this->_extension = pathinfo($name, PATHINFO_EXTENSION);

			}

		}

		if (function() { if(isset($file["tmp_name"])) {$tempName = $file["tmp_name"]; return $tempName; } else { return false; } }())
		{
			$this->_tmp = $tempName;

		}

		if (function() { if(isset($file["size"])) {$size = $file["size"]; return $size; } else { return false; } }())
		{
			$this->_size = $size;

		}

		if (function() { if(isset($file["type"])) {$type = $file["type"]; return $type; } else { return false; } }())
		{
			$this->_type = $type;

		}

		if (function() { if(isset($file["error"])) {$error = $file["error"]; return $error; } else { return false; } }())
		{
			$this->_error = $error;

		}

		if ($key)
		{
			$this->_key = $key;

		}

	}

	public function getSize()
	{
		return $this->_size;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function getTempName()
	{
		return $this->_tmp;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function getRealType()
	{

		$finfo = finfo_open(FILEINFO_MIME_TYPE);

		if (typeof($finfo) <> "resource")
		{
			return "";
		}

		$mime = finfo_file($finfo, $this->_tmp);

		finfo_close($finfo);

		return $mime;
	}

	public function isUploadedFile()
	{

		$tmp = $this->getTempName();

		return typeof($tmp) == "string" && is_uploaded_file($tmp);
	}

	public function moveTo($destination)
	{
		return move_uploaded_file($this->_tmp, $destination);
	}


}