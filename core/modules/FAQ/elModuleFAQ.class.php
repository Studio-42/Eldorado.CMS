<?php

class elModuleFAQ extends elModule
{
	var $_tb         = '';
	var $_tbCat      = '';
	var $_collection = array();
	var $_mMap       = array(
		'ask' => array('m' => 'askQuestion', /*'l' => 'Ask a question', 'ico' => 'icoQuestion'*/) );
	
	function defaultMethod()
	{ 
		$this->_loadCollection(); 
		$this->_initRenderer();
		$q     = & $this->_getQuestion();
		$cat   = & $this->_getCategory();
		$cList = $cat->getCollectionToArray('cname', null, 'csort, cid'); 
		$q->makeForm(array('admin'=>false, 'cList'=>$cList));
		$q->form->setAttr('action', EL_URL.'ask/');
		$this->_rnd->render( $this->_collection, $q->formToHtml());
	}
	
	function askQuestion()
	{
		$admin = EL_WRITE <= $this->_aMode;
		$q     = & $this->_getQuestion();
		$cat   = & $this->_getCategory();
		$cList = $cat->getCollectionToArray('cname', null, 'csort, cid'); 
		if ( empty($cList) )
		{
			elThrow(E_USER_WARNING, 'You could not send question, because of no one category were defined! Please, contact site andministrator!', null, EL_URL);
		}
		if ( $admin )
		{
			$q->fetch();
			$msg = m('Data saved');
		}
		else
		{
			$msg = m('Thanks for Your question! Answer will be published in closest time.');
		}
		if ( $q->editAndSave( array('admin'=>$admin, 'cList'=>$cList) ) )
		{
			elActionLog($q, false, '', $q->question);
			elMsgBox::put($msg);
			elLocation(EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $q->formToHtml() );
	}
	
	/////   PRIVATE METHODS    ////
	
	function _loadCollection()
	{
		$cat   = & $this->_getCategory();
		$quest = & $this->_getQuestion();
		$db    = & elSingleton::getObj('elDb');
		$this->_collection = $cat->getCollection(null, 'csort, cid');
		$qUniq = $quest->getUniq();
		$where = EL_WRITE <= $this->_aMode ? '' : ' WHERE status=\'1\' ';
		$sql   = 'SELECT '.implode(',', $quest->listAttrs())
						.' FROM '.$this->_tb.$where.' ORDER BY qsort, '.$qUniq;
		$db->query($sql);
		while ($r = $db->nextRecord() )
		{
			if (!empty($this->_collection[$r['cat_id']]))
			{
				$this->_collection[$r['cat_id']]->quests[$r[$qUniq]] = $quest->copy($r);
			}
		}
	}
	
	function _onInit()
	{
		$this->_tb    = 'el_faq_'.$this->pageID;
		$this->_tbCat = 'el_faq_'.$this->pageID.'_cat';
	}
	
	function &_getCategory()
	{
		$cat     = & elSingleton::getObj('elFAQCategory');
		$cat->tb = $this->_tbCat;
		$cat->setUniqAttr( (int)$this->_arg() );
		return $cat;
	}
	
	function &_getQuestion()
	{
		$q     = & elSingleton::getObj('elFAQQuestion');
		$q->tb = $this->_tb;
		$q->setUniqAttr( (int)$this->_arg() );
		return $q;
	}
}

?>