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

		if (!$this->UID) {
			$ats = &elSingleton::getObj('elATS');
			if (!$ats->allow(EL_WRITE)) {
				$form->add(new elCaptcha('__reg__', m('Enter code from picture')));
			}
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
	 * add new field if not exists
	 *
	 * @param  elUserProfileField
	 * @return bool
	 **/
	function add($el) {
		if ($el->ID) {
			return isset($this->_elements[$el->ID]) ? true : $el->save();
			if (isset($this->_elements[$el->ID])) {
				return true;
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
		} else {
			$this->_elements['login']->required = true;
		}
		$this->_elements['email']->rule = 'email';
		$this->_elements['email']->required = true;
		
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
	 * types list
	 *
	 * @var array
	 **/
	var $_types = array(
		// 'comment'   => 'Comment', 
		// 'title'     => 'Title for elements group', 
		'text'      => 'Text field', 
		'textarea'  => 'Text area', 
		'select'    => 'Drop down list', 
		'checkbox'  => 'Checkboxes', 
		'date'      => 'Date selector', 
		// 'file'      => 'File upload field', 
		// 'captcha'   => 'Captcha: image with code and input field (Spam protection)',
		'directory' => 'System directory',
		'slave-directory' => 'Slave directory'
		);


	/**
	 * return form element
	 *
	 * @return object
	 **/
	function toFormElement(&$fc, $admin=false) {
		if ($this->type == 'slave-directory') {
			$master = $fc->findElementDirectory($this->directory);
			if ($admin) {
				return new elCData2($this->ID, $this->label, m('Depends on').': '.($master ? $master->label : m('Undefined')));
			} else {
				$opts = array();
				if ($master) {
					$dm = & elSingleton::getObj('elDirectoryManager');
					if (false != ($dir = $dm->findSlave($master->directory, $master->getValue()))) {
						$opts = $dir->records(true);
					}
				}
				return new elSelect($this->ID, $this->label, $this->_value ? trim($this->_value) : trim($this->value), $opts, array('rel' => $this->directory));
			}
		}
		return parent::toFormElement($fc, $admin);
	}


	/**
	 * save profile field
	 *
	 * @return bool
	 **/
	function save() {
		$attrs = $this->_attrsForSave();
		if ($attrs['type'] != 'directory' && $attrs['type'] != 'slave-directory') {
			$attrs['directory'] = '';
		}
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
		// if (!empty($attrs['value'])) {
			// $sql = sprintf('UPDATE el_user SET %s="%s" WHERE %s=""', $this->ID, $attrs['value'], $this->ID);
		// }
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
	 * create form for edit object
	 *
	 * @return void
	 **/
	function _makeForm($params) {
		$this->_form = & elSingleton::getObj( 'elForm', 'mf'.get_class($this),  sprintf( m(!$this->{$this->__id__} ? 'Create object "%s"' : 'Edit object "%s"'), m($this->_objName))  );
		$this->_form->setRenderer( elSingleton::getObj($this->_formRndClass) );
		
		if (!$this->ID) {
			$this->sortNdx = 1+$params['cnt']++;
		}

		$rules = array(
			''                 => m('Any'),
			'email'            => m('E-mail'),
            'phone'            => m('Phone number'),
			'url'              => m('URL'),
            'numbers'          => m('Only numbers'),
            'letters_or_space' => m('Only letters')
			);
		$fileSizes = range(1, 10) + array(15, 20, 30, 40, 50, 60, 70, 80, 90, 100);
		$req = ' <span class="form-req">*</span>';
		
		$dm = & elSingleton::getObj('elDirectoryManager');
		
		if (!$this->_idAuto && !$this->ID) {
			$this->_form->add(new elText('id', m('ID'), $this->ID));
			$this->_form->setElementRule('id', 'alfanum_lat');
		}
		
		
		$this->_form->add(new elText('label',       m('Name').$req,       $this->label));
		$this->_form->add(new elSelect('type',      m('Type'),            $this->type, array_map('m', $this->_types)));
		$this->_form->add(new elTextArea('opts',    m('Value variants one per line').$req, $this->opts, array('rows' =>7)));
		$this->_form->add(new elTextArea('value',   m('Default value<br/>For checkboxes - one per line<br/>For date - in yyyy/mm/dd format').$req, $this->value, array('rows' =>7)));
		$this->_form->add(new elSelect('directory', m('Directory'),       $this->required, $dm->getList()));
		$this->_form->add(new elSelect('required',  m('Required'),        $this->required, $GLOBALS['yn']));
		$this->_form->add(new elSelect('rule',      m('Validation rule'), $this->rule, $rules));
		$this->_form->add(new elSelect('file_size', m('Max file size in Mb.'), $this->fileSize, $fileSizes, null, null, false));
		$this->_form->add(new elText('file_type',   m('Allowed file extensions list (separeted by semicolon)'),  $this->fileType));
		$this->_form->add(new elText('error',       m('Error message'),  $this->error));
		$this->_form->add(new elSelect('sort_ndx',  m('Index number'),    $this->sortNdx, range(1, $params['cnt']), null, null, false));
		
		$js = "
			$('#type').change(function() {
				var v = $(this).val();
				if ($(this).attr('laded')) {
					$('#".$this->_form->getAttr('name')." .form-errors').parent().hide();
				} else {
					$(this).attr('laded', 1);
				}
				
				switch(v) {
					case 'text':
						$('#row_opts, #row_file_size, #row_file_type, #row_directory').hide();
						$('#row_label, #row_value, #row_required, #row_rule, #row_error').show();
						break;
					case 'textarea':
						$('#row_opts, #row_rule, #row_file_size, #row_file_type, #row_directory').hide();
						$('#row_label, #row_value, #row_required, #row_error').show();
						break;
					case 'select':
						$('#row_rule, #row_required, #row_error, #row_file_size, #row_file_type, #row_directory').hide();
						$('#row_label, #row_value, #row_opts, #row_required, #row_error').show();
						break;
					case 'checkbox':
						$('#row_rule, #row_file_size, #row_file_type, #row_directory').hide();
						$('#row_label, #row_value, #row_opts, #row_required, #row_error').show();
						break;
					case 'file':
						$('#row_opts, #row_value, #row_rule, #row_directory').hide();
						$('#row_label, #row_file_size, #row_file_type').show();
						break;
					case 'captcha':
						$('#row_value, #row_opts, #row_required, #row_rule, #row_error, #row_file_size, #row_file_type, #row_directory').hide();
						$('#row_label').show();
						break;
					case 'date':
						$('#row_opts, #row_required, #row_rule, #row_error, #row_file_size, #row_file_type, #row_directory').hide();
						$('#row_label, #row_value').show();
						break;
					case 'directory':
					case 'slave-directory':
						$('#row_opts, #row_required, #row_value, #row_rule, #row_error, #row_file_size, #row_file_type, #row_directory').hide();
						$('#row_label, #row_directory').show();
						break;
					default:
						$('#row_label, #row_opts, #row_required, #row_rule, #row_error, #row_file_size, #row_file_type').hide();
						$('#row_value').show();
				}
				
				if (v == 'title' || v == 'comment') {
					$('#row_label .form-req').hide();
					$('#row_opts .form-req').hide();
					$('#row_value .form-req').show();
				} else if (v == 'select' || v == 'checkboxes') {
					$('#row_value .form-req').hide();
					$('#row_label .form-req').show();
					$('#row_opts .form-req').show();
				} else {
					$('#row_value .form-req').hide();
					$('#row_label .form-req').show();
					
				}
			}).change();
		";
		elAddJs($js, EL_JS_SRC_ONREADY);
		
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
