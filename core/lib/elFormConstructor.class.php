<?php

include_once EL_DIR_CORE.'forms'.DIRECTORY_SEPARATOR.'elForm.class.php';

class elFormConstructor {
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
	 * constructor
	 *
	 * @return void
	 **/
	function elFormConstructor($id) {
		$this->ID = $id;
		$el = & new elFormConstructorElement();
		$this->_elements = $el->collection(true, false, 'form_id="'.mysql_real_escape_string($id).'"', 'sort_ndx');
		// elPrintR($this->_elements);
	}
	
	/**
	 * return complete form
	 *
	 * @return object
	 **/
	function getForm($label, $url=EL_URL, $method='POST', $admin=false) {
		if ($admin) {
			$label = '
					<ul class="adm-icons">
						<li><a href="'.$url.'field_edit/"  class="icons create" title="'.m('New field').'"></a></li>
						<li><a href="'.$url.'field_rm/" class="icons clean" title="'.m('Clean').'"></a></li>
					</ul>
				'.$label;
		}
		$this->_form = & elSingleton::getObj('elForm', $this->ID, $label);
		$this->_form->setRenderer(new elTplFormRenderer());
		foreach ($this->_elements as $el) {
			$this->_form->add($el->toFormElement());
		}
		return $this->_form; 
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
	function getElements() {
		$ret = array();
		foreach ($this->_elements as $el) {
			$ret[] = $el->toFormElement();
		}
		return $ret;
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
	 * valu variants
	 *
	 * @var string
	 **/
	var $opts = '';
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
		switch ($this->type) {
			case 'comment':
				$el = & new elCData($this->ID, $this->value);
				break;
			case 'title':
				$el = & new elCData($this->ID, $this->value);
				break;
			case 'text':
				$el = & new elText($this->ID, $this->label, $this->value);
				break;	
			case 'textarea':
			
				break;
			case 'select':
			
				break;
			case 'checkbox':
			
				break;
			case 'date':
			
				break;
			case 'file':
			
				break;
			case 'captcha':
			
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
			$this->sortNdx = $params['cnt']++;
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
			'captcha'  => m('Captcha: image with code and input field (Spam protection)')
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
		$optsComments = '<br/>'.m('For drop-down list or checkboxes - one per line').'.<br/>'.m('For date selector - in DD.MM.YYYY format').'.';
		$valueComments = '<br/>'.m('For checkboxes - one per line');
		
		$this->_form->add(new elSelect('sort_ndx',  m('Index number'),   $this->sortNdx, range(1, $params['cnt']), null, null, false));
		$this->_form->add(new elText('label',       m('Name').$req,      $this->label));
		$this->_form->add(new elSelect('type',      m('Type'),           $this->type, $types));
		$this->_form->add(new elTextArea('opts',    m('Value variants').$optsComments, $this->opts, array('rows' =>7)));
		$this->_form->add(new elTextArea('value',   m('Default value').$req.$valueComments,  $this->value, array('rows' =>7)));
		$this->_form->add(new elSelect('required',  m('Required'),       $this->required, $GLOBALS['yn']));
		$this->_form->add(new elSelect('rule',      m('Type'),           $this->rule, $rules));
		$this->_form->add(new elSelect('file_size', m('Max file size in Mb.'), $this->fileSize, $fileSizes, null, null, false));
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
						$('#row_opts, #row_file_size').hide();
						$('#row_label, #row_value, #row_required, #row_rule, #row_error').show();
						break;
					case 'textarea':
						$('#row_opts, #row_rule, #row_file_size').hide();
						$('#row_label, #row_value, #row_required, #row_error').show();
						break;
					case 'select':
						$('#row_rule, #row_required, #row_error, #row_file_size').hide();
						$('#row_label, #row_value, #row_opts, #row_required, #row_error').show();
						break;
					case 'checkbox':
						$('#row_rule, #row_file_size').hide();
						$('#row_label, #row_value, #row_opts, #row_required, #row_error').show();
						break;
					case 'file':
						$('#row_opts, #row_value, #row_rule').hide();
						$('#row_label, #row_file_size').show();
						break;
					case 'captcha':
					case 'date':
						$('#row_value, #row_opts, #row_required, #row_rule, #row_error, #row_file_size').hide();
						$('#row_label').show();
						break;
					default:
						$('#row_label, #row_opts, #row_required, #row_rule, #row_error, #row_file_size').hide();
						$('#row_value').show();
				}
				
				if (v == 'title' || v == 'comment') {
					$('#row_label .form-req').hide();
					$('#row_value .form-req').show();
				} else {
					$('#row_value .form-req').hide();
					$('#row_label .form-req').show();
				}
			}).change();
		";
		elAddJs($js, EL_JS_SRC_ONREADY);
		
	}
	
	function _validForm()
	{
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
	
	function _initMapping()
  	{
    	return array( 
			'id'        => 'ID',
			'form_id'   => 'formID',
		    'type'      => 'type',
		    'label'     => 'label',
		    'value'     => 'value',
		    'opts'      => 'opts',
			'required'  => 'required',
		    'rule'      => 'rule',
		    'file_size' => 'fileSize',
		    'error'     => 'error',
		    'sort_ndx'  => 'sortNdx'
		);
	}
}

?>