<?php

define('EL_TSHOP_FEATURES_IN_LIST_DISABLE', 0);
define('EL_TSHOP_FEATURES_IN_LIST_ENABLE',  1);
define('EL_TSHOP_FEATURES_IN_LIST_COMPARE', 2);

define('EL_TS_ISHOP_DISABLED',   0);
define('EL_TS_ISHOP_ONLY_PRICE', 1);
define('EL_TS_ISHOP_ENABLED',    2);

define('EL_TS_LOBJ_POS_DEFAULT',  0);
define('EL_TS_LOBJ_POS_TAB',      1);

include_once(EL_DIR_CORE.'lib/elCatalogModule.class.php');

// elAddJs('jquery.js', EL_JS_CSS_FILE);

class elModuleTechShop extends elCatalogModule
{
	var $tbc       = 'el_wcm_techshop_%d_cat';
	var $tbi       = 'el_wcm_techshop_%d_item';
	var $tbm       = 'el_wcm_techshop_%d_model';
	var $tbmnf	   = 'el_wcm_techshop_%d_manufact';
	var $tbftg     = 'el_wcm_techshop_%d_ft_group';
	var $tbft      = 'el_wcm_techshop_%d_feature';
	var $tbi2c	   = 'el_wcm_techshop_%d_i2c';
	var $tbft2i    = 'el_wcm_techshop_%d_ft2i';
	var $tbft2m    = 'el_wcm_techshop_%d_ft2m';

	var $_itemClass = 'elTSItem';

	var $_mMap      = array(
		'item'       => array('m' => 'displayItem'),
		'mnfs'       => array('m' => 'displayManufacturers'),
		'mnf'        => array('m' => 'displayManufacturer'),
		'mnf_items'  => array('m' => 'displayManufacturerItems'),
		'compare'    => array('m' => 'displayCompareTable'),
		'price'      => array('m' => 'downloadPrice'),
		'order'      => array('m' => 'order')
		);

	var $_conf      = array(
  	'deep'              => 0,
    'catsCols'          => 1,
    'itemsCols'         => 2,
	'featuresInItemsList' => EL_TSHOP_FEATURES_IN_LIST_COMPARE,
    'itemsSortID'         => 1,
    'itemsPerPage'        => 10,
    'displayCatDescrip'   => 1,
    'modelsTmbSize'      => 100,
	'displayCode'       => 1,
	'ishop'             => EL_TS_ISHOP_DISABLED,
	'currency'          => '',
	'exchangeSrc'       => 'auto',
	'commision'         => 0,
	'rate'              => 1,
    'pricePrec'         => 2,
    'priceDownl'        => 0,
	'fakePrice'         => 0,
	'displayManufact'   => 1,
	'linkedObjPos'      => EL_TS_LOBJ_POS_TAB
    );

  var $_csvSep   = array( array(';', 'Semicolon'), array(',', 'Comma') );
  var $_csvDelim = array( array('', 'None'), array('"', 'Double quotes'), array("'", 'Single quotes') );

	/**
	 * Display categories and items list
	 *
	 */
	function defaultMethod()
	{
		$this->_initCat($this->_arg());
		$this->_initRenderer();
		list($total, $current, $offset, $step) = $this->_getPagerInfo( $this->_cat->countItems() );
		$item = $this->_factory->create(EL_TS_ITEM);
		$cats = $this->_cat->getChilds( (int)$this->_conf('deep') );
		$iSort = $this->_conf('itemsSortID');
		$items = $this->_factory->getItemsFromCategory($this->_cat->ID, $iSort, $offset, $step, $this->_conf('featuresInItemsList'));

		$this->_rnd->render( $cats,
		                     $items,
		                     $total,
		                     $current,
		                     $this->_cat
		                  );
	}


  function downloadPrice()
  {
    if (!$this->_conf('priceDownl'))
    {
      elLocation(EL_URL);
    }
    $price = $this->_factory->getPrice();
    $csv   = $this->_arrayToCsv($this->_factory->getPrice());
    if ( 'ru' == EL_LANG && function_exists('iconv') )
    {
      $csv = iconv('UTF-8', 'CP1251//TRANSLIT', $csv);
    }
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=price.csv" );
    header("Content-Location: ".EL_URL);
    header("Connection:close");
    echo $csv; exit();
  }


	/**
	* Display selected item details
	*
	*/
	function displayItem()
	{
		$this->_initCat($this->_arg());
		$this->_item = $this->_factory->getItem($this->_arg(1));
		if ( !$this->_item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($this->_item->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
		}
		$clm = & $this->_getCrossLinksManager();
		$this->_initRenderer(); 
		$this->_rnd->renderItem( $this->_item, $clm->getLinkedObjects($this->_item->ID) );
	}


	/**
	* Display list of manufacturers
	*
	*/
	function displayManufacturers()
	{
		$this->_mnf = $this->_factory->create(EL_TS_MNF);
		$this->_initRenderer();
		$this->_rnd->rndManufacturers( $this->_mnf->collection(false) );
	}

	/**
	* Display selected manufacturer details
	*
	*/
	function displayManufacturer()
	{
		$this->_mnf = $this->_factory->create(EL_TS_MNF, $this->_arg());
		if ( !$this->_mnf->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($this->_mnf->getObjName(), $this->_arg()),	EL_URL.'mnfs/');
		}
		$this->_initRenderer();
		$this->_rnd->rndManufacturer( $this->_mnf );
		elAppendToPagePath( array('url'=>'mnfs/', 'name'=>m('Manufacturers')) );
	}

	/**
    * Display list of items produced by selected manufacturer
    *
    */
	function displayManufacturerItems()
	{
	  $this->_mnf = $this->_factory->create(EL_TS_MNF, $this->_arg());
		if ( !$this->_mnf->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($this->_mnf->getObjName(), $this->_arg()), EL_URL.'mnfs/');
		}
		$this->_initRenderer();
		$this->_rnd->rndManufacturerItems($this->_factory->getManufacturerItems($this->_mnf->ID, $this->_conf('featuresInItemsList')) );
	}

	/**
    * Display features compare table of selected items
    *
    */
	function displayCompareTable ()
	{
		$this->_initCat($this->_arg());
		$iIDs = !empty($_GET['i']) && is_array($_GET['i']) ? array_keys($_GET['i']) : null;
		$mIDs = !empty($_GET['m']) && is_array($_GET['m']) ? array_keys($_GET['m']) : null;
		if ( !$iIDs && !$mIDs )	{
			elThrow(E_USER_WARNING, 'There are no one item was selected for compare', null, EL_URL.$this->_cat->ID);
		} elseif (count($iIDs)+count($mIDs) == 1) {
			elThrow(E_USER_WARNING, 'There are no one item was selected for compare', null, EL_URL.$this->_cat->ID);
		}
		
		$this->_initRenderer();
		$this->_rnd->rndCompareTable( $this->_factory->getCompareTable($iIDs, $mIDs));

	}

	
	function order() {
		$this->_initCat($this->_arg());
		$this->_item = $this->_factory->getItem($this->_arg(1));
		if ( !$this->_item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($this->_item->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
		}
		$url = EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID.'/';
		$data = array(
			'page_id' => $this->pageID,
			'i_id'    => $this->_item->ID,
			'm_id'    => 0,
			'code'    => '',
			'name'    => '',
			'price'   => 0,
			'props'   => array()
			);
		$type = trim($this->_arg(2));
		$mID = (int)$this->_arg(3);
		$currency = &elSingleton::getObj('elCurrency');
		$curOpts = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate')
			);
		if ($mID) {
			if ($type == 'm') {
				// model
				$model = $this->_factory->create(EL_TS_MODEL, $mID);
				if ($model->ID) {
					$data['m_id']  = $mID;
					$data['code']  = $model->code;
					$data['name']  = $model->name;
					$data['price'] = $currency->convert($model->price, $curOpts);
				}
			} elseif ($type == 'f' && $this->_item->hasFakePrice) {
				// record from fake price
				$price = $this->_item->getPriceList();
				if (!empty($price[$mID])) {
					$data['m_id']  = $mID;
					$data['code']  = $this->_item->code;
					$data['name']  = $price[$mID]['name'];
					$data['price'] = $currency->convert($price[$mID]['price'], $curOpts);
				}
			}
		} else {
			// item itself
			$data['code']  = $this->_item->code;
			$data['name']  = $this->_item->name;
			$data['price'] = $currency->convert($this->_item->price, $curOpts);
		}
		
		if (!$data['name']) {
			elThrow(E_USER_WARNING, 'Unable to find item to add into shopping cart', null, $url);
		} elseif (!$data['price']) {
			elThrow(E_USER_WARNING, 'Unable to add into shopping cart item without price', null, $url);
		}
		
		if (!$this->_conf('displayCode')) {
			$data['code'] = '';
		}
		
		elLoadMessages('ServiceICart');
		$ICart = & elSingleton::getObj('elICart');
		if ($ICart->add($data)) {
			$msg = sprintf( m('Item %s was added to Your shopping cart. To proceed order right now go to <a href="%s">this link</a>'), $data['code'].' '.$data['name'], EL_URL.'__icart__/' );
	        elMsgBox::put($msg);
			elLocation($url);
		} else {
			$msg = sprintf( m('Error! Could not add item to Your shopping cart! Please contact site administrator.') );
            elThrow(E_USER_WARNING, $msg, null, $url);
		}

	}
	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//

  function _initCat($ID)
  {
  	if (!$ID || 1 == $ID)
  	{
  		return;
  	}
  	$this->_cat->idAttr( (int)$ID );
  	if ( !$this->_cat->fetch() )
		{
			if (1 <> $ID)
			{
				header('HTTP/1.x 404 Not Found'); 
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',	array($this->_cat->getObjName(), $ID), EL_URL);
			}
			$nav = &elSingleton::getObj('elNavigator');
			$this->_cat->makeRootNode( $nav->getPageName($this->pageID) );
			$this->_cat->idAttr( 1 );
			if ( !$this->_cat->fetch() )
			{
				header('HTTP/1.x 404 Not Found'); 
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',	array($this->_cat->getObjName(), 1), EL_BASE_URL);
			}
		}
		$GLOBALS['categoryID'] = $this->_cat->ID;
  }

	function _onInit()
	{
		$this->_factory        = & elSingleton::getObj('elTSFactory');
		$this->_factory->init($this->pageID, $this->_conf);
		$this->_cat            = $this->_factory->create(EL_TS_CAT);
		$this->_cat->ID        = 1;
		$GLOBALS['categoryID'] = 1;
		
		if ($this->_conf('eshopFunc')) {
			
			$cur  = &elSingleton::getObj('elCurrency');
			
			if (empty($this->_conf['currency'])) {
				$conf = & elSingleton::getObj('elXmlConf');
				$this->_conf['currency'] = $cur->current['intCode'];
				$conf->set('currency', $cur->current['intCode'], $this->pageID);
				$conf->save();
			}
			
			if ($this->_conf['currency'] != $cur->current['intCode'] && $this->_conf('exchangeSrc') == 'manual' && !($this->_conf('rate') > 0)) {
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

  	function _onBeforeStop()
	{
	   $this->_cat->pathToPageTitle();
      if ( $this->_item )
      {
        elAppendToPagePath( array('url'=>'item/'.$this->_cat->ID.'/'.$this->_item->ID,
													'name'=>$this->_item->name.' '.$this->_item->code) );
      }
      elseif (!empty($this->_mnf))
      {
        elAppendToPagePath( array('url'=>'mnfs/',	'name'=>m('Manufacturers')) );
        if ($this->_mnf->ID)
        {
          elAppendToPagePath( array('url'=>'mnf/'.$this->_mnf->ID.'/', 'name'=>$this->_mnf->name) );
        }
      }
      $mt = &elSingleton::getObj('elMetaTagsCollection'); 
      $mt->init($this->pageID, $this->_cat->ID, ($this->_item) ? $this->_item->ID : 0, $this->_factory->tb('tbc'));
	}

  function _arrayToCsv($price)
  {
    $csv = '';
    $tpl = "\"%s\";\"%s\";%d;\r\n";
    foreach ($price as $i)
    {
      $csv .= sprintf($tpl, $i['code'], $i['name'], $i['price']);
    }
    return $csv;
  }

}

?>