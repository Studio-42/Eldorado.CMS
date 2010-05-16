<?php

class elRndDirectory extends elModuleRenderer
{
	
	function rndList($dirs)
	{
		$this->_setFile();
		foreach ($dirs as $d)
		{
			// $d['count'] = count(explode(',', $d['value']));
			$this->_te->assignBlockVars('SYS_DIRECTORY', $d);
		}
	}

}
