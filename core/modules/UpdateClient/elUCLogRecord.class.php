<?php

class elUCLogRecord extends elMemberAttribute
{
  var $tb         = 'el_uplog';
  var $ID         = 0;
  var $act        = 'Upgrade';
  var $result     = 'Success';
  var $version    = '';
  var $log        = '';
  var $changelog  = '';
  var $crtime     = 0;
  var $backupFile = '';
  var $_objName   = 'Update log record';

  function getList()
  {
    $ret = array();
    $db = & elSingleton::getObj('elDb');
    $sql = 'SELECT id, act, result, version, crtime, backup_file, IF(log!="", 1, 0) AS log, IF(changelog!="", 1, 0) AS changelog FROM '
      .$this->tb.' ORDER BY crtime DESC';
    $db->query($sql);
    while ( $r = $db->nextRecord() )
    {
      $ret[] = $this->copy($r);
    }
    return $ret;
  }

  function appendToLog($str)
  {
    $this->log .= $str."\n";
  }

  function _initMapping()
  {
    $map = array(
      'id'          => 'ID',
      'act'         => 'act',
      'result'      => 'result',
      'version'     => 'version',
      'log'         => 'log',
      'changelog'   => 'changelog',
      'crtime'      => 'crtime',
      'backup_file' => 'backupFile'
      );
    return $map;
  }

  function _attrsForSave()
  {
    if (!$this->ID)
    {
      $this->setAttr('crtime', time());
    }
    return parent::_attrsForSave();
  }
}
?>