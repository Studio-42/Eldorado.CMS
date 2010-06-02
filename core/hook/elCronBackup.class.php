<?php

// moved from elCore.class.php

class elCronBackup {

	function _autoBackup()
	{
		if ( elSingleton::incLib('./modules/SiteBackup/elModuleSiteBackup.class.php') )
		{
			$this->module = & elSingleton::getObj('elModuleSiteBackup');
			$this->module->init( $this->pageID, $this->args, $this->mName, EL_FULL );
			return $this->module->create(true);
		}
		return false;
	}

	function run()
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$period = $conf->get('auto', 'backup');
		if ($period && time()-$period*86400 > $conf->get('ts', 'backup'))
		{
			if ( $this->_autoBackup() )
			{
				$conf->set('ts', time(), 'backup');
			}
			else
			{
				@error_log( 'Could not create backup', 3, EL_DIR_LOG.$_SERVER['HTTP_HOST'].'.error-log');
				$conf->set('auto', 0, 'backup');
			}
			$conf->save();
		}
	}

}

