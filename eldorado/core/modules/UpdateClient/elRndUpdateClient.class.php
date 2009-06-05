<?php

class elRndUpdateClient extends elModuleRenderer
{

	var $_tpls = array('chlog' => 'changelog.html');

	function rndStart( $worksOK, $history)
	{
		$this->_setFile();
		$data = array(
		  'availableVer' => !empty($this->_conf['availableVer']) ? $this->_conf['availableVer'] : m('Unknown'),
		  'lastCheck'    => !empty($this->_conf['checkVerTs'])   ? date(EL_DATETIME_FORMAT, $this->_conf['checkVerTs']) : m('Unknown')
		  );
		$this->_te->assignVars( $data );
		if ( $worksOK && !empty($this->_conf['serverURL']) && !empty($this->_conf['licenseKey']) )
		{
		  $this->_te->assignBlockVars('UC_CHECK_VER');
		  if ( !empty($this->_conf['availableVer']) && EL_VER <> $this->_conf['availableVer'])
		  {
		    $this->_te->assignBlockVars('UC_ACT_UPDATE');
		  }
		}

		if (!$history)
		{
		  return;
		}
		foreach ($history as $rec)
		{
		  $data = array(
		    'id'=>$rec->ID,
		    'ver'=>$rec->version,
		    'act' => m($rec->act),
		    'result' => m($rec->result),
		    'date' => date(EL_DATETIME_FORMAT, $rec->crtime)
		    );
		  $this->_te->assignBlockVars('UC_LOGREC', $data);
		  if ( !empty($rec->log) )
		  {
		    $this->_te->assignBlockVars('UC_LOGREC.UC_INST_LOG', array('id'=>$rec->ID), 1);
		  }
		  if ( !empty($rec->changelog) )
		  {
		    $this->_te->assignBlockVars('UC_LOGREC.UC_VER_CHANGELOG', array('id'=>$rec->ID), 1);
		  }
		  if ( !empty($rec->backupFile) )
		  {
		    $this->_te->assignBlockVars('UC_LOGREC.UC_VER_DOWNGRADE', array('id'=>$rec->ID), 1);
		  }
		}
	}

	function rndLog($head, $log)
	{
		$this->_setFile('chlog');
    $this->_te->assignVars('head', $head);
    $this->_te->assignVars('log',  $log);
	}

}

?>
