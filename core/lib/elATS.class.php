<?php

elLoadMessages('Auth');

define ('EL_UNTF_REMIND',    1);
define ('EL_UNTF_REGISTER',  2);
define ('EL_UNTF_PASSWD',    3);

/**
 * Athentithication and access control
 *
 * @package core
 * @author dio
 **/
class elATS {
	
	var $user          = null;
	var $_dbAuth       = null;
	var $_dbACL        = null;
	var $_isLocalAuth  = true;
	var $_sessTO       = 86400; // 1 month
	var $_iGroups      = array();
	var $_ACL          = array();
	var $_userRoot     = false;
	var $_pageID       = 0;
	var $_userFullName = false;

	function elATS() {
		$conf         = & elSingleton::getObj('elXmlConf');
		$this->_conf  = $conf->getGroup('auth');
		if (!isset($this->_conf['loginCaseSensetive'])) {
			$this->_conf['loginCaseSensetive'] = true;
		}
		$this->_userFullName = $conf->get('userFullName',   'layout');
		$this->_dbACL        = & elSingleton::getObj('elDb');
		$dbAuthConf          = $conf->get('authDb', 'auth');
		$dbACLConf           = $conf->getGroup('db');
		
		if ($dbAuthConf && !empty($dbAuthConf['db']) && !empty($dbAuthConf['user'])) {
			$this->_dbAuth = & new elDb(
				$dbAuthConf['user'], 
				!empty($dbAuthConf['pass']) ? $dbAuthConf['pass'] : '', 
				$dbAuthConf['db'],
				!empty($dbAuthConf['host']) ? $dbAuthConf['host'] : '', 
				!empty($dbAuthConf['sock']) ? $dbAuthConf['sock'] : '' 
				);
			if ($this->_dbAuth->connect(true)) {
				$this->_isLocalAuth = false;
				$this->_iGroups = $conf->get('importGroups', 'auth');
				
				if (empty($this->_iGroups) || !is_array($this->_iGroups)) {
					$this->_iGroups = array(1 => 'root');
				}
				if (!isset($this->_iGroups[1])) {
					$this->_iGroups[1] = 'root';
				}
			} else {
				unset($this->_dbAuth);
				$conf->drop('authDb', 'auth');
				$conf->save();
			}
		}
		
		if (!$this->_dbAuth) {
			$this->_dbAuth = & $this->_dbACL;
		}
		
		$this->_dbHash = md5($this->_dbACL->_host.' '.$this->_dbACL->_db.' '.$this->_dbACL->_user.' '.$this->_dbACL->_pass);

		if ( 0 < ($to = $conf->get('sessionTimeOut', 'auth')) ) {
			$this->_sessTO = (int)$to;
		}
	}

	/**
	 * create user, autologin and load acl
	 *
	 * @param  int   $pageID
	 * @return void
	 **/
	function init( $pageID ) {
		$this->_pageID = $pageID;
		$this->user    = & $this->createUser();
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
		return (bool)$this->user->UID;
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
		$this->form = & elSingleton::getObj( 'elForm', 'elf', m('Authorization required'));
	    $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$this->form->setAttr('action', EL_URL.'__auth__/');
		$this->form->add( new elText('elLogin', m('Login')) );
		$this->form->add( new elPassword('elPass', m('Password')) );
		$this->form->add( new elCData('fp', '<a href="'.EL_URL.'__profile__/remind/'.'">'.m('Forgot Your password?').'</a>') );
		if ( $this->_regAllow ) {
			$this->form->add( new elCData('reg', '<a href="'.EL_URL.'__profile__/reg/'.'">'.m('New user registration').'</a>') );
		}
		$this->form->add(new elHidden('url', '', $url));
		$rnd = &elSingleton::getObj('elSiteRenderer');
		$rnd->setPageContent($this->form->toHtml());
		
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
	function logOutUser() {
		if ( $this->user->UID ) {
			if ( 1 == $this->user->UID ) {
				elCleanCache();
			}
			elMsgBox::put( sprintf( m('Good bye %s!'), $this->user->getFullName() ) );
			$this->user->logout();
		}
		elLocation(EL_BASE_URL);
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
	 * create and return new user group object
	 *
	 * @return elUserGroup
	 **/
	function createGroup() {
		include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elUserGroup.class.php';
		$group = & new elUserGroup();
		$group->db = & $this->_dbAuth;
		$group->onlyGroups = array_keys($this->_iGroups);
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
	 * оповещение пользователя по email о регистрации/смене пароля
	 *
	 * @param  string  $email
	 * @param  string  $login
	 * @param  string  $passwd
	 * @param  int     $type
	 * @return void
	 **/
	function notifyUser($user, $passwd, $type=EL_UNTF_REMIND) {
		
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
		// echo $msg;
		$postman->newMail($emails->getDefault(), $user->getEmail(), $subj, $msg, false, $sign);

		if ( !$postman->deliver() ) {
			elThrow( E_USER_WARNING, m("Sending e-mail to address %s was failed.\n Here is message conent: %s\n\n"), array(htmlspecialchars($email), $msg));
			elDebug($postman->error);
		}
	}
	
	/**
	 * add groups acl for new page
	 *
	 * @param  int  $pageID
	 * @return void
	 **/
	function onPageChange($pageID) {
		$nav  = &elSingleton::getObj('elNavigator');
		$page = $nav->pageByModule('NavigationControl');
		if ($page) {
			$sql = sprintf('SELECT group_id, '.$pageID.' AS page, perm FROM el_group_acl WHERE page_id=%d', $page['id']);
			$perms = $this->_dbACL->queryToArray($sql);
			if ($perms) {
				$this->_dbACL->prepare('REPLACE INTO el_group_acl (group_id, page_id, perm) VALUES', '(%d, %d, "%d")');
				$this->_dbACL->prepareData($perms, true);
				$this->_dbACL->execute();
			}
		}
	}

	/**
	 * delete groups acl for deleted page
	 *
	 * @param  int  $pageID
	 * @return void
	 **/
	function onPageDelete($pageID) {
		$this->_dbACL->query(sprintf('DELETE FROM el_group_acl WHERE page_id=%d', $pageID));
		$this->_dbACL->optimizeTable('el_group_acl');
	}

  /////////////////////////   *  PRIVATE  *  ////////////////////

	/**
	 * load acl for user
	 *
	 * @return void
	 **/
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

} // END class 

?>
