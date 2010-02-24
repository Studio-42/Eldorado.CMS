<?php

class elDirectory extends elDataMapping
{
	var $_tb      = 'el_directory';
	var $_objName = 'Directory';

	function _initMapping()
	{
		return array(
			'id'    => 'id',
			'name'  => 'name',
			'label' => 'label',
			'value' => 'value'
		);
	}
	
	function _makeForm()
	{
		parent::_makeForm();
		$this->_form->add(new elText('name',  m('Name'),  $this->attr('name')));
		$this->_form->add(new elText('label', m('Label'), $this->attr('label')));
		$this->_form->add(new elTextArea('value', m('Content'), str_replace(',', "\n", $this->attr('value'))));
		$this->_form->setRequired('name');
		$this->_form->setRequired('label');
		$this->_form->setRequired('value');
	}

	function getOpts($str)
	{
		list($dir, $name) = explode(':', $str);
		$array = array();
		if (!empty($name))
		{
			$col = $this->collection(false, false, sprintf("name='%s'", $name), null, 0, 1);
			$col = array_pop($col);
			foreach (explode(',', $col['value']) as $a)
			{
				$a = trim($a);
				$array[$a] = $a;
			}
		}
		return $array;
	}

	function _attrsForSave()
	{
		$value = array();
		foreach (explode("\n", $this->attr('value')) as $v)
		{
			$v = trim($v);
			if (empty($v))
				continue;
			$value[] = trim($v);
		}
		$this->attr('value', implode(',', $value));
		return array_map('mysql_real_escape_string', $this->attr());
	}
}

