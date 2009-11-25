<?php

class elModuleAdminLinksCatalog extends elModuleLinksCatalog
{
  var $_mMapAdmin = array(
		      'edit'      => array('m'=>'editCat',   'l'=>'New category', 'ico'=>'icoCatNew', 'g'=>'Actions'),
		      'edit_item' => array('m'=>'editItem',  'l'=>'New item',     'ico'=>'icoDocNew', 'g'=>'Actions'),
		      'rm'        => array('m'=>'rmCat'),
		      'up'        => array('m'=>'moveUp'),
		      'down'      => array('m'=>'moveDown'),
		      'rm_item'   => array('m'=>'rmItem'),
		      'sort'      => array('m'=>'sortItems', 'l'=>'Sort documents in current category', 'ico'=>'icoSortAlphabet', 'g'=>'Actions')
		      );

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//
  function editCat()
  {
    $cat =  $this->_getCategory();
    $cat->fetch();
    if (!$cat->ID)
    {
    	$cat->parentID = $this->_cat->ID;
    }
    if ( !$cat->editAndSave() )
    {
      $this->_initRenderer() ;
      $this->_rnd->addToContent( $cat->formToHtml());
    }
    else
    {
      elMsgBox::put( m('Data saved') );
      elActionLog($cat, false, $this->_cat->ID, $cat->name);
      elLocation( EL_URL.$this->_cat->ID );
    }
  }

  function rmCat()
  {
    $cat = $this->_getCategory();
    if ( !$cat->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
              array($cat->getObjName(), $cat->ID),
              EL_URL.$this->_cat->ID);
    }

    if ( !$cat->isEmpty() )
    {
      elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"',
              array($cat->getObjName(),$cat->name),
              EL_URL.$this->_cat->ID);
    }
	elActionLog($cat, 'delete', false, $cat->name);
    $cat->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $cat->getObjName(), $cat->name) );
    elLocation(EL_URL.$this->_cat->ID);
  }

  function moveUp()
  {

  	$cat = $this->_getCategory();
  	if ( !$cat->ID )
  	{
  		elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
  			array($cat->getObjName(), $cat->ID),
  			EL_URL.$this->_cat->ID);
  	}
  	if ( !$cat->move() )
  	{
  		elThrow(E_USER_NOTICE, 'Can not move object "%s" "%s" up',
  			array($cat->getObjName(), $cat->name),
  			EL_URL.$this->_cat->ID );
  	}
  	elMsgBox::put( m('Data saved') );
  	elLocation(EL_URL.$this->_cat->ID);
  }

  function moveDown()
  {

  	$cat = $this->_getCategory();
  	if ( !$cat->ID )
  	{
  		elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
  			array($cat->getObjName(), $cat->ID),
  			EL_URL.$this->_cat->ID);
  	}
  	if ( !$cat->move(false) )
  	{
  		elThrow(E_USER_WARNING, 'Can not move object "%s" "%s" up',
  			array($cat->getObjName(), $cat->name),
  			EL_URL.$this->_cat->ID );
  	}
  	elMsgBox::put( m('Data saved') );
  	elLocation(EL_URL.$this->_cat->ID);
  }

  function editItem()
  {
    $item = $this->_getItem();
    if (!$item->ID)
    {
    	$item->parents = array($this->_cat->ID);
    }
    if ( !$item->editAndSave($this->_cat->getTreeToArray(0, true)) )
    {
      $this->_initRenderer();
      $this->_rnd->addToContent( $item->formToHtml() );
    }
    else
    {
      elMsgBox::put( m('Data saved') );
      elActionLog($item, false, $this->_cat->ID, $item->name);
      elLocation(EL_URL.$this->_cat->ID);
    }
  }

  function rmItem()
  {
  	$item = $this->_getItem();
  	if ( !$item->ID )
  	{
  		elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
  			array($item->getObjName(), $item->ID),
  			EL_URL.$this->_cat->ID);
  	}
    elActionLog($item, 'delete', false, $item->name);
  	$item->delete();
  	elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $item->getObjName(), $item->name) );
  	elLocation(EL_URL.$this->_cat->ID);
  }

  function sortItems()
  {
    if ( !$this->_cat->countItems() )
    {
      elThrow(E_USER_WARNING, 'There are no one documents in this category was found', null, EL_URL);
    }
    $item =  $this->_getItem();


    if ( !$item->sortItems($this->_cat->ID, $this->_conf('itemsSortID')) )
    {
    	$this->_initRenderer();
      $this->_rnd->addToContent( $item->formToHtml());
    }
    else
    {
      elMsgBox::put( m('Data saved') );
      elLocation(EL_URL.$this->_cat->ID);
    }
  }

  function rmItems()
  {
		if ( !$this->_cat->countItems() )
    {
      elThrow(E_USER_WARNING, 'There are no one documents in this category was found', null, EL_URL);
    }

    $item = $this->_getItem();
    if ( !$item->removeItems($this->_cat->ID) )
    {
    	$this->_initRenderer();
      $this->_rnd->addToContent( $item->formToHtml() );
    }
    else
    {
      elMsgBox::put( m('Selected documents was deleted') );
      elLocation(EL_URL.$this->_cat->ID);
    }
  }


 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//


  function &_makeConfForm()
  {
    $form = &parent::_makeConfForm();

    $form->add( new elSelect('deep', m('How many levels of catalog will open at once'),
            $this->_conf('deep'), array( m('All levels'), 1, 2, 3, 4 ) ) );

    $views = array( 1=>m('One column'), 2=>m('Two columns'));

    $form->add( new elSelect('catsCols', m('Categories list view'), $this->_conf('catsCols'), $views ) );
    $form->add( new elSelect('itemsCols', m('Items list view'),      $this->_conf('itemsCols'), $views ) );
    $sort = array(m('By name'), m('By publish date'), m('By priority') );
    $form->add( new elSelect('itemsSortID', m('Sort documents by'), (int)$this->_conf('itemsSortID'), $sort) );
    $nums = array(m('All'), 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 40=>40, 50=>50, 100=>100);
    $form->add( new elSelect('itemsPerPage', m('Number of documents per page'), $this->_conf('itemsPerPage'), $nums ) );
		$form->add( new elSelect('displayCatDescrip', m('Display current category description'),
    						$this->_conf('displayCatDescrip'), $GLOBALS['yn']) );
    return $form;
  }

  function _onInit()
  {
    parent::_onInit();
    if (! $this->_cat->countItems())
    {
    	unset($this->_mMap['sort'], $this->_mMap['rm_group']);
    }
  }


}

?>