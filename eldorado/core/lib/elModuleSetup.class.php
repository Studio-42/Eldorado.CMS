<?php

class elModuleSetup
{
  var $pageID = 0;
  var $ID = 0;
  var $dir = '';
  var $name = 'container';
  var $defaultConf = array();
  var $sqlData = array();

  var $conf = null;
  var $db = null;

  function elModuleSetup( $ID, $moduleName, $pageID )
    {
      $this->ID = $ID;
      $this->name = $moduleName;
      $this->pageID = $pageID;
      //      $this->dir = EL_DIR_MODULES.$moduleDir.'/'; //echo $this->dir;
      $this->sqlData['page_id'] = $this->pageID;
      $this->sqlData['module_name'] = $this->name;
      $this->db = & elSingleton::getObj('elDb');
      $this->conf = & elSingleton::getObj('elXmlConf'); //print_r($this);
    }

  function install()
    {
      if ( !$this->_preInstall() )
	{
	  return false;
	}
      if ( !$this->_checkDependences() )
	{
	  return false;
	}
      if ( !$this->_processDb() )
	{
	  return false;
	}
      $this->_writeConf();
      $this->_postInstall();
      return true;
    }

  function uninstall()
    {
      if ( !$this->_preUnInstall() )
	{
	  return false;
	}
      if ( !$this->_checkRequirements() )
	{
	  return false;
	}
      if ( !$this->_processDb(false) )
	{
	  return false;
	}
      $this->_cleanConf();
      $this->_postUnInstall();
      return true;
    }


  /**
   * Virtual method - any actions before install module
   */
  function _preInstall()
    {
      return true;
    }

  /**
   * Virtual method - any actions after module was installed
   */
  function _postInstall() {}

  /**
   * Virtual method - any actions before uninstall module
   */
  function _preUnInstall()
    {
      return true;
    }

  /**
   * Virtual method - any actions after module was installed
   */
  function _postUnInstall() {}

  function _checkDependences()
    {
      $sql = 'SELECT mid, IF(module!=\'\', module, descrip) AS mod_name, id '
	.' FROM el_module_depend, el_module '
	.'LEFT JOIN el_menu ON module_id=mid '
	.'WHERE mod_id=\''.$this->ID.'\' AND mid=req_id AND id IS NULL';
      $this->db->query($sql);
      if ( $this->db->numRows() )
	{
	  $brokenDep = $this->db->queryToArray(null, 'mod_name');
	  array_unshift($brokenDep, $this->name);
	  elThrow(E_USER_NOTICE, 'Unsatisfied dependecies! Module "%s" required "%s". Install this module(s) first', $brokenDep);
	  return false;
	}
      return true;
    }

  function _checkRequirements()
    {
      $sql = 'SELECT sys, IF(module!=\'\', module, descrip) AS mod_name '
	.'FROM el_module WHERE mid=\''.$this->ID.'\'';
      $this->db->query($sql);
      if ( !$this->db->numRows() )
	{
	  elThrow(EL_USER_NOTICE, 'Impossible error. Module does not exists', null, EL_URL);
	}
      $row = $this->db->nextRecord();
      if ( $row['sys'] )
	{
	  elThrow(E_USER_NOTICE, 'This page is using system module "%s" and can not be deleted', 
		  $row['mod_name'], EL_URL);
	}

      $sql = 'SELECT mid, IF(module!=\'\', module, descrip) AS mod_name, id '
	.' FROM el_module_depend, el_module '
	.'LEFT JOIN el_menu ON module_id=mid '
	.'WHERE req_id=\''.$this->ID.'\' AND mid=mod_id AND id IS NOT NULL';
      $this->db->query($sql);
      if ( $this->db->numRows() )
	{
	  $brokenDep = $this->db->queryToArray(null, 'mod_name');
	  array_push($brokenDep, $this->name);
	  elThrow(E_USER_NOTICE, 'Unsatisfied dependecies! "%s" required module "%s"', $brokenDep);
	  return false;
	}
      return true;
    }


  function _readSql($install=true)
    {//echo 'Im here'; print_r($this->sqlData); exit();
      $fname = $install ? 'install.sql' : 'uninstall.sql';
      $sqlFile = $this->dir.$fname; //echo $sqlFile;
      if ( file_exists($sqlFile) && is_file($sqlFile) && is_readable($sqlFile) )
	{
	  if ( '' == ($buf = trim(file_get_contents($sqlFile))) )
	    {
	      return null;
	    }
	  $patterns = array_keys($this->sqlData);
	  foreach ( $patterns as $k=>$v )
	    {
	      $patterns[$k] = '{'.$v.'}';
	    }
	  $sql = str_replace( $patterns, $this->sqlData, $buf );
	  $sql = explode( ';', $sql ); //print_r($sql);
	  return $sql;
	}
      return null;
    }

  function _processDb($install=true)
    {
      $sqls = $this->_readSql($install);
      if ( is_array($sqls) && $sqls )
	{
	  foreach ( $sqls as $sql )
	    {
	      if ( !empty($sql) && !$this->db->query($sql) )
		{
		  return false;
		}
	    }
	}
      return true;
    }

  
  function _writeConf()
    {
      $this->conf->dropGroup( $this->pageID );
      $this->conf->makeGroup( $this->pageID );
      $this->conf->set('module', $this->name, $this->pageID);
      if ( $this->defaultConf && is_array($this->defaultConf) )
	{
	  foreach ( $this->defaultConf as $k=>$v )
	    {
	      $this->conf->set($k, $v, $this->pageID);
	    }
	}
      $this->conf->save();
    }
  
  function _cleanConf()
    {
      $this->conf->dropGroup( $this->pageID );
      $this->conf->save();
    }

}

?>