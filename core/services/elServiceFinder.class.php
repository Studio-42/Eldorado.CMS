<?php

include_once EL_DIR_CORE.'lib/elJSON.class.php';
if (substr(PHP_VERSION, 0, 1) > 4) {
	include_once EL_DIR_CORE.'lib/elFinder5.class.php';
} else {
	include_once EL_DIR_CORE.'lib/elFinder.class.php';
}


elLoadMessages('Finder');

class elServiceFinder extends elService 
{
	var $_fm = null;
	
	function run() {
		$ats = & elSingleton::getObj('elATS');
		$nav = & elSingleton::getObj('elNavigator');
		$pageID = $nav->getCurrentPageID(); 
		if (!$ats->allow(EL_WRITE, $pageID))
		{
			exit(elJSON::encode(array('error' => m('Access denied'))));
		}
		
		$page = $nav->getCurrentPage();
		if ($page['module'] == 'TemplatesEditor') 
		{
			$root = realpath('./style');
			$url = EL_BASE_URL.'/style/';
		}
		else 
		{
			$root = realpath(EL_DIR_STORAGE);
			$url = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/';
		}

		$opts = array(
			'root' => $root,
			'URL'  => $url,
			// 'debug' => true,
			// 'mimeDetect' => 'internal'
			);
		$fm = & new elFinder($opts); 
		$fm->run();
	}
	

	function stop() {}
}

?>