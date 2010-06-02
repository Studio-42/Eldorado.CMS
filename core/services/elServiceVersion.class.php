<?php

class elServiceVersion extends elService 
{
	var $_pageTitle = 'Eldorado CMS version information';
	
	function defaultMethod()
	{
		if ($this->_args[0] == 'raw')
		{
			exit('ELDORADO.CMS:'.EL_VER.':'.EL_NAME);
		}
		$str  = '<p style="text-align:center">ELDORADO.CMS Core. Version: '.EL_VER.' "<span style="color:blue">'.EL_NAME.'</span>"</p>';
		$str .= '<div style="text-align:center"><img src="'.EL_BASE_URL.'/style/images/logo.gif" /></div>';
		$this->_initRenderer();
		$this->_rnd->addToContent($str);

	}
	
}
?>
