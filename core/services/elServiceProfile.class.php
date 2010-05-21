<?php
elLoadMessages('Auth');

/**
 * display/edit profile, change password, registration
 *
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
			'reg'    => array('m' => 'registration')
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
		if ( !$this->_ats->editUser($this->_ats->user) ) {
			$this->_initRenderer();
			$this->_rnd->addToContent( $this->_ats->formToHtml() );
		} else {
			elMsgBox::put(m('Data saved'));
			elLocation(EL_BASE_URL.'/__profile__/');
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
		if ( !$this->_ats->passwd($this->_ats->user)) {
			$this->_initRenderer();
			$this->_rnd->addToContent( $this->_ats->formToHtml() );
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
		if ( !$this->_ats->editUser($this->_ats->user) ) {
			$this->_initRenderer();
			$this->_rnd->addToContent($this->_ats->formToHtml());
		} else {
			$this->_ats->user->UID = 0;
			elMsgBox::put( m('Registration complete! Password was sent on Your e-mail address') );
			elLocation(EL_BASE_URL);
		}
	}

}

?>