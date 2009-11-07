<?php

class elRndSiteBackup extends elModuleRenderer 
{
  	function rndBackupsList($list, $quote, $du )
  	{
    	$this->_setFile();
	    $this->_te->assignVars('backupQuote', $quote);
	    $this->_te->assignVars('backupDu',    $du);    
	    if ( $du >= $quote )
	    {
	      $this->_te->assignBlockVars('DU_WARNING');
	    }

		foreach ($list as $f)
		{
			$this->_te->assignBlockVars('ROW', $f);
			$this->_te->assignBlockVars($this->_admin ? 'ROW.LINK' : 'ROW.FILE', $f, 1);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ROW.ADMIN', $f, 2);
			}
		}
	    
	}
 }

?>