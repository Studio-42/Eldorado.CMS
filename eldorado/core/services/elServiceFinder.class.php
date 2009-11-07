<?php
include_once EL_DIR_CORE.'lib/elFS.class.php';
include_once EL_DIR_CORE.'lib/elFileInfo.class.php';
include_once EL_DIR_CORE.'lib/elJSON.class.php';
include_once EL_DIR_CORE.'lib/elFinder.class.php';

elLoadMessages('Finder');

class elServiceFinder extends elService 
{
	var $_fm = null;
	
	
	function run()
	{
		$ats = & elSingleton::getObj('elATS');
		$nav = & elSingleton::getObj('elNavigator');
		$pageID = $nav->getCurrentPageID(); 
		if (!$ats->allow(EL_WRITE, $pageID))
		{
			exit('<h1 style="color:red">Access denied</h1>');
		}
		
		$page = $nav->getCurrentPage();
		if ($page['module'] == 'TemplatesEditor') 
		{
			$root = realpath('./style');
			$url = EL_BASE_URL.'/style/';
			$mimetypes = array('text', 'image');
		}
		else 
		{
			$root = realpath(EL_DIR_STORAGE);
			$url = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/';
			$mimetypes = array();
		}
		
		$opts = array(
			'root' => $root,
			'URL' => $url,
			'tplDir' => realpath(EL_DIR_STYLES.'modules/Finder'),
			'mimetypes' => $mimetypes,
			'defaults' => array(
				'read' => true,
				'write' => true,
				'rm' => true
				)
			);

		$fm = & new elFinder($opts); 
		$fm->autorun();
		// exit();
	}
	
	function stop() {}
}

?>