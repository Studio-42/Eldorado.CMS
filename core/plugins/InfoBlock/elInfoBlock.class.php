<?php

class elInfoBlock extends elMemberAttribute
{
	var $tb       = 'el_plugin_ib';
	var $tbPages  = 'el_plugin_ib2page';
	var $ID       = 0;
	var $name     = '';
	var $content  = '';
	var $pos      = EL_POS_LEFT;
	var $tpl      = '';
	var $_uniq    = 'id';
	var $_objName = 'Info block';
	var $_pages   = array();

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

	function makeForm()
	{
		parent::makeForm();

		$p = & new elMultiSelectList('pids', m('Pages'), $this->_getPages(), elGetNavTree('+', m('Whole site')));
		$p->setSwitchValue(1);
		$this->form->add( $p );
		$this->form->add( new elSelect('pos', m('Position'), $this->pos, $GLOBALS['posLRTB']) );
		$tList = $this->_getAltTpls();
		if ( !empty($tList))
		{
		  //array_unshift($tList, m('No'));
		  $this->form->add( new elSelect('tpl', m('Use alternative template'), $this->tpl, $tList) );
		}
		$this->form->add( new elText('name', m('Name'), $this->name) );
		$this->form->add( new elEditor('content', m('Content'), $this->content) );
	}

	function _getPages()
	{
		if (empty($this->_pages) && $this->ID)
		{
			$db = & elSingleton::getObj('elDb');
			$sql = 'SELECT page_id FROM '.$this->tbPages.' WHERE id=\''.$this->ID.'\' ';
			$this->_pages = $db->queryToArray($sql, 'page_id', 'page_id');
		}
		return $this->_pages;
	}

	function _initMapping()
	{
		return array('id' => 'ID', 'name' => 'name', 'content' => 'content', 'pos'=>'pos', 'tpl'=>'tpl');
	}

	function _getAltTpls()
	{
		$tList   = glob(EL_DIR.'style/plugins/InfoBlock/*.html');
		$exclude = array('adminList.html', 'top.html', 'left.html', 'right.html');
		$ret     = array(''=>m('No'));
		for ($i=0, $s=sizeof($tList); $i<$s; $i++)
		{
		  $tpl = basename($tList[$i]);
		  if (!in_array( $tpl, $exclude ))
		  {
			$ret[$tpl] = $tpl;
		  }
		}
		return sizeof($ret) >1 ? $ret : null;
	}

	function _postSave()
	{
		$pids = $this->form->getElementValue('pids[]');
		$db   = & elSingleton::getObj('elDb');
		$db->query('DELETE FROM '.$this->tbPages.' WHERE id=\''.$this->ID.'\'');
		$db->optimizeTable($this->tbPages);

		if ( !empty($pids) )
		{
			$db->prepare('INSERT INTO '.$this->tbPages.' (id, page_id) VALUES ', '("%d", "%d")');
			foreach ($pids as $pid)
			{
				$db->prepareData( array($this->ID, $pid));
			}
			$db->execute();
		}
		return true;
	}

}
?>