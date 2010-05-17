<?php

elLoadMessages('Auth');
elLoadMessages('UserProfile');
elSingleton::incLib('lib/elCoreAdmin.lib.php');

/**
 * Класс управления системой контроля доступа
 * Методы изменяющие данные пользователей, групп и др
 */

class elATSManager
{
  var $_ats = null;
  var $form = null;

  /**
   * After site page was modified update write access to this page
   * for all groups which has write access to Navigation control
   * (exclude groups: root and default group for new registered users)
   *
   * @param int $pageID
   */
  function onPageChange($pageID)
  {
  	$conf   = & elSingleton::getObj('elXmlConf');
  	$navIDs = $conf->findGroup('module', 'NavigationControl', true);

  	$sql = 'SELECT group_id FROM el_group_acl '
                .'WHERE group_id NOT IN (1, '.$this->_ats->_defGID.' ) '
                .'AND page_id IN ('.implode(',', $navIDs).') '
                .'AND perm IN("'.EL_WRITE.'", "'.EL_FULL.'")';
  	$groups = $this->_ats->_dbACL->queryToArray($sql, 'group_id', 'group_id');

  	$sql     = 'SELECT group_id, page_id FROM el_group_acl WHERE page_id='.$pageID;
  	$exRecs  = $this->_ats->_dbACL->queryToArray($sql, 'group_id'); //elPrintR($recordsExists);
  	foreach ($groups as $groupID)
  	{
  		// set acl if not defined yet
  		if ( empty($exRecs[$groupID]))
  		{
  			$sql = 'REPLACE INTO el_group_acl SET '
                          .'group_id=\''.$groupID.'\', page_id=\''.$pageID.'\', perm=\''.EL_WRITE.'\'';
  			$this->_ats->_dbACL->query( $sql );
  		}
  	}
  }


	/**
	 * Редактирование/создание нового пользователя
	 *
	 * @param  elUser  $user
	 * @return bool
	 **/
	function editUser(&$user) {
		$isNew = !$user->UID;
		$skel = $user->profile->getSkel();
		$this->form = $skel->getForm(EL_URL, 'POST', $user->toArray());
		
		if (!$this->form->isSubmitAndValid()) {
			return false;
		}
		
		$data = $this->form->getValue();
		$user->attr($data);
		$user->profile->attr($data);

		if (!$user->save()) {
			return elThrow(E_USER_ERROR, 'Could save user data');
		}
		$user->profile->save();
		
		if ($isNew) {
			$passwd = $this->_randPasswd();
			$user->passwd($passwd);
	        if ( 1 < ($GID = (int)$this->conf('defaultGID')) ) {
	        	$this->_saveUserGroups($user->UID, array($GID));
	        }
	        //send login/pass on email and notify site admin about new user
	        $this->_notifyUser($user, $passwd, EL_UNTF_REGISTER);
		}
		return true;
	}

  function passwd_(&$user, $do=EL_PASSWD_INPUT)
  {
  	switch ($do)
    {
    	case EL_PASSWD_REMIND:
    		return $this->_passwdRemind( $user );
    	case EL_PASSWD_RAND:
    		return $this->_passwdRand( $user );
    	default:
    		return $this->_passwdInput( $user );
    }
  }

	/**
	 * change user password
	 *
	 * @param  elUser  $user
	 * @return void
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

  //смена пароля
  function _passwdInput( &$user )
  {
    $this->_initForm( sprintf( m('Change password for user "%s"'), $user->login ) );
    $this->form->add( new elPasswordDoubledField('pass', m('Password twice')) );
    $this->form->setElementRule('pass', 'password', 1, null);

      if ( $this->form->isSubmitAndValid() )
      {
        $passwd = $this->form->getElementValue('pass');
        $this->_savePasswd($user->UID, $passwd);
        $this->_notifyUser($user->getEmail(), $user->login, $passwd, EL_UNTF_PASSWD);
        return true;
      }
  }

  //устанавливает случайный пароль для пользователя
  function _passwdRand( &$user, $notify=false )
  {
    $passwd = $this->_randPasswd();
    $this->_savePasswd($user->UID, $passwd);
    return $passwd;
  }

  /**
   * Создание нового пароля по запросу пользователя
   * Получает от пользователя логин или email
   * сохраняет новый пароль если такой пользователь существует
   * отправляет новый пароль на email пользователя (не блокируется disableUserNotify в конфиге)
   */
  function _passwdRemind()
  {
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
    $sql  = 'SELECT uid, login, email FROM el_user WHERE ';
    $sql .= (strstr($test, '@')) ? 'email' : 'login';
    $sql .= '=\''.mysql_real_escape_string($test).'\'';

    $this->_ats->_dbAuth->query( $sql );
    if ( 1 != $this->_ats->_dbAuth->numRows() )
    {
      elThrow(E_USER_WARNING, 'User with this login/email does not exists', null, EL_URL.'__passwd__/');
    }
    $r = $this->_ats->_dbAuth->nextRecord();
    $UID    = $r['uid'];
    $login  = $r['login'];
    $passwd = $this->_randPasswd();
    $this->_savePasswd( $r['uid'], $passwd );
    $this->_notifyUser($r['email'], $r['login'], $passwd, EL_UNTF_REMIND);
    elMsgBox::put( sprintf(m('New password was send onto e-mail - %s'), $r['email']) );
    elLocation( EL_URL );
  }

  /**
   * Сохраняяет пароль для пользователя с UID
   */
  function _savePasswd( $UID, $passwd )
  {
    $sql = 'UPDATE el_user SET pass=\''.md5($passwd).'\', mtime=\''.time().'\' WHERE uid=\''.$UID.'\'';
    $this->_ats->_dbAuth->query($sql);
  }

  // изменяет список групп пользователя
  function setUserGroups( &$user )
  {
    $this->_initForm( sprintf( m('Groups for user "%s"'), $user->login ) );
    $sql = 'SELECT gid FROM el_group, el_user_in_group WHERE user_id=\''.$user->UID.'\' AND gid=group_id ';
    if ( $this->_ats->_iGroups )
    {
      $sql .= 'AND gid IN ('.implode(',', array_keys($this->_ats->_iGroups)).')';
    }
    $groups = $this->_ats->_dbAuth->queryToArray( $sql, null, 'gid' );
    $this->form->add( new elCheckBoxesGroup('gids', m('Groups'), $groups, $this->_ats->getGroupsList()) );

    if ( $this->form->isSubmitAndValid() )
    {
      $vals    = $this->form->getValue();
      $newGIDs = $vals['gids'];
      $this->_saveUserGroups( $user->UID, $vals['gids']);
      return true;
    }
    return false;
  }

  function addUserToGroup(&$user, $GID, $only=false)
  {
    if ( $only )
    {
      $groups = array($GID);
    }
    else
    {
      $sql = 'SELECT gid FROM el_group, el_user_in_group WHERE user_id=\''.$user->UID.'\' AND gid=group_id ';
      $sql .= !empty($this->_ats->_iGroups) ? 'AND gid IN ('.implode(',', array_keys($this->_ats->_iGroups)).')' : '';
      $groups = $this->_ats->_dbAuth->queryToArray( $sql, null, 'gid' );
      if (!in_array($GID, $groups))
      {
        $groups[] = $GID;
      }
    }
    $this->_saveUserGroups( $user->UID, $groups);
    return true;
  }

  // удаление пользователя
  // используется модулем users контрольного центра
  function rmUser( &$user )
  {
    $this->_ats->_dbAuth->query('DELETE FROM el_user WHERE uid=\''.$user->UID.'\'');
    $this->_ats->_dbAuth->query('DELETE FROM el_user_in_group WHERE user_id=\''.$user->UID.'\'');
    $this->_ats->_dbAuth->query('DELETE FROM el_user_pref WHERE user_id=\''.$user->UID.'\'');
    $this->_ats->_dbAuth->optimizeTable('el_user');
    $this->_ats->_dbAuth->optimizeTable('el_user_in_group');
    $this->_ats->_dbAuth->optimizeTable('el_user_pref');
    return true;
  }

  // возвращает массив - список пользователей сайта в соответствии с критериями отбора/сортировки
  // если используется внешняя бд авторизации - только пользователи из импортируемых групп, пользователи без групп не импортируются
  // используется модулем users контрольного центра
  function getUsersList($search, $group, $sf, $order, $start, $offset, $count=false, $incProfile=true)
  {
    $fields = $this->_ats->user->listAttrs();
    if ($incProfile)
    {
      $p = $this->_ats->user->getProfile();
      $fields = array_merge($fields, $p->attrsList());
      $fields = array_unique($fields);
    }
    $sql = 'SELECT '.implode(',',$fields).', email FROM el_user LEFT JOIN el_user_in_group ON uid=user_id WHERE ';
    if ( '' === $group )
    {
      $sql .= ' 1 ';
    }
    else
    {
      $sql .= $group>0
        ? ' group_id='.$group.' '
        : ' group_id IS NULL AND uid>1 ';
    }
    if ( $this->_ats->_iGroups )
    {
      $sql .= ' AND group_id IN ('.implode(',', array_keys($this->_ats->_iGroups)).') ';
    }

    if ( $search )
    {
      $sql .= ' AND login LIKE "%'.$search.'%" ';
    }
    $sql .= ' GROUP BY uid ';
    if ( $sf )
    {
      $sql .= ' ORDER BY '.$sf.' '.$order ;
    }
    if ( !$count )
    {
      $sql .= ' LIMIT '.$start.', '.$offset;
      return $this->_ats->_dbAuth->queryToArray($sql, 'uid');
    }
    else
    {
      $this->_ats->_dbAuth->query($sql);
      return $this->_ats->_dbAuth->numRows();
    }
  }

  // возвращает массив - список групп для всех пользователей сайта
  // используется модулем users контрольного центра
  function getUsersGroupsList( )
  {
    $sql = 'SELECT user_id, gid, name FROM el_group, el_user_in_group WHERE group_id=gid '
          .( $this->_ats->_iGroups ? ' AND gid IN ('.implode(',', array_keys($this->_ats->_iGroups)).')' : '')
          .' ORDER BY gid';
    $this->_ats->_dbAuth->query($sql);
    $ret = array();
    while ( $r = $this->_ats->_dbAuth->nextRecord() )
    {
      if ( !isset($ret[$r['user_id']]) )
      {
        $ret[$r['user_id']] = array();
      }
      $ret[$r['user_id']][$r['gid']] = $r['name'];
    }
    return $ret;
  }


  function setImportGroupsList()
  {
    $sql       = 'SELECT gid, name FROM el_group ORDER BY gid';
    $allGroups = $this->_ats->_dbAuth->queryToArray($sql, 'gid', 'name');
    $this->_initForm(m('Change import groups list'));
    $this->form->add( new elCheckBoxesGroup('imp_gids', m('Import groups list'), array_keys($this->_ats->_iGroups), $allGroups) );

    if ( $this->form->isSubmitAndValid() )
    {
      $impGroups = array();
      $vals      = $this->form->getValue();
      if ( !empty($vals['imp_gids']) && is_array($vals['imp_gids']) )
      {
        foreach ( $vals['imp_gids'] as $gid )
        {
          if ( isset($allGroups[$gid]) )
            {
              $impGroups[$gid] = $allGroups[$gid];
            }
        }
      }
      if ( empty($impGroups[1]) )
      {
        $impGroups = array(1=>'root');
      }
      $conf = &elSingleton::getObj('elXmlConf');
      $conf->set('importGroups', $impGroups, 'auth');
      $conf->save();
      return true;
    }
  }

  /////////////////////////////////////   * PRIVATE  * ///////////////////////////

  
	/**
	 * оповещение пользователя по email о регистрации/смене пароля
	 *
	 * @param  string  $email
	 * @param  string  $login
	 * @param  string  $passwd
	 * @param  int     $type
	 * @return void
	 **/
	function _notifyUser($user, $passwd, $type=EL_UNTF_REMIND ) {
		
		$conf     = &elSingleton::getObj('elXmlConf');
		$siteName = $conf->get('siteName', 'common');
		$emails   = & elSingleton::getObj('elEmailsCollection');
		$postman  = & elSingleton::getObj('elPostman');

		if (EL_UNTF_REMIND == $type ) {
			$subj = m('Changing password notification');
			$msg  = m("Your password for site %s [%s] was changed on Your request.\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		} elseif ( EL_UNTF_REGISTER == $type) {
			
			if ($conf->get('newUserAdminNotify') ) {
				$subj = sprintf( m('New user was registered on %s (%s)'), $siteName, EL_BASE_URL );
				$msg = '';
				foreach ( $user->getProfileData() as $one ) {
					$msg .= m($one['label']).': '.$one['value']."\n";
				}
				$postman->newMail($emails->getDefault(), $emails->getDefault(), $subj, $msg, false, $sign);
				$postman->deliver();
	        }
			
			if (!$this->_ats->conf('newUserNotify')) {
				return;
			}
			
			$subj = m('New user registration notification');
			$msg  = m("You are was registered as user on site %s [%s].\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		} elseif ( EL_UNTF_PASSWD == $type   &&  $this->_ats->conf('changePasswordNotify') ) {
			$subj = m('Changing password notification');
			$msg  = m("Your password for site %s [%s] was changed on Your request.\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		} else {
			return;
		}

		$msg  = sprintf( $msg, $siteName, EL_BASE_URL, $user->login, $passwd );
		$sign = sprintf( m("With best wishes\n%s\n"), $conf->get('owner', 'common') );

		$postman->newMail($emails->getDefault(), $user->getEmail(), $subj, $msg, false, $sign);

		if ( !$postman->deliver() ) {
			elThrow( E_USER_WARNING, m("Sending e-mail to address %s was failed.\n Here is message conent: %s\n\n"), array(htmlspecialchars($email), $msg));
			elDebug($postman->error);
		}
	}
	
  //сохраняет список групп пользователя
  function _saveUserGroups( $UID, $GIDs )
  {
    $this->_ats->_dbAuth->query('DELETE FROM el_user_in_group WHERE user_id=\''.$UID.'\'');
    $this->_ats->_dbAuth->optimizeTable('el_user_in_group');
    if ( $GIDs )
    {
      $this->_ats->_dbAuth->prepare('INSERT INTO el_user_in_group (user_id, group_id) VALUES ', '(%d, %d)');
      foreach ( $GIDs as $GID )
      {
        $this->_ats->_dbAuth->prepareData( array($UID, $GID) );
      }
      $this->_ats->_dbAuth->execute();
    }
  }

  function _initForm($label)
  {
    $this->form = & elSingleton::getObj( 'elForm', 'mf', $label );
    $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
  }

  // возвращает сгенерированный случайным образом пароль
  function _randPasswd()
  {
	return substr(md5(uniqid('')),-9,7);
  }

}

?>
