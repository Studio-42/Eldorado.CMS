<?php

class elServiceFM extends elService 
{
	var $_fm = null;
	
	function init($args)
	{
		
		if ( !elSingleton::incLib( './modules/FileManager/elModuleFileManager.class.php', true) )
		{
			return elThrow(E_USER_WARNING, m('File manager does not installed! Check Your site instalation!'), EL_WM_URL, null, null );
		}
		
		$this->_args = $args;
		$pageID = array_shift($this->_args);
		
		$ats = & elSingleton::getObj('elATS');
		
		if ( !$ats->allow(EL_WRITE, $pageID) )
		{
			return elThrow(E_USER_WARNING, m('You do not have access to edit page'), EL_WM_URL, null, null );
		}
		
		$aMode = $ats->allow(EL_FULL, $pageID) ? EL_FULL : EL_WRITE;

		$this->_fm = & elSingleton::getObj('elModuleFileManager');
		$this->_fm->init($pageID, $this->_args, 'FileManager', $aMode);

	}
	
	function run()
	{
		$this->_fm->run();
		$this->_fm->stop();
	}
	
	function stop() {}
}

?>