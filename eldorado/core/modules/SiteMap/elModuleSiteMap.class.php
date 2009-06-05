<?php

class elModuleSiteMap extends elModule
{
	var $_defMethodNoArgs = true;
	var $_mMapConf = array();

	function defaultMethod()
	{
		$nav   = & elSingleton::getObj( 'elNavigator' );
		$pages = $nav->getPages(0, 0, true, true, EL_PAGE_DISPL_MAP);

		$this->_initRenderer();
		$this->_rnd->render( $pages, null, 'MAP_PAGE');
	}
}

?>