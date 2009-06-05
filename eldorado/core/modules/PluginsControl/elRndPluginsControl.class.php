<?php

class elRndPluginsControl extends elModuleRenderer
{
	function rndDefault( $plugins )
	{
		$this->_setFile();
		foreach ( $plugins as $one )
		{
			$this->_te->assignBlockVars('PLUGIN', $one);
			if ('disable' == $one['status'])
			{
				$this->_te->assignBlockVars('PLUGIN.PL_STATE_DISABLE', null , 1);
				continue;
			}
			$block = 'on' == $one['status'] ? 'PLUGIN.PL_STATE_ON' : 'PLUGIN.PL_STATE_OFF';

			$this->_te->assignBlockVars($block, $one, 1);
			if ( $one['hasConf'] )
			{
				$this->_te->assignBlockVars('PLUGIN.PL_CONF', $one, 1);
			}
		}
	}

}

?>