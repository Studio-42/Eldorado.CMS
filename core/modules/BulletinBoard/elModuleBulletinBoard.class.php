<?php

class elModuleBulletinBoard extends elModule
{
	var $newMsgsNum = 0;
	var $_mMap      = array( 'msg' => array('m' => 'message') );
	var $_tbc       = 'el_bb_%d_cat';
	var $_tbi       = 'el_bb_%d_item';
	var $_cat       = null;
	var $_user      = null;
	var $_conf      = array(
		'rootCatAllowPosts' => 0,
		'maxPostSize'       => 500,
		'premoderation'     => 1,
		'itemsNumPerPage'   => 10,
		'catsCols'          => 1,
		'itemsCols'         => 1
		);
	var $_sharedRndMembers = array('newMsgsNum');
	
	/**
	 * Показывает список категорий и сообщений
	 *
	 * @return void
	 **/
	function defaultMethod()
	{
		$catID = (int)$this->_arg(); 
		$this->_cat->ID = $catID;
		if ( $catID && !$this->_cat->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($this->_cat->getObjName(), $catID), EL_URL);
		}

		$cats  = !$this->_cat->ID ? $this->_catsList() : array();
		if ($this->_cat->ID || $this->_conf('rootCatAllowPosts'))
		{
			list($items, $pageNum, $pagesNum) = $this->_itemsList((int)$this->_arg(1));
		}
		else
		{
			$items = array();
			$pageNum = $pagesNum = 0;
		}
		$allowPost = $this->_user->isAuthed() && ($this->_cat->ID || $this->_conf('rootCatAllowPosts'));
		$this->_initRenderer();
		$this->_rnd->rndDefault($this->_cat->ID, $cats, $items, $pageNum, $pagesNum, $allowPost, $this->_user->isAuthed());
	}

	/**
	 * Создание / редактирование сообщения
	 *
	 * @return void
	 **/
	function message()
	{
		if ( !$this->_user->isAuthed() )
		{
			elThrow(E_USER_WARNING, 'Only authorized user may add messages!', null, EL_URL);
		}
		
		$catID = (int)$this->_arg(); 
		$this->_cat->ID = $catID; 
		if ( $catID && !$this->_cat->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($this->_cat->getObjName(), $catID), EL_URL);
		}
		if ( $catID==0 && !$this->_conf('rootCatAllowPosts') )
		{
			elThrow(E_USER_WARNING, 'Add message to root category not allowed!',null, EL_URL);
		}
		$item = &elSingleton::getObj('elBBItem', null, $this->_tbi);
		$item->catID = $this->_cat->ID;
		$exists = $item->ID = (int)$this->_arg(1);
		if ( $item->ID && !$item->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($item->getObjName(), (int)$this->_arg(1)), EL_URL);
		}
		
		if ( $item->ID && ($this->_aMode<EL_WRITE && $item->UID<>$this->_user->UID) )
		{
			elThrow(E_USER_WARNING, 'Only author or administrator can edit this message', null, EL_URL);
		}
		
		if (!$item->ID)
		{
			$item->UID       = $this->_user->UID;
			$item->author    = $this->_user->getFullName();
			$item->published = $this->_aMode>EL_READ || (int)!$this->_conf('premoderation');
		}
		
		if ( $item->editAndSave() )
		{
			
			elMsgBox::put( $item->published ? m('Data saved') : m('Your message will be published after moderator allowed it') );
			elLocation(EL_URL.$this->_cat->ID);
		}
		
		$this->_initRenderer();
		$this->_rnd->addToContent($item->formToHtml());
	}
	


	
	

	/**
	 * Возвращает массив категрий
	 *
	 * @return array
	 **/
	function _catsList()
	{
		$cats = $this->_cat->getCollectionToArray(null, null, 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), id');
		$db = & elSingleton::getObj('elDb');
		$sql = 'SELECT cat_id,  COUNT(id) AS num FROM '.$this->_tbi.' GROUP BY cat_id';
		$db->query($sql);
		while($r = $db->nextRecord())
		{
			$cats[$r['cat_id']]['items_num'] = $r['num'];
		}
		return $cats;
	}
	
	/**
	 * Возвращает массив сообщений для текущей стр и номер текущей стр и кол-во страниц с объявлениями
	 *
	 * @param  int  $pageNum  запрашиваемый номер стр
	 * @return array
	 **/
	function _itemsList($pageNum)
	{
		$item = &elSingleton::getObj('elBBItem', null, $this->_tbi);
		$item->catID = $this->_cat->ID;
		$where    = 'cat_id="'.$this->_cat->ID.'"'.($this->_aMode < EL_WRITE ? ' AND published>0' : '');
		$itemsNum = $item->countAll($where);
		$limit    = (int)$this->_conf('itemsNumPerPage');
		if ($limit<=0)
		{
			$limit = 25;
		}
		$pagesNum = ceil($itemsNum/$limit); 
		$pageNum  = $pageNum>0 && $pageNum<=$pagesNum ? $pageNum : 1;
		$offset   = ($pageNum-1)*$limit;
		return array($item->getCollectionToArray(null, $where, 'crtime DESC', $offset, $limit), $pageNum, $pagesNum);
	}
	

	/**
	 * Инициализация модуля
	 *
	 * @return void
	 **/
	function _onInit()
	{
		$this->_tbc = sprintf($this->_tbc, $this->pageID);
		$this->_tbi = sprintf($this->_tbi, $this->pageID);
		$this->_cat = & elSingleton::getObj('elBBCategory', null, $this->_tbc); 
		$ats = &elSingleton::getObj('elATS');
		$this->_user = & $ats->getUser();
		
		if ($this->_aMode > EL_READ)
		{
			$db = &elSingleton::getObj('elDb');
			$db->query('SELECT COUNT(id) AS num FROM '.$this->_tbi.' WHERE published=0 '.(!$this->_conf('rootCatAllowPosts') ? 'AND cat_id>0' : '' ));
			$r = $db->nextRecord();
			$this->newMsgsNum = $r['num'];
		} 
	}
	

	
	function _onBeforeStop()
	{
		if ( $this->_cat->ID )
		{
			elAppendToPagePath( array('url'=>$this->_cat->ID.'/', 'name'=>$this->_cat->name) );
		}
	}
	
}

?>