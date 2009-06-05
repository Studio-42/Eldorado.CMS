<?php

class elImagerIMagick extends elImager
{
	var $_path = '';
	
	function __construct()
	{
		$this->_path = exec('which mogrify');
	}
	
	function elImagerIMagick()
	{
		$this->__construct();
	}
	
	function resize($img, $w, $h)
	{
		$cmd    = $this->_path.' -scale '.$w.'x'.$h.' '.escapeshellarg($img);
		$output = array();
		$ret    = null;
		exec($cmd, $output, $ret);
		return 0 == $ret;
		
	}
}

?>