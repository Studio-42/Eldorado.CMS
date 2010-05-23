<?php

include_once EL_DIR_CORE.'forms'.DIRECTORY_SEPARATOR.'elForm.class.php';

class elFormConstructor {
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	var $_tb = 'el_form';
	/**
	 * form id
	 *
	 * @var string
	 **/
	var $ID = '';
	/**
	 * form elements
	 *
	 * @var array
	 **/
	var $_elements = array();
	/**
	 * form object
	 *
	 * @var object
	 **/
	var $_form = null;
	/**
	 * form label
	 *
	 * @var string
	 **/
	var $label = '';
	/**
	 * data for form
	 *
	 * @var string
	 **/
	var $_data = array();
	/**
	 * disabled elements ids
	 *
	 * @var string
	 **/
	var $_disabled = array();

	var $_clause = '';
	
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elFormConstructor($id, $label='', $data=array(), $disabled=array()) {
		$this->ID        = $id;
		$this->label     = $label;
		$this->_data     = $data;
		$this->_disabled = $disabled;
		$this->_load();
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _load() {
		$el = & new elFormConstructorElement(null, $this->_tb);
		$this->_elements = $el->collection(true, true, 'form_id="'.mysql_real_escape_string($this->ID).'"', 'sort_ndx, label');
		foreach ($this->_data as $id=>$val) {
			if (isset($this->_elements[$id])) {
				$this->_elements[$id]->setValue($val);
			}
		}
		foreach ($this->_disabled as $id) {
			if (isset($this->_elements[$id])) {
				$this->_elements[$id]->disabled = true;
			}
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function getElements() {
		$ret = array();
		foreach ($this->_elements as $el) {
			$ret[] = $el->toFormElement();
		}
		return $ret;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function fieldExists($id) {
		return isset($this->_elements[$id]);
	}
	
	/**
	 * return complete form
	 *
	 * @return object
	 **/
	function getForm($url=EL_URL, $method='POST') {
		$this->_form = & elSingleton::getObj('elForm', $this->ID, $this->label);
		$this->_form->setRenderer(new elTplFormRenderer());
		foreach ($this->_elements as $el) {
			$rndParams = $el->type=='title' ? array('rowAttrs' => ' class="form-tb-sub"') : null;
			$this->_form->add($el->toFormElement(), $rndParams);
			if ($el->rule) {
				$this->_form->setElementRule($el->ID, $el->rule, $el->required, null, $el->error);
			} elseif ($el->required) {
				$this->_form->setRequired($el->ID);
			}
		}
		return $this->_form; 
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function getAdminFormHtml($url=EL_URL)	{
		$rnd = &elSingleton::getObj('elTE');
		$rnd->setFile('icartAdminForm', 'forms/simple_form.html');
		
		$label = '
			<ul class="adm-icons">
				<li><a href="'.$url.'field_sort/" class="icons sort-num" title="'.m('Sort').'"></a></li>
				<li><a href="'.$url.'field_edit/"  class="icons create" title="'.m('New field').'"></a></li>
				<li><a href="'.$url.'field_rm/" class="icons clean" title="'.m('Clean').'"></a></li>
			</ul>
			';
		
		$rnd->assignBlockVars('FORM_HEAD', array('form_label' => $label.$this->label));

		foreach ($this->_elements as $el) {
			$e = $el->toFormElement();
			if ($e->isCData) {

				$data = array(
					'cdata'  => '
							<ul class="adm-icons">
								<li><a href="'.$url.'field_edit/'.$el->ID.'/"  class="icons edit" title="'.m('Edit').'"></a></li>
								<li><a href="'.$url.'field_rm/'.$el->ID.'/" class="icons delete" title="'.m('Delete').'"></a></li>
							</ul>
						'.$e->toHtml(),
					'rowAttrs' => $el->type=='title' ? ' class="form-tb-sub"' : ''
					);

				$rnd->assignBlockVars('FORM_BODY.CDATA', $data, 0);
			} else {

				$data = array(
					'label' => $e->label,
					'el'    => '
							<ul class="adm-icons">
								<li><a href="'.$url.'field_edit/'.$el->ID.'/"  class="icons edit" title="'.m('Edit').'"></a></li>
								<li><a href="'.$url.'field_rm/'.$el->ID.'/" class="icons delete" title="'.m('Delete').'"></a></li>
							</ul>
						'.$e->toHtml()
					);
				$rnd->assignBlockVars('FORM_BODY.ELEMENT', $data, 0);
				if ($el->required) {
					$rnd->assignBlockVars('FORM_BODY.ELEMENT.RQ', null, 2);
				}
			}
		}
		$rnd->parse('icartAdminForm');
		return $rnd->getVar('icartAdminForm');
	}
	
	/**
	 * return form html
	 *
	 * @return string
	 **/
	function formToHtml() {
		if (!$this->_form) {
			$this->getForm('');
		}
		return $this->_form->toHtml();
	}
	
	/**
	 * create/edit form element
	 *
	 * @return bool
	 **/
	function edit($eID) {
		$el = & new elFormConstructorElement(array('form_id' => $this->ID));
		$el->idAttr($eID);
		$el->fetch();
		if ($el->editAndSave(array('cnt' => count($this->_elements)))) {
			return true;
		} else {
			$this->_form = $el->getForm();
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function delete($id) {
		if (isset($this->_elements[$id])) {
			$this->_elements[$id]->delete();
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function clean() {
		$db = &elSingleton::getObj('elDb');
		$db->query('DELETE FROM el_form WHERE form_id="'.$this->ID.'"');
		$db->optimizeTable('el_form');
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function sort()	{
		$this->_form = & elSingleton::getObj('elForm', $this->ID, $this->label);
		$this->_form->setRenderer(new elTplFormRenderer());
		foreach ($this->_elements as $id => $e) {
			$label = $e->type == 'title' || $e->type == 'comment' ? $e->value : $e->label;
			$this->_form->add(new elText('el['.$id.']', $label, $e->sortNdx, array('size' => 7)));
		}
		
		if ($this->_form->isSubmitAndValid()) {
			$data = $this->_form->getValue();
			$res  = $data['el'];
			$db   = & elSingleton::getObj('elDb');
			$sql  = 'UPDATE el_form SET sort_ndx=%d WHERE id=%d LIMIT 1';
			asort($res);
			$i = 1;
			foreach ($res as $id=>$ndx) {
				if ($ndx != $i) {
					$res[$id] = $i;
				}
				$db->query(sprintf($sql, $res[$id], $id));
				$i++;
			}
			return true;
		}
	}
	
}

class elFormConstructorElement extends elDataMapping {
	
	/**
	 * table name
	 *
	 * @var string
	 **/
	var $_tb = 'el_form';
	/**
	 * object name
	 *
	 * @var string
	 **/
	var $_objName = 'Form field';
	/**
	 * id
	 *
	 * @var int
	 **/
	var $ID = 0;
	/**
	 * form ID
	 *
	 * @var string
	 **/
	var $formID = '';
	/**
	 * field type
	 *
	 * @var string
	 **/
	var $type = 'text';
	/**
	 * field label
	 *
	 * @var string
	 **/
	var $label = '';
	/**
	 * field default value
	 *
	 * @var string
	 **/
	var $value = '';
	/**
	 * field value
	 *
	 * @var string
	 **/
	var $_value = '';
	/**
	 * value variants
	 *
	 * @var string
	 **/
	var $opts = '';
	/**
	 * directory name
	 *
	 * @var string
	 **/
	var $directory = '';
	/**
	 * is field required
	 *
	 * @var string
	 **/
	var $required = '0';
	/**
	 * validate rule
	 *
	 * @var string
	 **/
	var $rule = '';
	/**
	 * max file size for file input
	 *
	 * @var string
	 **/
	var $fileSize = 1;
	/**
	 * allowed file types
	 *
	 * @var string
	 **/
	var $fileType = '';
	/**
	 * error message
	 *
	 * @var string
	 **/
	var $error = '';
	/**
	 * sort index
	 *
	 * @var string
	 **/
	var $sortNdx = 0;
	
	/**
	 * disable input field
	 *
	 * @var bool
	 **/
	var $disabled = false;
	
	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function setValue($v) {
		$this->_value = $v;
	}
	
	/**
	 * return form object
	 *
	 * @return object
	 **/
	function getForm() {
		return $this->_form;
	}
	
	/**
	 * return object - form element
	 *
	 * @return object
	 **/
	function toFormElement() {
		$el = null;
		$attrs = $this->disabled ? array('disabled' => 'on') : array();
		switch ($this->type) {
			case 'comment':
				$el = & new elCData($this->ID, $this->value);
				break;
			case 'title':
				$el = & new elCData($this->ID, $this->value);
				break;
			case 'text':
				$el = & new elText($this->ID, $this->label, $this->_value ? $this->_value : $this->value, $attrs);
				break;	
			case 'textarea':
				$el = & new elTextArea($this->ID, $this->label, $this->_value ? $this->_value : $this->value, $attrs);
				break;
			case 'select':
				$opts = explode("\n", $this->opts);
				$el = &new elSelect($this->ID, $this->label, $this->_value ? $this->_value : $this->value, $opts, $attrs, false, false);
				break;
			case 'checkbox':
				$opts = array_map('trim', explode("\n", $this->opts)); 
				$value = $this->_value ? implode("\n", $this->_value) : $this->value;
				if (sizeof($opts) == 1) {
					if ($this->value) {
						$attrs['checked'] = 'on';
					}
					// $attrs = $this->value ? array('checked' => '') : null;
					$el = & new elCheckBox($this->ID, $this->label, 1, $attrs);
				} else {
					$value = array_map('trim', explode("\n", $this->value)); 
					$el = & new elCheckBoxesGroup($this->ID, $this->label, $value, $opts, $attrs, false, false);
				}
				break;
			case 'date':
				$v = 0;
				$value = $this->_value ? $this->_value : $this->value;
				if ($value) {
					list($y, $m, $d) = explode('/', $value);
					if ($y>0 && $m>0 && $d>0) {
						$v = mktime(0, 0, 0, $m, $d, $y);
					}
				}
				$el = & new elDateSelector($this->ID, $this->label, $v, null, 60);
				break;
			case 'file':
				$el = & new elFileInput($this->ID, $this->label);
				$el->setMaxSize($this->fileSize);
				if (!empty($this->fileType)) {
					$el->setFileExt(array_map('trim', explode(',', $this->fileType)));
				}
				break;
			case 'captcha':
				$el = & new elCaptcha($this->ID, $this->label);
				break;
				
			case 'directory':
				$dm = & elSingleton::getObj('elDirectoryManager');
				$el = &new elSelect($this->ID, $this->label, $this->_value, $dm->get($this->directory), $attrs);
				
				break;
		}
		return $el;
	}
	
	/**
	 * create form for edit object
	 *
	 * @return void
	 **/
	function _makeForm($params) {
		parent::_makeForm();
		
		if (!$this->ID) {
			$this->sortNdx = 1+$params['cnt']++;
			// echo $this->sortNdx;
		}
		
		$types = array(
			'comment'  => m('Comment'), 
			'title'    => m('Title for elements group'), 
			'text'     => m('Text field'), 
			'textarea' => m('Text area'), 
			'select'   => m('Drop down list'), 
			'checkbox' => m('Checkboxes'), 
			'date'     => m('Date selector'), 
			'file'     => m('File upload field'), 
			'captcha'  => m('Captcha: image with code and input field (Spam protection)'),
			'directory' => m('System directory')
			);
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
		
		$this->_form->add(new elSelect('sort_ndx',  m('Index number'),    $this->sortNdx, range(1, $params['cnt']), null, null, false));
		$this->_form->add(new elText('label',       m('Name').$req,       $this->label));
		$this->_form->add(new elSelect('type',      m('Type'),            $this->type, $types));
		$this->_form->add(new elTextArea('opts',    m('Value variants one per line').$req, $this->opts, array('rows' =>7)));
		$this->_form->add(new elTextArea('value',   m('Default value<br/>For checkboxes - one per line<br/>For date - in yyyy/mm/dd format').$req, $this->value, array('rows' =>7)));
		$this->_form->add(new elSelect('directory', m('Directory'),       $this->required, $dm->getList()));
		$this->_form->add(new elSelect('required',  m('Required'),        $this->required, $GLOBALS['yn']));
		$this->_form->add(new elSelect('rule',      m('Validation rule'), $this->rule, $rules));
		$this->_form->add(new elSelect('file_size', m('Max file size in Mb.'), $this->fileSize, $fileSizes, null, null, false));
		$this->_form->add(new elText('file_type',   m('Allowed file extensions list (separeted by semicolon)'),  $this->fileType));
		$this->_form->add(new elText('error',       m('Error message'),  $this->error));
		
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
	
	function _validForm() {
		elLoadMessages('formError');
		$data = $this->_form->getValue();
		
		switch ($data['type']) {
			case 'title':
			case 'comment':
				if (!$data['value']) {
					$this->_form->pushError('value', m('Field could not be empty'));
				}
				break;
			case 'select':
			case 'checkbox':
				if (!$data['opts']) {
					$this->_form->pushError('opts', m('Field could not be empty'));
				}
			default:
				if (!$data['label']) {
					$this->_form->pushError('label', m('Field could not be empty'));
				}
		}
		

		return !$this->_form->hasErrors();
	}
	
	function _postSave($isNew, $params=null) {
		$db  = $this->_db();
		$indexes = $db->queryToArray('SELECT id, sort_ndx FROM '.$this->_tb.' WHERE form_id="'.$this->formID.'" ORDER BY sort_ndx', 'id', 'sort_ndx');
		$i = 1;
		$s = sizeof($indexes);
		foreach ($indexes as $id=>$ndx) {
			if ($id != $this->ID) {
				if ($i == $this->sortNdx) {
					$i = $i == $s ? $s-1 : $i++;
				}
				if ($ndx != $i) {
					$db->query(sprintf('UPDATE %s SET sort_ndx=%d WHERE id=%d LIMIT 1', $this->_tb, $i, $id));
				}
			}
			$i++;
		}
		return true;
	}
	
	function _initMapping()
  	{
    	return array( 
			'id'        => 'ID',
			'form_id'   => 'formID',
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