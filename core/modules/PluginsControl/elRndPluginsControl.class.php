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
			
			if ($this->_admin)
			{
				$data = array('name' => $one['name']);
				if ('on' == $one['status'])
				{
					$data['action'] = 'off';
					$data['title']  = m('Switch plugin off');
					$data['class']  = 'switch-on';
				}
				else
				{
					$data['action'] = 'on';
					$data['title']  = m('Switch plugin on');
					$data['class']  = 'switch-off';
				}
				$this->_te->assignBlockVars('PLUGIN.PL_STATE', $data, 1);
				if ( $one['hasConf'] )
				{
					$this->_te->assignBlockVars('PLUGIN.PL_CONF', $one, 1);
				}
			}
		}
	}

}

?>