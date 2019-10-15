<?php
namespace Phalcon\Image;

use Phalcon\Image;
abstract 
class Adapter implements AdapterInterface
{
	protected $_image;
	protected $_file;
	protected $_realpath;
	protected $_width;
	protected $_height;
	protected $_type;
	protected $_mime;
	protected static $_checked = false;

	public function resize($width = null, $height = null, $master = Image::AUTO)
	{

		if ($master == Image::TENSILE)
		{
			if (!($width) || !($height))
			{
				throw new Exception("width and height must be specified");
			}

		}

		$width = (int) max(round($width), 1);

		$height = (int) max(round($height), 1);

		$this->_resize($width, $height);

		return $this;
	}

	public function liquidRescale($width, $height, $deltaX = 0, $rigidity = 0)
	{
		$this->_liquidRescale($width, $height, $deltaX, $rigidity);

		return $this;
	}

	public function crop($width, $height, $offsetX = null, $offsetY = null)
	{
		if (is_null($offsetX))
		{
			$offsetX = $this->_width - $width * 2;

		}

		if (is_null($offsetY))
		{
			$offsetY = $this->_height - $height * 2;

		}

		if ($width > $this->_width - $offsetX)
		{
			$width = $this->_width - $offsetX;

		}

		if ($height > $this->_height - $offsetY)
		{
			$height = $this->_height - $offsetY;

		}

		$this->_crop($width, $height, $offsetX, $offsetY);

		return $this;
	}

	public function rotate($degrees)
	{
		if ($degrees > 180)
		{
			$degrees = $degrees % 360;

			if ($degrees > 180)
			{
				$degrees -= 360;

			}

		}

		$this->_rotate($degrees);

		return $this;
	}

	public function flip($direction)
	{
		if ($direction <> Image::HORIZONTAL && $direction <> Image::VERTICAL)
		{
			$direction = Image::HORIZONTAL;

		}

		$this->_flip($direction);

		return $this;
	}

	public function sharpen($amount)
	{
		if ($amount > 100)
		{
			$amount = 100;

		}

		$this->_sharpen($amount);

		return $this;
	}

	public function reflection($height, $opacity = 100, $fadeIn = false)
	{
		if ($height <= 0 || $height > $this->_height)
		{
			$height = (int) $this->_height;

		}

		if ($opacity < 0)
		{
			$opacity = 0;

		}

		$this->_reflection($height, $opacity, $fadeIn);

		return $this;
	}

	public function watermark($watermark, $offsetX = 0, $offsetY = 0, $opacity = 100)
	{

		$tmp = $this->_width - $watermark->getWidth();

		if ($offsetX < 0)
		{
			$offsetX = 0;

		}

		$tmp = $this->_height - $watermark->getHeight();

		if ($offsetY < 0)
		{
			$offsetY = 0;

		}

		if ($opacity < 0)
		{
			$opacity = 0;

		}

		$this->_watermark($watermark, $offsetX, $offsetY, $opacity);

		return $this;
	}

	public function text($text, $offsetX = false, $offsetY = false, $opacity = 100, $color = "000000", $size = 12, $fontfile = null)
	{

		if ($opacity < 0)
		{
			$opacity = 0;

		}

		if (strlen($color) > 1 && substr($color, 0, 1) === "#")
		{
			$color = substr($color, 1);

		}

		if (strlen($color) === 3)
		{
			$color = preg_replace("/./", "$0$0", $color);

		}

		$colors = array_map("hexdec", str_split($color, 2));

		$this->_text($text, $offsetX, $offsetY, $opacity, $colors[0], $colors[1], $colors[2], $size, $fontfile);

		return $this;
	}

	public function mask($watermark)
	{
		$this->_mask($watermark);

		return $this;
	}

	public function background($color, $opacity = 100)
	{

		if (strlen($color) > 1 && substr($color, 0, 1) === "#")
		{
			$color = substr($color, 1);

		}

		if (strlen($color) === 3)
		{
			$color = preg_replace("/./", "$0$0", $color);

		}

		$colors = array_map("hexdec", str_split($color, 2));

		$this->_background($colors[0], $colors[1], $colors[2], $opacity);

		return $this;
	}

	public function blur($radius)
	{
		if ($radius < 1)
		{
			$radius = 1;

		}

		$this->_blur($radius);

		return $this;
	}

	public function pixelate($amount)
	{
		if ($amount < 2)
		{
			$amount = 2;

		}

		$this->_pixelate($amount);

		return $this;
	}

	public function save($file = null, $quality = -1)
	{
		if (!($file))
		{
			$file = (string) $this->_realpath;

		}

		$this->_save($file, $quality);

		return $this;
	}

	public function render($ext = null, $quality = 100)
	{
		if (!($ext))
		{
			$ext = (string) pathinfo($this->_file, PATHINFO_EXTENSION);

		}

		if (empty($ext))
		{
			$ext = "png";

		}

		if ($quality < 1)
		{
			$quality = 1;

		}

		return $this->_render($ext, $quality);
	}


}