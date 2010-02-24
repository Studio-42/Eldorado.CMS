<?php

class elSingleton
{
	function &getObj( $class )
	{
		if ( 1 == func_num_args() )
		{
			$args = array();
		}
		else
		{
			$args = func_get_args();
			array_shift( $args );
		}

		$key = crc32($class.' '.implode('|', $args));
		if ( !isset($GLOBALS['_elStorage_'][$key]) )
		{
			if ( !class_exists($class) )
			{
				include_once $class.'.class.php';
			}
			if ( !$args )
			{
				$GLOBALS['_elStorage_'][$key] = & new $class;
			}
			else
			{
				eval( "\$GLOBALS['_elStorage_'][$key] = & new ".$class.'(\''.implode('\',\'', $args).'\');' );
			}
		}
		return $GLOBALS['_elStorage_'][$key];
	}

	function &getModule()
	{
		return $GLOBALS['core']->module;
	}

	function incLib( $file, $addPath=false )
	{
	  return null != ($p = elSingleton::_incLib(EL_DIR_CORE.$file, $addPath)) ? $p : null;
	}

	function _incLib($lib, $addPath)
	{
		if ( is_readable($lib) && include_once($lib) )
		{
			$path = dirname($lib);
			if ( $addPath )
			{
				elSingleton::path($path);
			}
			return $path;
		}
		return null;
	}

	function path( $dir )
	{
		$paths = ini_get('include_path'); //echo '<br>'.$paths.'<br>';
		if ( !strstr($paths, $dir) )
		{
			ini_set( 'include_path', $paths.$dir.':');
		}
	}

	function & createPlugin($name, $pageID, $params)
	{
		$plugin = null;
		$class = 'elPlugin'.$name;
		$file  = 'plugins/'.$name.'/'.$class.'.class.php';
		if (!elSingleton::incLib($file, 1))
		{
			return $plugin;
		}
		$plugin = & new $class($name, $pageID, $params);
		return $plugin;
	}

	function loadPlugins($pageID)
	{
		$db  = & elSingleton::getObj('elDb');
		$db->query('SELECT name FROM el_plugin WHERE status=\'on\'');
		if ( !$db->numRows())
		{
			return;
		}

		$conf = &elSingleton::getObj('elXmlConf');
		include_once EL_DIR_CORE.'lib/elPlugin.class.php';

		while ($r = $db->nextRecord() )
		{
			$hash  = md5( $r['name'] );
			$params = $conf->getGroup('plugin'.$r['name']);
			if (!empty($GLOBALS['_elPlugins_'][$hash])
			|| null == ($pl = &elSingleton::createPlugin($r['name'], $pageID, $params) ) )
			{
				continue;
			}
			$GLOBALS['_elPlugins_'][$hash] = & $pl;
			$GLOBALS['_elPlugins_'][$hash]->onLoad();
		}
	}

	function &loadPlugin($name, $pageID)
	{
		$hash  = md5( $name );
		if (empty($GLOBALS['_elPlugins_'][$hash]))
		{
			$conf = &elSingleton::getObj('elXmlConf');
			include_once EL_DIR_CORE.'lib/elPlugin.class.php';
			$params = $conf->getGroup('plugin'.$name);
			if (null == ($pl = &elSingleton::createPlugin($name, $pageID, $params)) )
			{
				return $pl;
			}
			$GLOBALS['_elPlugins_'][$hash] = & $pl;
			$GLOBALS['_elPlugins_'][$hash]->onLoad();
		}
		return $pl;
	}

	/**
   * Calls uload() method in all plugins and unload its
   */
	function unloadPlugins()
	{
		foreach ($GLOBALS['_elPlugins_'] as $k=>$p )
		{
			$GLOBALS['_elPlugins_'][$k]->onUnload();
			unset($GLOBALS['_elPlugins_'][$k]);
		}
	}

	/**
   * Return references to plugin if it is loaded
   */
	function &getPlugin( $name )
	{
		$pl = null;
		$hash = md5($name);

		if ( !empty($GLOBALS['_elPlugins_'][$hash]) )
		{
			return $GLOBALS['_elPlugins_'][$hash];
		}
		elseif ( empty($GLOBALS['_elPlugins_']) )
		{
			$nav = &elSingleton::getObj('elNavigator');
			return elSingleton::loadPlugin($name, $nav->getCurrentPageID());
		}
		return $pl;
	}


}

?>