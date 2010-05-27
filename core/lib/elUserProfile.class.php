<?php

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';

class elUserProfile extends elFormConstructor {
	var $_tb      = 'el_user_profile';
	var $_elClass = 'elUserProfileField';
	var $UID      = 0;
	var $ID       = 'elf';
	var $db       = null;

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
	 * delete all fields except login&email
	 *
	 * @return void
	 **/
	function clean() {
		foreach ($this->_elements as $id=>$el) {
			if ($el != 'login' && $el != 'email') {
				$el->delete();
			}
		}
	}
	
	/**
	 * load elements
	 *
	 * @return void
	 **/
	function _load() {
		$el = & new elUserProfileField(null, $this->_tb);
		$el->db = $this->db;
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
	/**
	 * table name
	 *
	 * @var string
	 **/
	var $_tb = 'el_user_profile';
	/**
	 * object name
	 *
	 * @var string
	 **/
	var $_objName = 'Profile field';
	/**
	 * is id autoincrement?
	 *
	 * @var bool
	 **/
	var $_idAuto = false;

	/**
	 * save profile field
	 *
	 * @return bool
	 **/
	function save() {
		$attrs = $this->_attrsForSave();
		$db    = $this->_db();
		$sql   = sprintf('REPLACE INTO el_user_profile (%s) VALUES (%s)',  implode(',', array_keys($attrs)), '"'.implode('", "', $attrs).'"');
		if (!$db->query($sql)) {
			return false;
		}
		if (!$db->isFieldExists('el_user', $this->ID) 
		&&  !$db->query('ALTER TABLE  `el_user` ADD  `'.$this->ID.'` MEDIUMTEXT NOT NULL')) {
			$db->query('DELETE FROM '.$this->_tb.' WHERE id="'.$this->ID.'"');
			return false;
		}
		return $this->_postSave();
	}
	
	/**
	 * delete record
	 *
	 * @return void
	 **/
	function delete() {
		if ($this->ID) {
			parent::delete();
			$db    = $this->_db();
			if ($db->isFieldExists('el_user', $this->ID)) {
				$db->query('ALTER TABLE `el_user` DROP `'.$this->ID.'`');
			}
		}
	}
	
	/**
	 * update sort indexes
	 *
	 * @return bool
	 **/
	function _postSave() {
		$db      = $this->_db();
		$indexes = $db->queryToArray('SELECT id, sort_ndx FROM '.$this->_tb.' ORDER BY sort_ndx', 'id', 'sort_ndx');
		$i       = 1;
		$s       = sizeof($indexes);
		foreach ($indexes as $id=>$ndx) {
			if ($id != $this->ID) {
				if ($i == $this->sortNdx) {
					$i = $i == $s ? $s-1 : $i++;
				}
				if ($ndx != $i) {
					$db->query(sprintf('UPDATE %s SET sort_ndx=%d WHERE id="%s" ', $this->_tb, $i, $id));
				}
			}
			$i++;
		}
		return true;
	}
	
	/**
	 * init attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
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
