<?php

class elServiceVersion extends elService 
{
	var $_pageTitle = 'Eldorado CMS version information';
	
	function defaultMethod()
	{
		$str  = '<p style="text-align:center">ELDORADO.CMS Core. Version: '.EL_VER.' "<span style="color:blue">'.EL_NAME.'</span>"</p>';
		$str .= '<div  style="text-align:center"><img src="'.EL_BASE_URL.'/core/logo.gif" /></div>';
		$this->_initRenderer();
		$this->_rnd->addToContent($str);

	}
	
}
?>