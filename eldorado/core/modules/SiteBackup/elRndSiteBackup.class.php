<?php
/*
* @package eldorado 3.4
*/

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
    $this->_te->assignBlockFromArray('ROW', $list);
  }
  
 }

?>