<?php

class elModuleAdminBulletinBoard extends elModuleBulletinBoard
{
	var $_mMapAdmin = array(
		'cat_edit'     => array('m' => 'catEdit', 'ico' => 'icoCatNew', 'l' => 'New category', 'g' => 'Actions'),
		'cat_rm'       => array('m' => 'catRm'),
		'cat_clean'    => array('m' => 'catClean'),
		'sort'         => array('m' => 'catsSort'),
		'publish'      => array('m' => 'messagePublish'),
		'msg_rm'       => array('m' => 'messageRm'),
		'new'          => array('m' => 'displayAllNew'),
		'publish_all'  => array('m' => 'publishAll'),
		'rm_all'       => array('m' => 'rmAll')
		);
	
	/**
	 * Список новых неопубликованых сообщений
	 *
	 * @return void
	 **/
	function displayAllNew()
	{
		$cats  = $this->_catsList(); 
		$items = $this->_newItems(); 
		$this->_initRenderer();
		$this->_rnd->rndNew($cats, $items);
	}
	
	/**
	 * Создание/ редактирование категории
	 *
	 * @return void
	 **/
	function catEdit()
	{
		$cat = & elSingleton::getObj('elBBCategory', null, $this->_tbc);
		$cat->ID = (int)$this->_arg();
		$cat->fetch();
		if ( $cat->editAndSave() )
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent($cat->formToHtml());
	}
	
	/**
	 * Удаление пустой категории
	 *
	 * @return void
	 **/
	function catRm()
	{
		$cat = & elSingleton::getObj('elBBCategory', null, $this->_tbc);
		$cat->ID = (int)$this->_arg();
		if ( !$this->_cat->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($this->_cat->getObjName(), $catID), EL_URL);
		}
		list($items,,) = $this->_itemsList(0);
		if (sizeof($items)>0)
		{
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"', array($cat->getObjName(), $cat->name), EL_URL);
		}
		$cat->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $cat->getObjName(), $cat->name) );
		elLocation(EL_URL);
	}
	
	/**
	 * Удаление всех сообщений из категории
	 *
	 * @return void
	 **/
	function catClean()
	{
		$cat = & elSingleton::getObj('elBBCategory', null, $this->_tbc);
		$cat->ID = (int)$this->_arg();
		if ( !$this->_cat->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($this->_cat->getObjName(), $catID), EL_URL);
		}
		list($items,,) = $this->_itemsList(0);
		if (sizeof($items)==0)
		{
			elThrow(E_USER_WARNING, 'Category "%s" does not contains any items to delete', $cat->name, EL_URL);
		}
		$item = &elSingleton::getObj('elBBItem', null, $this->_tbi);
		$item->catID = $this->_cat->ID;
		$item->deleteFromCategory();
		elMsgBox::put( sprintf(m('All items from category "%s" was removed'), $cat->name) );
		elLocation(EL_URL);
	}
	
	/**
	 * Публикация сообщения
	 *
	 * @return void
	 **/
	function messagePublish()
	{
		$item = &elSingleton::getObj('elBBItem', null, $this->_tbi);
		$item->ID = (int)$this->_arg();
		if ( !$item->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($item->getObjName(), $this->_arg()), EL_URL);
		}
		$item->publish();
		elMsgBox::put( sprintf(m('Message "%s" was published'), $item->title) );
		elLocation(EL_URL.$item->catID);
	}
	
	/**
	 * Удаление сообщения
	 *
	 * @return void
	 **/
	function messageRm()
	{
		$catID = (int)$this->_arg(); 
		$this->_cat->ID = $catID; 
		if ( $catID && !$this->_cat->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($this->_cat->getObjName(), $catID), EL_URL);
		}
		$item = &elSingleton::getObj('elBBItem', null, $this->_tbi);
		$item->catID = $this->_cat->ID;
		$item->ID = (int)$this->_arg(1);
		if ( !$item->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($item->getObjName(), (int)$this->_arg(1)), EL_URL);
		}
		$item->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $item->getObjName(), $item->title) );
		elLocation(EL_URL.$this->_cat->ID);
	}
	
	/**
	 * Публикация группы сообщений
	 *
	 * @return void
	 **/
	function publishAll()
	{
		if (!empty($_POST['item_id']) && is_array($_POST['item_id']))
		{
			$itemsIDs = array_map('intval', $_POST['item_id']);
			$db = & elSingleton::getObj('elDb');
			$db->query('UPDATE '.$this->_tbi.' SET published=1 WHERE id IN ('.implode(', ', $itemsIDs).')');
			elMsgBox::put( m('Selected messages was published') );
		}
		elLocation(EL_URL.'new/');
	}
	
	/**
	 * Удаление группы сообщений
	 *
	 * @return void
	 **/
	function rmAll()
	{
		if (!empty($_POST['item_id']) && is_array($_POST['item_id']))
		{
			$itemsIDs = array_map('intval', $_POST['item_id']);
			$db = & elSingleton::getObj('elDb');
			$db->query('DELETE FROM '.$this->_tbi.' WHERE id IN ('.implode(', ', $itemsIDs).')');
			$db->optimizeTable($this->_tbi);
			elMsgBox::put( m('Selected messages was removed') );
		}
		elLocation(EL_URL.'new/');
	}
	
	/**
	 * Возвращает список новых сообщений
	 *
	 * @return array
	 **/
	function _newItems()
	{
		$item  = &elSingleton::getObj('elBBItem', null, $this->_tbi);
		$where = ' published=0 '.(!$this->_conf('rootCatAllowPosts') ? ' AND cat_id>0' : '');
		return $item->getCollectionToArray(null, $where, 'cat_id, crtime DESC');
	}
	
	function &_makeConfForm()
	{
		$form = & parent::_makeConfForm();
		$form->add( new elSelect('rootCatAllowPosts', m('Post messages to root category'), $this->_conf('rootCatAllowPosts'), $GLOBALS['yn'] ));
		$form->add( new elSelect('premoderation', m('Publish message'), $this->_conf('premoderation'), array(0=>m('Immediately'), 1=>m('After administrator allowed')) ));
		$nums = array(5, 10, 15, 20, 25, 30, 35, 40, 42, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);
		$form->add( new elSelect('itemsNumPerPage', m('Number of messages per page'), (int)$this->_conf('itemsNumPerPage'), $nums, null, false, false));
		return $form;
	}
}

?>