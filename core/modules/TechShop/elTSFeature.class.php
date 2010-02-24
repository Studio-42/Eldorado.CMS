<?php

class elTSFeature extends elDataMapping
{
	var $tbftg    = '';
	var $tbft2i   = '';
	var $tbft2m   = '';
	var $ID       = 0;
	var $GID      = 0;
	var $name     = '';
	var $unit     = '';
	var $sortNdx  = 0;
	var $isAnn    = 0;
	var $isSplit  = 0;
	var $_objName = 'Feature';
	
	/**
	 * Remove feature
	 *
	 * @param  array  $ref  refrenced tables
	 * @return void
	 **/
	function delete($ref=null)
	{
		return parent::delete(array($this->tbft2i => 'ft_id', $this->tbft2m => 'ft_id'));
	}

	/**
	 * Create form for editing object
	 *
	 * @return void
	 **/
	function _makeForm($params=null)
	{
		parent::_makeForm();
		$db = & elSingleton::getObj('elDb');
		$groups = $db->queryToArray( 'SELECT id, name FROM '.$this->tbftg.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name', 'id', 'name');
		$this->_form->add( new elSelect('gid', m('Group'), $this->GID, $groups) );
		$this->_form->add( new elText('name',  m('Name'),  $this->name) );
		$this->_form->add( new elText('unit',  m('Unit'),  $this->unit) );
		$this->_form->setRequired('name');
	}

	function _initMapping()
	{
		return array('id'=>'ID', 'gid'=>'GID', 'name'=>'name', 'unit'=>'unit', 'sort_ndx'=>'sortNdx');
	}
}

?>