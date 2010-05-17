<?php
elLoadMessages('UserProfile');
elLoadMessages('Auth');
elLoadMessages('CommonAdmin');

class elServiceProfile extends elService
{
	var $_vdir      = '__profile__';
	var $_ats       = null;
	var $_tplFile   = 'defaultTable.html';
	var $_pageTitle = 'User profile';
	var $_mMap      = array(
							'edit'   => array( 'm'=>'editProfile'),
							'passwd' => array( 'm'=>'passwd'),
							);


	function init($args)
	{
		$this->_args = $args;
		$this->_ats = & elSingleton::getObj('elATS');
		if ( !$this->_ats->user->isAuthed() )
		{
			elThrow(E_USER_WARNING, m('You need to be authenticate user to edit your profile'), null, EL_BASE_URL);
		}
	}


	function defaultMethod()
	{
		$this->_initRenderer();
		$this->_rnd->_setFile();

		$label = '<ul class="adm-icons">
			<li><a href="'.EL_URL.'__profile__/edit/" class="icons user-edit" title="'.m('Edit').'"></a></li>
			<li><a href="'.EL_URL.'__profile__/passwd/" class="icons passwd" title="'.m('Change password').'"></a></li>
			</ul>';
		$this->_rnd->_te->assignVars('dtLabel', $label.m('User profile'));
		$this->_rnd->render( $this->_ats->user->getProfileData(), null, 'DT_ROW' );
	}

	/**
	 * edit user data
	 *
	 * @return void
	 **/
	function editProfile() {
		if ( !$this->_ats->editUser($this->_ats->user) ) {
			$this->_initRenderer();
			$this->_rnd->addToContent( $this->_ats->formToHtml() );
		} else {
			elMsgBox::put(m('Data saved'));
			elLocation(EL_BASE_URL.'/__profile__/');
		}
	}

	function passwd()
	{
		if ( !$this->_ats->passwd($this->_ats->user))
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $this->_ats->formToHtml() );
		}
		else
		{
			elMsgBox::put( sprintf( m('Password for user "%s" was changed'), $this->_ats->user->login ) );
			elLocation(EL_BASE_URL.'/__profile__/');
		}
	}

}

?>