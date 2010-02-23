<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFileInfo.class.php';

class elImage {
	
	var $error = '';
	var $_lib  = '';
	
	function __construct()
	{
		if (extension_loaded('imagick'))
		{
			$this->_lib = 'imagick';
		}
		exec('mogrify --version', $o, $c);
		if ($c == 0 && !empty($o))
		{
			$this->_lib = 'mogrify';
		}
		elseif (function_exists('gd_info'))
		{
			$this->_lib = 'gd';
		}
	}
	
	function elImage()
	{
		$this->__construct();
	}
	
	function allowResize()
	{
		return $this->_lib;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function calcTmbSize($imgW, $imgH, $tmbW, $tmbH, $crop=false)
	{
		if ( $imgW >= $imgH )
		{
			if (!$crop)
			{
				$width  = $tmbW;
				$height = ceil(($imgH*$width)/$imgW);
			}
			else
			{
				$height = $tmbH;
				$width  = ceil(($imgW*$height)/$imgH);
			}
		}
		else
		{
			if (!$crop)
			{
				$height = $tmbH;
				$width  = ceil(($imgW*$height)/$imgH);
			}
			else
			{
				$width  = $tmbW;
				$height = ceil(($imgH*$width)/$imgW);
			}
		}
		return array($width, $height);
	}
	
	function _cropPos($w, $h)
	{
		$x = $y = 0;
		$size = min($w, $h);
		if ($w > $h) {
			$x = ceil(($w - $h)/2);
		} else {
			$y = ceil(($h - $w)/2);
		}
		return array($x, $y, $size);
	}
	
	function imageInfo($img)
	{
		$path = realpath($img); //echo "img: $img path: $path";
		if (empty($img) || !$path || !file_exists($path) )
		{
			return $this->_error('File %s does not exists', $img?$img:'');
		}
		if (!is_readable($path))
		{
			return $this->_error('File %s is not readable', $img);
		}
		$s = getimagesize($path);

		if (!$s || ($s['mime'] != 'image/gif' && $s['mime'] != 'image/png' && $s['mime'] != 'image/jpeg'))
		{
			return $this->_error('File "%s" is not an image or has unsupported type', $img);
		}
		return array(
			'basename' => basename($path),
			'path'     => $path,
			'mime'     => $s['mime'],
			'width'    => $s[0],
			'height'   => $s[1]
			);
	}
	
	function tmb($img, $tmb, $w, $h, $crop=true)
	{
		if (false == ($info = $this->imageInfo($img)))
		{
			return false;
		} 
		$_tmb = $tmb;
		if (is_dir($tmb))
		{
			$_tmb = realpath($tmb).DIRECTORY_SEPARATOR.$info['basename'];
		}
		if (!@copy($info['path'], $_tmb))
		{
			return $this->_error('Could not copy %s to %s!', array($img, $tmb));
		}
		$info['path'] = realpath($_tmb);
		$info['basename'] = basename($_tmb);
		return $this->_resize($info, $w, $h, $crop);
	}
	
	function resize($img, $w, $h, $crop=true)
	{
		if (false == ($info = $this->imageInfo($img)))
		{
			return false;
		}	
		return $this->_resize($info, $w, $h, $crop);
	}
	
	function watermark($img, $wm, $pos='br')
	{
		if (false == ($imgInfo = $this->imageInfo($img)) || false == ($wmInfo = $this->imageInfo($wm)))
		{
			return false;
		}
		switch ($this->_lib)
		{
			case 'imagick': return $this->_watermarkGD($imgInfo, $wmInfo, $pos);
			case 'mogrify': return $this->_watermarkGD($imgInfo, $wmInfo, $pos);
			case 'gd'     : return $this->_watermarkGD($imgInfo, $wmInfo, $pos);
			default       : return $this->_error('There are no one libs for image manipulation');
		}
	}
	
	function _resize($info, $w, $h, $crop)
	{
		switch ($this->_lib)
		{
			case 'imagick': return $this->_resizeImagick($info, $w, $h, $crop);
			case 'mogrify': return $this->_resizeMogrify($info, $w, $h, $crop);
			case 'gd'     : return $this->_resizeGD($info, $w, $h, $crop );
			default       : return $this->_error('There are no one libs for image manipulation');
		}
	}
	
	function _resizeGD($image, $w, $h, $crop=true)
	{
		if ($image['mime'] == 'image/jpeg') {
			$_img = imagecreatefromjpeg($image['path']);
		} elseif ($image['mime'] = 'image/png') {
			$_img = imagecreatefrompng($image['path']);
		} elseif ($image['mime'] = 'image/gif') {
			$_img = imagecreatefromgif($image['path']);
		} else {
			return $this->_error('File "%s" is not an image or has unsupported type', $image['basename']);;
		}
		if (!$_img || false == ($_tmb = imagecreatetruecolor($w, $w))) {
			return $this->_error('Unable to resize image %s', $image['basename']);
		}
		list($x, $y, $size) = $this->_cropPos($image['width'], $image['height']);
		if (!imagecopyresampled($_tmb, $_img, 0, 0, $x, $y, $w, $w, $size, $size)) {
			return $this->_error('Unable to resize image %s', $image['basename']);
		}
		if ($image['mime'] == 'image/jpeg') {
			$r = imagejpeg($_tmb, $image['path']);
		} elseif ($image['mime'] = 'image/png') {
			$r = imagepng($_tmb, $image['path'], 7);
		} elseif ($image['mime'] = 'image/gif') {
			$r = imagegif($_tmb, $image['path']);
		}
		imagedestroy($_img);
		imagedestroy($_tmb);
		return $r ? $image['path'] : $this->_error('Unable to resize image %s', $image['basename']);
	}
	
	function _resizeImagick($image, $w, $h, $crop=true)
	{
		$i = new imagick($image['path']);
		if ($w<300 || $h<300)
		{
			$i->contrastImage(1);
			//$image->adaptiveBlurImage( 1, 1 );
		}
		if ($crop)
		{
			$i->cropThumbnailImage($w, $h);
		}
		else
		{
			$i->thumbnailImage($w, $h, true);
		}
		
		$result = $i->writeImage($image['path']);
		$i->destroy();
		return $result ? $image['path'] : false;
	}
	
	function _resizeMogrify($image, $w, $h, $crop=true)
	{
		exec('mogrify -scale '.$w.'x'.$h.' '.escapeshellarg($image['path']), $o, $c);
		return 0 == $c ? $image['path'] : false;
	}
	
	function _watermarkGD($imgInfo, $wmInfo, $pos)
	{
		$imgMethods = $this->_gdMethods($imgInfo['mime']);
		$wmMethods = $this->_gdMethods($wmInfo['mime']);
		if (empty($imgMethods))
		{
			return $this->_error('File "%s" is not an image or has unsupported type', $imgInfo['basename']);
		}
		if (empty($wmMethods))
		{
			return $this->_error('File "%s" is not an image or has unsupported type', $wmInfo['basename']);
		}
		if (false === ($orig = $imgMethods[0]($imgInfo['path'])))
		{
			return $this->_error('Unable to load image %s', $imgInfo['basename']);
		}
		if (false === ($wm = $wmMethods[0]($wmInfo['path'])))
		{
			return $this->_error('Unable to load image %s', $wmInfo['basename']);
		}
		$wOrig = imagesx( $orig );
		$hOrig = imagesy( $orig );
		$wWm   = imagesx( $wm );
		$hWm   = imagesy( $wm );
		switch ($pos)
		{
			case 'tl':
				$x = $y = 0;
				break;
			case 'tr':
				$x = $wOrig - $wWm;
				$y = 0;
				break;
			case 'c':
				$x = ($wOrig - $wWm)/2;
				$y = ($hOrig - $hWm)/2;
				break;
			case 'bl':
				$x = 0;
				$y = $hOrig - $hWm;
				break;
			default:
				$x = $wOrig - $wWm;
				$y = $hOrig - $hWm;
				
		}
		$out = imagecreatetruecolor($wOrig, $hOrig);
		imagealphablending($out, TRUE);
		
		imagecopy($out, $orig, 0, 0, 0, 0, $wOrig, $hOrig);
		imagecopy($out, $wm, $x, $y, 0, 0, $wWm, $hWm);
		imagedestroy($wm);
		imagedestroy($orig);
		$imgMethods[1]($out, $imgInfo['path'], 100);
		imagedestroy($out);
		
		return true;
		echo 'HERE';
	}
	
	
	function _error($err, $args=null)
	{
		elLoadMessages('Errors');
		$this->error = $args ? vsprintf(m($err), $args) : m($err);
		elMsgBox::put($this->error, EL_WARNQ); 
		return false;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function _gdMethods($mime)
	{
		switch ($mime)
		{
			case 'image/jpeg': return imagetypes() & IMG_JPG ? array('imagecreatefromjpeg', 'imagejpeg', 100)  : false;
			case 'image/gif' : return imagetypes() & IMG_GIF ? array('imagecreatefromgif',  'imagegif',  null) : false;
			case 'image/png' : return imagetypes() & IMG_PNG ? array('imagecreatefrompng',  'imagepng',  5)   : false;
		}
	}
}

?>