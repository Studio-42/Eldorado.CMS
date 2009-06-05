<?php
class elSubModuleCountersControl extends elModule
{
	var $_cntContent = '';
	
	function defaultMethod()
	{
		$this->_initRenderer();
		$this->_rnd->addToContent( nl2br(htmlspecialchars($this->_cntContent)) );
	}
	
	function &_makeConfForm()
  {
  	$form = & parent::_makeConfForm();
  	$form->add( new elTextArea('cnt_code', m('Counters code'), $this->_cntContent) );
  	return $form;
  }
  
  function _updateConf( $newConf )
	{
		if ( false != ($fp = @fopen(EL_DIR_CONF.'counters.html', 'w')))
		{
			fwrite($fp, $newConf['cnt_code']);
			fclose($fp);
		}
		else 
		{
			elThrow(E_USER_WARNING, 'Could not not save counters code in file %s', EL_DIR_CONF.'counters.html');
		}
	}
	
	
	function _onInit()
	{
		if (!file_exists(EL_DIR_CONF.'counters.html') )
		{
			if (false == ($fp = fopen(EL_DIR_CONF.'counters.html', 'w')) )
			{
				elThrow(E_USER_WARNING, 'Could not not create file %s', EL_DIR_CONF.'counters.html');
				return elThrow(E_USER_WARNING, 'File for counters code does not exists or does not readable');
			}
			fclose($fp);
		}
		if (!is_readable(EL_DIR_CONF.'counters.html'))
		{
			return elThrow(E_USER_WARNING, 'File for counters code does not exists or does not readable');
		}
		else 
		{
			$this->_cntContent = file_get_contents(EL_DIR_CONF.'counters.html');
			if (!is_writable(EL_DIR_CONF.'counters.html'))
			{
				$this->_mMap = array();
				elThrow(E_USER_WARNING, 'File for counters does not writable');
			}
		}
	}
}

?>