<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'constants.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';

/**
 * Internet shop module
 *
 * @package IShop
 **/
class elModuleIShop extends elModule {
	/**
	 * factory
	 *
	 * @var elIShopFactory
	 **/
	var $_factory = null;
	/**
	 * All products number
	 *
	 * @var int
	 **/
	var $itemsNum = 0;
	/**
	 * Current base url
	 * @todo kill ?
	 * @var string
	 **/
	var $_url = EL_URL;
	/**
	 * Categories url
	 *
	 * @var string
	 **/
	var $_urlCats = '';
	/**
	 * Manufacturers url
	 *
	 * @var string
	 **/
	var $_urlMnfs = '';
	/**
	 * Products tyes url
	 *
	 * @var string
	 **/
	var $_urlTypes = '';
	/**
	 * Current category
	 *
	 * @var elCatalogCategory
	 **/
	var $_cat = null;
	/**
	 * Current manufacturer
	 *
	 * @var elIShopManufacturer
	 **/
	var $_mnf = null;
	/**
	 * Current product type
	 *
	 * @var elIShopItemType
	 **/
	var $_type = null;
	var $_item      = null;
	var $_jslib     = true;
	var $_viewMap   = array(
		'cats'  => EL_IS_VIEW_CATS,
		'mnfs'  => EL_IS_VIEW_MNFS,
		'types' => EL_IS_VIEW_TYPES
		);
	var $_mMap      = array(
		'cats'          => array('m' => 'viewCategories'),
		'mnfs'          => array('m' => 'viewManufacturers'),
		'types'         => array('m' => 'viewTypes'),
		'mnf'           => array('m' => 'viewManufacturer'),
		'tm'            => array('m' => 'viewTrademark'),
		'type'          => array('m' => 'viewType'),
		'item'          => array('m' => 'viewItem'),
		'search'        => array('m' => 'search'),
		'search_params' => array('m' => 'searchParams'),
		'order'         => array('m' => 'order') 
	);

	var $_conf      = array(
		'default_view'       => EL_IS_VIEW_CATS,
		'displayViewSwitch'  => 0,
		'deep'               => 0,
		'catsCols'           => 1,
		'displayCatDescrip'  => EL_CAT_DESCRIP_IN_LIST,
		'mnfsCols'           => 1,
		'displayEmptyMnf'    => 0,
		'displayMnfDescrip'  => EL_CAT_DESCRIP_IN_LIST,
		'tmsCols'            => 1,
		'displayEmptyTm'     => 0,
		'displayTMDescrip'   => EL_CAT_DESCRIP_IN_LIST,
		'typesCols'          => 1,
		'displayEmptyTypes'  => 0,
		'displayTypeDescrip' => EL_CAT_DESCRIP_IN_LIST,
		'itemsCols'          => 1,
		'itemsSortID'        => EL_IS_SORT_NAME,
		'itemsPerPage'       => 10,
		'displayCode'        => 1,
		'tmbListSize'        => 125,
		'tmbItemCardSize'    => 250,
		'sliderSize'         => 4,
		'crossLinksGroups'   => array(),
		'currency'           => '',
		'exchangeSrc'        => 'auto',
		'commision'          => 0,
		'rate'               => 1,
	    'pricePrec'          => 0,
	    'ymName'             => '',
	    'ymCompany'          => '',
	    'ymURL'              => '',
	    'ymDelivery'         => 1
	);
	
	var $_view;
	/**
	 * shared with render members
	 *
	 * @var array
	 **/
	var $_sharedRndMembers = array('_view', '_cat', '_mnf', '_url', '_urlCats', '_urlMnfs', '_urlTypes', 'itemsNum');
 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//
 
	/**
	 * display categories or manufacturers according to config
	 *
	 * @return void
	 **/
	function defaultMethod() {
		switch ($this->_view) {
			case EL_IS_VIEW_MNFS:
				$this->viewManufacturers();
				break;
			case EL_IS_VIEW_TYPES:
				$this->viewTypes();
				break;
			default:
				$this->viewCategories();
		}
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
		
		list($total, $current, $offset, $step) = $this->_getPagerInfo(
			$this->_factory->ic->count(EL_IS_CAT, $this->_cat->ID), (int)$this->_arg(1)
		);

		if (!$current) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such page', null, $this->_url.$this->_cat->ID);
		}

		$this->_rnd->render( 
				$this->_cat->getChilds((int)$this->_conf('deep')),
		        $this->_factory->ic->create(EL_IS_CAT, $this->_cat->ID, $offset, $step),
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
	 * display types
	 *
	 * @return void
	 **/
	function viewTypes() {
		$this->_initRenderer();
		$this->_rnd->rndTypes($this->_factory->getAllFromRegistry(EL_IS_ITYPE));
	}

	/**
	 * Display manufacturer
	 *
	 * @return void
	 **/
	function viewManufacturer() {
		$this->_mnf->idAttr($this->_arg());
		if(!$this->_mnf->fetch()) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such manufacturer',	null, EL_URL);
		}
		
		list($total, $current, $offset, $step) = $this->_getPagerInfo(
			$this->_factory->ic->count(EL_IS_MNF, $this->_mnf->ID), (int)$this->_arg(1)
		);

		if (!$current) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such page', null, $this->_url.$this->_mnf->ID);
		}
		
		$this->_initRenderer();
		$this->_rnd->rndMnf($this->_mnf, $this->_factory->ic->create(EL_IS_MNF, $this->_mnf->ID, $offset, $step), $total, $current);
		
		elAppendToPagePath(array(
			'url'  => $this->_urlMnfs.'mnf/'.$this->_mnf->ID.'/',	
			'name' => $this->_mnf->name)
			);
	}

	/**
	 * Display trademark
	 *
	 * @return void
	 **/
	function viewTrademark() {
		$this->_mnf->idAttr($this->_arg());
		if(!$this->_mnf->fetch()) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such manufacturer',	null, EL_URL);
		}

		$tm = $this->_factory->create(EL_IS_TM, $this->_arg(1));
		if (!$tm->ID) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such category', null, EL_URL);
		}

		list($total, $current, $offset, $step) = $this->_getPagerInfo(
			$this->_factory->ic->count(EL_IS_TM, $tm->ID), (int)$this->_arg(2)
		);

		$this->_initRenderer();
		$this->_rnd->rndTm($this->_mnf, $tm, $this->_factory->ic->create(EL_IS_TM, $tm->ID, $offset, $step), $total, $current);
		elAppendToPagePath(array(
			'url'  => $this->_urlMnfs.'mnf/'.$this->_mnf->ID.'/',	
			'name' => $this->_mnf->name)
			);
		elAppendToPagePath(array(
			'url'  => $this->_urlMnfs.'tm/'.$tm->ID.'/',	
			'name' => $tm->name)
			);
	}

	/**
	 * display type
	 *
	 * @return void
	 **/
	function viewType() {
		$this->_type->idAttr((int)$this->_arg(0));
		if (!$this->_type->fetch()) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such category',	null, $this->_urlTypes);
		}
		list($total, $current, $offset, $step) = $this->_getPagerInfo(
			$this->_factory->ic->count(EL_IS_ITYPE, $this->_type->ID), (int)$this->_arg(1)
		);
		if (!$current) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such page', null, $this->_urlTypes.$this->_type->ID.'/');
		}
		$this->_initRenderer();
		$this->_rnd->rndType($this->_type, $this->_factory->ic->create(EL_IS_ITYPE, $this->_type->ID, $offset, $step), $total, $current);
		elAppendToPagePath(array(
			'url'  => $this->_urlTypes.'type/'.$this->_type->ID.'/',	
			'name' => $this->_type->name)
			);
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
		$this->_initRenderer();
		$this->_rnd->rndSearchResult($this->_factory->ic->create('search', $finder->find()));
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
		// TODO add type view
		$nav = & elSingleton::getObj('elNavigator');
		return $nav->getPageURL($this->pageID).$path;
	}

	/**
	 * Yandex.Market generate xml file
	 *
	 * @TODO   possible problem if catalog is too big we can hit memory_limit, can be fixed using
	 *         direct write to file while generating
	 * @return void
	 **/
	function yandexMarket()
	{
		// Currency
		$currency = & elSingleton::getObj('elCurrency');
		$curOpts  = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate')
		);
		$yml_cur = sprintf('<currency id="%s" rate="1" />', $currency->current['intCode']);

		// Categories
		$cat = $this->_factory->create(EL_IS_CAT, 1);
		$categories = array();
		$this->_yandexMarketGetCategories($cat, $categories);

		$yml_cat     = '';
		$yml_cat_tpl = "\t\t\t".'<category id="%d" parentId="%d">%s</category>'."\n";
		foreach ($categories as $c)
		{
			$yml_cat .= sprintf($yml_cat_tpl, $c['id'], $c['parentID'], $this->_ymlSC($c['name']));
		}
		$yml_cat = rtrim($yml_cat);

		// Generate simple offers
		$yml_offer     = '';
		$yml_offer_tpl = <<<EOL
			<offer id="%d" available="true">
				<url>%s</url>
				<price>%.2f</price>
				<currencyId>%s</currencyId>
				<categoryId>%d</categoryId>
				<picture>%s</picture>
				<delivery>%s</delivery>
				<name>%s</name>
				<vendor>%s</vendor>
				<description>%s</description>%s
			</offer>

EOL;
/*
				<local_delivery_cost>%.2f</local_delivery_cost>
				<vendorCode>%s</vendorCode>
				<country_of_origin>%s</country_of_origin>
				<barcode>%s</barcode>
*/
		$delivery = $this->_conf('ymDelivery') == 1 ? 'true' : 'false';
		$param_tpl = "\n\t\t\t\t".'<param name="%s">%s</param>';
		foreach ($this->_factory->ic->create('yandex_market', 1) as $i)
		{
			$params = '';
			foreach ($i->getProperties() as $pos)
			{
				foreach ($pos as $prop)
				{
					$params .= sprintf($param_tpl, $prop['name'], $prop['value']);
				}
			}

			$yml_offer .= sprintf($yml_offer_tpl,
				$i->ID,                                      // offer id
				$this->getItemUrl($i->ID),                   // url
				$currency->convert($i->price, $curOpts),     // price
				$currency->current['intCode'],               // currencyId
				array_shift($i->getCats()),                  // categoryId
				$i->getDefaultTmb('c'),                      // picture
				$delivery,                                   // delivery
				//'',                                        // local_delivery_cost
				$this->_ymlSC($i->name),                     // name
				$this->_ymlSC($i->getMnf()->name),           // vendor
				//'',                                        // vendor code
				strip_tags($this->_ymlSC($i->content)),      // description
				//'',                                        // country_of_origin
				//''                                         // barcode
				$params                                      // last %s for param
			);
		}
		$yml_offer = rtrim($yml_offer);

		// TODO yandex type=vendor.model
		$yml = <<<EOL
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE yml_catalog SYSTEM "shops.dtd">
<yml_catalog date="%s">
	<shop>
		<name>%s</name>

		<company>%s</company>

		<url>%s</url>

		<currencies>
			%s
		</currencies>

		<categories>
%s
		</categories>

		<offers>
%s
		</offers>

	</shop>
</yml_catalog>
EOL;
/*
		<local_delivery_cost>%.2f</local_delivery_cost>
*/
		$full_yml = sprintf($yml,
			date('Y-m-d H:i'),          // yml_catalog date
			$this->_conf('ymName'),     // name
			$this->_conf('ymCompany'),  // company
			$this->_conf('ymURL'),      // url
			$yml_cur,       // currencies
			$yml_cat,       // categories
			//null,         // local_delivery_cost
			$yml_offer      // offers
		);
		@file_put_contents(EL_DIR_STORAGE.'yandex-market-'.$this->pageID.'.yml', $full_yml);
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

		$h = $this->_arg();
		$defaultView = $this->_conf['default_view'];
		if (in_array($h, array_keys($this->_viewMap))) {
			$this->_view = $this->_viewMap[$h];
			array_shift($this->_args);
		} else {
			$this->_view = $defaultView;
			
		}
		$this->_urlCats  = EL_URL.'cats/';
		$this->_urlMnfs  = EL_URL.'mnfs/';
		$this->_urlTypes = EL_URL.'types/';
		
		switch ($defaultView) {
			case EL_IS_VIEW_MNFS:
				$this->_urlMnfs  = EL_URL;
				break;
			case EL_IS_VIEW_TYPES:
				$this->_urlTypes  = EL_URL;
				break;
			default:
				$this->_urlCats  = EL_URL;
		}
		

		$this->_factory = & elSingleton::getObj('elIShopFactory', $this->pageID);
		$this->_cat  = $this->_factory->create(EL_IS_CAT, 1);
		$this->_mnf  = $this->_factory->create(EL_IS_MNF);
		$this->_type = $this->_factory->create(EL_IS_ITYPE);
		
		switch ($this->_view) {
			case EL_IS_VIEW_MNFS:
				$this->_url = $this->_urlMnfs;
				$this->_mnf->idAttr($this->_arg(0));
				$this->_mnf->fetch();
				break;
			case EL_IS_VIEW_TYPES:
				$this->_url = $this->_urlTypes;
				$this->_type->idAttr($this->_arg(0));
				$this->_type->fetch();
				break;
			default:
				$this->_url = $this->_urlCats;
				$this->_cat->idAttr($this->_arg(0));
				$this->_cat->fetch();
		}
	}

	/**
	 * create category and manufacturer and check currency exchange config
	 *
	 * @return void
	 **/
	function _onInit() {

		// $this->_cat  = $this->_factory->create(EL_IS_CAT, 1);
		// $this->_mnf  = $this->_factory->create(EL_IS_MNF);
		// $this->_type = $this->_factory->create(EL_IS_ITYPE);
		// 
		// switch($this->_view) {
		// 	case EL_IS_VIEW_CATS:
		// 	
		// 		break;
		// 	case EL_IS_VIEW_MNFS:
		// 		break;
		// 	
		// }
		// 
		// if ($this->_view == EL_IS_VIEW_CATS) {
		// 	$this->_cat->idAttr($this->_arg(0));
		// 	
		// } else {
		// 	$this->_mnf->idAttr($this->_arg(0));
		// 	$this->_mnf->fetch();
		// 	
		// }
		// 
		// $this->_cat->fetch();
		$GLOBALS['categoryID'] = $this->_cat->ID;

		$this->itemsNum = $this->_factory->ic->countAll();
		
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

	// Yandex.Market related

	/**
	 * YML Sepcial Characters replace
	 *
	 * @param  string  $s
	 * @return string
	 **/
	function _ymlSC($s)
	{
		$s = htmlspecialchars($s);
		$s = str_replace("'", '&apos;', $s);
		return $s;
	}

	/**
	 * get categories for Yandex.Market
	 *
	 * @param  object  $cat    start category
	 * @param  array   &$ar    array reference for information population
	 * @return void
	 **/
	function _yandexMarketGetCategories($cat, &$ar)
	{
		$cat->_initTree();
		$parentID = $cat->tree->getParentID($cat->ID);
		if ($parentID == 1)
		{
			//$parentID = 0;
		}
		array_push($ar, array(
			'name'     => $cat->name,
			'id'       => (int)$cat->ID,
			'parentID' => (int)$parentID
		));
		//echo $cat->name." : ".$cat->ID." (".$cat->tree->getParentID($cat->ID).")<br>";

		$childs = $cat->getChilds(1);
		if ($childs)
		{
			foreach ($childs as $c)
			{
				$this->_yandexMarketGetCategories($c, $ar);
			}
		}
	}

} // END class

?>
