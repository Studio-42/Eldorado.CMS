<?php

include_once(EL_DIR_CORE.'lib/elCatalogModule.class.php');

elAddJs('jquery.js', EL_JS_CSS_FILE);

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
		'mod_img'    => array('m' => 'displayModelImg'),
		'mnfs'       => array('m' => 'displayManufacturers'),
		'mnf'        => array('m' => 'displayManufacturer'),
		'mnf_items'  => array('m' => 'displayManufacturerItems'),
		'compare'    => array('m' => 'displayCompareTable'),
		'price'      => array('m' => 'downloadPrice')
		);

	var $_conf      = array(
  	'deep'              => 0,
    'catsCols'          => 1,
    'itemsCols'         => 2,
    'itemsSortID'       => 1,
    'itemsPerPage'      => 10,
    'modelsInRow'       => 5,
    'displayCatDescrip' => 1,
    'modelsTmbSize'     => 100,
    'eshopFunc'         => 0,
    'pricePrec'         => 2,
    'priceDownl'        => 0,
	'displayManufact'   =>1
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
    $items = $this->_factory->getItemsFromCategory($this->_cat->ID, $iSort, $offset, $step);

    $this->_rnd->render( $cats,
                         $items,
                         $total,
                         $current,
                         $this->_cat->getAttr('name'),
                         $this->_cat->getAttr('descrip')
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
      $this->_item = $this->_factory->create(EL_TS_ITEM, $this->_arg(1));
      if ( !$this->_item->ID )
      {
          elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
              array($this->_item->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
      }
      
      $clm = & $this->_getCrossLinksManager();
      $this->_initRenderer(); 
      $this->_rnd->renderItem( $this->_item, $clm->getLinkedObjects($this->_item->ID) );

	}

	function displayModelImg()
	{
	   $mID = (int)$this->_arg(); //echo $mID;
	   $model = $this->_factory->create(EL_TS_MODEL, $this->_arg(0));
	   if ( !$model->ID )
	   {
	     return elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($model->getObjName(), $this->_arg(0)) );
	   }
	   $this->_initRenderer();
	   $this->_rnd->rndModel($model);
	}


	/**
   * Display list of manufacturers
   *
   */
	function displayManufacturers()
	{
		$this->_mnf = $this->_factory->create(EL_TS_MNF);
		$this->_initRenderer();
		$this->_rnd->rndManufacturers( $this->_mnf->getCollection() );
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
		elAppendToPagePath( array('url'=>'mnfs/',	'name'=>m('Manufacturers')) );
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
				array($this->_mnf->getObjName(), $this->_arg()),	EL_URL.'mnfs/');
		}
		$this->_initRenderer();
		$this->_rnd->rndManufacturerItems( $this->_mnf, $this->_factory->getMnfItems($this->_mnf) );
	}

	/**
   * Display features compare table of selected items
   *
   */
	function displayCompareTable ()
	{
		$iIDs = !empty($_GET['i']) && is_array($_GET['i']) ? array_keys($_GET['i']) : null;
		$mIDs = !empty($_GET['m']) && is_array($_GET['m']) ? array_keys($_GET['m']) : null;
		if ( !$iIDs && !$mIDs )
		{
			elThrow(E_USER_WARNING, 'There are no one item was selected for compare', null, EL_URL);
		}
		list($objs, $ftgs) = $this->_factory->getCompare($iIDs, $mIDs); //elPrintR($ftgs);
		$this->_initRenderer();
		$this->_rnd->rndCompare( $objs, $ftgs );

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
  	$this->_cat->setUniqAttr( (int)$ID );
  	if ( !$this->_cat->fetch() )
		{
			if (1 <> $ID)
			{
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
								array($this->_cat->getObjName(), $ID), EL_URL);
			}
			$nav = &elSingleton::getObj('elNavigator');
			$this->_cat->makeRootNode( $nav->getPageName($this->pageID) );
			$this->_cat->setUniqAttr( 1 );
			if ( !$this->_cat->fetch() )
			{
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
								array($this->_cat->getObjName(), 1), EL_BASE_URL);
			}
		}
		$GLOBALS['categoryID'] = $this->_cat->ID;
  }

  function _onInit()
  {
     $this->_factory        = & elSingleton::getObj('elTSFactory');
     $this->_factory->init($this->pageID);
     $this->_cat            = $this->_factory->create(EL_TS_CAT);
     $this->_cat->ID        = 1;
     $GLOBALS['categoryID'] = 1;
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

	function _initRenderer()
	{
	  parent::_initRenderer();
	  if ( 0 < $this->_conf('eshopFunc') )
	  {
	    $this->_rnd->setCurrencyInfo(elGetCurrencyInfo(), (int)$this->_conf('pricePrec'));
	    if ( 1 < $this->_conf('eshopFunc') )
	    {
	      $this->_rnd->switchCartOn();
	    }
	  }

     elAddJs("var switchOpenLabel=\"".m('Display models list')."\";\n"
      ."var switchCloseLabel=\"".m('Hide models list')."\";\n"
      ."var switchOpenClass=\"tsOpen\";\n"
      ."var switchCloseClass=\"tsClose\";\n"
      );
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