<?php
require_once 'elModuleEIProccessor.class.php';

class elModuleDocsCatalogEIProccessor extends elModuleEIProccessor
{
  var $_db = null;

  function init(&$module)
  {
    $this->_module  = &$module;
    $this->_db      = & elSingleton::getObj('elDb');
    $this->_db->supressDebug = 1;
  }

  function export($param)
  {
    $ret  = "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?>\n";
    $ret .= "<exportData>\n";
    $ret .= "<baseURL><![CDATA[".EL_BASE_URL."]]></baseURL>\n";


    $ret .= "</exportData>\n";
    //echo nl2br(htmlspecialchars($ret));
    return $ret;
  }

}

?>