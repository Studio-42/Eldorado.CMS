<?php
// object types
define ('EL_IS_CAT',      1);
define ('EL_IS_ITEM',     2);
define ('EL_IS_ITYPE',    3);
define ('EL_IS_PROP',     4);
define ('EL_IS_MNF',      5);
define ('EL_IS_TM',       6);
// views 
define('EL_IS_VIEW_CATS',     1);
define('EL_IS_VIEW_MNFS',     2);
// sort item variants
define('EL_IS_SORT_NAME',  1);
define('EL_IS_SORT_CODE',  2);
define('EL_IS_SORT_PRICE', 3);
define('EL_IS_SORT_TIME',  4);

/**
 * Internet shop module
 *
 * @package IShop
 **/
class elModuleIShop extends elModule {
	var $_factory   = null;
	var $_url       = EL_URL;
	var $_cat       = null;
	var $_mnf       = null;
	var $_item      = null;
	var $_jslib     = true;
	var $_mMap      = array(
		'cats'  => array('m' => 'viewCategories'),
		'mnfs'  => array('m' => 'viewManufacturers'),
		'mnf'   => array('m' => 'viewManufacturer'),
		'tm'    => array('m' => 'viewTrademark'),
		'item'  => array('m' => 'viewItem'),
		'order' => array('m' => 'order') 
	);

	var $_conf      = array(
		'default_view'      => EL_IS_VIEW_MNFS,
		'deep'              => 0,
		'catsCols'          => 1,
		'itemsCols'         => 1,
		'mnfsCols'          => 1,
		'tmsCols'           => 2,
		'itemsSortID'       => EL_IS_SORT_NAME,
		'itemsPerPage'      => 10,
		'displayEmptyMnf'   => 1,
		'displayEmptyTm'    => 1,
		'displayCatDescrip' => EL_CAT_DESCRIP_IN_LIST,
		'displayMnfDescrip' => EL_CAT_DESCRIP_IN_SELF,
		'displayTMDescrip'  => EL_CAT_DESCRIP_IN_SELF,
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
	
	var $_view;
	/**
	 * shared with render members
	 *
	 * @var array
	 **/
	var $_sharedRndMembers = array('_view', '_cat', '_mnf');
 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//
 
	/**
	 * display categories or manufacturers according to config
	 *
	 * @return void
	 **/
	function defaultMethod() {
		// elPrintR($this->_args);
		$this->_view == EL_IS_VIEW_MNFS ? $this->viewManufacturers() : $this->viewCategories();
		return;
		// $this->_initRenderer();
		// if ( $this->_conf('search') && ( $this->_conf('searchOnAllPages') || $this->_cat->ID == 1 ) ) {
		// $sm = $this->_factory->getSearchManager();
		// if ( $sm->isConfigured() )
		// {
		// $this->_rnd->rndSearchForm( $sm->formToHtml(), $this->_conf('searchTitle') );
		// if ( $sm->hasSearchCriteria() )
		// {
		// if ( $sm->find() )
		// {
		// return $this->_rnd->rndSearchResult( $sm->getResult() );
		// }
		// else
		// {
		// elThrow(E_USER_WARNING, 'Nothing was found on this request');
		// }
		// }  
		// }
		// 
		// }

	}

	/**
	 * display categories
	 *
	 * @return void
	 **/
	function viewCategories() {
		
		$this->_initRenderer();

		if (!$this->_cat->ID) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such category', null, EL_URL);
		}
		
		$c = & elSingleton::getObj('elIShopItemsCollection');
		
		list($total, $current, $offset, $step) = $this->_getPagerInfo($c->count(EL_IS_CAT, $this->_cat->ID), (int)$this->_arg(1));
		
		if (!$current) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such page', null, $this->_url.$this->_cat->ID);
		}

		$this->_rnd->render( 
				$this->_cat->getChilds((int)$this->_conf('deep')),
		        $c->create(EL_IS_CAT, $this->_cat->ID, $offset, $step),
		        $total,
		        $current,
		        $this->_cat
		      );
	}


	/**
	 * display manufacturers 
	 *
	 * @return void
	 **/
	function viewManufacturers() {
		$this->_initRenderer();
		$this->_rnd->rndMnfs($this->_factory->getAllFromRegistry(EL_IS_MNF));
	}

	/**
	 * Display manufacturer
	 *
	 * @return void
	 **/
	function viewManufacturer() {

		if(!$this->_mnf->ID) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such manufacturer',	null, EL_URL);
		}
		
		$c = & elSingleton::getObj('elIShopItemsCollection');
		list($total, $current, $offset, $step) = $this->_getPagerInfo($c->count(EL_IS_MNF, $this->_mnf->ID), (int)$this->_arg(1));

		if (!$current) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such page', null, $this->_url.$this->_mnf->ID);
		}
		
		$this->_initRenderer();
		$this->_rnd->rndMnf($this->_mnf, $c->create(EL_IS_MNF, $this->_mnf->ID, $offset, $step), $total, $current);
		
		elAppendToPagePath(array(
			'url'  => $this->_url.'mnf/'.$this->mnf->ID.'/',	
			'name' => $this->_mnf->name)
			);
	}

	/**
	 * Display trademark
	 *
	 * @return void
	 **/
	function viewTrademark() {
		
		$tm = $this->_factory->create(EL_IS_TM, $this->_arg());
		if (!$tm->ID) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such category', null, EL_URL);
		}
		$this->_mnf->ID = $tm->mnfID;

		if(!$this->_mnf->fetch()) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such manufacturer',	null, EL_URL);
		}
		
		$c = & elSingleton::getObj('elIShopItemsCollection');
		list($total, $current, $offset, $step) = $this->_getPagerInfo($c->count(EL_IS_TM, $tm->ID), (int)$this->_arg(1));

		$this->_initRenderer();
		$this->_rnd->rndTm($this->_mnf, $tm, $c->create(EL_IS_TM, $tm->ID, $offset, $step), $total, $current);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function viewItem() {
		// elPrintR($this->_args);
		$item = $this->_factory->create(EL_IS_ITEM, $this->_arg(1));
		// elPrintR($item);
		// elPrintR($item);
		$this->_initRenderer();
		$this->_rnd->rndItem($item);
	}

	/**
	 * add items to icart from external call (now used from OrderHistory)
	 *
	 * @return void
	 **/
	function addToICart($itemID = null, $props = array(), $qnt = false) {
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

		elLoadMessages('ServiceICart');
		$ICart = & elSingleton::getObj('elICart');

		if ($this->_addToICart($itemID)) {
			$item = $this->_factory->getItem($itemID);
			$msg = sprintf(m('Item %s was added to Your shopping cart. To proceed order right now go to <a href="%s">this link</a>'), $item->code.' '.$item->name, EL_URL.'__icart__/' );
			elMsgBox::put($msg);
			elLocation($url);
		} else {
			$msg = sprintf(m('Error! Could not add item to Your shopping cart! Please contact site administrator.'));
			elThrow(E_USER_WARNING, $msg, null, $url);
		}
	}

  function _viewItem()
  {

    $this->_item = $this->_factory->create(EL_IS_ITEM, $this->_arg(1) );
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



 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

	/**
	 * add to icart routine
	 *
	 * @return bool
	 **/
	function _addToICart($itemID = null, $props = array(), $qnt = 1) {
	
		$item = $this->_factory->getItem((int)$itemID);
		if (!$item->ID or !$item->price or $qnt < 1) {
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
		for ($i = 0; $i < $qnt; $i++) {
			if (!$ICart->add($data)) {
				return false;
			}
		}
		return true;
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
	 * return info about current page for items list
	 *
	 * @param  int   $count  total number of items
	 * @param  int   current page number
	 * @return array
	 **/
	function _getPagerInfo($count, $current) {
		if (!$current) {
			$current = 1;
		}
		$step = (int)$this->_conf('itemsPerPage');
		$total  = $count > 0 ? ceil($count/$step) : 1;
		$offset = $step*($current-1);
		return array($total, $current <= $total ? $current : 0, $offset, $step);
	}
	

	/**
	* create factory (here because list of types required in _initAdmin() wich called before _onInit())
	* check required view
	*
	* @return void
	*/
	function _initNormal() {
		parent::_initNormal();
		if ($this->_conf['default_view'] != EL_IS_VIEW_CATS && $this->_conf['default_view'] != EL_IS_VIEW_MNFS) {
			$this->_conf['default_view'] = EL_IS_VIEW_CATS;
		}
		$h = $this->_arg();
		$this->_view = $this->_conf('default_view');

		if (($this->_view == EL_IS_VIEW_CATS && $h == 'mnfs')
		|| 	($this->_view == EL_IS_VIEW_MNFS && $h == 'cats')) {
			$this->_view = $this->_view == EL_IS_VIEW_MNFS ? EL_IS_VIEW_CATS : EL_IS_VIEW_MNFS;
			array_shift($this->_args);
		}
		$this->_factory = & elSingleton::getObj('elIShopFactory');
		$this->_factory->init($this->pageID, $this->_conf);
	}

	/**
	 * create category and manufacturer and check currency exchange config
	 *
	 * @return void
	 **/
	function _onInit() {
		$this->_cat = $this->_factory->create(EL_IS_CAT, 1);
		$this->_mnf = $this->_factory->create(EL_IS_MNF);

		if ($this->_view == EL_IS_VIEW_CATS) {
			$this->_cat->idAttr($this->_arg(0));
		} else {
			$this->_mnf->idAttr($this->_arg(0));
			$this->_mnf->fetch();
		}
		$this->_cat->fetch();
		$GLOBALS['categoryID'] = $this->_cat->ID;

		if ($this->_view != $this->_conf('default_view')) {
			$this->_url = EL_URL.($this->_view == EL_IS_VIEW_MNFS ? 'mnfs/' : 'cats/');
		}
		
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
		
		if ($this->_conf('itemsPerPage') < 1) {
			$this->_conf['itemsPerPage'] = 10;
		}
	}

} // END class

?>
