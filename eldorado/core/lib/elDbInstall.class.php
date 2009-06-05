<?php
if (!defined('EL_WARNQ') )
{
	define('EL_WARNQ', 1);
}
if (!defined('EL_DEBUGQ') )
{
	define('EL_DEBUGQ', 2);
}
include_once './core/lib/elDb.class.php';


class elDbInstall extends elDb
{
	function connect()
	{
		if( !$this->LID )
    {
      if (false == ($this->LID = mysql_connect($this->_host, $this->_user, $this->_pass)) )
      {
      	return elThrow(E_USER_ERROR, 'Can not connect to db on host %s', $this->_host);
      }
    }
    
    $dbs = $this->queryToArray('SHOW DATABASES', 'Database', 'Database');  //echo '<pre>'; print_r($dbs);
    if ( empty($dbs[$this->_db]) )
    {
    	if ( !$this->query('CREATE DATABASE `'.$this->_db.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;') )
    	{
    		return elThrow(E_USER_ERROR, 'Could not create MySQL database "%s"! MySQL says: %s', 
    										array($this->_db, mysql_error()));
    	}
    }
    
    if ( !mysql_select_db($this->_db) )
      {
      	return elThrow(E_USER_ERROR, 'Can not change db to %s', $this->_db);
      }
    $serverVars = $this->queryToArray('SHOW VARIABLES', 'Variable_name', 'Value') ;
    if ( 4 < $serverVars['version'][0] || (4 == $serverVars['version'][0] && 1 <= $serverVars['version'][2]) )
    {
    	$this->query('SET SESSION character_set_client=\'utf8\'');
    	$this->query('SET SESSION character_set_connection=\'utf8\'');
    	$this->query('SET SESSION character_set_results=\'utf8\'');
    }
    return true;
	}

	function initDb()
	{
		if (!$this->connect() )
		{
			return false;
		}
		
		$cont = file_get_contents('./conf/install.sql');
		if (empty($cont))
		{
			return elThrow(E_USER_ERROR, 'Could not read file "%s" or file is empty', './conf/install.sql');
		}
		$cont = explode(";\n", $cont);
		foreach ($cont as $sql)
		{
			$strs = explode("\n", $sql);
			for ( $i=0, $s=sizeof($strs); $i<$s; $i++)
			{
				if ( empty($strs[$i]) || '--' == substr($strs[$i], 0, 2) )
				{
					unset($strs[$i]);
				}
			}
			$sql = implode("\n", $strs);
			if ( !empty($sql) && !$this->query($sql) )
			{
				return false;
			}
			//echo '<pre>'; echo $sql; echo '</pre>';
		}
		return true;
	}
	
	function localizeDb($locale)
	{
		$msgFile = './core/locale/'.$locale.'/elInstall.php';
		if ( !include_once $msgFile )
		{
			return;
		}
		$modList = $this->queryToArray('SELECT module, descrip FROM el_module', 'module', 'descrip');
		foreach ($modList as $mod=>$descrip)
		{
			if ( !empty($elModDescrip[$mod]) && $elModDescrip[$mod] != $descrip )
			{
				$sql = 'UPDATE el_module SET descrip=\''.mysql_real_escape_string($elModDescrip[$mod]).'\' '
								.'WHERE module=\''.$mod.'\''; 
				$this->query($sql);
			}
		}
		$plList = $this->queryToArray('SELECT name, label, descrip FROM el_plugin', 'name');
		foreach ($plList as $name=>$pl)
		{
			$label   = !empty($elPlugin[$name]['label'])   ? $elPlugin[$name]['label']   : $pl['label'];
			$descrip = !empty($elPlugin[$name]['descrip']) ? $elPlugin[$name]['descrip'] : $pl['descrip'];
			$sql = 'UPDATE el_plugin SET label=\''.mysql_real_escape_string($label).'\', '
						.'descrip=\''.mysql_real_escape_string($descrip).'\' WHERE name=\''.$name.'\'';
			$this->query($sql);
		}
		$pList = $this->queryToArray('SELECT id, name FROM el_menu WHERE level>0', 'id', 'name');
		foreach ($pList as $id=>$name)
		{
			$sql = 'UPDATE el_menu SET name=\''.mysql_real_escape_string($elPages[$name]).'\' WHERE id='.$id;
			$this->query($sql);
		}
	}
	
}

?>