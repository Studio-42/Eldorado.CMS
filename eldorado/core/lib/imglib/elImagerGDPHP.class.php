<?php

class elImagerGDPHP extends elImager
{
  function resize($img, $w, $h)
  {
    $gd  = & elSingleton::getObj('elGDImg'); //echo 'use gd';
    $tmp = realpath($img).'/'.rand();

		if (! $gd->createFromFile($img) )
		{
			return $this->_setError($gd->getErrorMsg());
		}
		if ( !$gd->resample( $w, $h) )
		{
			return $this->_setError(E_USER_WARNING, $gd->getErrorMsg());
		}
		if ( $gd->save($img) )
		{
			return $this->_setError(E_USER_WARNING, $gd->getErrorMsg());
		}
		$gd->destroy();
		return true;
  }

}


class elGDImg
{
	var $_im     = null;
	var $_type   = IMAGETYPE_JPEG;
	var $_width  = 100;
	var $_height = 100;
	var $_exifOn = false;
	var $_error  = '';


	var $_hndl  = array(
  	IMAGETYPE_JPEG => array('r'=>'imageCreateFromJpeg', 'w'=>'imageJpeg' ),
  	IMAGETYPE_GIF  => array('r'=>'imageCreateFromGif',  'w'=>'imageGif'  ),
  	IMAGETYPE_PNG  => array('r'=>'imageCreateFromPng',  'w'=>'imagePng'  )
  	);

	var $_ext = array(
  	'gif'  => IMAGETYPE_GIF,
  	'jpg'  => IMAGETYPE_JPEG,
  	'jpeg' => IMAGETYPE_JPEG,
  	'png'  => IMAGETYPE_PNG
  	);

	var $_errorMsgs = array(
  	0 => 'Could not create image',
  	1 => 'Could not read image file',
  	2 => 'Could not write image file',
  	3 => 'Image width and height must be greater then 0',
  	4 => 'File does not exists or has usupported image format',
  	5 => 'Could not save image in requested format, use default',
  	6 => 'Could not save image in any format! Check stupid GD installation!'
  	);

	function elGDImg()
	{
		$nfo = gd_info();

		if ( !$nfo['GIF Read Support'] )
		{
			unset($this->_hndl[IMAGETYPE_GIF]['r']);
		}
		if ( !$nfo['GIF Create Support'] )
		{
			unset($this->_hndl[IMAGETYPE_GIF]['w']);
		}
		if ( !$nfo['JPG Support'] )
		{
			unset($this->_hndl[IMAGETYPE_JPEG]);
		}
		if ( !$nfo['PNG Support'] )
		{
			unset($this->_hndl[IMAGETYPE_PNG]);
		}

		$this->_exifOn = function_exists('exif_on');

	}

	function create( $w, $h )
	{
		if ( $w<=0 || $h<=0 )
		{
			return $this->_error(3);
		}

		$this->_width   = $w;
		$this->_height  = $h;
		$this->_im = imageCreateTrueColor( $this->_width,
		$this->_height );
		return $this->_im ? true : $this->_error(0);
	}

	function createFromFile( $file )
	{
		$t = $this->_imageType( $file );
		if ( empty($this->_hndl[$t]['r']) )
		{
			return $this->_error(4);
		}

		$this->_type = $t;
		$reader      = $this->_hndl[$this->_type]['r'];

		if ( false == ($this->_im = $reader($file) ) )
		{
			return $this->_error(1);
		}

		$this->_width  = imageSX($this->_im);
		$this->_height = imageSY($this->_im);

		return true;
	}

	function getWidth()
	{
		return $this->_width;
	}

	function getHeight()
	{
		return $this->_height;
	}

	function getErrorMsg()
	{
		return $this->_error;
	}

	function getMimeType()
	{
		return $this->_type ? image_type_to_mime_type($this->_type) : '';
	}

	function resample($w, $h)
	{
		if ( $w<=0 || $h<=0 )
		{
			return $this->_error(3);
		}
		if ( $w/$this->_width <= $h/$this->_height )
		{
			$width  = $w;
			$height = ceil($this->_height*$width/$this->_width);
		}
		else
		{
			$height = $h;
			$width = ceil($this->_width*$height/$this->_height);
		}

		$im = imageCreateTrueColor($width, $height);
		imageCopyResampled($im, $this->_im, 0,0,0,0, $width, $height, $this->_width, $this->_height);

		imagedestroy($this->_im);
		$this->_im = &$im;
		return true;
	}

	function save( $file )
	{
		if ( null == ($t = $this->_imageTypeForWrite($file) ) )
		{
			return $this->_error(6);
		}
		$this->_type = $t;
		$writer      = $this->_hndl[$this->_type]['w'];
		$writer($this->_im, $file);
	}


	function destroy()
	{
		if ( $this->_im )
		{
			imagedestroy($this->_im);
		}
	}

	function _imageTypeForWrite( $f )
	{
		$ext = preg_replace('/.*\./i', "", $f);
		if ( $ext && !empty($this->_ext[$ext]) && !empty($this->_hndl[$this->_ext[$ext]]['w']) )
		{
			return $this->_ext[$ext];
		}
		elseif ( !empty($this->_hndl[$this->_type]['w']) )
		{
			$this->_error(5);
			$this->_type;
		}
		foreach ( $this->_hndl as $t=>$v )
		{
			if ( !empty($v['w']) )
			{
				$this->_error(5);
				return $t;
			}
		}
		return null;
	}

	function _imageType($f)
	{
		if ( $this->_exifOn )
		{
			return exif_imagetype($f);
		}
		elseif ( false != ($s = getimagesize($f)) )
		{
			return $s[2];
		}
		return false;
	}

	function _error( $ndx )
	{
		$this->_error = !empty($this->_errorMsgs[$ndx]) ? $this->_errorMsgs[$ndx] : 'ERROR';
		return false;
	}

}


?>