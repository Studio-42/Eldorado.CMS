<?php

class elModuleSimplePage extends elModule
{
	var $_page            = null;
	var $_mMapAdmin       = array('edit' => array('m' => 'edit', 'ico' => 'icoEdit', 'l' => 'Edit', 'g' => 'Actions'));
	var $_mMapConf        = array();
	var $_defMethodNoArgs = true;
	var $_css             = false;

	function defaultMethod()
	{
		$this->_initRenderer();
		if (!is_file('./style/modules/SimplePage/default.html'))
		{
			$this->_rnd->addToContent($this->_page->content);
		}
		else
		{
			$this->_rnd->_setFile();
			$this->_rnd->render(array('simplePageContent'=>$this->_page->content));
		}
		
	}

	function edit()
	{
		if ($this->_page->editAndSave())
		{
			elMsgBox::put(m('Data saved'));
			elActionLog($this->_page, false, '', $this->_page->content);
			elLocation(EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent($this->_page->formToHtml());
	}

	function ifModifiedSince()
	{
		return array(true, (int)$this->_page->mtime);
	}

	function _onInit()
	{
		$this->_page     = & elSingleton::getObj('elPage');
		$this->_page->ID = $this->pageID;
		if (!$this->_page->fetch())
		{
			$db = & elSingleton::getObj('elDb');
			$db->query('INSERT INTO el_page (id) VALUES (\''.$this->pageID.'\')');
		}
		elseif ($this->_page->mtime == 0)
		{
			$this->_page->save();	// this will set mtime to time()
		}
	}
}
