<?php

class elForumProfile extends elDataMapping
{
	var $_tb        = 'el_user';
	var $_tbc       = 'el_forum_cat';
	var $_tbp       = 'el_user_profile';
	var $_tbpu      = 'el_user_profile_use';
	var $_tbm       = 'el_forum_moderator';
	var $_tbr       = 'el_forum_role';
	var $_tblrf     = 'el_forum_log_read_forum';
	var $_tblrt     = 'el_forum_log_read_topic';
	var $_id        = 'uid';
	var $__id__     = 'UID';
	var $_objName   = 'Profile';
	var $_gids      = null;
	var $UID        = 0;
	var $login      = '';
	var $fName      = '';
	var $sName      = '';
	var $lName      = '';
	var $email      = '';
	var $fax        = '';
	var $phone      = '';
	var $company    = '';
	var $postalCode = '';
	var $address    = '';
	var $ICQ        = '';
	var $webSite    = '';
	var $crtime     = 0;
	var $mtime      = 0;
	var $atime      = 0;
	var $postsCount = 0;
	var $avatar     = '';
	var $gender     = '';
	var $signature  = '';
	var $defaultAvatar  = '';
	var $showEmail = false;
	var $showOnline = false;
	
	
	/**
	 * Возвращает массив данных пользователей
	 * массив данных пользователя такой же как возвращается методом brief()
	 *
	 * @param  string  $type     тип выборки пользователей (все, по опред первой букве, модераторы форума)
	 * @param  misc    $param    доп параметр (буква или id форумов)
	 * @param  int     $pageNum  текущая страница результатов
	 * @param  int     $limit    кол-во записей в выборке
	 *
	 * @return array
	 **/
	function collection($type, $param=null, $pageNum=1, $limit=0)
	{
		$collection = array();
		$total      = 1;

		$db = & elSingleton::getObj('elDb');
		if ('letter' == $type)
		{
			$sql = sprintf(
				'SELECT uid, IF(f_name!="", CONCAT(f_name, " ", l_name), login) AS name, email,  
				web_site, forum_posts_count AS posts, icq_uin AS icq, avatar,
				DATE_FORMAT(FROM_UNIXTIME(crtime), "%s") AS reg_date, 
				(UNIX_TIMESTAMP(NOW())-atime)<=900 AS online
				FROM %s WHERE IF(f_name!="", LEFT(UPPER(f_name), 1)="%s", LEFT(UPPER(login), 1)="%s") 
				ORDER BY name', 
				EL_MYSQL_DATE_FORMAT, $this->_tb, $param, $param);
		}
		elseif ( 'moderators' == $type )
		{
			$sql = sprintf(
				'SELECT  m.rid, u.uid, IF(u.f_name!="", CONCAT(u.f_name, " ", u.l_name), u.login) AS name, u.email,  
				u.web_site, u.forum_posts_count AS posts, u.icq_uin AS icq, avatar,
				DATE_FORMAT(FROM_UNIXTIME(u.crtime), "%s") AS reg_date, 
				(UNIX_TIMESTAMP(NOW())-atime)<=900 AS online
				FROM %s AS u, %s AS m, %s AS r WHERE m.cat_id IN (%s) AND u.uid=m.uid AND r.id=m.rid
				GROUP BY u.uid ORDER BY name', 
				EL_MYSQL_DATE_FORMAT,  $this->_tb, $this->_tbm, $this->_tbr, implode(',', $param));
		}
		else
		{
			$db->query('SELECT COUNT(*) AS num FROM '.$this->_tb);
			$r       = $db->nextRecord();
			$total   = ceil($r['num']/$limit);
			$pageNum = $pageNum>0 && $pageNum <= $total ? $pageNum : 1;
			$sql     = sprintf(
				'SELECT uid, IF(f_name!="", CONCAT(f_name, " ", l_name), login) AS name, email,  
				web_site, forum_posts_count AS posts, icq_uin AS icq, avatar,
				DATE_FORMAT(FROM_UNIXTIME(crtime), "%s") AS reg_date, 
				(UNIX_TIMESTAMP(NOW())-atime)<=900 AS online
				FROM  %s ORDER BY name LIMIT %d, %d',
				EL_MYSQL_DATE_FORMAT,  $this->_tb, ($pageNum-1)*$limit, $limit);
		}
		return array($db->queryToArray($sql), $pageNum, $total);
	}
	
	function getGroups()
	{
		if ( is_null($this->_gids) )
		{
			$db = & elSingleton::getObj('elDb');
			$this->_gids = $db->queryToArray('SELECT group_id FROM el_user_in_group WHERE user_id="'.$this->UID.'"', null, 'group_id');
		}
		return $this->_gids;
	}
	
	/**
	 * Возвращает массив первых букв имен или логинов пользователей
	 *
	 * @return bool
	 **/
	function letters()
	{
		$db = & elSingleton::getObj('elDb');
		return $db->queryToArray('SELECT LEFT( UPPER(IF(f_name!="", CONCAT(f_name, " ", l_name), login)), 1) AS letter FROM '.$this->_tb.' GROUP BY letter ORDER BY letter', null, 'letter');
	}
	
	/**
	 * Возвращает/устанавливает значение сессионной переменой с именем $key
	 *
	 * @param  string $key  имя сессионной переменной
	 * @param  misc   $val  новое значение переменной
	 * @return misc
	 **/
	function sessionData($key, $val=null)
	{
		return is_null($val) ? (isset($_SESSION[$key]) ? $_SESSION[$key] : null) : $_SESSION[$key] = $val;
	}
	
	/**
	 * Возвращает массив полей, подготовленый для вставки в шаблон списка пользователей
	 *
	 * @return array
	 **/
	function brief()
	{
		//$avatar = $this->avatar ? $this->avatar : $this->defaultAvatar;
		return array(
			'uid'         => $this->UID,
			'name'        => empty($this->fName) ? $this->login : $this->fName.' '.$this->sName.' '.$this->lName,
			'email'       => $this->showEmail ? $this->email : '',
			'gender'      => $this->gender,
			'icq'         => $this->ICQ,
			'web_site'    => $this->webSite,
			'reg_date'    => date(EL_DATE_FORMAT, $this->crtime),
			'lv_time'     => $this->atime ? date(EL_DATETIME_FORMAT, $this->atime) : '',
			'mod_date'    => date(EL_DATE_FORMAT, $this->mtime),
			'posts'       => $this->postsCount,
			'avatar'      => $this->avatar,
			'online'      => $this->showOnline ? (time()-$this->atime <= 15*60) : false

			);
	}
	
	/**
	 * Возвращает имя пользователя или логин
	 *
	 * @return string
	 **/
	function getName()
	{
		return $this->fName ? $this->fName.' '.$this->lName : $this->login;
	}
	
	function isModerator()
	{
		$db  = & elSingleton::getObj('elDb');
		return $db->queryToArray(
			sprintf('SELECT c.id, c.name FROM %s AS c, %s AS m WHERE m.uid=%d AND c.id=m.cat_id ORDER BY c._left',	$this->_tbc, $this->_tbm, $this->UID)
			, 'id', 'name');
	}
	
	function setAsModerator($cats, $roles)
	{
		$db  = & elSingleton::getObj('elDb');
		$db->query(sprintf('SELECT MAX(rid) AS rid FROM %s WHERE uid=%d', $this->_tbm, $this->UID));
		$r = $db->nextRecord();
		
		parent::_makeForm(); 
		$this->_form->setLabel( sprintf( m('Deligate moderator permissions to user %s'), $this->getName()) );
		$this->_form->add( new elSelect('rid', m('Permissions'), $r['rid'], $roles) );
		$msg = m('Permissions details').' <a href="#" onclick="return popUp(\''.(EL_URL.EL_URL_POPUP.'/roles/').', 500, 700\')" >'.m('see here')."</a>";
		$this->_form->add( new elCData('c1', $msg));
		$this->_form->add( new elCheckBoxesGroup('cat_id', m('Forums'), array_keys($this->isModerator()), $cats) );
		
		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue();
			$db->query( sprintf('DELETE FROM %s WHERE uid=%d', $this->_tbm, $this->UID) );
			$db->optimizeTable( $this->_tbm );
			if ( !empty($data['cat_id']) )
			{
				$db->prepare('INSERT INTO '.$this->_tbm.' (cat_id, uid, rid) VALUES ', '(%d, %d, %d)');
				foreach ( $data['cat_id'] as $catID=>$v )
				{
					$db->prepareData( array($catID, $this->UID, $data['rid']) );
				}
				$db->execute();
			}
			return true;
		}
	}
	
	/**
	 * Увеличивает счетчик постов пользователя
	 *
	 * @return void
	 **/
	function updatePostsCount()
	{
		if ( $this->UID )
		{
			
			$db = & elSingleton::getObj('elDb');
			$db->query( sprintf('UPDATE %s SET forum_posts_count=forum_posts_count+1 WHERE uid=%d LIMIT 1', $this->_tb, $this->UID) );
		}
	}
	
	/**
	 * Обновляет время посещение форума
	 *
	 * @return void
	 **/
	function logForumRead($catID)
	{
		if ( $this->UID )
		{
			$db = & elSingleton::getObj('elDb');
			$db->query( sprintf('REPLACE INTO %s (uid, cat_id, lvt) VALUES (%d, %d, UNIX_TIMESTAMP())', $this->_tblrf, $this->UID, $catID));
		}
	}
	
	/**
	 * Обновляет запись о последнем прочитанном посте в топике
	 *
	 * @return void
	 **/
	function logTopicRead($topicID, $postID)
	{
		if ( $this->UID )
		{
			$db  = & elSingleton::getObj('elDb');
			$r = $db->queryToArray( sprintf('SELECT post_id FROM %s WHERE uid=%d AND t_id=%d LIMIT 1', $this->_tblrt, $this->UID, $topicID), null, 'post_id' );
			if ( empty($r[0]) || $r[0] < $postID )
			{
				$db->query( sprintf('REPLACE INTO %s (uid, t_id, post_id) VALUES (%d, %d, %d)', $this->_tblrt, $this->UID, $topicID, $postID) );
			}
		}
	}
	
	/**
	 * Загружает аватар
	 *
	 * @param  int  $maxFileSize максимальный размер файла в кб
	 * @param  int  $maxDim      максимальное разрешение картинки
	 * @param  int	$miniDim     разрешение создаваемой превьюшки аватары (мини-аватара)
	 * @return array значения - сообщение об ошибке, имя файла, размер файла, разрешение (wxh)
	 **/
	function avatarUpload($maxFileSize, $maxDim, $miniDim)
	{
		$filename = $error = '';
		if ( empty($_FILES['avatar']) || !$_FILES['avatar']['size'] )
		{
			return array(m('File was not uploaded'));
		}
		if ( $_FILES['avatar']['size']/1024 > $maxFileSize )
		{
			return array(sprintf(m('Image file size must be less or equal then %d Kb'), $maxFileSize));
		}
		
		$nfo = getimagesize($_FILES['avatar']['tmp_name']);
		
		if ( sizeof($nfo) < 4 || $nfo[2] < 1 || $nfo[2]>3)
		{
			return array(m('Image must be in jpg, gif or png format'));
		}
		if ( $nfo[0] > $maxDim || $nfo[1] > $maxDim )
		{
			return array( sprintf(m('Image width add height must be les or equal %s pixels'), $maxDim));
		}
		
		$exts     = array(1=>'.gif', '.jpg', '.png');
		$filename = md5($this->UID).$exts[$nfo[2]];
		
		if ( (!is_dir('./storage/avatars') && !mkdir('./storage/avatars'))
		||  !move_uploaded_file($_FILES['avatar']['tmp_name'], './storage/avatars/'.$filename) )
		{
			return array(m('An error occures while saving file! Please, contacts site administrator.'));
		}

		$db = & elSingleton::getObj('elDb');
		$db->query( sprintf('UPDATE %s SET avatar="%s" WHERE uid="%d" LIMIT 1', $this->_tb, $filename, $this->UID));
		$img = & elSingleton::getObj('elImage');
		$img->tmb('./storage/avatars/'.$filename, './storage/avatars/mini-'.$filename, $miniDim, $miniDim);
		
		return array('', $filename, ceil($_FILES['avatar']['size']/1024), $nfo[0].'x'.$nfo[1]);
	}
	
	/**
	 * Удаляет аватар
	 *
	 * @return void
	 **/
	function avatarRm()
	{
		$db = & elSingleton::getObj('elDb');
		$db->query(sprintf('UPDATE %s SET avatar="" WHERE uid="%d" LIMIT 1', $this->_tb, $this->UID));
		@unlink('./storage/avatars/'.$this->avatar);
		@unlink('./storage/avatars/mini-'.$this->avatar);
	}
	
	/**
	 * Смена пароля
	 *
	 * @return bool
	 **/
	function passwd($owner)
	{
		elLoadMessages('Auth');
		parent::_makeForm();
		$this->_form->setLabel( sprintf(m('Change password for user "%s"'), $this->getName()));
		$this->_form->add( new elPasswordDoubledField('passwd', m('Password twice')) );
		$this->_form->setElementRule('passwd', 'password', 1, null);
		if ( !$owner )
		{
			$this->_form->add( new elCheckbox('notify', m('Send new password to user')) );
		}
		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue();
			$this->_passwd( $data['passwd'] );
			if ( $owner || $data['notify'] )
			{
				$this->_notifyUser( $data['passwd'] );
			}
			return true;
		}
	}
	

	/**
	 * Удаляет пользователя и все его данные из других таблиц
	 *
	 * @return void
	 **/
	function delete()
	{
		if ($this->avatar)
		{
			$this->avatarRm();
		}
		$ref = array(
			'el_user_in_group'=>'user_id', 
			'el_user_pref'=>'user_id', 
			$this->_tbm=>'uid', 
			$this->_tblrf=>'uid',
			$this->_tblrt=>'uid'
			);
		parent::delete($ref);
	}
	
	/***************************************************/
	/**                      PRIVATE                  **/
	/***************************************************/
	/**
	 * Создание формы редактирования профайла
	 *
	 * @return void
	 **/
	function _makeForm()
	{
		elLoadMessages('Auth');
		elLoadMessages('UserProfile');
		$this->_form = & elSingleton::getObj('elForm');
		$this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		
		include_once './core/lib/elCoreAdmin.lib.php';
		$skel = $this->_skel();
		
		// редактирование профайла - не меняем логин и показываем все поля
		if ($this->UID)
		{
			$this->_form->setLabel( sprintf(m('Edit user %s profile'), $this->getName()) );
			unset($skel['login']);
		}
		else // новый пользователь - только обязательные поля
		{
			$this->_form->setLabel( m('New user registration') );
			foreach ( $skel as $k=>$v )
			{
				if ( $v['rq'] < 2 )
				{
					unset($skel[$k]);
				}
			}
		}

		foreach ($skel as $k => $v) 
		{
			switch ($v['type']) {
				case 'textarea':
					$this->_form->add( new elTextarea($k, m($v['label']), $this->attr($k)) );
					break;
					
				case 'select':
					$opts = array();
					foreach (explode(',', $v['opts']) as $opt)
					{
						$tmp = explode(':', $opt);
						$opts[$tmp[0]] = m($tmp[1]);
					}
					$this->_form->add( new elSelect($k, m($v['label']), $this->attr($k), $opts) );
					break;
					
				default:
					$this->_form->add( new elText($k, m($v['label']), $this->attr($k)) );
			}
			//$class = $v['type'] == 'textarea' ? 'elTextArea' : 'elText';
			//$this->_form->add( new $class($k, m($v['label']), $this->attr($k)) );
			if ( $v['is_func'] )
	    	{
	    		$this->_form->registerRule($v['rule'], 'func', $v['rule'], null);
	    	}
	    	$this->_form->setElementRule($k, $v['rule'], $v['rq']-1, $this->UID);
		}
		if ( !$this->UID )
		{
			$this->_form->add( new elCaptcha('cap1', m('Enter code from picture')) );
		}
	}
	
	function _passwd($passwd)
	{
		$db = &elSingleton::getObj('elDb');
		$db->query( sprintf('UPDATE %s SET pass="%s" WHERE uid="%d" LIMIT 1', $this->_tb, md5($passwd), $this->UID) );
	}
	
	function _notifyUser($passwd, $register=false)
	{
		$conf    = & elSingleton::getObj('elXmlConf');
		$emails  = & elSingleton::getObj('elEmailsCollection');
	    $postman = & elSingleton::getObj('elPostman');
		if ( $register )
		{
			$subj = m('New user registration notification');
			$msg = m("You are was registered as user on site %s [%s].\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		}
		else
		{
			$subj = m('Changing password notification');
			$msg = m("Your password for site %s [%s] was changed.\n Please, use the following data to log in this site:\n Login: %s \nPassword: %s\n");
		}
		$msg  = sprintf( $msg, $conf->get('siteName', 'common'), EL_BASE_URL, $this->login, $passwd);
		$sign = sprintf( m("With best wishes\n%s\n"), $conf->get('owner', 'common') );
	    
		$postman->newMail($emails->getDefault(), $this->email, $subj, $msg, false, $sign);
		if ( !$postman->deliver() )
	    {
	      	elThrow( E_USER_WARNING, m("Sending e-mail to address %s was failed"), $this->email );
	     	elDebug($postman->error);
	    }
	}
	
	function _skel()
	{
		$db  = &elSingleton::getObj('elDb');
		$sql = 'SELECT p.field, p.label, p.type, p.rule, p.opts, p.is_func, u.rq FROM '.$this->_tbp.' AS p, '.$this->_tbpu.' AS u WHERE u.rq!="0" AND p.field=u.field ORDER BY u.sort_ndx';
		return $db->queryToArray($sql, 'field');
	}
	
	function _attrsForSave()
	{
		$attrs = $this->attr();
		$attrs['mtime'] = time();
		if (!$this->UID)
		{
			$attrs['crtime'] = time();
		}
		return $attrs;
	}
	
	function _postSave($isNew, $params)
	{
		if ( $isNew )
		{
			$conf = &elSingleton::getObj('elXmlConf');
			$db = &elSingleton::getObj('elDb');
			// пароль для нового пользователя
			$pass = substr(md5(uniqid('')),-9,7);
			$this->_passwd( $pass );
			$this->_notifyUser($pass, true);
			// дефолтная группа для новых пользоватлей если есть
			$gid = $conf->get('defaultGID', 'auth');
			if ( false != ($gid = $conf->get('defaultGID', 'auth')) )
			{
				$db->query( sprintf('REPLACE INTO el_user_in_group (user_id, group_id) VALUES (%d, %d)', $this->UID, $gid) );
			}
			
			// уведомить админов о новом пользователе
			if ( $params[0] || $params[1])
			{
				$pageID = $params[2];
				$notify = array();
				// админов 
				if ($params[0])
				{
					$sql = sprintf(
							'SELECT IF(u.f_name!="", CONCAT(u.f_name, " ", u.l_name), u.login) AS user_name, u.email 
							FROM %s AS u, el_user_in_group AS ug, el_group_acl AS a, el_menu AS m, el_menu AS p 
							WHERE p.id=%d AND m._left BETWEEN p._left AND p._right AND a.page_id=m.id AND a.perm>'.EL_READ
							.' AND ug.group_id=a.group_id AND u.uid=ug.user_id',
							$this->_tb, $pageID
						);
					$db->query($sql);
					while ($r = $db->nextRecord())
					{
						$notify[] = sprintf('"'.$r['user_name'].'"<'.$r['email'].'>');
					}
				}
				// модераторов
				if ($params[1])
				{
					$sql = sprintf('SELECT IF(u.f_name!="", CONCAT(u.f_name, " ", u.l_name), u.login) AS user_name, u.email 
						FROM %s AS u, %s AS m 
						WHERE m.rid>=%d AND u.uid=m.uid GROUP BY u.uid',
						$this->_tb, $this->_tbm, $params[1]
						);
					$db->query($sql);
					while ($r = $db->nextRecord())
					{
						$notify[] = sprintf('"'.$r['user_name'].'"<'.$r['email'].'>');
					}
				}
				
				if ( $notify )
				{
					$notify = array_unique($notify);
					$subj   = m('New user registration notification');
					$msg    = sprintf('New user was registered on site %s [%s]. His(her) login is: %s. Profile is here: %s',
									$conf->get('siteName', 'common'), EL_BASE_URL, $this->login, EL_URL.'profile/'.$this->UID.'/');
					$emails  = & elSingleton::getObj('elEmailsCollection');
					$postman = & elSingleton::getObj('elPostman');
					$postman->newMail($emails->getDefault(), $notify, $subj, $msg);
					if ( !$postman->deliver() )
				    {
				     	elDebug($postman->error);
				    }
				}
				
			}
			
		}
		return true;
	}


	function _initMapping()
	{
		$map = array(
			'uid'         => 'UID',
			'login'       => 'login',
			'f_name'      => 'fName',
			's_name'      => 'sName',
			'l_name'      => 'lName',
			'email'       => 'email',
			'phone'       => 'phone',
			'fax'         => 'fax',
			'company'     => 'company',
			'postal_code' => 'postalCode',
			'address'     => 'address',
			'icq_uin'     => 'ICQ',
			'web_site'    => 'webSite',
			'crtime'      => 'crtime',
			'mtime'       => 'mtime',
			'atime'       => 'atime',
			'forum_posts_count' =>'postsCount',
			'avatar'      => 'avatar',
			'show_email'  => 'showEmail',
			'show_online' => 'showOnline',
			'gender'      => 'gender',
			'signature'   => 'signature'
			);
		return $map;
	}
	

}

?>