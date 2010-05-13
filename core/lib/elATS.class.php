<?php

define ('EL_PASSWD_INPUT',   1);
define ('EL_PASSWD_RAND',    2);
define ('EL_PASSWD_REMIND',  3);
define ('EL_UNTF_REMIND',    1);
define ('EL_UNTF_REGISTER',  2);
define ('EL_UNTF_PASSWD',    3);

  /**
    * Athentithication and access control system.
    *TODO - master password from file
   */

class elATS
{
  var $user          = null;
  var $_dbAuth       = null;
  var $_dbACL        = null;
  var $_isLocalAuth  = true;
  var $_sessTO       = 86400; // 1 month
  var $_iGroups      = array();
  var $_ACL          = array();
  var $_userRoot     = false;
  var $_pageID       = 0;
  var $_mngr         = null;
  var $_regAllow     = true;
  var $_defGID       = 0;
  var $_defGroupName = '';
  var $_userFullName = false;
  var $_loginCaseSens = true;

  function __construct()
  {
    $conf         = & elSingleton::getObj('elXmlConf');
    $this->_dbACL = & elSingleton::getObj('elDb');
    $this->_initAuthDb($conf->get('authDb', 'auth'), $conf->getGroup('db'), $conf->get('importGroups', 'auth'));
	$this->_dbHash = md5($this->_dbACL->_host.' '.$this->_dbACL->_db.' '.$this->_dbACL->_user.' '.$this->_dbACL->_pass);

    if ( 0 < ($to = $conf->get('sessionTimeOut', 'auth')) )
    {
      $this->_sessTO = (int)$to;
    }
    $this->_regAllow       = $conf->get('registrAllow',   'auth');
    $this->_defGID         = $conf->get('defaultGID',     'auth');
    $this->_userFullName   = $conf->get('userFullName',   'layout');
	$this->_loginCaseSens  = $conf->get('loginCaseSensitive',   'auth');
  }

  function elATS()
  {
  	$this->__construct();
  }

  function init( $pageID )
  {
  	$this->_pageID         = $pageID;
  	$this->user            = & elSingleton::getObj('elUser');

  	if (empty($_SESSION['UID']) || empty($_SESSION['key']))
  	{
  		return $this->_loadACL();
  	}

  	$this->user->db        = & $this->_dbAuth;
  	$this->user->allowFullName($this->_userFullName);
  	$UID                   = (int)$_SESSION['UID'];
  	$al                    = !empty($_SESSION['al']) && 1 <> $UID;
  	$onlyGroups            = $this->_iGroups ? array_keys($this->_iGroups) : null;

  	if ( !$onlyGroups )
  	{
  		$sql = 'SELECT '.implode(',', $this->user->listAttrs() ).' FROM el_user WHERE uid='.intval($UID);
  	}
  	else
  	{
  		$sql = 'SELECT DISTINCT '.implode(',', $this->user->listAttrs() )
  					.' FROM el_user, el_user_in_group WHERE '
  					.'uid='.intval($UID).' AND user_id=uid AND '
  					.'group_id IN (\''.implode('\',\'', $onlyGroups).'\')';
  	}

  	$this->_dbAuth->query($sql);
  	if ( 1 != $this->_dbAuth->numRows() )
  	{
  		elDebug('Usotski');
  		$this->user->onLogout();
  		return $this->_loadACL();
  	}

  	$data = $this->_dbAuth->nextRecord();

  	$key = md5($UID.' '.$data['login'].' '.$this->_dbHash);
  	if ($key <> $_SESSION['key'])
  	{
  		elDebug('Usotski - key wa warui des');
  		elDebug('key - '.$_SESSION['key']);
  		elDebug('must be - '.$key);
  		$this->user->onLogout();
  		return $this->_loadACL();
  	}

  	if ( time() - $data['atime'] > $this->_sessTO && !$al )
  	{
  		elDebug('time pass = '.(time()-$data['atime']).' TO='.$this->_sessTO.' autologin='.(int)$al);
  		$this->user->setAttrs($data);
  		$this->user->onLogout();
  		elThrow(E_USER_WARNING, m('Session timeout'), null, EL_BASE_URL);
  	}

  	$this->user->onLogin( $data, $this->_dbHash, $al, $onlyGroups, false);

  	if ( $this->user->isRoot() || $this->user->isInGroupRoot() )
  	{
  		$this->_userRoot = true;
  	}
  	else
  	{
  		$this->_loadACL();
  	}
  }

   /**
   * Render auth form and login user
   */
  function auth($url=EL_URL)
  {
    elLoadMessages('Auth');
    $this->form = & elSingleton::getObj('elForm');
    $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    $this->form->setLabel(m('Authorization required'));
    $this->form->setAttr('action', EL_URL.'__auth__/');
    $this->form->add( new elText('elLogin', m('Login')) );
    $this->form->add( new elPassword('elPass', m('Password')) );
    $this->form->add( new elCData('fp', '<a href="'.EL_URL.'__passwd__/'.'">'.m('Forgot Your password?').'</a>'  ) );
	if ( $this->_regAllow )
	{
		$this->form->add( new elCData('reg', '<a href="'.EL_URL.'__auth__/'.'">'.m('New user registration').'</a>'  ) );
	}
	$this->form->add(new elHidden('url', '', $url));
    $rnd = &elSingleton::getObj('elSiteRenderer');
    $rnd->setPageContent( $this->form->toHtml() );
    if ($this->form->isSubmitAndValid())
    {
    	$this->_logInUser();
    }
  }


  function logOutUser( )
  {
    if ( $this->user->UID )
    {
      if ( 1 == $this->user->UID )
      {
        elCleanCache();
      }
      elLoadMessages('Auth');
      elMsgBox::put( sprintf( m('Good bye %s!'), $this->user->getFullName() ) );
      $this->user->onLogout();
    }
    elLocation(EL_BASE_URL);
  }

  /**
   * check page permissions
   */
  function allow($mode=EL_READ, $pageID=null )
  {
    if ( null == $pageID )
    {
      $pageID = $this->_pageID;
    }
    elseif ( !is_numeric($pageID) )
    {
      $nav = & elSingleton::getObj('elNavigator');
      $pageID = $nav->getPageID($pageID);
    }
    if ( $this->_userRoot )
    {
      return TRUE;
    }
    return !empty( $this->_ACL[$pageID] ) ? $this->_ACL[$pageID] >= $mode : FALSE;
  }

   // return auth DB object (remote)
  function &getAuthDb()
  {
    return $this->_dbAuth;
  }

  // return ACL DB object (local)
  function &getACLDb()
  {
    return $this->_dbACL;
  }

  function &getUser()
  {
    return $this->user;
  }

  function getUserID()
  {
    return $this->user->UID;
  }

 /**
   * Возвращает права доступа к текущей странице для пользователя
   */
  function getPageAccessMode()
  {
    if ( $this->_userRoot )
    {
      return EL_FULL;
    }
    return !empty( $this->_ACL[$this->_pageID] ) ? $this->_ACL[$this->_pageID] : 0;
  }


  function onPageChange($pageID)
  {
  		$this->_initManager();
    	$this->_mngr->onPageChange($pageID);
  }

  function onPageDelete($pageID)
  {
  	$this->_dbACL->query('DELETE FROM el_group_acl WHERE page_id='.$pageID);
  	$this->_dbACL->optimizeTable('el_group_acl');
  }

  function getSessionTimeOut()
  {
    return $this->_sessTO;
  }

  function getGroupInfo( $GID )
  {
    $this->_dbAuth->query('SELECT gid, name, perm, mtime FROM el_group WHERE gid=\''.(int)$GID.'\'');
    return $this->_dbAuth->numRows() ? $this->_dbAuth->nextRecord() : null;
  }

  function getDefaultGID()
  {
    return $this->_defGID;
  }

  function getDefaultGroupName()
  {
    if ( $this->_defGID && '' == $this->_defGroupName )
    {
      $g = $this->getGroupInfo($this->_defGID);
      $this->_defGroupName = !empty($g['name']) ? $g['name'] : m('Undefined');
    }
    return $this->_defGroupName;
  }

  /**
   * список групп для сайта
   */
  function getGroupsList()
  {
    if ( !$this->_iGroups )
    {
      return $this->_dbAuth->queryToArray('SELECT gid, name FROM el_group ORDER BY gid', 'gid', 'name');
    }
    return $this->_iGroups;
  }

  function getUsersList($search='', $group=null, $sf='login', $order='ASC', $start=0, $offset=30, $count=false)
  {
    $this->_initManager();
    return $this->_mngr->getUsersList($search, $group, $sf, $order, $start, $offset, $count);
  }

  function getUsersGroupsList()
  {
    $this->_initManager();
    return $this->_mngr->getUsersGroupsList();
  }

  function isUserRegAllow()
  {
  	return $this->_regAllow;
  }

  function isRegistrationAllowed()
  {
    return $this->_regAllow;
  }

  function isLocalAuth()
  {
    return $this->_isLocalAuth;
  }

  function isUserAuthed()
  {
    return $this->user->UID;
  }

  function addGroupToImportList( $GID, $name )
  {
    $conf = &elSingleton::getObj('elXmlConf');
    $this->_iGroups[$GID] = $name;
    $conf->set('importGroups', $this->_iGroups, 'auth');
    $conf->save();
  }

  function rmGroupFromImportList( $GID )
  {
    if ( isset($this->_groups[$GID]) )
    {
      $conf = &elSingleton::getObj('elXmlConf');
      unset($this->_iGroups[$GID]);
      $conf->set('importGroups', $this->_iGroups, 'auth');
      $conf->save();
    }
  }

  function setImportGroupsList()
  {
    $this->_initManager();
    return $this->_mngr->setImportGroupsList();
  }



  ///////////////////  *  Манипуляции с пользователем  *  ///////////////////////////////////////

  /**
   * изменение пользовательских данных
   * профайл
   * регистрация пользователя
   * управление пользователями из КЦ
   * уведомление на email отсылается только при создании нового пользователя
   */
  function editUser( &$user )
  {
    $this->_initManager();
    return $this->_mngr->editUser( $user );
  }

  /**
   * смена пароля
   * @param $user  obj user object
   * @param $do    int action - change password, set random password or send new password
   */
  function passwd( &$user, $do=EL_PASSWD_INPUT )
  {
    $this->_initManager();
    return $this->_mngr->passwd($user, $do);
  }

  /**
   * смена групп пользователя
   */
  function userGroups( &$user )
  {
    $this->_initManager();
    return $this->_mngr->setUserGroups( $user );
  }

  function addUserToGroup(&$user, $GID, $only=false)
  {
    $this->_initManager();
    return $this->_mngr->addUserToGroup($user, $GID, $only);
  }

  /**
   * удаление пользователя
   */
  function rmUser( &$user )
  {
    $this->_initManager();
    return $this->_mngr->rmUser( $user );
  }

  function formToHtml()
  {
    if ( isset($this->_mngr->form ))
    {
      return $this->_mngr->form->toHtml();
    }
    return '';
  }

  /////////////////////////   *  PRIVATE  *  ////////////////////


  function _initAuthDb( $db, $dbACL, $importGroups=null )
  {
  	$testDbAuth = $db;    unset($testDbAuth['db']);
  	$testDbACL  = $dbACL; unset($testDbACL['db']);
    if ( empty($db) || !is_array($db) || !array_diff($testDbACL, $testDbAuth) || empty($db['db']) || empty($db['user']) )
    {
      $this->_dbAuth = & $this->_dbACL;
      if ( !empty($db) )
      {
        $this->_dropAuthDbParams();
      }
      return;
    }
    $host = !empty($db['host']) ? $db['host'] : 'localhost';
    $pass = !empty($db['pass']) ? $db['pass'] : '';
    $this->_dbAuth = & new elDb($db['user'], $pass, $db['db'], $host, $db['sock']);
    if ( !$this->_dbAuth->connect(true) )
    {
      $this->_dbAuth = & $this->_dbACL;
      return $this->_dropAuthDbParams();
    }
    $this->_isLocalAuth = false;
    if ( empty($importGroups) )
    {
    	$this->_iGroups = array(1 => 'root');
    }
    else
    {
    	$this->_iGroups = $importGroups;
    	if ( !empty($this->_iGroups[1]) )
    	{
    		$this->_iGroups[1] = 'root';
    	}
    }
  }

  function _dropAuthDbParams()
  {
    $conf = & elSingleton::getObj('elXmlConf');
    $conf->drop('authDb', 'auth');
    $conf->save();
    elDebug('Invalid auth DB params - remove it from conf file');
  }


  /**
   * инициализует обект-менеджер для манипуляций с пользователями, группами, привелегиями
   */
  function _initManager()
  {
    if ( !$this->_mngr )
    {
      $this->_mngr = & elSingleton::getObj('elATSManager');
      $this->_mngr->_ats = & $this;
    }
  }

  function _getMasterPasswd()
  {
  	if ( file_exists(EL_DIR_CONF.'mp') && false != ($c = file(EL_DIR_CONF.'mp')) && 32 == strlen(trim($c[0])) )
  	{
  		return trim($c[0]);
  	}
  	return false;
  }

 	function _logInUser()
  {
  	elLoadMessages('Auth');

    if ( $this->user->isAuthed() )
    {
    	elThrow(E_USER_WARNING, m('You must logout before logging in'), null, EL_URL);
    }

    if ( empty($_POST['elLogin']) || empty($_POST['elPass']) )
    {
      elThrow(E_USER_WARNING, m('Authorization failed'), null, EL_URL );
    }
    $this->user->db = & $this->_dbAuth;
		$mp    = $this->_getMasterPasswd();

		if ( 'root' == $_POST['elLogin']
		&& (!empty($mp) && $mp == md5($_POST['elPass']))  )
		{
			$this->_dbAuth->query('SELECT uid, login FROM el_user WHERE uid=1');
			if ( 0 == $this->_dbAuth->numRows() )
			{
				$this->_dbAuth->query('INSERT INTO el_user SET uid=1, login=\'root\', pass=\''.md5(time()).'\'');
				$this->_dbAuth->query('SELECT uid, login FROM el_user WHERE uid=1');
			}
			elMsgBox::put( m('Wellcome back, master!') );
		}
		else
		{
			$login = mysql_real_escape_string($_POST['elLogin']);
			$pass  = mysql_real_escape_string($_POST['elPass']);
			if ( !$this->_iGroups )
			{
				if ($this->_loginCaseSens) {
					$sql = 'SELECT uid, login FROM el_user WHERE '
	    				.'login=\''.$login.'\' AND pass=MD5(\''.$pass.'\') AND pass<>\'\'';
				} 
				else
				{
					$sql = 'SELECT uid, login FROM el_user WHERE '
	    				.'LOWER(login)=\''.strtolower($login).'\' AND pass=MD5(\''.$pass.'\') AND pass<>\'\'';
					
				}
			}
			else
			{
				if ($this->_loginCaseSens) {
					$sql = 'SELECT DISTINCT uid, login FROM el_user, el_user_in_group WHERE '
	    				.'login=\''.$login.'\' AND pass=MD5(\''.$pass.'\') AND pass<>\'\' AND '
							.'user_id=uid AND group_id IN (\''.implode('\',\'', array_keys($this->_iGroups)).'\')';
					
				}
				else 
				{
					$sql = 'SELECT DISTINCT uid, login FROM el_user, el_user_in_group WHERE '
	    				.'LOWER(login)=\''.strtolower($login).'\' AND pass=MD5(\''.$pass.'\') AND pass<>\'\' AND '
							.'user_id=uid AND group_id IN (\''.implode('\',\'', array_keys($this->_iGroups)).'\')';
					
				}
			}
    	$this->_dbAuth->query($sql);
		}
    if ( 1 != $this->_dbAuth->numRows() )
    {
    	elThrow(E_USER_WARNING, m('Authorization failed'), null, EL_URL );
    }

    $data = $this->_dbAuth->nextRecord();

    $onlyGroups = $this->_iGroups ? array_keys($this->_iGroups) : null;
    $al = !empty($_POST['elAutoLogin']) && 1<>$data['uid'];

    $this->user->onLogin($data, $this->_dbHash, $al, $onlyGroups, true);

    elMsgBox::put( sprintf( m('Wellcome back %s!'), $this->user->getFullName() ) );
    elLocation(!empty($_POST['url']) ? $_POST['url'] : EL_URL);
  }

  /**
   * Загружает таблицу привилегий пользователя
   * доступ к странице = доступ стр по умолчанию & [доступ групп пользователя к этой странице]
   */
  function _loadACL()
  {
    if ( !$this->user->groups )
    {
      $sql = 'SELECT ch.id, MIN(p.perm) AS perm 
            FROM el_menu AS p, el_menu AS ch 
            WHERE ch._left BETWEEN p._left AND p._right AND p.level>0 GROUP BY ch.id';
    }
    else
    {
      $sql = 'SELECT ch.id, IF ( acl.perm>MIN(p.perm), acl.perm, MIN(p.perm) ) AS perm 
            FROM el_menu AS p, el_menu AS ch 
            LEFT JOIN el_group_acl AS acl 
            ON page_id=ch.id 
            AND group_id IN ('.implode(',', $this->user->groups)
            .') WHERE ch._left BETWEEN p._left AND p._right AND p.level>0 GROUP BY ch.id'; //echo $sql;
    }
    $this->_ACL = $this->_dbACL->queryToArray($sql, 'id', 'perm');
  }

  function allowGuest($pageID = null)
  {
    if ( null == $pageID )
    {
      $pageID = $this->_pageID;
    }
    elseif ( !is_numeric($pageID) )
    {
      $nav = & elSingleton::getObj('elNavigator');
      $pageID = $nav->getPageID($pageID);
    }
    if ( empty($this->_ACL) )
    {
      $sql = 'SELECT ch.id, p.perm AS perm '
            .'FROM el_menu AS p, el_menu AS ch '
            .' WHERE ch._left BETWEEN p._left AND p._right AND p.level>0 GROUP BY ch.id';
      $this->_ACL = $this->_dbACL->queryToArray($sql, 'id', 'perm');
//      elPrintR($this->_ACL);
    }
    if ( $this->_ACL[$pageID] == 0 )
    {
      return FALSE;
    }
    return TRUE;
  }

}

?>