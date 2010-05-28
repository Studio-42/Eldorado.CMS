<?php
/**
 * @package   core
 * @author    dio
 * @version 3.1
 */

if ( !defined('EL_MYSQL_CHARSET') )
{
  define('EL_MYSQL_CHARSET', 'utf8');
}
class elDb
{
  /**
   * @param string  $_host  Hostname of our MySQL server
   */
  var $_host    = '';
  /**
   * @param string  $_db  DB name to select
   */
  var $_db      = '';
  /**
   * @param string  $_user  Database user
   */
  var $_user    = '';
  /**
   * @param string  $_pass  Database user's password
   */
  var $_pass    = '';
  /**
   * @param resource $LID  Link identifier
   */
  var $LID     = null;
  /**
   * @param resource $resID Resouse identifier returned by last mysql_query()
   */
  var $resID   = null;
  /**
   * @param array $prepare store parts of preparing query
  */
  var $prepare = array();

  var $supressDebug = false;

  /**
   * constructor
   *
   * @param string $user
   * @param string $pass
   * @param string $db
   * @param string $host
   * @param string $sock
   */
  function elDb( $user=null, $pass=null, $db=null, $host='localhost', $sock=null )
  {
		if ( !function_exists('mysql_connect') )
	  	{
	  		dl('mysql');
	  		if (!function_exists('mysql_connect') )
	  		{
	  			elThrow(E_USER_ERROR, 'Fatal server configuration! There is no MySQL module available!', null, null, true);
	  		}
	  	}

	 	 	if ( empty($user))
	  	{
	  		$conf = & elSingleton::getObj('elXmlConf');
	  		$user = $conf->get('user', 'db');
	  		$pass = $conf->get('pass', 'db');
	  		$db   = $conf->get('db',   'db');
	  		$host = $conf->get('host', 'db');
	  		$sock = $conf->get('sock', 'db');
	  	}

	  	$this->setConnectParams($user, $pass, $db, $host, $sock);
  }

  /**
   * Enter description here...
   *
   * @param string $user
   * @param string $pass
   * @param string $db
   * @param string $host
   * @param string $sock
   */
  function setConnectParams($user, $pass, $db, $host, $sock)
  {
  	$this->_user = $user;
  	$this->_pass = $pass;
  	$this->_db   = $db;
  	$this->_host = $host;
  	if ( !empty($sock) )
  	{
  		$this->_host .= ':'.$sock;
  	}
  }

  /**
   * Connect to DB server and set db. Set charset for MySQL ver >= 4.1
   * in test mode produce warning message, otherwise halt system
   *
   * @param bool $test
   * @return unknown
   */
  function connect( $test=false )
  {
    if( !$this->LID )
    {
      if (false == ($this->LID = mysql_connect($this->_host, $this->_user, $this->_pass)) )
      {
        if ( !$test )
        {
          elThrow(E_USER_ERROR, 'Can not connect to db', null, null, 1, __FILE__, __LINE__);
        }
        else
        {
          return elMsgBox::put( sprintf('Can not connect to db', null), EL_WARNQ);
        }
      }
      if ( !mysql_select_db($this->_db) )
      {
        if ( !$test )
        {
          elThrow(E_USER_ERROR, 'Can not change db', null, null, 1, __FILE__, __LINE__);
        }
        else
        {
          return elMsgBox::put( sprintf('Can not change db'), EL_WARNQ);
        }
      }
      $serverVars = $this->queryToArray('SHOW VARIABLES', 'Variable_name', 'Value') ;
      if ( 4 < $serverVars['version'][0] || (4 == $serverVars['version'][0] && 1 <= $serverVars['version'][2]) )
      {
      	$this->query('SET SESSION character_set_client='.EL_MYSQL_CHARSET);
      	$this->query('SET SESSION character_set_connection='.EL_MYSQL_CHARSET);
      	$this->query('SET SESSION character_set_results='.EL_MYSQL_CHARSET);
      }

    }
    return true;
  }

  /**
   * Process sql query
   * @param string  $sql  SQL query
   * @return  int
   */
  function query( $sql )
  {
    if ( !$this->LID && !$this->connect() )
    {
      return false;
    }

    $this->resID = mysql_query( $sql, $this->LID );

    if ( !$this->resID  )
    {
		if (!$this->supressDebug)
		{
		  elDebug('__SQL__ query failed. '.$sql.' '.mysql_error(), EL_DEBUGQ);
		}
      	return elThrow(E_USER_WARNING, 'SQL query failed.');
		
    }
    elseif (!$this->supressDebug)
    {
      elDebug('__SQL__ '.$sql, EL_DEBUGQ);
    }

    return $this->resID;
  }

  /**
   * Make query on DB with mysql_real_escape_string
   * First arg is query string with sprintf's placeholders
   *
   * @param string $sql
   * @return unknown
   */
  function safeQuery($sql)
  {
    $args = func_get_args();
    if ( 1 >= sizeof($args) )
    {
      return $this->query( $sql );
    }
    array_shift($args);
    $args = array_map( 'mysql_real_escape_string', $args);
    return $this->query( vsprintf($sql, $args) );
  }

  /**
   * Return next row from Record Set
   * @return  array
   */
  function nextRecord()
  {
    if ( !$this->resID )
    {
      return elThrow(E_USER_ERROR, 'There is no valid MySQL result resource');
    }
    if ( false == ($record = mysql_fetch_array($this->resID, MYSQL_ASSOC)) )
    {
      mysql_free_result( $this->resID );
      $this->resID = null;
    }
    return $record;
  }

  function queryToArray( $sql='', $index='', $only='', $exclIndex=false )
  {
    $ret = array();
    if ( $sql )
    {
      $this->query($sql);
    }
    else
    {
      mysql_data_seek($this->resID, 0);
    }
    if ( !($this->resID) )
    {
      elDebug('[Db::queryToArray] no result ID', EL_DEBUGQ);
      return $ret;
    }

    while ( $row = mysql_fetch_assoc($this->resID) )
    {
      if ( $index)
      {
        if ( $only && !strpos($only, ','))
        {
          $ret[$row[$index]] = $row[$only];
        }
        else
        {
          $ret[$row[$index]] = $row;
          if ( $exclIndex )
          {
            unset( $ret[$row[$index]][$index] );
          }
        }
      }
      else
      {
        $ret[] = !$only ? $row : $row[$only];
      }
    }

    return $ret;
  }

  /**
   * @return  int
   */
  function numRows()
  {
    return @mysql_num_rows( $this->resID );
  }
  /**
   * @return  int
   */
  function insertID()
  {
    return @mysql_insert_id( $this->LID );
  }

  function affectedRows()
  {
    return @mysql_affected_rows( $this->LID );
  }

  function prepare($head, $tokens, $where='')
  {
    $this->prepare = array( 'head'   => $head,
                            'tokens' => $tokens,
                            'build'  => '',
                            'where'  => $where );
  }

  function prepareData($data, $multi=false)
  {
    if ( empty($this->prepare) )
    {
      return elThrow(E_USER_ERROR, 'There is no prepared query defined');
    }
    if ( !is_array($data) || empty($data) )
    {
      return elThrow(E_USER_ERROR, 'data for prepareData must be an array');
    }
    if ( $multi )
    {
      foreach ( $data as $part )
      {
        $this->prepareData( $part );
      }
		return;
    }

    $data = array_map('mysql_real_escape_string', $data);
    $this->prepare['build'] .= ($this->prepare['build'] ? ', ' : '');
    $this->prepare['build'] .= vsprintf($this->prepare['tokens'], $data);
  }

  function execute( $data=null, $multi=false )
  {
    if ( empty($this->prepare) )
    {
      return elThrow(E_USER_ERROR, 'There is no prepared query defined');
    }
    if ( is_array($data) )
    {
      $this->prepareData($data, $multi);
    }
    $sql = $this->prepare['head'] . ' ' . $this->prepare['build'] . ' ' . $this->prepare['where'];
    $this->prepare = null;
    //     echo $sql;
    return $this->query($sql);
  }

  function optimizeTable( $tb )
  {
		$args = func_get_args();
		return $this->query('OPTIMIZE TABLE '.implode(',', $args));
  }

	function tablesList()
	{
		$tables = array();
		$resID = $this->query('SHOW TABLES');
	  	while ( $r = mysql_fetch_array($resID))
	  	{
	  		$tables[] = $r[0];
	  	}
		return $tables;
	}

  function isTableExists( $tb )
  {
  	$resID = $this->query('SHOW TABLES');
  	while ( $r = mysql_fetch_array($resID))
  	{
  		if ( $r[0] == $tb )
  		{
  			return true;
  		}
  	}
  	return false;
  }

  function isFieldExists( $tb, $field )
  {
	$fList = $this->query('SHOW COLUMNS FROM '.$tb);
	while ( $r = $this->nextRecord() )
	{
	  if ($r['Field'] == $field)
	  {
		return $r['Type'];
	  }
	}
	return false;
  }

	function fieldsNames($tb) {
		$ret = array();
		$this->query('SHOW COLUMNS FROM '.$tb);
		while ( $r = $this->nextRecord() )
		{
			$ret[] = $r['Field'];
		}
		return $ret;
	}

  function close()
  {
    if( $this->LID )
    {
      mysql_close( $this->LID );
      unset($this->LID);
    }
  }

}

?>
