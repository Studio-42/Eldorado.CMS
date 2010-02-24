<?php

class elModuleDirectory extends elModule
{

	var $_mMap = array(
		'list' => array('m' => 'listDir'),
		'show' => array('m' => 'showDir'),
		'new'  => array('m' => 'editDir', 'ico' => 'icoDocNew', 'l' => 'Add new Directory', 'g' => 'Actions'),
		'edit' => array('m' => 'editDir')

	);
	var $_mMapConf  = array();
	
	function defaultMethod()
	{
		elLocation(EL_URL . 'list');
	}
	
	function listDir()
	{
		$dir = & elSingleton::getObj('elDirectory');
		
		$this->_initRenderer();
		$col = $dir->collection(false, false, null, null, 0, 10);
		$this->_rnd->rndList($col);
//		$this->_rnd->addToContent('<pre>'.print_r($col, true).'</pre>');		
	}
	
	function editDir()
	{		
		$dir = $this->_getDir($this->_arg(0));
		if (!$dir->editAndSave())
		{
			//elPrintR($dme);
			$this->_initRenderer();
			$this->_rnd->addToContent($dir->formToHtml());
		}
		else
		{
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL);
		}
	}
	
	function showDir()
	{
		$dir = $this->_getDir($this->_arg(0));
		$a = explode(',', $dir->attr('value'));
		$this->_initRenderer();
		$this->_rnd->addToContent('<h2>'.$dir->attr('label').'</h2>');
		$this->_rnd->addToContent(implode('<br />', $a));
	}

	function removeDir()
	{
		$staff = $this->_getStaff($this->_arg(0));
		if (!$staff->ID)
      		elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($staff->objName(), $staff->ID),
				EL_URL);
		$staff->delete();
		elMsgBox::put(sprintf(m('Object "%s" "%s" was deleted'), $staff->objName(), $staff->staff_ru));
		elLocation(EL_URL . 'staff/');
	}
	
	function _getDir($id = null)
	{
		$dir = & elSingleton::getObj('elDirectory');
		if ($id)
		{
			$dir->idAttr($id);
			$dir->fetch();
		}
		else
			$dir->clean();
		return $dir;
	}


}
?>
