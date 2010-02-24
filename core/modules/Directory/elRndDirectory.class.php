<?php

class elRndDirectory extends elModuleRenderer
{
	var $_tpls = array('list' => 'list.html');
	
	function rndList($dir)
	{
		$this->_setFile('list');
		foreach ($dir as $d)
		{
			$d['count'] = count(explode(',', $d['value']));
			$this->_te->assignBlockVars('DIR', $d);
		}
	}

}
