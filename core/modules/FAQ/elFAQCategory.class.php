<?php

class elFAQCategory extends elMemberAttribute
{
	var $ID       = 0;
	var $name     = '';
	var $quests   = array();
	var $csort    = 0;
	var $_uniq    = 'cid';
	var $_objName = 'Category';
	
	
	function makeForm()
	{
		parent::makeForm();
		$this->form->add( new elText('cname', m('Name'), $this->getAttr('cname'), array('style'=>'width:100%')) );
	}
	
	function _initMapping()
	{
		return array('cid' => 'ID', 'cname' => 'name', 'csort' => 'csort');
	}
}
?>