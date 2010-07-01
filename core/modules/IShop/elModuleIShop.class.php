<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'constants.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';

/**
 * Internet shop module
 *
 * @package IShop
 **/
class elModuleIShop extends elModule {
	var $_factory   = null;
	var $_url       = EL_URL;
	var $_urlCats   = '';
	var $_urlMnfs   = '';
	var $_cat       = null;
	var $_mnf       = null;
	var $_item      = null;
	var $_jslib     = true;
	var $_mMap      = array(
		'cats'          => array('m' => 'viewCategories'),
		'mnfs'          => array('m' => 'viewManufacturers'),
		'mnf'           => array('m' => 'viewManufacturer'),
		'tm'            => array('m' => 'viewTrademark'),
		'item'          => array('m' => 'viewItem'),
		'search'        => array('m' => 'search'),
		'search_params' => array('m' => 'searchParams'),
		'order'         => array('m' => 'order') 
	);

	var $_conf      = array(
		'default_view'      => EL_IS_VIEW_CATS,
		'displayViewSwitch' => 0,
		'deep'              => 0,
		'catsCols'          => 1,
		'displayCatDescrip' => EL_CAT_DESCRIP_IN_LIST,
		'mnfsCols'          => 1,
		'displayEmptyMnf'   => 0,
		'displayMnfDescrip' => EL_CAT_DESCRIP_IN_SELF,
		'tmsCols'           => 1,
		'displayEmptyTm'    => 0,
		'displayTMDescrip'  => EL_CAT_DESCRIP_IN_SELF,
		'itemsCols'         => 1,
		'itemsSortID'       => EL_IS_SORT_NAME,
		'itemsPerPage'      => 10,
		'displayCode'       => 1,
		'tmbListSize'       => 125,
		'tmbItemCardSize'   => 250,
		'sliderSize'        => 4,
		'crossLinksGroups'  => array(),
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
	var $_sharedRndMembers = array('_view', '_cat', '_mnf', '_url', '_urlCats', '_urlMnfs');
 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//
 
	/**
	 * display categories or manufacturers according to config
	 *
	 * @return void
	 **/
	function defaultMethod() {
		$this->_view == EL_IS_VIEW_MNFS ? $this->viewManufacturers() : $this->viewCategories();
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
		
		$c = & elSingleton::getObj('elIShopItemsCollection', $this->pageID);
		
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
		
		$mt = &elSingleton::getObj('elMetaTagsCollection');  
	    $mt->init($this->pageID, $this->_cat->ID, 0, $this->_factory->tb('tbc'));
	}


	/**
	 * display manufacturers 
	 *
	 * @return void
	 **/
	function viewManufacturers() {
		$this->_initRenderer();
		$this->_rnd->rndMnfs($this->_factory->getAllFromRegistry(EL_IS_MNF));
		$mt = &elSingleton::getObj('elMetaTagsCollection');  
	    $mt->init($this->pageID, $this->_cat->ID, 0, $this->_factory->tb('tbc'));
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
		
		$c = & elSingleton::getObj('elIShopItemsCollection', $this->pageID);
		list($total, $current, $offset, $step) = $this->_getPagerInfo($c->count(EL_IS_MNF, $this->_mnf->ID), (int)$this->_arg(1));

		if (!$current) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such page', null, $this->_url.$this->_mnf->ID);
		}
		
		$this->_initRenderer();
		$this->_rnd->rndMnf($this->_mnf, $c->create(EL_IS_MNF, $this->_mnf->ID, $offset, $step), $total, $current);
		
		elAppendToPagePath(array(
			'url'  => $this->_urlMnfs.'mnf/'.$this->_mnf->ID.'/',	
			'name' => $this->_mnf->name)
			);
		$mt = &elSingleton::getObj('elMetaTagsCollection');  
	    $mt->init($this->pageID, $this->_cat->ID, 0, $this->_factory->tb('tbc'));
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
		
		$c = & elSingleton::getObj('elIShopItemsCollection', $this->pageID);
		list($total, $current, $offset, $step) = $this->_getPagerInfo($c->count(EL_IS_TM, $tm->ID), (int)$this->_arg(1));

		$this->_initRenderer();
		$this->_rnd->rndTm($this->_mnf, $tm, $c->create(EL_IS_TM, $tm->ID, $offset, $step), $total, $current);
		elAppendToPagePath(array(
			'url'  => $this->_urlMnfs.'mnf/'.$this->_mnf->ID.'/',	
			'name' => $this->_mnf->name)
			);
		elAppendToPagePath(array(
			'url'  => $this->_urlMnfs.'tm/'.$tm->ID.'/',	
			'name' => $tm->name)
			);
		$mt = &elSingleton::getObj('elMetaTagsCollection');  
	    $mt->init($this->pageID, $this->_cat->ID, 0, $this->_factory->tb('tbc'));
	}

	/**
	 * display one item
	 *
	 * @return void
	 **/
	function viewItem() {
		$item = $this->_factory->create(EL_IS_ITEM, $this->_arg(1));
		// $clm = & $this->_getCrossLinksManager();
		// $cl = $clm->getLinkedObjects($item->ID) 
		$this->_initRenderer();
		$this->_rnd->rndItem($item);
		
		if ($this->_view == EL_IS_VIEW_CATS) {
			$this->_cat->pathToPageTitle();
		} else {
			$mnf = $item->getMnf();
			elAppendToPagePath(array(
				'url'  => $this->_urlMnfs.'mnf/'.$mnf->ID.'/',	
				'name' => $mnf->name)
				);

			if ($this->_arg(2) == 'tm') {
				$tm = $item->getTm();
				elAppendToPagePath(array(
					'url'  => $this->_urlMnfs.'tm/'.$tm->ID.'/',	
					'name' => $tm->name)
					);
			}
		}
		// $mt = &elSingleton::getObj('elMetaTagsCollection');  
	    // $mt->init($this->pageID, $this->_cat->ID, $this->_item->ID, $this->_factory->tb('tbc'));
	}

	/**
	 * display finded items
	 *
	 * @return void
	 **/
	function search() {
		$finder = & elSingleton::getObj('elIShopFinder', $this->pageID);
		if (!$finder->isConfigured() || !$finder->formIsSubmit) {
			elLocation(EL_URL);
		}
		$c = & elSingleton::getObj('elIShopItemsCollection', $this->pageID);
		$this->_initRenderer();
		$this->_rnd->rndSearchResult($c->create('search', $finder->find()));
		
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function searchParams() {
		include_once EL_DIR_CORE.'lib/elJSON.class.php';
		// elPrintr($_GET);
		$finder = & elSingleton::getObj('elIShopFinder', $this->pageID);
		if (!$finder->isConfigured()) {
			exit(elJSON::encode(array('error' => 'Search form is not configured')));
		} elseif (empty($_GET['name']) || !isset($_GET['value'])) {
			exit(elJSON::encode(array('error' => 'Invalid arguments')));
		}
		
		exit(elJSON::encode($finder->getParams($_GET['name'], $_GET['value'])));
		
		exit(elJSON::encode(array('result' => 'OK')));
		
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

	/**
	 * Return Item URL by item ID
	 *
	 * @param   int    $itemID
	 * @return  string
	 **/
	function getItemUrl($itemID = null) {
		$item = $this->_factory->create(EL_IS_ITEM, (int)$itemID);
		if (!$item->ID) {
			return false;
		}
		
		if ($this->_conf('default_view') == EL_IS_VIEW_CATS) {
			$db = & elSingleton::getObj('elDb');
			$db->query(sprintf('SELECT c_id FROM %s WHERE i_id=%d LIMIT 1', $item->tbi2c, $item->ID));
			$r = $db->nextRecord();
			$path = 'item/'.$r['c_id'].'/'.$item->ID;
		} else {
			$path = 'item/'.$item->mnfID.'/'.$item->ID;
		}
		$nav = & elSingleton::getObj('elNavigator');
		return $nav->getPageURL($this->pageID).$path;
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

	/**
	 * create elCatalogCrossLinksManager object and cofigure it
	 *
	 * @return elCatalogCrossLinksManager
	 **/
	function &_getCrossLinksManager() {
		$clm = & elSingleton::getObj('elCatalogCrossLinksManager');
		$clm->_conf = $this->_conf('crossLinksGroups');
		return $clm;
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
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function _onBeforeStop_() {
		
		if ($this->_mh == 'item' && $this->_conf('searchFormOnItemPage')) {
			echo 'item search';
		} elseif ((in_array($this->_mh, array('mnf', 'tm')) || (!$this->_mh && $this->_cat->ID>1)) && $this->_conf('searchFormOnListPage')) {
			echo 'list search';
		} elseif (!$this->_mh && $this->_conf('searchFormOnDefaultPage')) {
			$this->_loadFinder();
			// elPrintr($this->_finder);
			$this->_initRenderer();
			$this->_rnd->addToContent($this->_finder->formToHtml());

		}
		
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

		$this->_factory = & elSingleton::getObj('elIShopFactory', $this->pageID);

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
		
		if ($this->_conf('default_view') == EL_IS_VIEW_CATS) {
			$this->_urlCats = EL_URL;
			$this->_urlMnfs = EL_URL.'mnfs/';
		} else {
			$this->_urlCats = EL_URL.'cats/';
			$this->_urlMnfs = EL_URL;
		}
		
		$this->_cat->fetch();
		$GLOBALS['categoryID'] = $this->_cat->ID;

		if ($this->_view != $this->_conf('default_view')) {
			$this->_url = EL_URL.($this->_view == EL_IS_VIEW_MNFS ? 'mnfs/' : 'cats/');
		}
		
		// $this->_finder = & new elIShopFinder($this->pageID);
		
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
