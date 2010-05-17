<?php

class elServiceRegistration extends elService 
{
	var $_pageTitle = 'New user registration';

	function init($args) {
		$this->_args = $args;
		$this->_ats  = & elSingleton::getObj('elATS');
		if ( $this->_ats->user->isAuthed() ) {
			elThrow(E_USER_WARNING, m('You need to log out before register as new user'), null, EL_BASE_URL);
		}
	}

	function defaultMethod() {
		if (!$this->_ats->isRegistrationAllowed()) { 
			elThrow(E_USER_WARNING, m('Access denied'), null, EL_BASE_URL);
		}
		if ( !$this->_ats->editUser( $this->_ats->user ) ) {
			$this->_initRenderer();
			$this->_rnd->addToContent( $this->_ats->formToHtml() );
		} else {
			$this->_ats->user->UID = 0;
			elMsgBox::put( m('Congratulation! Registration was successfully complited. Your login and password was send on your e-mail.') );
			elLocation(EL_BASE_URL);
		}		
	}
}

?>