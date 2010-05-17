<?php

class elRndSiteControl extends elModuleRenderer
{
  var $_tpls = array('emails'=>'emails.html');

  // ********************  PUBLIC METHODS  ************************ //

	function renderEmailsConf($emails)
	{
		$this->_setFile('emails');

		foreach ($emails as $one)
		{
			$this->_te->assignBlockVars('ROW', $one);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ROW.ADMIN', $one, 1);
			}
		}
	}

}


?>