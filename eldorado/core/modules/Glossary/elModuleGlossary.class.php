<?php

class elModuleGlossary extends elModule
{
	var $_tb   = '';
	var $_mMap = array(
		'letter' => array ( 'm' => 'showLetter')
	);

	function defaultMethod()
	{
		$gloss = $this->_getGlossary();
		$this->_initRenderer();
		$this->_rnd->rndGloss($this->_getEntries(), $this->_getABCs());
	}

	function showLetter() {
		$this->_initRenderer();
		$this->_rnd->rndGloss(
			$this->_getEntries(urldecode($this->_args[0])),
			$this->_getABCs(),
			urldecode($this->_args[0])
		);
	}

	// Private
	function _getEntries($letter = false)
	{
		$gloss = $this->_getGlossary();
		if (!$letter)
			$letter = $gloss->firstLetter();
		$cond = 'upper(word) like "' . strtoupper($letter) . '%"';
		$entries = $gloss->getCollection(null, 'word', 0, 0, $cond);
		return $entries;
	}

	function _getABCs() {
		$gloss = $this->_getGlossary();
		return $gloss->getABCs();
	}

	function _getGlossary($ID = 0)
	{
		$gloss = & elSingleton::getObj('elGlossary');
		$gloss->setTb($this->_tb);
		if ($ID)
		{
			$gloss->setUniqAttr($ID);
			$gloss->fetch();
		}
		else
		{
			$gloss->cleanAttrs();
		}
		return $gloss;
	}

	function _onInit()
	{
		$this->_tb = 'el_gloss_'.$this->pageID;
	}
}

?>