<?php

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';

class elUserProfile extends elFormConstructor {
	var $_tb = 'el_user_profile';
	var $UID  = 0;
	var $db   = null;
	var $_map = array( 
		'id'        => 'ID',
	    'type'      => 'type',
	    'label'     => 'label',
	    'value'     => 'value',
	    'opts'      => 'opts',
		'directory' => 'directory',
		'required'  => 'required',
	    'rule'      => 'rule',
	    'file_size' => 'fileSize',
		'file_type' => 'fileType',
	    'error'     => 'error',
	    'sort_ndx'  => 'sortNdx'
	);
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elUserProfile($db, $data=array()) {
		$this->db = $db;
		parent::elFormConstructor(0, $data['uid'] ? m('User profile') : m('New user registration'), $data);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function _load() {
		parent::_load();
		if (!empty($this->_data['login'])) {
			unset($this->_elements['login']);
		}
	}
	/**
	 * return complete form
	 *
	 * @return object
	 **/
	function getForm($url=EL_URL, $method='POST') {
		// $this->label = $this->UID ? m('User profile') : m('New user registration');
		$form = parent::getForm($url, $method);
		$form->registerRule('validUserForm', 'func', 'validUserForm', null);
		$form->setElementRule('login', 'validUserForm', true, $this->UID);
		$form->setElementRule('email', 'validUserForm', true, $this->UID);
		if (!$this->UID) {
			// $form->add(new elCaptcha('__reg__', m('Enter code from picture')));
		}
		
		return $form;
	}
	
}

class elUserProfileField extends elFormConstructorElement {
	
	var $_tb = 'el_user_profile';
	
	function _initMapping()
  	{
    	return array( 
			'id'        => 'ID',
		    'type'      => 'type',
		    'label'     => 'label',
		    'value'     => 'value',
		    'opts'      => 'opts',
			'directory' => 'directory',
			'required'  => 'required',
		    'rule'      => 'rule',
		    'file_size' => 'fileSize',
			'file_type' => 'fileType',
		    'error'     => 'error',
		    'sort_ndx'  => 'sortNdx'
		);
	}
}


?>
