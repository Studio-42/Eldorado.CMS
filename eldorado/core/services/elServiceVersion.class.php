<?php

class elServiceVersion extends elService 
{
	var $_pageTitle = 'Eldorado CMS version information';
	
	function defaultMethod()
	{
		$str  = '<p align="center">Eldorado CMS Core version: '.EL_VER.' "<span style="color:blue">'.EL_NAME.'</span>"</p>';
		$str .= '<div align="center"><img src="'.EL_BASE_URL.'/core/logo.jpg" /></div>';
		$this->_initRenderer();
		$this->_rnd->addToContent($str);

	}
	
}
?>