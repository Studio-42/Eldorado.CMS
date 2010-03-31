<?php

class elSubModuleUsers extends elModule
{
	var $_mMap      = array('view' => array('m'=>'viewProfile'));
	var $_mMapAdmin = array(
		'edit'    => array('m'=>'editUser', 'ico'=>'icoUserNew', 'l'=>'Create user', 'g'=>'Actions'),
		'ugroups' => array('m'=>'groups'),
		'passwd'  => array('m'=>'passwd'),
		'delete'  => array('m'=>'rmUser'),
		'get_profile' => array('m'=>'getProfile'),
		'field_rm'=> array('m'=>'profileRemove')
	);
	var $_mMapConf  = array(
		'conf' => array(
			'm'   => 'configure',
			'ico' => 'icoConf',
			'l'   => 'Configuration of user profile',
			'g'   => 'Configuration'
		),
		'profile' => array(
			'm'   => 'profile',
			'ico' => 'icoMenu',
			'l'   => 'Add profile field',
			'g'   => 'Configuration'
		)
	);
	var $_filter   = array(
		'pattern' => '',
		'group'   => '',
		'sort'    => 'login',
		'order'   => 'ASC',
		'offset'  => 30
	);
	var $_ats = null;


	// *************************  PUBLIC METHODS ***************************** //
	/**
   * показывает список пользователй
   */
	function defaultMethod()
	{
		elLoadJQueryUI();
		$this->_initRenderer();
		$ats = &elSingleton::getObj('elATS'); //echo $this->_arg();
		$page = 0 < $this->_arg() ? (int)$this->_arg() : 1;
		$start = ($page-1)*$this->_filter['offset'] ;
		$nums = $ats->getUsersList($this->_filter['pattern'], $this->_filter['group'], $this->_filter['sort'],
		  $this->_filter['order'], $start, $this->_filter['offset'], true, false);
		if ( $start > 0 && $start > $nums )
		{
			elLocation(EL_URL);
		}
		$users = $ats->getUsersList($this->_filter['pattern'], $this->_filter['group'], $this->_filter['sort'],
		  $this->_filter['order'], $start, $this->_filter['offset'], false, false);
		$this->_rnd->rndUsers( $users, $ats->getUsersGroupsList(), $page, ceil($nums/$this->_filter['offset']) );
	}

	function getProfile() {
		
		$UID = (int)$this->_arg(0);
		$user = & new elUser();
		$user->setUniqAttr( $UID );
		if ( !$user->fetch() )
		{
			elLoadMessages('Errors');
			$msg = sprintf(m('Object "%s" with ID="%d" does not exists'), m('User'), $UID);
			echo '<p class="warn">'.$msg.'</p>';
			exit();
		}
		
		
		elLoadMessages('UserProfile');
		$this->_initRenderer();
		$p = $this->_rnd->rndProfile( $user->toArray() );
		echo $p;
		exit();
	}



	//редактирование/создание пользователя
	function editUser()
	{
		$UID = (int)$this->_arg(0);
		$user = & new elUser();
		$user->setUniqAttr( $UID );
		$user->fetch();

		$ats = & elSingleton::getObj('elATS');
		if ( !$ats->editUser($user) )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $ats->formToHtml());
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation( EL_URL);
		}
	}

	//смена пароля
	function passwd()
	{
		$UID = (int)$this->_arg(0);
		$user = & new elUser();
		$user->setUniqAttr( $UID );
		if ( !$user->fetch() )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists', array(m('User'), $UID), EL_URL );
		}

		$ats = & elSingleton::getObj('elATS');
		$curUser = $ats->getUser();
		if ( 1== $user->UID && 1 != $curUser->UID )
		{
			elThrow(E_USER_WARNING, 'Only root can modify his password', array(m('User'), $UID), EL_URL );
		}

		if ( !$ats->passwd($user) )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $ats->formToHtml());
		}
		else
		{
			elMsgBox::put( sprintf(m('Password for user "%s" was changed'), $user->login ) );
			elLocation( EL_URL);
		}
	}

	//изменение списка групп
	function groups()
	{
		$UID = (int)$this->_arg(0);
		$user = & new elUser();
		$user->setUniqAttr( $UID );
		if ( !$user->fetch() )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists', array(m('User'), $UID), EL_URL );
		}
		if ( 1 == $UID )
		{
			elThrow(E_USER_WARNING, 'Groups for user "root" can not be changed', null, EL_URL );
		}

		$ats = & elSingleton::getObj('elATS');
		if ( !$ats->userGroups($user) )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $ats->formToHtml());
		}
		else
		{
			elMsgBox::put( sprintf(m('Groups list for user "%s" was changed'), $user->login ) );
			elLocation( EL_URL);
		}
	}

	//Удаление пользователя
	function rmUser()
	{
		$UID = (int)$this->_arg(0);
		$user = & new elUser();
		$user->setUniqAttr( $UID );
		if ( !$user->fetch() )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists', array(m('User'), $UID), EL_URL );
		}
		if ( 1 == $UID )
		{
			elThrow(E_USER_WARNING, 'User "root" can not be deleted', null, EL_URL );
		}

		$ats = & elSingleton::getObj('elATS');
		$ats->rmUser( $user );
		elMsgBox::put( sprintf(m('User "%s" was deleted'), $user->login) );
		elLocation( EL_URL );
	}

	// Добавление/Изменение поля профиля
	function profile()
	{
		$pc = & elSingleton::getObj('elProfileConf');
		$pc->clean();
		$frozen = false;

		$f = $this->_arg(0);
		if (!empty($f))
		{
			if (!$pc->checkFieldAllowed($f))
				elThrow(E_USER_WARNING, 'Not allowed value for "%s"', 'Field in DB', EL_URL.'conf');

			$frozen = true;
			$pc->idAttr($f);
			if (!($pc->checkFieldName($f) and $pc->fetch()))
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists', array(m('Profile field'), $f), EL_URL.'conf');
		}

		$pc->_new = !$frozen;
		if (!$pc->editAndSave(array('frozen' => $frozen)))
		{
			$this->_initRenderer();
			$this->_rnd->addToContent($pc->formToHtml());
		}
		else
		{
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL.'conf');
		}
	}

	// Удаление поля из профайла
	function profileRemove()
	{
		$pc = & elSingleton::getObj('elProfileConf');
		$f = $this->_arg(0);
		if ($pc->checkFieldAllowed($f) and $pc->checkFieldExists($f))
		{
			if (($_POST) and (!$_POST['delete']))
				elMsgBox::put(m('Select delete method'), EL_WARNQ);

			if (!$_POST['delete'])
			{
				$this->_initRenderer();
				$this->_rnd->addToContent($this->_confirmRemoveForm($f));
			}
			else
			{
				$delete_data = false;
				if ($_POST['delete'] == 'field_data')
					$delete_data = true;

				if ($pc->deleteDataField($f, $delete_data))
					elMsgBox::put(sprintf(m('Field "%s" deleted'), $f));
				else
					elMsgBox::put(sprintf(m('Cannot delete field "%s"'), $f), EL_WARNQ);

				elLocation(EL_URL.'conf');
			}
		}
		else
			elThrow(E_USER_WARNING, 'Cannot delete field "%s"', $f, EL_URL.'conf');
	}

	// ====================== PRIVATE METHODS ======================  //

	function _onInit()
	{
		$this->_ats = & elSingleton::getObj('elATS');
		if ( !isset($_POST['filter']) )
		{
			$this->_loadFilter();
		}
		else
		{
			if ( $_POST['drop'] )
			{
				$this->_dropFilter();
			}
			else
			{
				$this->_applyFilter( $_POST );
			}
			$this->_saveFilter();
			elLocation(EL_URL);
		}
	}


	function _onBeforeStop()
	{
		$this->_rnd->addOnPane( $this->_getFilterHtml() );
	}

	function _confirmRemoveForm($f)
	{
		$form = & elSingleton::getObj('elForm', 'confirm');
		$form->setRenderer(elSingleton::getObj('elTplGridFormRenderer', 1));
		$form->setLabel(sprintf(m('Delete field "%s"'), $f));
		$form->add(new elRadioButtons('delete', null, null,
			array('field'      => m('Delete only field'),
			      'field_data' => m('Delete field and data'))));
		$form->add(new elHidden('field', null, $f));
		$form->renderer->addButton(new elSubmit('s', null, m('Delete')));
		return $form->toHtml();
	}

	/**
   * помещяет форму фильтра на панель под заголовком страницы
   */
	function _getFilterHtml()
	{
		$form = & elSingleton::getObj('elForm');
		$form->setRenderer( elSingleton::getObj('elGridFormRenderer', 5) );
		$form->renderer->setTpl('header', "<table>\n");

		$form->add( new elHidden('filter',  null, 1) );
		$form->add( new elHidden('drop',    null, 0) );
		$form->add( new elText  ('pattern', m('Filter').':', $this->_filter['pattern'], array('size'=>16)), array('label'=>1) );

		$ats = & elSingleton::getObj('elATS');

		$g         = array(''=>m('Only group').':', '-1'=>m('w/o group'));
		$g         = $g + $ats->getGroupsList();
		$sort      = array(''=>m('Sort by').':', 'uid'=>'ID','login'=>m('Login'), 'email'=>m('Email'));
		$offset    = range(9, 100);
		$offset[0] = m('Nums');

		$form->add( new elSelect('group',  null, $this->_filter['group'],  $g) );
		$form->add( new elSelect('sort',   null, $this->_filter['sort'],   $sort) );
		$form->add( new elSelect('offset', null, $this->_filter['offset'], $offset, null, false, false ) );

		$buttons = '<ul class="adm-icons"><li><a href="#" class="icons find" title="'.m('Apply filter').'" onclick="$(this).parents(\'form\').submit(); return false;"></a></li>';
		$buttons .= '<li><a href="#" class="icons clean" title="'.m('Clean filter').'" onclick="$(this).parents(\'form\').find(\'#drop\').val(1).end().submit()"></a></li></ul>';
		
		$form->add( new elCData('i', $buttons));
		return $form->toHtml();
	}

	/**
   * загружает значения полей фильтра из предпочтений пользователя
   */
	function _loadFilter()
	{
		$user = &elSingleton::getObj('elUser');
		$filter = $user->getPref('usersFilter');
		if ( !empty($filter) && is_array($filter) )
		{
			foreach ( $filter as $k=>$v )
			{
				if ( isset($this->_filter[$k]) )
				{
					$this->_filter[$k] = $v;
				}
			}
		}
	}


	/**
   * устанавливает значения фильтра из POST
   */
	function _applyFilter( $vals )
	{
		$this->_filter['pattern'] = trim($vals['pattern']);
		$this->_filter['group'] = $vals['group'];
		$this->_filter['sort'] = $vals['sort'];
		$this->_filter['offset'] = $vals['offset']>0 ? (int)$vals['offset'] : 30;
	}


	/**
   * Сохраняет поля фильтра в предпочтениях пользователя
   */
	function _saveFilter()
	{
		$user = & elSingleton::getObj('elUser');
		$user->setPref('usersFilter', $this->_filter);
	}

	/**
   * Удаляет значения фильтра из предпочтений пользователя
   */
	function _dropFilter()
	{
		$user = & elSingleton::getObj('elUser');
		$user->dropPref('usersFilter');
	}


	function &_makeConfForm()
	{
		elLoadMessages('UserProfile');
		$form = & elSingleton::getObj( 'elForm', 'moduleConf' );
		$form->setRenderer( elSingleton::getObj('elTplGridFormRenderer', 4) );
		$form->setLabel(m('Configuration of user profile'));
		$form->add( new elCData('l1', m('Profile field')), array('class'=>'form-tb-sub'));
		$form->add( new elCData('l2', m('Usage')),         array('class'=>'form-tb-sub'));
		$form->add( new elCData('l3', m('Sort index')),    array('class'=>'form-tb-sub'));
		$form->add( new elCData('l4', m('Actions')),       array('class'=>'form-tb-sub'));

		$ats = &elSingleton::getObj('elATS');
		$skelConf = $ats->user->profile->getSkelConf();
		$usage = array(m('No'), m('Yes'), m('Required') );
		$sort = range(1, count($skelConf));

		foreach ($skelConf as $k=>$v)
		{
			$frozen = 'login' == $k || 'email' == $k;
			$form->add( new elCData('l_'.$k, m($v['label'])) );
			$form->add( new elSelect($k."[rq]", m($v['label']), $v['rq'], $usage, null, $frozen) );
			$form->add( new elSelect($k."[sort_ndx]", m($v['label']), $v['sort_ndx'], $sort) );
			if (!$frozen)
				$actions = '<ul class="adm-icons">'
					. '<li><a href="'.EL_URL.'profile/'.$k.'" class="icons edit" title="'.m('Edit').'"></a></li>'
					. '<li><a href="'.EL_URL.'field_rm/'.$k.'" class="icons delete" title="'.m('Delete').'" onclick="return confirm(\''.m('Do You really want to delete').'?\');"></a></li>'
					. '</ul>';
			else
				$actions = '';

			$form->add( new elCData('a_'.$k, $actions) );

		}
		$form->renderer->addButton( new elSubmit('s', null, m('Submit')) );
		$form->renderer->addButton( new elReset('r', null, m('Drop')) );
		return $form;
	}

	function _updateConf( $newConf )
	{
		$ats = &elSingleton::getObj('elATS');
		$ats->user->profile->setSkelConf( $newConf );
	}


}

?>
