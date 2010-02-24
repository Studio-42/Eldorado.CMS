<?php

class elUpdServLogRecord extends elMemberAttribute
{
  var $tb     = 'el_userv_log';
  var $ID     = 0;
  var $lKey   = '';
  var $URL    = '';
  var $IP     = '';
  var $action = 'version';
  var $isOk   = '1';
  var $error  = '';
  var $debug  = '';
  var $crtime = 0;

  function _initMapping()
  {
    $map = array(
      'id'     => 'ID',
      'lkey'   => 'lKey',
      'url'    => 'URL',
      'ip'     => 'IP',
      'act'    => 'action',
      'is_ok'  => 'isOk',
      'error'  => 'error',
      'debug'  => 'debug',
      'crtime' => 'crtime');
    return $map;
  }

}

?>