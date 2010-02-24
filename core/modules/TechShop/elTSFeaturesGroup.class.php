<?php

class elTSFeaturesGroup extends elDataMapping
{
	var $tbft     = '';
	var $ID       = 0;
	var $name     = '';
	var $sortNdx  = 0;
	var $_objName = 'Features group';
	var $features = array();

	/**
	 * Return true if group does not contains features
	 *
	 * @return boolean
	 **/
	function isEmpty()
	{
		if ($this->ID)
		{
			$db = & elSingleton::getObj('elDb');
			$db->query('SELECT id FROM '.$this->tbft.' WHERE gid=\''.$this->ID.'\'');
			return !$db->numRows();
		}
		return true;
	}

	/**
	 * Sort features groups
	 *
	 * @return boolean
	 **/
	function sortGroups()
	{
		parent::_makeForm();
		$this->_form->setLabel( m('Sort features groups') );
		$groups = $this->collection(false, true, null, 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name');
		foreach ($groups as $gid=>$group) {
			$this->_form->add(new elText('ftgsort['.$gid.']', $group['name'], (int)$group['sort_ndx'], array('size'=>12)));
		}
		
		if ($this->_form->isSubmitAndValid()) {
			$data = $this->_form->getValue();
			$data = is_array($data['ftgsort']) ? $data['ftgsort'] : array();
			$db   = & elSingleton::getObj('elDb');
			$db->prepare('REPLACE INTO '.$this->_tb.' (id, name, sort_ndx) VALUES ', '(%d, "%s", %d)');
			foreach($data as $gid=>$ndx) {
				$db->prepareData(array($gid, $groups[$gid]['name'], $ndx));
			}
			$db->execute();
			return true;
		}
	}

	/**
	 * Sort features in current group
	 *
	 * @return boolean
	 **/
	function sortFeatures($feature)
	{
		parent::_makeForm();
		$this->_form->setLabel(sprintf( m('Sort features in group "%s"'), $this->name));
		$features = $feature->collection(false, true, 'gid="'.$this->ID.'"', 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name');
		foreach ($features as $id=>$f) {
			$this->_form->add(new elText('sort['.$id.']', $f['name'], $f['sort_ndx'], array('size' => 16)));
		}
		
		if ($this->_form->isSubmitAndValid()) {
			$data = $this->_form->getValue();
			$data = is_array($data['sort']) ? $data['sort'] : array();
			$db = elSingleton::getObj('elDb');
			$db->prepare('REPLACE INTO '.$this->tbft.' (id, gid, name, unit, sort_ndx) VALUES ', '(%d, %d, "%s", "%s", %d)');
			foreach ($data as $id=>$ndx) {
				$db->prepareData(array($id, $this->ID, $features[$id]['name'], $features[$id]['unit'], $ndx));
			}
			$db->execute();
			return true;
		}
	}

	function _initMapping()
	{
		return array('id' => 'ID', 'name' => 'name', 'sort_ndx' => 'sortNdx');
	}

	/**
	 * Create form for editing object
	 *
	 * @return void
	 **/
	function _makeForm($params=null)
	{
		parent::_makeForm();
		$this->_form->add( new elText('name', m('Name'), $this->name) );
	}

}

?>