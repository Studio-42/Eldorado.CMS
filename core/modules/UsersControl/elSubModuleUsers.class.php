<?php

class elSubModuleUsers extends elModule
{
	var $_mMap      = array('view' => array('m'=>'viewProfile'));
	var $_mMapAdmin = array(
		'edit'    => array('m'=>'editUser', 'ico'=>'icoUserNew', 'l'=>'Create user', 'g' => 'Actions'),
		'ugroups' => array('m'=>'groups'),
		'passwd'  => array('m'=>'passwd'),
		'delete'  => array('m'=>'rmUser'),
		'field_rm'=> array('m'=>'profileRemove')
	);
	var $_mMapConf  = array(
		'conf' => array(
			'm'   => 'configure',
			'ico' => 'icoConf',
			'l'   => 'Configuration of user profile',
			'g'   => 'Configuration'
		)
	);
	var $_filter   = array(
		'pattern' => '',
		'group'   => '',
		'sort'    => 'login',
		'offset'  => 30
	);
	var $_ats  = null;
	var $_user = null;


	/**
	 * display users list
	 *
	 * @return void
	 **/
	function defaultMethod() {
		$this->_ats->onPageDelete(99);
		$page  = 0 < $this->_arg() ? (int)$this->_arg() : 1;
		$start = ($page-1)*$this->_filter['offset'];
		$num   = $this->_user->count($this->_filter['pattern'], $this->_filter['group']);
		if ($start > $num) {
			elLocation(EL_URL);
		}

		$users = $this->_user->collection($this->_filter['pattern'], $this->_filter['group'], $this->_filter['sort'], $start, $this->_filter['offset']);
		$group = $this->_ats->createGroup();
		$groups = $group->usersGroups(array_keys($users));

		$this->_initRenderer();
		$this->_rnd->rndUsers($users, $groups, $page, ceil($num/$this->_filter['offset']));
	}

	/**
	 * create/edit user
	 *
	 * @return void
	 **/
	function editUser() {
		$user = & $this->_ats->createUser();
		$user->idAttr((int)$this->_arg(0));
		$user->fetch();

		if (!$user->editAndSave()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($user->formToHtml());
		} else {
			elMsgBox::put(m('Data saved'));
			elLocation( EL_URL);
		}
	}

	/**
	 * change user password
	 *
	 * @return void
	 **/
	function passwd() {

		$UID  = (int)$this->_arg(0);
		$user = & $this->_ats->createUser();
		$user->idAttr($UID);
		if (!$user->fetch()) {
			elThrow(E_USER_WARNING, 'There is no such user', null, EL_URL);
		}

		if ($user->UID == 1 && $this->_user->UID != 1) {
			elThrow(E_USER_WARNING, 'Only root can modify his password', null, EL_URL);
		}

		if (!$user->changePasswd()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($user->formToHtml());
		} else {
			elMsgBox::put( sprintf(m('Password for user "%s" was changed'), $user->login ) );
			elLocation( EL_URL);
		}

	}

	/**
	 * change user groups
	 *
	 * @return void
	 **/
	function groups() {
		if (!empty($_POST['action'])) {
			$UID = (int)$this->_arg(0); 
			$user = & $this->_ats->createUser();
			$user->idAttr($UID);
			if (!$user->fetch()) {
				elThrow(E_USER_WARNING, 'There is no such user', null, EL_URL);
			}
			if ($user->UID == 1 && $this->_user->UID != 1) {
				elThrow(E_USER_WARNING, 'Only root can change his groups', null, EL_URL);
			}
			
			$gids   = isset($_POST['gids']) && is_array($_POST['gids']) ? $_POST['gids'] : array();
			$group  = $this->_ats->createGroup();
			$groups = $group->collection(false);
			foreach ($gids as $i => $gid) {
				if (!isset($groups[$gid])) {
					unset($gids[$i]);
				}
			}
			
			$user->updateGroups($gids);
			elMsgBox::put( sprintf(m('Groups list for user "%s" was changed'), $user->login ) );
		}
		elLocation( EL_URL);
	}

	/**
	 * remove user
	 *
	 * @return void
	 **/
	function rmUser() {
		
		if (!empty($_POST['action'])) {
			$UID = (int)$this->_arg(0); 
			$user = & $this->_ats->createUser();
			$user->idAttr($UID);
			if (!$user->fetch()) {
				elThrow(E_USER_WARNING, 'There is no such user', null, EL_URL);
			}
			if ($user->UID == 1) {
				elThrow(E_USER_WARNING, 'User "root" can not be deleted', null, EL_URL );
			}
			$user->delete();
			elMsgBox::put( sprintf(m('User "%s" was deleted'), $user->login) );
		}
		elLocation( EL_URL);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function configure() {
		$user    = $this->_ats->createUser();
		$profile = $user->getProfile();
		$url     = EL_URL.'conf/';
		$id      = trim($this->_arg(1));
		if ($id == 'login' || $id == 'email') {
			elThrow(E_USER_WARNING, 'Fields login and email cannot be modified or removed', null, $url);
		}
		$this->_initRenderer();
		switch($this->_arg()) {
			case 'field_edit':
				if ($profile->edit($id)) {
					elMsgBox::put(m('Data saved'));
					elLocation($url);
				} else {
					$this->_rnd->addToContent($profile->formToHtml());
				}
				break;
			case 'field_rm':
				$id = trim($this->_arg(1));
				if ($id) {
					if (!$profile->fieldExists($id)) {
						elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists', array(m('Profile field'), $id), $url);
					}
					$profile->delete($id);
					elMsgBox::put(sprintf(m('Object "%s" "%s" was deleted'), m('Profile field'), $id));
				} else {
					$profile->clean();
					elMsg::put('Fields removed');
				}
				elLocation($url);
				break;
			case 'field_sort':
				if ($profile->sort()) {
					elMsgBox::put(m('Data saved'));
					elLocation($url);
				} else {
					$this->_rnd->addToContent($profile->formToHtml());
				}
				break;
			default:
				$html = $profile->getAdminFormHtml($url, m('Configuration of user profile'));
				$this->_rnd->addToContent($html);
		}
		
	}


	// ====================== PRIVATE METHODS ======================  //

	/**
	 * some init stuff
	 *
	 * @return void
	 **/
	function _onInit() {
		
		if ($this->_aMode < EL_WRITE) {
			elThrow(E_USER_WARNING, 'Access denied', null, EL_BASE_URL);
		}
		
		$this->_ats  = & elSingleton::getObj('elATS');
		$this->_user = & $this->_ats->getUser();

		if (empty($_POST['filter'])) {
			$this->_loadFilter();
		} else {
			empty($_POST['drop']) ? $this->_applyFilter($_POST) : $this->_resetFilter() ;
			$this->_user->prefrence('usersFilter', $this->_filter);
			elLocation(EL_URL);
		}
	}

	/**
	 * load user filter from user prefrence
	 *
	 * @return void
	 **/
	function _loadFilter() {
		$filter = $this->_user->prefrence('usersFilter');
		if ( !empty($filter) && is_array($filter) ) {
			foreach ($filter as $k=>$v) {
				if ( isset($this->_filter[$k]) ) {
					$this->_filter[$k] = $v;
				}
			}
		}
	}

	/**
	 * set filter values from  _POST
	 *
	 * @param  array
	 * @return void
	 **/
	function _applyFilter($vals) {
		
		$this->_filter['pattern'] = isset($vals['pattern']) ? trim($vals['pattern']) : '';
		$this->_filter['group']   = isset($vals['group']) ? $vals['group'] : '';
		$this->_filter['sort']    = isset($vals['sort']) ? $vals['sort'] : 'login';
		$this->_filter['offset']  = !empty($vals['offset']) && $vals['offset']>0 ? (int)$vals['offset'] : 30;
	}

	/**
	 * reset filter to default values
	 *
	 * @return void
	 **/
	function _resetFilter() {
		$this->_filter = array(
			'pattern' => '',
			'group'   => '',
			'sort'    => 'login',
			'offset'  => 30
			);
	}

	/**
	 * put filter form on top panel
	 *
	 * @return void
	 **/
	function _onBeforeStop() {
		$form = & elSingleton::getObj('elForm');
		$form->setRenderer( elSingleton::getObj('elGridFormRenderer', 5) );
		$form->renderer->setTpl('header', "<table>\n");

		$form->add( new elHidden('filter',  null, 1) );
		$form->add( new elHidden('drop',    null, 0) );
		$form->add( new elText  ('pattern', m('Filter').':', $this->_filter['pattern'], array('size'=>16)), array('label'=>1) );

		$group  = $this->_ats->createGroup();
		$groups = $group->collection(false);
		$g      = array(''=>m('Only group').':', '-1'=>m('w/o group'));
		foreach ($groups as $_g) {
			$g[$_g['gid']] = $_g['name'];
		}

		$sort      = array(''=>m('Sort by').':', 'uid'=>'ID','login'=>m('Login'), 'email'=>m('Email'));
		$offset    = range(9, 100);
		$offset[0] = m('Nums');

		$form->add( new elSelect('group',  null, $this->_filter['group'],  $g) );
		$form->add( new elSelect('sort',   null, $this->_filter['sort'],   $sort) );
		$form->add( new elSelect('offset', null, $this->_filter['offset'], $offset, null, false, false ) );

		$buttons = '<ul class="adm-icons"><li><a href="#" class="icons find" title="'.m('Apply filter').'" onclick="$(this).parents(\'form\').submit(); return false;"></a></li>';
		$buttons .= '<li><a href="#" class="icons clean" title="'.m('Clean filter').'" onclick="$(this).parents(\'form\').find(\'#drop\').val(1).end().submit()"></a></li></ul>';
		
		$form->add( new elCData('i', $buttons));
		$this->_rnd->addOnPane( $form->toHtml() );
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
