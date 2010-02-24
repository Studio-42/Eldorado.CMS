<?php
elAddJs('jquery.js', EL_JS_CSS_FILE);
class elModuleAdminFAQ extends elModuleFAQ
{
	var $_mMapConf   = array();
	var $_mMapAdmin  = array(
		'cat_edit'   => array('m' => 'editCategory',   'g'=>'Actions', 'l' => 'New category', 'ico' => 'icoCatNew' ),
		'cat_rm'     => array('m' => 'rmCategory'),
		'quest_edit' => array('m' => 'askQuestion',    'g'=>'Actions', 'l' => 'New question', 'ico' => 'icoNew' ),
		'quest_rm'   => array('m' => 'rmQuestion'),
		'cat_sort'   => array('m' => 'sortCategories', 'g'=>'Actions', 'l' => 'Categories sort', 'ico' => 'icoSortNumeric' ),
		'quest_sort' => array('m' => 'sortQuestions')
		);

	function editCategory()
	{
		$cat = & $this->_getCategory();
		$cat->fetch();
		if ($cat->editAndSave())
		{
			elMsgBox::put(m('Data saved'));
			elActionLog($cat, false, '', $cat->name);
			elLocation(EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $cat->formToHtml() );
	}

  function rmCategory()
	{
		$cat = & $this->_getCategory();
		if ( !$cat->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
		  				array($cat->getObjName(), $cat->getUniqAttr()), EL_URL);
		}
		$db = & elSingleton::getObj('elDb');
		$db->query('SELECT COUNT(*) AS num FROM '.$this->_tb.' WHERE cat_id='.$cat->ID);
		$r = $db->nextRecord();
		if ( $r['num'] )
		{
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"',
              array($cat->getObjName(), $cat->getAttr('cname')), EL_URL);
		}
		elActionLog($cat, 'delete', false, $cat->name);
		$cat->delete();
		elMsgBox::put(sprintf(m('Object "%s" "%s" was deleted'),
			$cat->getObjName(), $cat->getAttr('cname')) );
		elLocation(EL_URL);
	}


	function editQuestion()
	{
		$this->askQuestion(true);
	}

	function rmQuestion()
	{
		$q = & $this->_getQuestion();
		if ( !$q->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
		  				array($q->getObjName(), $q->getUniqAttr()), EL_URL);
		}
		elActionLog($q, 'delete', false, $q->question);
		$q->delete();
		$name = substr($q->getAttr('question'), 0, 25).'...';
		elMsgBox::put(sprintf(m('Object "%s" "%s" was deleted'), $q->getObjName(), $name) );
		elLocation(EL_URL);
	}

	function sortCategories()
	{
		$this->_loadCollection();
		$this->_makeSort($this->_collection);
		// elActionLog($this->_collection, 'sort', '', false);
	}

	function sortQuestions()
	{
		$this->_loadCollection();
		$ID = (int)$this->_arg();
		if (empty($this->_collection[$ID]))
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
		  				array(m('Category'), $ID), EL_URL);
		}
		if (empty($this->_collection[$ID]->quests))
		{
			elThrow(E_USER_WARNING, 'Category "%s" does contains any questions',
		  				$this->_collection[$ID]->getAttr('cname'), EL_URL);
		}
		$this->_makeSort($this->_collection[$ID]->quests, false);
		// elActionLog($this->_collection, 'sort', '', false);
	}


	//*******************************************************//
	//            		 PRIVATE METHODS                  		 //
	//*******************************************************//

	function _initAdminMode()
	{
		parent::_initAdminMode();
		//unset($this->_mMap['ask']);
	}

	function _makeSort($objList, $isObjCat=true)
	{
		if ($isObjCat)
		{
			$l  = m('FAQ categories sorting');
			$c  = m('To change categories display order set sort indexes');
			$f  = 'csort';
			$id = 'cid';
			$tb = $this->_tbCat;
		}
		else
		{
			$l  = m('Questions sorting');
			$c  = m('To change question display order set sort indexes');
			$f  = 'qsort';
			$id = 'id';
			$tb = $this->_tb;
		}
		$form = & elSingleton::getObj('elForm');
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->setLabel( $l );
		$form->add( new elCData('comment', $c), array('cellAttrs'=>'class="formSubheader"') );
		$attrs = array('size'=>6);

		foreach ($objList as $ID=>$obj )
		{
			$name = $isObjCat ? $obj->getAttr('cname') : $obj->getAttr('question');
			$form->add( new elText('sort['.$ID.']', $name, $obj->getAttr($f), $attrs) );
		}
		if ( $form->isSubmitAndValid() )
		{
			$data = $form->getValue(); //elPrintR($data);
			$db = & elSingleton::getObj('elDb');
			foreach ($data['sort'] as $ID=>$sortNdx)
			{
				$db->query('UPDATE '.$tb.' SET '.$f.'=\''.intval($sortNdx).'\' WHERE '.$id.'=\''.$ID.'\'');
			}
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $form->toHtml() );
	}


}

?>