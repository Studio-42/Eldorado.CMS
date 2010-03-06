<?php

class elProfileConf extends elDataMapping
{
	var $_tb      = 'el_user_profile';
	var $_tbu     = 'el_user';
	var $_id      = 'field';
	var $_objName = 'Profile field';

	var $field  = '';
	var $label;
	var $type;
	var $opts;
	var $rule;
	var $is_func = 0;
	var $rq;
	var $sort_ndx;

	function checkFieldName($input)
	{
		$l = strlen($input);
		if (preg_match('/^[a-z].[a-z0-9_]+$/i', $input) and ($l >= 3) and ($l <= 30))
			return true;
		else
			return false;
	}

	function checkFieldAllowed($input)
	{
		$deny = array('uid', 'login', 'pass', 'email', 'crtime', 'mtime', 'atime', 'auto_login');
		if (in_array($input, $deny))
			return false;
		else
			return true;
	}

	function checkFieldExists($input)
	{
		$this->idAttr($input);
		if ($this->fetch())
			return true;
		else
			return false;
	}

	function checkLabelExists($input)
	{
		$c = $this->collection(false, false, 'label="'.mysql_real_escape_string($input).'"');
		if (count($c) >= 1)
			return true;
		else
			return false;
	}

	function deleteDataField($field, $delete_data = false)
	{
		if (empty($field))
			return false;

		if ($delete_data == true)
		{
			$db  = & elSingleton::getObj('elDb');
			$sql = 'ALTER TABLE `'.$this->_tbu.'` DROP `'.$field.'`';
			if(!$db->query($sql))
				return false;
		}
		$this->idAttr($field);
		$this->delete();

		return true;
	}

	function _validForm()
	{
		$data = $this->_form->getValue();
		$f = $data['field'];
		$l = $data['label'];

		$e = '';
		// check 'field' for errors
		if (!$this->checkFieldName($f))
			$e = sprintf(m('"%s" must contain only latin, numeral, or "_" characters. Length must be from 3 to 30 characters.'), m('Field in DB'));
		elseif (!$this->checkFieldAllowed($f))
			$e = sprintf(m('Not allowed value for "%s".'), m('Field in DB'));
		elseif (($this->_new) and ($this->checkFieldExists($f)))
			$e = sprintf(m('"%s" = "%s" already exists.'), m('Field in DB'), $f);

		// check 'label' for duplicate
		$la = new elProfileConf();
		$la->idAttr($f);
		if ($l != $la->attr('label'))
			if ($this->checkLabelExists($l))
				$e = sprintf(m('"%s" = "%s" already exists.'), m('Label'), $l);
		unset($la);
		
		if (!empty($e))
		{
			$this->_form->pushError('field', $e);
			return false;
		}
		
		if ($this->_new)
		{
			unset($this->_id);
			$this->attr('sort_ndx', 99);
		}

		return true;
	}

	function _postSave($new, $params)
	{
		// $new not working here because of _validForm()

		$f = $this->attr('field');
		$exists = false;

		$db  = & elSingleton::getObj('elDb');
		$sql = 'DESCRIBE '.$this->_tbu;
		$db->query($sql);
		while ($r = $db->nextRecord())
			if ($r['Field'] == $f)
				$exists = true;

		if (!$exists)
		{

			$sql = 'ALTER TABLE `'.$this->_tbu.'` ADD `'.$f.'` VARCHAR(255) NOT NULL';
			if ($db->query($sql))
			{
				return true;
			}
			else
			{
				// TODO delete from profile + message
			}
		}

		return true;
	}

	// workaround for buggy elDataMapping with 0 == undef
	function _attrsForSave()
	{
		$r = array_map('mysql_real_escape_string', $this->attr());
		if (!$r['is_func'])
			$r['is_func'] = 0;
		if (!$r['rq'])
			$r['rq'] = 0;
		return $r;
	}

	function _makeForm($params = null)
	{
		parent::_makeForm();
		$this->_form->add(new elText('field', m('Field in DB'), $this->attr('field'), '', $params['frozen']));
		$this->_form->add(new elText(  'label',   m('Label'),   $this->attr('label')));
		$this->_form->add(new elSelect('type',    m('Type'),    $this->attr('type'),
			array('text' => 'text', 'textarea' => 'textarea', 'select' => 'select')));
		$this->_form->add(new elTextArea('opts',  m('Options'), $this->attr('opts')));
		$this->_form->add(new elSelect('rule',  m('Check rule'), $this->attr('rule'),
			array(''=>'', 'letters'=>'letters', 'numbers'=>'numbers', 'phone'=>'phone', 'alfanum_lat_dot'=>'alfanum_lat_dot')));
		$this->_form->setRequired('field');
		$this->_form->setRequired('label');
		$this->_form->setRequired('type');
	}

	function _initMapping()
	{
		return array(
			'field'    => 'field',
			'label'    => 'label',
			'type'     => 'type',
			'opts'     => 'opts',
			'rule'     => 'rule',
			'is_func'  => 'is_func',
			'rq'       => 'rq',
			'sort_ndx' => 'sort_ndx'
		);
	}
}
