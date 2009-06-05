<?php

define('EL_IMG_IMAGICK_PHP', 1);
define('EL_IMG_IMAGICK',     2);
define('EL_IMG_GD_PHP',      3);

class elImager
{
	var $_map      = array(
												EL_IMG_IMAGICK_PHP => 'elImagerIMagickPHP',
												EL_IMG_IMAGICK     => 'elImagerIMagick',
												EL_IMG_GD_PHP      => 'elImagerGDPHP'
												);
	var $_imgTypes = array(
												IMAGETYPE_GIF  => 'gif',
												IMAGETYPE_JPEG => 'jpg',
												IMAGETYPE_PNG  => 'png'
												);
	var $_error       = '';

	function __construct()
	{
		if (get_class($this) != 'elimager')
		{
			return;
		}
	    if ( function_exists('gd_info') )
	    {
			$class = $this->_map[EL_IMG_GD_PHP];
	    }
		elseif (function_exists('imagick_readimage'))
		{
			$class = $this->_map[EL_IMG_IMAGICK_PHP];
		}
		elseif ( false != ($mogrPath = exec('which mogrify')) && '/' == $mogrPath[0] )
		{
			$class = $this->_map[EL_IMG_IMAGICK];
		}
		//elseif ( function_exists('gd_info') )
		//{
		//	$class = $this->_map[EL_IMG_GD_PHP];
		//}
		else
		{
			$this->_setError('There are no one libs for image manipulation');
			return;
		}
		elSingleton::incLib( 'lib/imglib/'.$class.'.class.php');
		$this = new $class;
	}

	function elImager()
	{
		$this->__construct();
	}

	function makeTmb($img, $dir='tmb/', $w=125, $h=125, $rmOnFailed=false)
	{
		$img = realpath($img);
		if ( !$this->getInfo($img, true) )
		{
			return false;
		}

		$dir = '/' == $dir{0}	? $dir : dirname($img).'/'.$dir;

		if ( ( !is_dir($dir) && !mkdir($dir, 0775)) )
		{
			return $this->_setError('Could not create directory %s', $dir);
		}
		elseif (  !is_writable($dir) )
		{
			return $this->_setError('Directory %s is not writable', $dir);
		}

		$tmb = $dir.'/'.basename($img);
		if ( !copy($img, $tmb) )
		{
			return $this->_setError('Could not copy %s to %s!', array($img, $tmb));
		}
		chmod($tmb, 0664);
		if ( !$this->resize($tmb, $w, $h) || false == ($s = getimagesize($tmb)) )
		{
			if ($rmOnFailed)
			{
				@unlink($tmb);
			}
			return $this->_setError('Could not make thumbnail for image %s', $tmb);
		}
		return array($s[0], $s[1]);
	}

	function resize($img, $w, $h)
	{
		return false;
	}

	function copyResized($src, $dst, $w, $h)
	{
	   if (!copy($src, $dst))
	   {
		 return $this->_setError('Could not copy %s to %s!', array($src, $dst));
	   }
	   return $this->resize($dst, $w, $h);
	}

	function calcTmbSize($w, $h, $tmbW, $tmbH)
	{
		$tmbW = $tmbH = 0;
		if ( $tmbW/$w <= $tmbH/$h )
		{
			$retW = $tmbW;
			$retH = ceil($h*$retW/$w);
		}
		else
		{
			$retH = $tmbH;
			$retW = ceil($w*$retH/$h);
		}
		return array($retW, $retH);
	}


	function getInfo($file)
	{
		if ( !is_file($file) )
		{
			return $this->_setError('File %s does not exists', $file);
		}
		if ( false == ( $s = getimagesize($file) ) || empty($this->_imgTypes[$s[2]]) )
		{
			return $this->_setError('File "%s" is not an image or has unsupported type', $file);
		}
		return array($s[0], $s[1], $this->_imgTypes[$s[2]]);
	}


	function getError()
	{
		return $this->_error;
	}
	//**************************************************//
	//					PRIVATE METHODS
	//**************************************************//


	function _setError($msg, $args=null)
	{
		elLoadMessages('Errors');
		$this->_error = (!$args ? m($msg) : vsprintf(m($msg), $args))."\n";
	}

	function _getExt($incDot=true)
	{
		$ext = !empty($this->_imgTypes[$this->_info[2]]) ? $this->_imgTypes[$this->_info[2]] : '';
		return $incDot ? '.'.$ext : $ext;
	}

}


?>