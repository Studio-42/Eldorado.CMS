<?php
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';
class elCatalogModule extends elModule
{
  var $tbc        = '';
  var $tbi        = '';
  var $tbi2c      = '';
  var $_cat       = null;
  var $_item      = null;
  var $_itemClass = '';
  var $_mMap      = array('item' => array('m'=>'viewItem') );
  var $_conf      = array(
  	'deep'              => 0,
    'catsCols'          => 1,
    'itemsCols'         => 2,
    'itemsSortID'       => 1,
    'itemsPerPage'      => 10,
    'displayCatDescrip' => 1,
    'crossLinksGroups'  => array()
    );

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  function defaultMethod()
  {
    $this->_initRenderer();
    $item = $this->_getItem();
    list($total, $current, $offset, $step) = $this->_getPagerInfo( $this->_cat->countItems());
    $this->_rnd->render( $this->_cat->getChilds( (int)$this->_conf('deep') ),
                         $item->getByCategory($this->_cat->ID, $this->_conf('itemsSortID'), $offset, $step),
                         $total,
                         $current,
                         $this->_cat->getAttr('name'),
                         $this->_cat->getAttr('descrip')
                      );
  }

  function viewItem()
  {
    $this->_item = $this->_getItem();

    if ( !$this->_item->ID )
    {
      elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
              array($this->_item->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
    }
    
    $this->_initRenderer();
    $clm = & $this->_getCrossLinksManager();
    $this->_rnd->renderItem( $this->_item, $clm->getLinkedObjects($this->_item->ID) );
  }

  function configureCrossLinks()
  {
	$clm = & $this->_getCrossLinksManager();
	if ( !$clm->confCrossLinks() )
	{
	  $this->_initRenderer();
	  return $this->_rnd->addToContent($clm->formToHtml());
	}
	elMsgBox::put( m('Configuration was saved') );
	elLocation( EL_URL );
  }

 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  function _getPagerInfo($qnt)
  {
    $cur    = 0 < $this->_arg(1) ? (int)$this->_arg(1) : 1;
    $i      = (int)$this->_conf('itemsPerPage');
    $total  = ceil($qnt/$i);
    $offset = $i*($cur-1);
    return array($total, $cur <= $total ? $cur : 1, $offset, $i);
  }

  function _initRenderer()
  {
    parent::_initRenderer();
    $this->_rnd->setCatID( $this->_cat->ID );
  }

  function _getCategory()
  {
	$cat = new elCatalogCategory();
    // $cat = elSingleton::getObj( 'elCatalogCategory' );
    $cat->tb = $this->tbc;
    $cat->tbi2c = $this->tbi2c;
    $cat->setUniqAttr( (int)$this->_arg(1) );
    if ( $cat->ID && !$cat->fetch() )
    {
    	$cat->cleanAttrs();
    }
    return $cat;
  }

  function _getItem()
  {
    $item        = elSingleton::getObj( $this->_itemClass );
    $item->tb    = $this->tbi;
    $item->tbi2c = $this->tbi2c;
    $item->setUniqAttr( (int)$this->_arg(1) );
    if ( $item->ID && !$item->fetch() )
    {
    	$item->cleanAttrs();
    }
    return $item;
  }

  function &_getCrossLinksManager()
	{
	  $clm = & elSingleton::getObj('elCatalogCrossLinksManager');
	  $clm->_conf = $this->_conf('crossLinksGroups');
	  return $clm;
	}

  function _onBeforeStop()
  {
    $this->_cat->pathToPageTitle();
    if ( $this->_item )
    {
      elAppendToPagePath( array('url'=>'item/'.$this->_cat->ID.'/'.$this->_item->ID,
      													'name'=>$this->_item->name) );
    }
    
    $mt = &elSingleton::getObj('elMetaTagsCollection'); 
    $mt->init($this->pageID, $this->_cat->ID, ($this->_item) ? $this->_item->ID : 0, $this->tbc);

  }

  function _initAdminMode()
  {
    parent::_initAdminMode();
  }

  function _onInit()
  {
    $this->tbc   = sprintf($this->tbc,   $this->pageID);
    $this->tbi   = sprintf($this->tbi,   $this->pageID);
    $this->tbi2c = sprintf($this->tbi2c, $this->pageID);

    $ID = (int)$this->_arg();
    if (!$ID)
    {
    	$ID = 1;
    }
    $this->_cat = $this->_getCategory();
    $this->_cat->setUniqAttr( $ID );
    if ( !$this->_cat->fetch() )
		{
			if (1 <> $ID)
			{
				header('HTTP/1.x 404 Not Found');
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',	array($this->_cat->getObjName(), $ID), EL_URL);
			}
			$nav = &elSingleton::getObj('elNavigator');
			if ( !$this->_cat->makeRootNode( $nav->getPageName($this->pageID) ) )
			{
				header('HTTP/1.x 404 Not Found');
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',	array($this->_cat->getObjName(), 1), EL_BASE_URL);
			}
		}
		$GLOBALS['categoryID'] = $this->_cat->ID;

		foreach ($this->_mMap as $k=>$v)
		{
			if ('conf'<>$k && 'conf_nav'<>$k)
			{
				$this->_mMap[$k]['apUrl'] = $this->_cat->ID;
			}
		}
		if (! $this->_cat->countItems())
    	{
    		unset($this->_mMap['sort'], $this->_mMap['rm_group']);
    	}

		if ('item' != $this->_mh && 'cross_links' != $this->_mh )
    	{
      		$this->_removeMethods('cross_links');
    	}
    	else
    	{
      		$this->_mMap['cross_links']['apUrl'] .= '/'.((int)$this->_arg(1)).'/';
    	}
  }

}

?>