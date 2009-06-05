<?php

class elBBCategory extends elMemberAttribute
{
	var $ID       = 0;
	var $name     = '';
	var $descrip  = '';
	var $sortNdx  = 0;
	var $_objName = 'Category';
	
	
	function makeForm()
	{
		parent::makeForm();
		$this->form->add( new elText('name', m('Name'), $this->name));
		$this->form->add( new elTextArea('descrip', m('Description'), $this->descrip));
		$this->form->setRequired('name');
	}
	
	function _initMapping()
	{
		return array('id' => 'ID', 'name' => 'name', 'descrip' => 'descrip', 'sort_ndx' => 'sortNdx');
	}
}

?>