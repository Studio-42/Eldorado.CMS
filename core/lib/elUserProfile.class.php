<?php

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';

class elUserProfile extends elFormConstructor {
	var $_tb = 'el_user_profile';
	var $UID = 0;
	var $ID  = 'elf';
	var $db  = null;

	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elUserProfile($db, $data=array()) {
		$this->db    = $db;
		$this->label = $data['uid'] ? m('User profile') : m('New user registration');
		$this->_data = $data;
		$this->UID   = $data['uid'];
		$this->_load();
	}
	

	
	/**
	 * return complete form
	 *
	 * @return object
	 **/
	function getForm($url=EL_URL, $method='POST') {
		$form = parent::getForm($url, $method);
		$form->registerRule('validUserForm', 'func', 'validUserForm', null);
		$form->setElementRule('login', 'validUserForm', true, $this->UID);
		$form->setElementRule('email', 'validUserForm', true, $this->UID);
		if (!$this->UID) {
			// $form->add(new elCaptcha('__reg__', m('Enter code from picture')));
		}
		
		return $form;
	}
	
	/**
	 * load elements
	 *
	 * @return void
	 **/
	function _load() {
		$el = & new elUserProfileField(null, $this->_tb);
		$this->_elements = $el->collection(true, true, null, 'sort_ndx, label');
		foreach ($this->_data as $id=>$val) {
			if (isset($this->_elements[$id])) {
				$this->_elements[$id]->setValue($val);
			}
		}
		if (!empty($this->_data['login'])) {
			unset($this->_elements['login']);
		}
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
