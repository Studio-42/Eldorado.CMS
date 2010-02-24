<?php

class elRndUpdateServer extends elModuleRenderer
{
  var $_tpls = array('log'=>'log.html');

	function rndLicenseList ($licenses)
	{
		$this->_setFile();
		foreach ($licenses as $one)
		{
		  $one['expire'] = date(EL_DATE_FORMAT, $one['expire']);
		  $this->_te->assignBlockVars('US_LICENSE', $one);
		}

	}


	function rndLog( $records )
	{
//elPrintR($records);
    $this->_setFile('log');
    foreach ($records as $r)
    {
      $data = $r->toArray();
      $data['crtime'] = date(EL_DATETIME_FORMAT, $data['crtime']);
      $data['result'] = $data['is_ok'] ? m('Ok') : m('Failed');
      $this->_te->assignBlockVars('LOG_RECORD', $data);
    }
	}

}

?>
