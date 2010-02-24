<?php

class elPcCalculator extends elDataMapping {
	var $_tb       = 'el_plugin_calc';
	var $_tbPages  = 'el_plugin_calc2page';
	var $_tbv      = 'el_plugin_calc_var';
	var $ID        = 0;
	var $name      = '';
	var $view      = '';
	var $pos       = 'l';
	var $tpl       = '';
	var $formula   = '';
	var $dtype     = 'int';
	var $unit      = '';
	var $_objName  = 'Calculator';
	var $_pages    = array();
	
	
	function toJSON() {
		
		$var = new elPcVar();
		$vars = $var->collection(true, false, 'cid='.$this->ID, 'sort_ndx, id');
		
		$data = array(
			'name'    => $this->name,
			'formula' => $this->formula,
			'unit'    => $this->unit,
			'dtype'   => $this->dtype,
			'vars'    => array()

			);
			
		if (!sizeof($vars)) {
			return elJSON::encode(array('error' => m('Requested calculator does not configured')));
		}
		
		foreach ($vars as $v) {
			$data['vars'][] = $v->toArray(false);
		}
		return elJSON::encode($data, true);
	}
	
	function getPagesNames()
	{
		$ret       = array();
		$all       = elGetNavTree();
		$intersect = array_intersect( array_keys($this->_getPages()), array_keys($all));
		foreach ($intersect as $id)
		{
			$ret[$id] = $all[$id];
		}
		if (!empty($ret[1]) )
		{
			$ret[1] = m('Whole site');
		}
		return $ret;
	}
	
	function _makeForm() {
		parent::_makeForm();
		$this->_form->add(new elHidden('action', '', 'edit'));
		$this->_form->add(new elHidden('id', '', $this->ID));
		
		$p = & new elMultiSelectList('pids', m('Pages'), $this->_getPages(), elGetNavTree('+', m('Whole site')));
		$p->setSwitchValue(1);
		$this->_form->add( $p );
		$this->_form->add( new elSelect('pos', m('Position'), $this->pos, $GLOBALS['posLRTB']) );
		$this->_form->add(new elSelect('view', m('View'), $this->view, array('inline'=>m('On page'), 'dialog'=>m('In dialog window'))));
		$this->_form->add(new elText('name', m('Name'), $this->name));

		//$this->_form->add(new elSelect('split', m('Split with other calculators in same position'), $this->split, $GLOBALS['yn']));
		//$this->_form->add(new elText('group_name', m('Name for splitted group'), $this->groupName));
		$this->_form->add(new elSelect('dtype', m('Result data type'), $this->dtype, array('int'=>m('Integer'), 'double'=>m('Double'))));
		$this->_form->add(new elText('unit', m('Unit'), $this->unit));
		$this->_form->setRequired('name');
		$this->_form->add(new elHidden('formula', '', $this->formula));
	}
	
	function _getPages()
	{
		if (empty($this->_pages) && $this->ID)
		{
			$db  = & elSingleton::getObj('elDb');
			$sql = 'SELECT page_id FROM '.$this->_tbPages.' WHERE id=\''.$this->ID.'\' ';
			$this->_pages = $db->queryToArray($sql, 'page_id', 'page_id');
		}
		return $this->_pages;
	}
	
	function _postSave()
	{
		$pids = $this->_form->getElementValue('pids[]');
		$db   = & elSingleton::getObj('elDb');
		$db->query('DELETE FROM '.$this->_tbPages.' WHERE id=\''.$this->ID.'\'');
		$db->optimizeTable($this->_tbPages);

		if ( !empty($pids) )
		{
			$db->prepare('INSERT INTO '.$this->_tbPages.' (id, page_id) VALUES ', '("%d", "%d")');
			foreach ($pids as $pid)
			{
				$db->prepareData( array($this->ID, $pid));
			}
			$db->execute();
		}
		return true;
	}
	
	function _initMapping()
	{
		return array(
			'id'      => 'ID',
			'name'    => 'name',
			'view'    => 'view',
			'pos'     => 'pos',
			'tpl'     => 'tpl',
			'formula' => 'formula',
			'unit'    => 'unit',
			'dtype'   => 'dtype'
			);
	}
	
}
?>