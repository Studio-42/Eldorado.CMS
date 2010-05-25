<?php

/**
 * display/edit profile, change password, registration
 * return profile data via ajax
 *
 * @package services
 * @author dio
 **/
class elServiceProfile extends elService
{
	var $_vdir      = '__profile__';
	var $_ats       = null;
	var $_tplFile   = 'defaultTable.html';
	var $_pageTitle = 'User profile';
	var $_mMap      = array(
			'edit'   => array('m' => 'edit'),
			'passwd' => array('m' => 'passwd'),
			'reg'    => array('m' => 'registration'),
			'remind' => array('m' => 'remind'),
			'get'    => array('m' => 'get'),
			'groups' => array('m' => 'groups')
			);


	function init($args) {
		$this->_args = $args;
		$this->_ats = & elSingleton::getObj('elATS');
	}

	/**
	 * display user profile
	 *
	 * @return void
	 **/
	function defaultMethod() {
		if (!$this->_ats->user->isAuthed()) {
			elThrow(E_USER_WARNING, m('You need to be authenticate user to edit your profile'), null, EL_BASE_URL);
		}
		$this->_initRenderer();
		$this->_rnd->_setFile();

		$label = '<ul class="adm-icons">
			<li><a href="'.EL_URL.'__profile__/edit/" class="icons user-edit" title="'.m('Edit').'"></a></li>
			<li><a href="'.EL_URL.'__profile__/passwd/" class="icons passwd" title="'.m('Change password').'"></a></li>
			</ul>';
		$this->_rnd->_te->assignVars('dtLabel', $label.m('User profile'));
		$this->_rnd->render( $this->_ats->user->getData(), null, 'DT_ROW' );
	}

	/**
	 * edit user data
	 *
	 * @return void
	 **/
	function edit() {
		if (!$this->_ats->user->isAuthed()) {
			elThrow(E_USER_WARNING, m('You need to be authenticate user to edit your profile'), null, EL_BASE_URL);
		}
		
		if (!$this->_ats->user->editAndSave()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($this->_ats->user->formToHtml());
		} else {
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL.'/__profile__/');
		}
	}

	/**
	 * change user password
	 *
	 * @return void
	 **/
	function passwd() {
		if (!$this->_ats->user->isAuthed()) {
			elThrow(E_USER_WARNING, m('You need to be authenticate user to edit your profile'), null, EL_BASE_URL);
		}
		
		if (!$this->_ats->user->changePasswd()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($this->_ats->user->formToHtml());
		} else {
			elMsgBox::put( sprintf( m('Password for user "%s" was changed'), $this->_ats->user->login ) );
			elLocation(EL_BASE_URL.'/__profile__/');
		}
	}

	/**
	 * new user registration
	 *
	 * @return void
	 **/
	function registration() {
		if ($this->_ats->user->isAuthed()) {
			elThrow(E_USER_WARNING, m('You need to log out before register as new user'), null, EL_BASE_URL);
		}
		if (!$this->_ats->isRegistrationAllowed()) { 
			elThrow(E_USER_WARNING, m('Access denied'), null, EL_BASE_URL);
		}
		
		$user = $this->_ats->createUser();
		
		if (!$user->editAndSave()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($user->formToHtml());
		} else {
			elMsgBox::put( m('Registration complete! Password was sent on Your e-mail address') );
			elLocation(EL_URL);
		}
	}

	/**
	 * remind password
	 *
	 * @return void
	 **/
	function remind() {
		if ($this->_ats->user->isAuthed()) {
			elThrow(E_USER_WARNING, m('You need to log out before register as new user'), null, EL_BASE_URL);
		}
		$user = $this->_ats->createUser();
		if (!$user->remindPasswd()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($user->formToHtml());
		} else {
			elMsgBox::put( sprintf(m('New password was send onto e-mail - %s'), $user->email) );
		    elLocation(EL_URL);
		}
	}
	
	/**
	 * display user profile in json
	 *
	 * @return void
	 **/
	function get() {
		include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
		$UID = (int)$this->_args[1];
		if (!$this->_allow($UID) && $this->_ats->user->UID != $UID) {
			exit(elJSON::encode(array('error' => m('Access denied'))));
		}
		
		$user = $this->_ats->createUser();
		$user->IdAttr($UID);
		if (!$user->fetch()) {
			exit(elJSON::encode(array('error' => m('There is no such user'))));
		}
		exit(elJSON::encode($user->getData()));
	}

	/**
	 * display user groups in json
	 *
	 * @return void
	 **/
	function groups() {
		include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
		$UID = (int)$this->_args[1];
		if (!$this->_allow($UID)) {
			exit(elJSON::encode(array('error' => m('Access denied'))));
		}
		
		$user = $this->_ats->createUser();
		$user->IdAttr($UID);
		if (!$user->fetch()) {
			exit(elJSON::encode(array('error' => m('There is no such user'))));
		}
		$ret        = array();
		$userGroups = $user->getGroups();
		$g          = $this->_ats->createGroup();
		$groups     = $g->collection(false);
		foreach ($groups as $gid=>$group) {
			$ret[] = array(
				'id'       => $gid,
				'name'     => $group['name'],
				'selected' => in_array($gid, $userGroups)
				);
		}
		
		exit(elJSON::encode($ret));
	}

	/**
	 * return true if current user can get user profile/groups list 
	 *
	 * @param  int  $UID  user id
	 * @return bool
	 **/
	function _allow($UID) {
		if ($this->_ats->user->isInGroupRoot()) {
			return true;
		}
		$nav = & elSingleton::getObj('elNavigator');
		$pid = $nav->pageByModule('UsersControl');
		return $pid && $this->_ats->allow(EL_WRITE, $pid);
	}

} // END class 

?>