<?php

class elModuleAdminForum extends elModuleForum
{
	var $_mMapAdmin = array(
		'edit'       => array('m' => 'editCat', 'g'=>'Actions', 'l'=>'New forum', 'ico'=>'icoCatNew'),
		'move_up'    => array('m' => 'move'),
		'move_down'  => array('m' => 'moveDown'),
		'rm'         => array('m' => 'forumRm'),
		'edit_rootf' => array('m' => 'editRootCat',      'g'=>'Actions', 'ico'=>'icoCatNew', 'l'=>'Edit root forum'),
		'perm'       => array('m' => 'forumPermissions', 'g'=>'Actions', 'ico'=>'icoLock',   'l'=>'Root forum permissions'),
		'moderator'  => array('m' => 'moderator'),
		'profile_rm' => array('m' => 'profileRm'),
		'roles'      => array('m' => 'rolesList')
		
		);
	var $_mMapConf  = array(
		'conf'   => array('m'=>'configure', 'ico'=>'icoConf', 'l'=>'Configuration'),
		'import' => array('m' => 'forumImport', 'l'=>'Import from SMF', 'ico'=>'icoConf')
		);
		
	var $_importForums = array('SMF');
	
	/**
	 * Создание/редактирование категории (форума)
	 *
	 * @return void
	 **/
	function editCat($root = false)
	{
		$cat = & $this->_cat(!$root ? $this->_arg(1) : 1, false);
		if ($cat->editAndSave())
		{
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL.$this->_catID);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $cat->formToHtml() );
	}
	
	/**
	 * редактирование корневой категории (форума)
	 *
	 * @return void
	 **/
	function editRootCat()
	{
		return $this->editCat(true);
	}
	
	/**
	 * Перемещение форума в списке вверх/вниз на одну позицию (сортировка)
	 *
	 * @param  bool  $up - переместить форум вверх
	 * @return void
	 **/
	function move( $up=true )
	{
		$cat = & $this->_cat($this->_arg(1));
		if (!$cat->move($up))
		{
			$msg = $up ? 'Can not move object "%s" "%s" up' : 'Can not move object "%s" "%s" down';
			elThrow(E_USER_WARNING, $msg, array($cat->objName(), $cat->name), EL_URL.$this->_catID);
		}
		elLocation(EL_URL.$this->_catID);
	}
	
	/**
	 * Перемещение форума в списке вниз на одну позицию (сортировка)
	 *
	 * @return void
	 **/
	function moveDown()
	{
		return $this->move(false);
	}
	
	/**
	 * Удаление форума, если оне не содержит подфорумаов и топиков и не является форумом верхнего уровня
	 *
	 * @return void
	 **/
	function forumRm()
	{
		$cat = & $this->_cat($this->_arg(1));
		if ( 1 == $cat->ID)
		{
			elThrow(E_USER_WARNING, 'Top level forum could not be deleted', null, EL_URL.$this->_catID);
		}
		if ( $cat->hasChilds() )
		{
			elThrow(E_USER_WARNING, '%s "%s" contains nested %s, and could not be deleted', array($cat->objName(),$cat->name, m('Forums')), EL_URL.$this->_catID);
		}
		if ($cat->numTopics)
		{
			elThrow(E_USER_WARNING, '%s "%s" contains nested %s, and could not be deleted', array($cat->objName(),$cat->name, m('Topics')), EL_URL.$this->_catID);
		}
		$cat->delete();
		elMsgBox::put( sprintf( m('%s "%s" was deleted'), $cat->objName(), $cat->name ) );
		elLocation(EL_URL.$this->_catID);
	}
	
	/**
	 * Устанавливает права доступа к форуму для групп пользователей
	 *
	 * @return void
	 **/
	function forumPermissions()
	{
		$catID = !empty($this->_args[1]) && $this->_args[1] > 1 ? (int)$this->_args[1] : 1;
		$cat   = & $this->_cat($catID);
		$gids  = $this->_db->queryToArray('SELECT gid FROM '.$this->_tbrb.' GROUP BY gid', null, 'gid'); 
		$perms = $this->_rbacList($gids);
		$pid   = $perms[$catID]['pid'];

		if ($cat->changePermissions($perms[$catID]['roles'], $pid ? $perms[$pid]['roles'] : null ))
		{
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL.$this->_catID);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $cat->formToHtml() );
	}
	
	function moderator()
	{
		$profile = & $this->_profile((int)$this->_arg());
		$roles   = $this->_db->queryToArray('SELECT id, name FROM '.$this->_tbrl.' WHERE id>=8 ORDER BY id', 'id', 'name');

		if ( !$roles )
		{
			elThrow(E_USER_WARNING, 'Action not allowed! Reason: %s.', m('Could not fetch moderators roles'), EL_URL.$this->_catID);
		}

		$forumsPerms = $this->_rbacList(array_merge($profile->getGroups(), array(0)));
		$cats        = array();
		
		foreach ($forumsPerms as $id=>$v)
		{
			$cats[$id] = '|'.str_repeat('&ndash;', $v['level']).' '.$v['name'];
		}
		
		if ( $profile->setAsModerator($cats, array_map('m', array_map('m', $roles))) )
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.'profile/'.$profile->UID);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $profile->formToHtml() );
		$this->_path[] = array('url'=>'users', 'name'=>m('Users'));
		$this->_path[] = array('url'=>'profile/'.$profile->UID, 'name'=>m('User profile').' '.$profile->getName());
	}
	
	/**
	 * Удаление пользователя
	 *
	 * @return void
	 **/
	function profileRm()
	{
		$profile = & $this->_profile((int)$this->_arg());
		
		// модератор не должен удалять пользователей из группы root
		$groups  = $this->_db->queryToArray('SELECT group_id FROM el_user_in_group WHERE user_id='.$profile->UID, null, 'group_id');
		if (1== $profile->UID || in_array(1, $groups))
		{
			elThrow(E_USER_WARNING, 'Users from group root can be deleted only in Control center > Users control', null, EL_URL.'profile/'.$profile->UID);
		}
		$profile->delete();
		elMsgBox::put( sprintf(m('User "%s" was deleted'), $profile->getName()) );
		elLocation(EL_URL.'users/');
	}
	
	function rolesList()
	{
		$this->_initRenderer();
		if ( file_exists('./core/locale/'.EL_LOCALE.'/elForumRoles.html') )
		{
			$str = file_get_contents('./core/locale/'.EL_LOCALE.'/elForumRoles.html');
		}
		elseif ( EL_LANG != 'en' && file_exists('./core/locale/en_US.UTF-8/elForumRoles.html') )
		{
			$str = file_get_contents('./core/locale/en_US.UTF-8/elForumRoles.html');
		}
		else
		{
			$str = m('Sorry, but permissions description was not found');
		}
		$this->_rnd->addToContent($str, true);
	}
	
	function forumImport()
	{
		if ( false == ($test = exec('which mysql')) || '/' <> $test[0] )
		{
		  elThrow(E_USER_ERROR, 'Utility "%s" was not found!', 'mysql', EL_URL);
		}
		$form = & parent::_makeConfForm();
		$form->setLabel( m('Import data from another forum') );
		$form->add( new elSelect('forum_type', m('Forum type'), null, $this->_importForums, null, false, false));
		$form->add( new elCData('c1', m('File must be valid database dump file in UTF-8 charset, created mysqldump utility or phpMyAdmin')));
		$fi = & new elFileInput('dump', m('Forum database dump file'));
		$fi->setFileExt('sql');
		$form->add( $fi );
		$form->add( new elText('prefix', m('Tables prefix (if exists)')));
		$form->add( new elText('url', m('Forum URL')) );
		$form->add( new elSelect('attachments', m('Download attachments'), 0, $GLOBALS['yn']));
		$form->setRequired('dump');
		
		if ( $form->isSubmitAndValid() )
		{
			$data = $form->getValue();
			//elPrintR($data);
			$import = & elSingleton::getObj('elForumImport');
			$m = 'import'.$data['forum_type'];
			if ( !method_exists($import, $m) )
			{
				elThrow(E_USER_ERROR, 'Unsupported forum type', null, EL_URL);
			}
			
			if ( !$import->$m($data['dump']['tmp_name'], $data['prefix'], $data['url'], $data['attachments'], $this->_conf('avatarMaxDimension'), $this->_conf('avatarMiniDimension'), $this->_conf('attachImgDimensions')) )
			{
				elThrow(E_USER_ERROR, 'Import failed! %s', $import->error, EL_URL);
			}
			elMsgBox::put( m('Data successfully imported!') );
			elLocation(EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent($form->toHtml());
	}
	/****************************************************/
	/***                    PRIVATE                   ***/
	/****************************************************/	
	
	function &_makeConfForm()
	{
		$form = & parent::_makeConfForm();
		
		$nums = array(5, 10, 15, 20, 25, 30, 35, 40, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);
		$form->add( new elCData('c1', '<strong>'.m('Forum layout').'</strong>'), array('cellAttrs'=>'class="form-tb-sub"'));
		$form->add( new elSelect('topicsNum', m('Topics number per page'), (int)$this->_conf('topicsNum'), $nums, null, false, false) );
		$form->add( new elSelect('postsNum',  m('Posts number per page'), (int)$this->_conf('postsNum'), $nums, null, false, false) );
		$form->add( new elSelect('hotTopicPostsNum',  m('Posts number in hot topic'), (int)$this->_conf('hotTopicPostsNum'), $nums, null, false, false) );
		$form->add( new elSelect('maxAttachments', m('Maximum files attached to post'), (int)$this->_conf('maxAttachments'), range(1, 25), null, false, false) );
		$fsizes = array(8, 16, 32, 42, 64, 128, 129, 256, 512, 1024, 2048, 3072);
		$form->add( new elSelect('maxAttachSize', m('Attachment file maximum size (Kb)'), (int)$this->_conf('maxAttachSize'), $fsizes, null, false, false) );
		$form->add( new elSelect('usersNum',  m('Users number per page in users list'), (int)$this->_conf('postsNum'), $nums, null, false, false) );		
		$form->add( new elSelect('autolinks', m('Convert URLs into links'), (int)$this->_conf('autolinks'), $GLOBALS['yn']) );
		$width = array(100, 200, 300, 400, 500, 600, 700, 800, 900, 1000, 1100, 1200, 1300, 1400);
		$form->add( new elSelect('maxPostImgSize', m('Maximum width for images in posts (px)'), (int)$this->_conf('maxPostImgSize'), $width, null, false, false ));
		$form->add( new elSelect('smiley', m('Use smilies in messages'), (int)$this->_conf('smiley'), $GLOBALS['yn']) );
		
		$bb = array(
			'text'      => m('Text style (bold, italic, underline, etc.)'), 
			'alignment' => m('Text alignment'), 
			'elements'  => m('Misc elements (quotes, lists, etc)'), 
			'table'     => m('Tables') 
			);
		$form->add( new elCheckBoxesGroup('bbcodes', m('Use bb codes'), array_keys($this->_conf('bbcodes')), $bb));
		
		$form->add( new elCData('c2', '<strong>'.m('New users registration').'</strong>'), array('cellAttrs'=>'class="form-tb-sub"'));
		$form->add( new elSelect('notifyModerators', m('Notify moderators about new user registration'), (int)$this->_conf('notifyModerators'), array(m('No'), 7=>m('Yes'))) );
		$form->add( new elSelect('notifyAdmins', m('Notify administrators about new user registration'), (int)$this->_conf('notifyModerators'), array(m('No'), m('Yes'))) );
		
		$form->add( new elCData('c3', '<strong>'.m('Avatars').'</strong>'), array('cellAttrs'=>'class="form-tb-sub"'));
		$form->add( new elSelect('defaultAvatar', m('Use default avatar'), $this->_conf('defaultAvatar'), array('0' => m('No'), 'avatar.png'=>m('Yes') )) );
		$dim = array_slice($nums, 4);
		$form->add( new elSelect('avatarMaxFileSize', m('Avatar maximum file size (Kb)'), (int)$this->_conf('avatarMaxFileSize'), range(10, 50), null, false, false));
		$form->add( new elSelect('avatarMaxDimension', m('Avatar maximum dimension (px)'), (int)$this->_conf('avatarMaxDimension'), $dim, null, false, false));
		$form->add( new elSelect('avatarMiniDimension', m('Mini avatar dimension (px)'), (int)$this->_conf('avatarMiniDimension'), $dim, null, false, false));

		$form->add( new elCData('c4', '<strong>'.m('Security').'</strong>'), array('cellAttrs'=>'class="form-tb-sub"'));
		$form->add( new elSelect('postTimeout', m('Post sending timeout (sec)'), (int)$this->_conf('postTimeout'), array(5, 10, 15, 20, 25, 30), null, false, false));
		
		return $form;
	}
}

?>