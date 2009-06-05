<?php
// ver 2.0
class elModulePluginsControl extends elModule
{
	var $_prnt      = false;
	var $_plugins   = array();
	var $_mMapConf  = null;
	var $_mMapAdmin = array(
		'on'        => array('m'=>'switchOn'),
		'off'       => array('m'=>'switchOff'),
		'pl_conf'   => array('m'=>'pluginConf'),
	);

	function defaultMethod()
	{
		$this->_initRenderer();
		$this->_rnd->rndDefault( $this->_pluginsList );
	}

	function switchOn()
	{
		$pl = & $this->_getPlugin();
		if ( $this->_isPluginDisable($pl->name) )
		{
			elThrow(E_USER_ERROR, 'Plugin "%s" is disabled! Operation terminated!', $pl->name, EL_URL);
		}
		$pl->setStatus('on');
		elLocation( EL_URL );
	}

	function switchOff()
	{
		$pl = & $this->_getPlugin();
		if ( $this->_isPluginDisable($pl->name) )
		{
			elThrow(E_USER_ERROR, 'Plugin "%s" is disabled! Operation terminated!', $pl->name, EL_URL);
		}
		$pl->setStatus('off');
		elLocation( EL_URL );
	}



	function pluginConf()
	{
		$pl = & $this->_getPlugin();
		if ( $this->_isPluginDisable($pl->name) )
		{
			elThrow(E_USER_ERROR, 'Plugin "%s" is disabled! Operation terminated!', $pl->name, EL_URL);
		}
		elLoadMessages('PluginAdmin'.$pl->name);
		$args = $this->_args;
		array_shift($args);
		$pl->conf($args);
		$name = $this->_pluginsList[$pl->name]['label'];
		elAppendToPagePath( array('url'=>'pl_conf/'.$pl->name.'/', 'name'=>$name) );
	}

	function _isPluginDisable($name)
	{
		return 'disable' == $this->_pluginsList[$name]['status'];
	}

	function &_getPlugin()
	{
		$plName = $this->_arg();

		if ( !$plName || empty($this->_plugins[$plName]) )
		{
			elThrow(E_USER_WARNING, 'Plugin "%s" does not exists or not loaded', array($plName), EL_URL );
		}
		return $this->_plugins[$plName];
	}


	function _onInit()
	{
		$db = &elSingleton::getObj('elDb');
		$sql = 'SELECT name, IF(label<>"", label, name) AS label, status FROM el_plugin ORDER BY name';
		$db->query($sql);
		if (!$db->numRows())
		{
			return;
		}
		$conf = & elSingleton::getObj('elXmlConf');
		while ($r = $db->nextRecord() )
		{

			if ( null == ($pl = &elSingleton::getPlugin($r['name']))
			&& ( null == ($pl = &elSingleton::createPlugin($r['name'], $this->pageID, $conf->getGroup('plugin'.$r['name']))) ))
			{

				continue;
			}
			if ( !$pl->checkRequiredModule() )
			{
				$pl->setStatus('disable');
				$r['state'] = 'disable';
			}
			$this->_plugins[$r['name']]      = & $pl;
			$r['hasConf']                   = method_exists($pl, 'conf');
			$this->_pluginsList[$r['name']] = $r;
		}
	}
}

?>