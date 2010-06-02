<?php

define('IS_VIEW_CATS',     1);
define('IS_VIEW_MNFS',     2);

define('EL_IS_USE_MNF',    1);
define('EL_IS_USE_TM',     2);
define('EL_IS_USE_MNF_TM', 3);

define('EL_IS_SORT_NAME',  1);
define('EL_IS_SORT_CODE',  2);
define('EL_IS_SORT_PRICE', 3);
define('EL_IS_SORT_TIME',  4);

if (!defined('EL_CAT_DESCRIP_NO')) {
	define('EL_CAT_DESCRIP_NO',      0);
}
if (!defined('EL_CAT_DESCRIP_IN_LIST')) {
	define('EL_CAT_DESCRIP_IN_LIST', 1);
}
if (!defined('EL_CAT_DESCRIP_IN_SELF')) {
	define('EL_CAT_DESCRIP_IN_SELF', 2);
}
if (!defined('EL_CAT_DESCRIP_IN_BOTH')) {
	define('EL_CAT_DESCRIP_IN_BOTH', 3);
}



class elModuleIShop extends elModule {
	var $_factory   = null;
	var $_cat       = null;
	var $_item      = null;
	var $_jslib     = true;
	var $_mMap      = array(
		'item'  => array('m' => 'viewItem'),
		'order' => array('m' => 'order') 
	);

	var $_conf      = array(
		'default_view'      => IS_VIEW_CATS,
		'deep'              => 0,
		'catsCols'          => 1,
		'itemsCols'         => 1,
		'itemsSortID'       => EL_IS_SORT_NAME,
		'itemsPerPage'      => 10,
		'displayCatDescrip' => EL_CAT_DESCRIP_IN_LIST,
		'displayCode'       => 1,
		'mnfNfo'            => EL_IS_USE_MNF,
		'tmbListSize'       => 125,
		'tmbListPos'        => EL_POS_LEFT,
		'tmbItemCardSize'   => 250,
		'tmbItemCardPos'    => EL_POS_LEFT,
		'crossLinksGroups'  => array(),
		'search'            => 1,
		'searchOnAllPages'  => 1,
		'searchColumnsNum'  => 3,
		'searchTitle'       => '',
		'searchTypesLabel'  => '',
		'currency'          => '',
		'exchangeSrc'       => 'auto',
		'commision'         => 0,
		'rate'              => 1,
	    'pricePrec'         => 0
	);
 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//
 

  function defaultMethod()
  {
    $this->_initRenderer();
    if ( $this->_conf('search') && ( $this->_conf('searchOnAllPages') || $this->_cat->ID == 1 ) )
    {
        $sm = $this->_factory->getSearchManager();
        if ( $sm->isConfigured() )
        {
          $this->_rnd->rndSearchForm( $sm->formToHtml(), $this->_conf('searchTitle') );
          if ( $sm->hasSearchCriteria() )
          {
            if ( $sm->find() )
            {
              return $this->_rnd->rndSearchResult( $sm->getResult() );
            }
            else
            {
              elThrow(E_USER_WARNING, 'Nothing was found on this request');
            }
          }  
        }
        
    }

    list($total, $current, $offset, $step) = $this->_getPagerInfo( $this->_cat->countItems() );
    $this->_rnd->render( $this->_cat->getChilds( (int)$this->_conf('deep') ),
                         $this->_factory->getItems( $this->_cat->ID, $this->_conf('itemsSortID'), $offset, $step ),
                         $total,
                         $current,
                         $this->_cat
                      );
  }

	// add items to icart from external call (now used from OrderHistory)
	function addToICart($itemID = null, $props = array(), $qnt = false)
	{
		return $this->_addToICart($itemID, $props, $qnt);
	}


	/**
	 * Add item into shopping cart
	 *
	 * @return void
	 **/
	function order() {
		$catID  = (int)$this->_arg();
		$itemID = (int)$this->_arg(1);
		$url    = EL_URL.'item/'.$catID.'/'.$itemID.'/';

		// elPrintR($data);
		elLoadMessages('ServiceICart');
		$ICart = & elSingleton::getObj('elICart');

		if ($this->_addToICart($itemID)) {
			$msg = sprintf( m('Item %s was added to Your shopping cart. To proceed order right now go to <a href="%s">this link</a>'), $data['code'].' '.$data['name'], EL_URL.'__icart__/' );
	        elMsgBox::put($msg);
			elLocation($url);
		} else {
			$msg = sprintf( m('Error! Could not add item to Your shopping cart! Please contact site administrator.') );
            elThrow(E_USER_WARNING, $msg, null, $url);
		}
	}

  function viewItem()
  {
    $this->_item = $this->_factory->getItem( (int)$this->_arg(1) );
    if ( !$this->_item->ID )
    {
      elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
              array($this->_item->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
    }
    
    $this->_initRenderer();
    $clm = & $this->_getCrossLinksManager();
    $this->_rnd->renderItem( $this->_item, $clm->getLinkedObjects($this->_item->ID) );
  }

  function toXML()
  {
    $act = $this->_arg();
    if ( 'depend' == $act )
    {
      $itemID  = (int)$this->_arg(1);
      $propID  = (int)$this->_arg(2);
      $propVal = $this->_arg(3);
      $item    = $this->_factory->getItem( $itemID ); //elPrintR($item);
      if ( !$item->ID )
      {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?><error>"
                ."No item  $itemID $propID $propVal</error>";
      }
      $xml = $item->getDependValues($propID, $propVal);
    }
    elseif ( 'search_form' == $act )
    {
      $sm = $this->_factory->getSearchManager();
      $xml = $sm->formToXml((int)$this->_arg(1));
    }
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?>"
			.$xml."";
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

	function _addToICart($itemID = null, $props = array(), $qnt = 1)
	{
		if (!$itemID)
		{
			return false;
		}
		$item = $this->_factory->getItem($itemID);
		if (!$item->ID or !$item->price or $qnt < 1)
		{
			return false;
		}

		// if $props is empty try to load from POST
		if (!empty($_POST['prop']) && is_array($_POST['prop']) && empty($props)) {
			foreach ($_POST['prop'] as $pID => $v) {
				$props[] = array($item->getPropName($pID), $v);
			}
		}

		$currency = &elSingleton::getObj('elCurrency');
		$curOpts = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate')
			);
		$data = array(
			'page_id' => $this->pageID,
			'i_id'    => $item->ID,
			'm_id'    => 0,
			'code'    => $this->_conf('displayCode') ? $item->code : '',
			'name'    => $item->name,
			'price'   => $currency->convert($item->price, $curOpts),
			'props'   => $props
		);

		$ICart = & elSingleton::getObj('elICart');
		for ($i = 0; $i < $qnt; $i++)
		{
			if (!$ICart->add($data))
			{
				return false;
			}
		}
		return true;
	}

  function _getPagerInfo($qnt)
  {
    $cur    = 0 < $this->_arg(1) ? (int)$this->_arg(1) : 1;
    $i      = 0 < $this->_conf('itemsPerPage') ? (int)$this->_conf('itemsPerPage') : 10;
    $total  = ceil($qnt/$i);
    $offset = $i*($cur-1);
    return array($total, $cur <= $total ? $cur : 1, $offset, $i);
  }

  function _initRenderer()
  {
    parent::_initRenderer();
    $this->_rnd->setCatID( $this->_cat->ID );
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
    $mt->init($this->pageID, $this->_cat->ID, ($this->_item) ? $this->_item->ID : 0, $this->_factory->tb('tbc'));
  }


  /**
   * Создает  фабрику
   * тут а не в _onInit() потому что в методы для редактирования товаров
   * должны быть добавлены в _initAdmin() дочернего объекта,
   * который вызывается до _onInit()
   *
   */
  function _initNormal()
  {
    parent::_initNormal();
    $this->_factory = & elSingleton::getObj('elIShopFactory');
    $this->_factory->init($this->pageID, $this->_conf);
  }

    /**
   * Создает текущую категорию
   * если категории с  требуемым id нет - редирект на корень каталога
   * если нет корневой категории - пытается создать - в случае неудачи
   * - сообщает об ошибке и редиректит на корень сайта
   *
   */
	function _onInit() {
    $catID = $this->_arg(0) ;
    if ( $catID <= 0 )
    {
      $catID = 1;
    }

    $this->_cat = $this->_factory->getCategory($catID);

    if ( empty($this->_cat->ID) )
		{
		  if (1 <> $catID)
		  {
			header('HTTP/1.x 404 Not Found'); 
		    elLocation(EL_URL);
		  }

		  $nav = & elSingleton::getObj('elNavigator');
			if ( !$this->_cat->makeRootNode( $nav->getPageName($this->pageID) ) )
			{
			  elThrow(EL_USER_ERROR, 'Critical error in module! %s', m('Could not create root category! Check DB tables!'), EL_BASE_URL);
			}
		}
		$GLOBALS['categoryID'] = $this->_cat->ID;
		
		$cur  = &elSingleton::getObj('elCurrency');
		
		if (empty($this->_conf['currency'])) {
			$conf = & elSingleton::getObj('elXmlConf');
			$this->_conf['currency'] = $cur->current['intCode'];
			$conf->set('currency', $cur->current['intCode'], $this->pageID);
			$conf->save();
		}
		
		if ($this->_conf['currency'] != $cur->current['intCode'] 
		&& $this->_conf('exchangeSrc') == 'manual' 
		&& !($this->_conf('rate') > 0)) {
			$conf = & elSingleton::getObj('elXmlConf');
			$conf->set('exchangeSrc', 'auto', $this->pageID);
			$conf->set('rate',         0,     $this->pageID);
			$conf->save();
			$cur->updateConf();
			$this->_conf['exchangeSrc'] = 'auto';
			$this->_conf['rate']        = 0;
		}
  }

}

?>
