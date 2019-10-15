<?php
namespace Phalcon\Image\Adapter;

use Phalcon\Image\Adapter;
use Phalcon\Image\Exception;

class Imagick extends Adapter
{
	protected static $_version = 0;
	protected static $_checked = false;

	public static function check()
	{
		if (self::_checked)
		{
			return true;
		}

		if (!(class_exists("imagick")))
		{
			throw new Exception("Imagick is not installed, or the extension is not loaded");
		}

		if (defined("Imagick::IMAGICK_EXTNUM"))
		{
			self::_version = constant("Imagick::IMAGICK_EXTNUM");

		}

		self::_checked = true;

		return self::_checked;
	}

	public function __construct($file, $width = null, $height = null)
	{

		if (!(self::_checked))
		{
			self::check();

		}

		$this->_file = $file;

		$this->_image = new \Imagick();

		if (file_exists($this->_file))
		{
			$this->_realpath = realpath($this->_file);

			if (!($this->_image->readImage($this->_realpath)))
			{
				throw new Exception("Imagick::readImage " . $this->_file . " failed");
			}

			if (!($this->_image->getImageAlphaChannel()))
			{
				$this->_image->setImageAlphaChannel(constant("Imagick::ALPHACHANNEL_SET"));

			}

			if ($this->_type == 1)
			{
				$image = $this->_image->coalesceImages();

				$this->_image->clear();

				$this->_image->destroy();

				$this->_image = $image;

			}

		}

		$this->_width = $this->_image->getImageWidth();

		$this->_height = $this->_image->getImageHeight();

		$this->_type = $this->_image->getImageType();

		$this->_mime = "image/" . $this->_image->getImageFormat();

	}

	protected function _resize($width, $height)
	{

		$image = $this->_image;

		$image->setIteratorIndex(0);

		while (true) {
			$image->scaleImage($width, $height);
			if ($image->nextImage() === false)
			{
				break;

			}
		}

		$this->_width = $image->getImageWidth();

		$this->_height = $image->getImageHeight();

	}

	protected function _liquidRescale($width, $height, $deltaX, $rigidity)
	{

		$image = $this->_image;

		$image->setIteratorIndex(0);

		while (true) {
			$ret = $image->liquidRescaleImage($width, $height, $deltaX, $rigidity);
			if ($ret !== true)
			{
				throw new Exception("Imagick::liquidRescale failed");
			}
			if ($image->nextImage() === false)
			{
				break;

			}
		}

		$this->_width = $image->getImageWidth();

		$this->_height = $image->getImageHeight();

	}

	protected function _crop($width, $height, $offsetX, $offsetY)
	{

		$image = $this->_image;

		$image->setIteratorIndex(0);

		while (true) {
			$image->cropImage($width, $height, $offsetX, $offsetY);
			$image->setImagePage($width, $height, 0, 0);
			if (!($image->nextImage()))
			{
				break;

			}
		}

		$this->_width = $image->getImageWidth();

		$this->_height = $image->getImageHeight();

	}

	protected function _rotate($degrees)
	{

		$this->_image->setIteratorIndex(0);

		$pixel = new \ImagickPixel();

		while (true) {
			$this->_image->rotateImage($pixel, $degrees);
			$this->_image->setImagePage($this->_width, $this->_height, 0, 0);
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

		$this->_width = $this->_image->getImageWidth();

		$this->_height = $this->_image->getImageHeight();

	}

	protected function _flip($direction)
	{

		$func = "flipImage";

		if ($direction == \Phalcon\Image::HORIZONTAL)
		{
			$func = "flopImage";

		}

		$this->_image->setIteratorIndex(0);

		while (true) {
			$this->_image->func();
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

	}

	protected function _sharpen($amount)
	{
		$amount = $amount < 5 ? 5 : $amount;

		$amount = $amount * 3.0 * 100;

		$this->_image->setIteratorIndex(0);

		while (true) {
			$this->_image->sharpenImage(0, $amount);
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

	}

	protected function _reflection($height, $opacity, $fadeIn)
	{

		if (self::_version >= 30100)
		{
			$reflection = clone $this->_image;

		}

		$reflection->setIteratorIndex(0);

		while (true) {
			$reflection->flipImage();
			$reflection->cropImage($reflection->getImageWidth(), $height, 0, 0);
			$reflection->setImagePage($reflection->getImageWidth(), $height, 0, 0);
			if ($reflection->nextImage() === false)
			{
				break;

			}
		}

		$pseudo = $fadeIn ? "gradient:black-transparent" : "gradient:transparent-black";
		$fade = new \Imagick();

		$fade->newPseudoImage($reflection->getImageWidth(), $reflection->getImageHeight(), $pseudo);

		$opacity /= 100;

		$reflection->setIteratorIndex(0);

		while (true) {
			$ret = $reflection->compositeImage($fade, constant("Imagick::COMPOSITE_DSTOUT"), 0, 0);
			if ($ret !== true)
			{
				throw new Exception("Imagick::compositeImage failed");
			}
			$reflection->evaluateImage(constant("Imagick::EVALUATE_MULTIPLY"), $opacity, constant("Imagick::CHANNEL_ALPHA"));
			if ($reflection->nextImage() === false)
			{
				break;

			}
		}

		$fade->destroy();

		$image = new \Imagick();
		$pixel = new \ImagickPixel();
		$height = $this->_image->getImageHeight() + $height;

		$this->_image->setIteratorIndex(0);

		while (true) {
			$image->newImage($this->_width, $height, $pixel);
			$image->setImageAlphaChannel(constant("Imagick::ALPHACHANNEL_SET"));
			$image->setColorspace($this->_image->getColorspace());
			$image->setImageDelay($this->_image->getImageDelay());
			$ret = $image->compositeImage($this->_image, constant("Imagick::COMPOSITE_SRC"), 0, 0);
			if ($ret !== true)
			{
				throw new Exception("Imagick::compositeImage failed");
			}
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

		$image->setIteratorIndex(0);

		$reflection->setIteratorIndex(0);

		while (true) {
			$ret = $image->compositeImage($reflection, constant("Imagick::COMPOSITE_OVER"), 0, $this->_height);
			if ($ret !== true)
			{
				throw new Exception("Imagick::compositeImage failed");
			}
			if ($image->nextImage() === false || $reflection->nextImage() === false)
			{
				break;

			}
		}

		$reflection->destroy();

		$this->_image->clear();

		$this->_image->destroy();

		$this->_image = $image;

		$this->_width = $this->_image->getImageWidth();

		$this->_height = $this->_image->getImageHeight();

	}

	protected function _watermark($image, $offsetX, $offsetY, $opacity)
	{

		$opacity = $opacity * 100;
		$watermark = new \Imagick();
		$method = "setImageOpacity";

		if (method_exists($watermark, "getVersion"))
		{
			$version = $watermark->getVersion();

			if ($version["versionNumber"] >= 0x700)
			{
				$method = "setImageAlpha";

			}

		}

		$watermark->readImageBlob($image->render());

		$watermark->method($opacity);

		$this->_image->setIteratorIndex(0);

		while (true) {
			$ret = $this->_image->compositeImage($watermark, constant("Imagick::COMPOSITE_OVER"), $offsetX, $offsetY);
			if ($ret !== true)
			{
				throw new Exception("Imagick::compositeImage failed");
			}
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

		$watermark->clear();

		$watermark->destroy();

	}

	protected function _text($text, $offsetX, $offsetY, $opacity, $r, $g, $b, $size, $fontfile)
	{

		$opacity = $opacity * 100;
		$draw = new \ImagickDraw();
		$color = sprintf("rgb(%d, %d, %d)", $r, $g, $b);

		$draw->setFillColor(new \ImagickPixel($color));

		if ($fontfile)
		{
			$draw->setFont($fontfile);

		}

		if ($size)
		{
			$draw->setFontSize($size);

		}

		if ($opacity)
		{
			$draw->setfillopacity($opacity);

		}

		$gravity = null;

		if (typeof($offsetX) == "bool")
		{
			if (typeof($offsetY) == "bool")
			{
				$offsetX = 0;
				$offsetY = 0;

				if ($offsetX && $offsetY)
				{
					$gravity = constant("Imagick::GRAVITY_SOUTHEAST");

				}

			}

		}

		$draw->setGravity($gravity);

		$this->_image->setIteratorIndex(0);

		while (true) {
			$this->_image->annotateImage($draw, $offsetX, $offsetY, 0, $text);
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

		$draw->destroy();

	}

	protected function _mask($image)
	{

		$mask = new \Imagick();

		$mask->readImageBlob($image->render());

		$this->_image->setIteratorIndex(0);

		while (true) {
			$this->_image->setImageMatte(1);
			$ret = $this->_image->compositeImage($mask, constant("Imagick::COMPOSITE_DSTIN"), 0, 0);
			if ($ret !== true)
			{
				throw new Exception("Imagick::compositeImage failed");
			}
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

		$mask->clear();

		$mask->destroy();

	}

	protected function _background($r, $g, $b, $opacity)
	{

		$color = sprintf("rgb(%d, %d, %d)", $r, $g, $b);

		$pixel1 = new \ImagickPixel($color);

		$opacity = $opacity * 100;

		$pixel2 = new \ImagickPixel("transparent");

		$background = new \Imagick();

		$this->_image->setIteratorIndex(0);

		while (true) {
			$background->newImage($this->_width, $this->_height, $pixel1);
			if (!($background->getImageAlphaChannel()))
			{
				$background->setImageAlphaChannel(constant("Imagick::ALPHACHANNEL_SET"));

			}
			$background->setImageBackgroundColor($pixel2);
			$background->evaluateImage(constant("Imagick::EVALUATE_MULTIPLY"), $opacity, constant("Imagick::CHANNEL_ALPHA"));
			$background->setColorspace($this->_image->getColorspace());
			$ret = $background->compositeImage($this->_image, constant("Imagick::COMPOSITE_DISSOLVE"), 0, 0);
			if ($ret !== true)
			{
				throw new Exception("Imagick::compositeImage failed");
			}
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

		$this->_image->clear();

		$this->_image->destroy();

		$this->_image = $background;

	}

	protected function _blur($radius)
	{
		$this->_image->setIteratorIndex(0);

		while (true) {
			$this->_image->blurImage($radius, 100);
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

	}

	protected function _pixelate($amount)
	{

		$width = $this->_width * $amount;

		$height = $this->_height * $amount;

		$this->_image->setIteratorIndex(0);

		while (true) {
			$this->_image->scaleImage($width, $height);
			$this->_image->scaleImage($this->_width, $this->_height);
			if ($this->_image->nextImage() === false)
			{
				break;

			}
		}

	}

	protected function _save($file, $quality)
	{

		$ext = pathinfo($file, PATHINFO_EXTENSION);

		$this->_image->setFormat($ext);

		$this->_image->setImageFormat($ext);

		$this->_type = $this->_image->getImageType();

		$this->_mime = "image/" . $this->_image->getImageFormat();

		if (strcasecmp($ext, "gif") == 0)
		{
			$this->_image->optimizeImageLayers();

			$fp = fopen($file, "w");

			$this->_image->writeImagesFile($fp);

			fclose($fp);

			return ;
		}

	}

	protected function _render($extension, $quality)
	{

		$image = $this->_image;

		$image->setFormat($extension);

		$image->setImageFormat($extension);

		$image->stripImage();

		$this->_type = $image->getImageType();
		$this->_mime = "image/" . $image->getImageFormat();

		if (strcasecmp($extension, "gif") === 0)
		{
			$image->optimizeImageLayers();

		}

		return $image->getImageBlob();
	}

	public function __destruct()
	{
		if ($this->_image instanceof $\Imagick)
		{
			$this->_image->clear();

			$this->_image->destroy();

		}

	}

	public function getInternalImInstance()
	{
		return $this->_image;
	}

	public function setResourceLimit($type, $limit)
	{
		$this->_image->setResourceLimit($type, $limit);

	}


}