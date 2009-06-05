<?php

class elImagerIMagickPHP extends elImager
{
	function resize($img, $w, $h)
	{
		$ih = imagick_readimage($img);
		if ( imagick_iserror($ih) )
			{
				return $this->_setError(imagick_failedreason($ih).' '.imagick_faileddescription($ih));
			}
		if ( !imagick_resize($ih, $w, $h, IMAGICK_FILTER_UNKNOWN, 0) )
		{
			return $this->_setError(imagick_failedreason($ih).' '.imagick_faileddescription($ih));		
		}
		if ( !imagick_writeimage($ih, $img) )
		{
			return $this->_setError(imagick_failedreason($ih).' '.imagick_faileddescription($ih));
		}
		return true;
	}
}

?>