<?php
class elSubModuleCounersControl extends elModule
{
	var $_mMapAdmin = array();
	
	function defaultMethod()
	{
		$this->_initRenderer();
		$this->_rnd->addToContent( file_get_contents(EL_DIR_CONF.'counters.html'));
	}
}

?>