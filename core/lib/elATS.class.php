<?php

elLoadMessages('Auth');

// define ('EL_PASSWD_INPUT',   1);
// define ('EL_PASSWD_RAND',    2);
// define ('EL_PASSWD_REMIND',  3);
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

	function elATS() {
		$conf         = & elSingleton::getObj('elXmlConf');
		$this->_conf  = $conf->getGroup('auth');
		if (!isset($this->_conf['loginCaseSensetive'])) {
			$this->_conf['loginCaseSensetive'] = true;
		}
		$this->_userFullName   = $conf->get('userFullName',   'layout');
		$this->_dbACL = & elSingleton::getObj('elDb');
		$this->_initAuthDb($conf->get('authDb', 'auth'), $conf->getGroup('db'), $conf->get('importGroups', 'auth'));
		$this->_dbHash = md5($this->_dbACL->_host.' '.$this->_dbACL->_db.' '.$this->_dbACL->_user.' '.$this->_dbACL->_pass);

		if ( 0 < ($to = $conf->get('sessionTimeOut', 'auth')) ) {
			$this->_sessTO = (int)$to;
		}
		// $this->_iGroups = array(1 => 'root', 2 => 'guests');
	}

	/**
	 * create user, autologin and load acl
	 *
	 * @param  int   $pageID
	 * @return void
	 **/
	function init( $pageID ) {
		$this->_pageID = $pageID;

		// $this->user = & new elUser($this->_dbAuth, $this->_dbACL, array_keys($this->_iGroups), $this->_dbHash, $this->_userFullName);
		$this->user = & $this->createUser();

		if ( $this->user->autoLogin($this->_sessTO) && ($this->user->isRoot() || $this->user->isInGroupRoot()) ) {
			$this->_userRoot = true;
		} else {
			$this->_loadACL();
		}
	}

	/**
	 * return auth conf
	 *
	 * @return mixed
	 **/
	function conf($var) {
		return isset($this->_conf[$var]) ? $this->_conf[$var] : false;
	}

	/**
	 * return true if new users registration allowed
	 *
	 * @return bool
	 **/
	function isRegistrationAllowed() {
		return $this->conf('registrAllow');
	}

	/**
	 * return auth db
	 *
	 * @return elDb
	 **/
	function &getAuthDb() {
		return $this->_dbAuth;
	}

	/**
	 * return acl db
	 *
	 * @return elDb
	 **/
	function &getACLDb() {
		return $this->_dbACL;
	}

	/**
	 * true if auth db is local db
	 *
	 * @return bool
	 **/
	function isLocalAuth() {
		return $this->_isLocalAuth;
	}

	// USER
	/**
	 * create new user
	 *
	 * @return elUser
	 **/
	function createUser() {
		return new elUser($this->_dbAuth, array_keys($this->_iGroups), $this->_dbHash, $this->_userFullName);
	}
	
	/**
	 * return current user
	 *
	 * @return elUser
	 **/
	function &getUser() {
		return $this->user;
	}

	/**
	 * return current user ID
	 *
	 * @return int
	 **/
	function getUserID() {
		return $this->user->UID;
	}

	/**
	 * return true if user authed
	 *
	 * @return bool
	 **/
	function isUserAuthed() {
		return $this->user->UID;
	}

	/**
	 * render auth form and login user
	 *
	 * @param  string  $url  URL for redirect after auth
	 * @return bool
	 **/
	function auth($url=EL_URL) {
		elLoadMessages('Auth');

		// login via ajax
		if (isset($_POST['ajax'])) {
			include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
			
			if ( $this->user->isAuthed() )	{
				exit(elJSON::encode(array('error' => m('You must logout before logging in'))));
			}
			$login = trim($_POST['login']);
			$pass  = trim($_POST['pwd']);
			if (!$login || !$pass || !$this->user->login($login, $pass, $this->_conf['loginCaseSensetive'])) {
				exit(elJSON::encode(array('error' => m('Authorization failed'))));
			} else {
				elMsgBox::put( sprintf( m('Wellcome back %s!'), $this->user->getFullName() ) );
				exit(elJSON::encode(array('ok' => 1)));
			}
		}

		// ordinary login
		if ( $this->user->isAuthed() )	{
			elThrow(E_USER_WARNING, m('You must logout before logging in'), null, EL_URL);
		}
		
		$this->_initForm(m('Authorization required'));
		$this->form->setAttr('action', EL_URL.'__auth__/');
		$this->form->add( new elText('elLogin', m('Login')) );
		$this->form->add( new elPassword('elPass', m('Password')) );
		$this->form->add( new elCData('fp', '<a href="'.EL_URL.'__profile__/remind/'.'">'.m('Forgot Your password?').'</a>') );
		if ( $this->_regAllow ) {
			$this->form->add( new elCData('reg', '<a href="'.EL_URL.'__profile__/reg/'.'">'.m('New user registration').'</a>') );
		}
		$this->form->add(new elHidden('url', '', $url));
		$rnd = &elSingleton::getObj('elSiteRenderer');
		$rnd->setPageContent( $this->form->toHtml() );
		
		if ($this->form->isSubmitAndValid()) {

			$data = $this->form->getValue();
			$url  = !empty($data['url']) ? $data['url'] : EL_URL;

			if ( (empty($data['elLogin']) || empty($data['elPass'])) 
			|| !$this->user->login($data['elLogin'], $data['elPass'], $this->_conf['loginCaseSensetive']) ) {
				return elThrow(E_USER_WARNING, m('Authorization failed'));
			}
			
			elMsgBox::put( sprintf( m('Wellcome back %s!'), $this->user->getFullName() ) );
		    elLocation($url);
		}
	}

	/**
	 * logout user
	 *
	 * @return void
	 **/
	function logOutUser( ) {
		if ( $this->user->UID ) {
			if ( 1 == $this->user->UID ) {
				elCleanCache();
			}
			elLoadMessages('Auth');
			elMsgBox::put( sprintf( m('Good bye %s!'), $this->user->getFullName() ) );
			$this->user->logout();
		}
		elLocation(EL_BASE_URL);
	}

	/**
	 * возвращает сгенерированный случайным образом пароль
	 *
	 * @return string
	 **/
	function randPasswd() {
		return substr(md5(uniqid('')),-9,7);
	}

	/**
	 * create/edit  user
	 *
	 * @param  elUser  $user
	 * @return bool
	 **/
	function editUser(&$user) {
		$isNew = !$user->UID;
		
		if (!$user->editAndSave()) {
			$this->form = $user->getForm();
		} else {
			if ($isNew) {
		        //send login/pass on email and notify site admin about new user
		        $this->_notifyUser($user, $passwd, EL_UNTF_REGISTER);
			}
			return true;
		}
	}

	/**
	 * change user password
	 *
	 * @param  elUser  $user
	 * @return bool
	 **/
	function passwd(&$user) {
		$this->_initForm( sprintf( m('Change password for user "%s"'), $user->login ) );
	    $this->form->add( new elPasswordDoubledField('pass', m('Password twice')) );
	    $this->form->setElementRule('pass', 'password', 1, null);
		if ($this->form->isSubmitAndValid()) {
	        $passwd = $this->form->getElementValue('pass');
			$user->passwd($passwd);
	        $this->_notifyUser($user, $passwd, EL_UNTF_PASSWD);
	        return true;
	      }
	}

	/**
	 * create new user password on request
	 *
	 * @return void
	 **/
	function remindPasswd() {
		$this->_initForm( m('Remind login/password') );
	    $this->form->add( new elCData(null, m('Please, enter Your login or email and new authorization data will be send on Your email')) );
	    $this->form->add( new elText('im', m('Login or e-mail')) );
	    $this->form->setRequired( 'im' );

	    if ( !$this->form->isSubmitAndValid() )
	    {
	      $rnd = &elSingleton::getObj('elSiteRenderer');
	      $rnd->setPageContent( $this->form->toHtml() );
	      return;
	    }
	
		$test = $this->form->getElementValue('im');
		$sql  = sprintf('SELECT * FROM el_user WHERE %s="%s"', strstr($test, '@') ? 'email' : 'login', mysql_real_escape_string($test));
	    $this->_dbAuth->query($sql);
	    if (1 != $this->_dbAuth->numRows()) {
	    	elThrow(E_USER_WARNING, 'User with this login/email does not exists', null, EL_URL.'__passwd__/');
	    }

		$user = & new elUser($this->_dbAuth, array_keys($this->_iGroups), $this->_dbHash, $this->_userFullName);
		$user->attr($this->_dbAuth->nextRecord());
	    $passwd = $this->randPasswd();
		$user->passwd($passwd);
	    $this->_notifyUser($user, $passwd, EL_UNTF_REMIND);
	    elMsgBox::put( sprintf(m('New password was send onto e-mail - %s'), $user->email) );
	    elLocation(EL_URL);
	}

	/**
	 * return form html
	 *
	 * @return string
	 **/
	function formToHtml() {
		if (!$this->form) {
			$this->initForm('');
		}
		return $this->form->toHtml();
	}

	// ACCESS
	/**
	 * return true if access of requeired type is allowed to page
	 *
	 * @param  int  $mode  access type
	 * @param  int  $pageID page ID
	 * @return bool
	 **/
	function allow($mode=EL_READ, $pageID=null) {
		if ( null == $pageID) {
			$pageID = $this->_pageID;
		} elseif (!is_numeric($pageID)) {
			$nav = & elSingleton::getObj('elNavigator');
			$pageID = $nav->getPageID($pageID);
		}
		if ($this->_userRoot) {
			return TRUE;
		}
		return !empty( $this->_ACL[$pageID] ) ? $this->_ACL[$pageID] >= $mode : FALSE;
	}

	/**
	 * return current user access mode to current page
	 *
	 * @return int
	 **/
	function getPageAccessMode() {
		return $this->_userRoot
			? EL_FULL
			: (!empty( $this->_ACL[$this->_pageID] ) ? $this->_ACL[$this->_pageID] : 0);
	}

  	//  GROUPS

	/**
	 * return imported groups if exists
	 *
	 * @return array
	 **/
	function getImportGroups() {
		return $this->_iGroups;
	}

	/**
	 * return default group ID
	 *
	 * @return int
	 **/
	function getDefaultGID() {
		return (int)$this->conf('defaultGID');
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function createGroup() {
		include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elUserGroup.class.php';
		$group = & new elUserGroup();
		$group->db = & $this->_dbAuth;
		return $group;
	}

	/**
	 * return default group name
	 *
	 * @return string
	 **/
	function getDefaultGroupName() {
		$g = $this->createGroup();
		$g->idAttr($this->getDefaultGID());
		return $g->fetch() ? $g->name : m('Undefined');
	}

	

	

	/**
	 * return groups list
	 *
	 * @return array
	 **/
	function getGroupsList() {
		return $this->_iGroups
			? $this->_iGroups
			: $this->_dbAuth->queryToArray('SELECT gid, name FROM el_group ORDER BY gid', 'gid', 'name');
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
   * смена пароля
   * @param $user  obj user object
   * @param $do    int action - change password, set random password or send new password
   */
  function _passwd( &$user, $do=EL_PASSWD_INPUT )
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

	/**
	 * оповещение пользователя по email о регистрации/смене пароля
	 *
	 * @param  string  $email
	 * @param  string  $login
	 * @param  string  $passwd
	 * @param  int     $type
	 * @return void
	 **/
	function notifyUser($user, $passwd, $type=EL_UNTF_REMIND ) {
		
		$conf     = &elSingleton::getObj('elXmlConf');
		$siteName = $conf->get('siteName', 'common');
		$emails   = & elSingleton::getObj('elEmailsCollection');
		$postman  = & elSingleton::getObj('elPostman');

		if (EL_UNTF_REMIND == $type ) {
			$subj = m('Changing password notification');
			$msg  = m("Your password for site %s [%s] was changed on Your request.\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		} elseif ( EL_UNTF_REGISTER == $type) {
			
			if ($this->conf('newUserAdminNotify') ) {
				$subj = sprintf( m('New user was registered on %s (%s)'), $siteName, EL_BASE_URL );
				$msg = '';
				foreach ( $user->getData() as $one ) {
					$msg .= m($one['label']).': '.$one['value']."\n";
				}
				$postman->newMail($emails->getDefault(), $emails->getDefault(), $subj, $msg, false, $sign);
				$postman->deliver();
	        }
			
			if (!$this->conf('newUserNotify')) {
				return;
			}
			
			$subj = m('New user registration notification');
			$msg  = m("You are was registered as user on site %s [%s].\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		} elseif ( EL_UNTF_PASSWD == $type   &&  $this->conf('changePasswordNotify') ) {
			$subj = m('Changing password notification');
			$msg  = m("Your password for site %s [%s] was changed on Your request.\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		} else {
			return;
		}

		$msg  = sprintf( $msg, $siteName, EL_BASE_URL, $user->login, $passwd );
		$sign = sprintf( m("With best wishes\n%s\n"), $conf->get('owner', 'common') );
		echo $msg;
		$postman->newMail($emails->getDefault(), $user->getEmail(), $subj, $msg, false, $sign);

		if ( !$postman->deliver() ) {
			elThrow( E_USER_WARNING, m("Sending e-mail to address %s was failed.\n Here is message conent: %s\n\n"), array(htmlspecialchars($email), $msg));
			elDebug($postman->error);
		}
	}

	/**
	* Загружает таблицу привилегий пользователя
	* доступ к странице = доступ стр по умолчанию & [доступ групп пользователя к этой странице]
	*/
	function _loadACL() {
		if ( !$this->user->groups ) {
			$sql = 'SELECT ch.id, MIN(p.perm) AS perm FROM el_menu AS p, el_menu AS ch 
					WHERE ch._left BETWEEN p._left AND p._right AND p.level>0 GROUP BY ch.id';
		} else {
			$sql = 'SELECT ch.id, IF ( acl.perm>MIN(p.perm), acl.perm, MIN(p.perm) ) AS perm 
					FROM el_menu AS p, el_menu AS ch 
					LEFT JOIN el_group_acl AS acl 
					ON page_id=ch.id 
					AND group_id IN ('.implode(',', $this->user->groups)
					.') WHERE ch._left BETWEEN p._left AND p._right AND p.level>0 GROUP BY ch.id'; //echo $sql;
		}
		$this->_ACL = $this->_dbACL->queryToArray($sql, 'id', 'perm');
	}

	/**
	 * create form object
	 *
	 * @param  string  $label
	 * @return void
	 **/
	function _initForm($label) {
	    $this->form = & elSingleton::getObj( 'elForm', 'elf', $label );
	    $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
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