<?php

class elTSFeature extends elMemberAttribute
{
	var $tbftg    = '';
	var $ID       = 0;
	var $GID      = 0;
	var $name     = '';
	var $unit     = '';
	var $sortNdx  = 0;
	var $_objName = 'Feature';
	var $isAnn    = 0;
	var $isSplit = 0;
	var $_iVals   = array();
	var $_mVals   = array();

	function setItemValue($iID, $val)
	{
		$this->_iVals[$iID] = $val;
	}

	function getItemValue($iID)
	{
		return !empty($this->_iVals[$iID]) ? $this->_iVals[$iID] : null;
	}

	function setModelValue($mID, $val)
	{
		$this->_mVals[$mID] = $val;
	}

	function getModelValue($mID)
	{
		return !empty($this->_mVals[$mID]) ? $this->_mVals[$mID] : null;
	}

	function isEmpty()
	{
		if ($this->ID)
		{
			$db = & elSingleton::getObj('elDb');
			$db->query('SELECT i_id FROM '.$this->tbft2i.' WHERE ft_id=\''.$this->ID.'\'');
			if ($db->numRows())
			{
				return false;
			}
			$db->query('SELECT m_id FROM '.$this->tbft2m.' WHERE ft_id=\''.$this->ID.'\'');
			return !$db->numRows();
		}
		return true;
	}

	function makeForm()
	{
		parent::makeForm();
		$this->form->add( new elSelect('gid', m('Group'), $this->getAttr('gid'), $this->_getFtGroups()) );
		$this->form->add( new elText('name',  m('Name'),  $this->getAttr('name')) );
		$this->form->add( new elText('unit',  m('Unit'),  $this->getAttr('unit')) );
		$this->form->setRequired('name');
	}



	function _getFtGroups()
	{
		$db = & elSingleton::getObj('elDb');
		return $db->queryToArray( 'SELECT id, name FROM '.$this->tbftg.' ORDER BY name', 'id', 'name');
	}

	function _initMapping()
	{
		return array('id'=>'ID', 'gid'=>'GID', 'name'=>'name', 'unit'=>'unit', 'sort_ndx'=>'sortNdx');
	}
}

?>