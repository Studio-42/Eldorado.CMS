<?php

elLoadMessages('ModuleAdminDocsCatalog');

class elModuleAdminIShop extends elModuleIShop
{
   var $_mMapAdmin = array(
	  	'edit'        => array('m'=>'editCat',          'g'=>'Actions', 'ico'=>'icoCatNew',         'l'=>'New category'),
	  	'rm'          => array('m'=>'rmCat'),
	  	'move'        => array('m'=>'moveCat'),
		'mnfs'        => array('m'=>'viewManufacturers', 'g'=>'Actions', 'ico'=>'icoMnfList',       'l'=>'Manufacturers list' ),
	  	'mnf_edit'    => array('m'=>'editMnf',           'g'=>'Actions', 'ico'=>'icoMnfNew',        'l'=>'New manufacturer' ),
		'mnf_rm'      => array('m'=>'rmMnf'),
		'tm_edit'     => array('m'=>'editTm',            'g'=>'Actions', 'ico'=>'icoTmNew',         'l'=>'New trade mark'),
      	'tm_rm'       => array('m'=>'rmTm'),
		
		
	  	'rm_item'     => array('m'=>'rmItem'),
	  	'item_img'    => array('m'=>'itemImg'),
	  	'rm_img'      => array('m'=>'rmItemImg'),
	
		'type_props'  => array('m' => 'typeProps'),

	  	'edit_type'   => array('m'=>'editItemsType',     'g'=>'Actions', 'ico'=>'icoItemTypeNew',   'l'=>'New items type'),
	  	
		'sort'        => array('m'=>'sortItems',         'g'=>'Actions', 'ico'=>'icoSortAlphabet',  'l'=>'Sort documents in current category'),
	  	'rm_group'    => array('m'=>'rmItems',           'g'=>'Actions', 'ico'=>'icoDocGroupRm',    'l'=>'Delete group of documents',),
		'cross_links' => array('m'=>'editCrossLinks',    'g'=>'Actions', 'ico'=>'icoCrosslinks',    'l'=>'Edit linked objects list' ),
      	'rm_type'     => array('m'=>'rmItemsType'),
	  	'edit_prop'   => array('m'=>'editProperty'),
	  	'edit_pdep'   => array('m'=>'editPropertyDependance'),
	  	'rm_prop'     => array('m'=>'rmProperty')
      
	);

	var $_mMapNoAppendCatID = array('mnfs', 'mnf_edit', 'mnf_rm');

	var $_mMapConf  = array(
		'conf'            => array('m'=>'configure',           'ico'=>'icoConf',          'l'=>'Configuration'),
		'conf_search'     => array('m'=>'configureSearch',     'ico'=>'icoSearchConf',    'l'=>'Configure advanced search'),
		'conf_nav'        => array('m'=>'configureNav',        'ico'=>'icoNavConf',       'l'=>'Configure navigation for catalog'),
		'conf_crosslinks' => array('m'=>'configureCrossLinks', 'ico'=>'icoCrosslinksConf','l'=>'Linked objects groups configuration'),
		'yandex_market'   => array('m'=>'yandexMarketConf',    'ico'=>'icoYandexMarket',  'l'=>'Yandex.Market'),
		'yandex_market2'  => array('m'=>'yandexMarket',        'ico'=>'icoYandexMarket',  'l'=>'Yandex.Market'),
		'import_comml'    => array('m'=>'importCommerceML',    'ico'=>'icoConf',          'l'=>'Import from 1C')
	);

	/**
	 * Create/edit category
	 *
	 * @return void
	 **/
	function editCat()  {
		$cat = $this->_factory->create(EL_IS_CAT, $this->_arg(1));
		if (!$cat->ID) {
			$cat->parentID = (int)$this->_arg(0);
		}
		if (!$cat->editAndSave()) {
			$this->_initRenderer();
			return $this->_rnd->addToContent($cat->formToHtml());
		}
		elMsgBox::put( m('Data saved') );
		elLocation($this->_urlCats.$this->_cat->ID);
	}

	/**
	 * Move category up/down, if third arg is set - move up, otherwise - down
	 *
	 * @return void
	 **/
	function moveCat() {
		$cat = $this->_factory->create(EL_IS_CAT, $this->_arg(1));
  		if (!$cat->ID) {
	  		elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($cat->getObjName(), $cat->ID), $this->_urlCats.$this->_cat->ID);
	  	}
	  	$moveUp = (bool)$this->_arg(2);
	  	if (!$cat->move($moveUp)) {
	  		$msg = $moveUp ? 'Can not move object "%s" "%s" up' : 'Can not move object "%s" "%s" down';
	  		elThrow(E_USER_NOTICE, $msg, array($cat->getObjName(), $cat->name),	$this->_urlCats.$this->_cat->ID );
	  	}
	  	elMsgBox::put(m('Data saved'));
	  	elLocation($this->_urlCats.$this->_cat->ID);
	}

	/**
	 * Remove empty category
	 *
	 * @return void
	 **/
	function rmCat() {
		$cat = $this->_factory->create(EL_IS_CAT, $this->_arg(1));
		if (!$cat->ID) {
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($cat->getObjName(), $cat->ID), $this->_urlCats.$this->_cat->ID);
		}

		if (!$cat->isEmpty()) {
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"', array($cat->getObjName(),$cat->name), $this->_urlCats.$this->_cat->ID);
		}
		$cat->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $cat->getObjName(), $cat->name) );
		elLocation($this->_urlCats.$this->_cat->ID);
	}

	/**
	 * Create/edit manufacturer
	 *
	 * @return void
	 **/
	function editMnf() {
		$mnf = $this->_factory->create(EL_IS_MNF, (int)$this->_arg(0)); 
		if (!$mnf->editAndSave()) {
			$this->_initRenderer();
			return $this->_rnd->addToContent($mnf->formToHtml());
		}
		elMsgBox::put(m('Data saved'));
		elLocation($this->_urlMnfs);
	}

	/**
	 * Remove manufacturer
	 *
	 * @return void
	 **/
	function rmMnf() {
		$mnf = $this->_factory->create(EL_IS_MNF, (int)$this->_arg(0)); 
		if (!$mnf->ID) {
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($mnf->getObjName(), $this->_arg(1)), $this->_urlMnfs);	
		}
		$mnf->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $mnf->getObjName(), $mnf->name) );
	    elLocation($this->_urlMnfs);
	}

	/**
	 * Create/edit trade mark (model)
	 *
	 * @return void
	 **/
	function editTm() {
		$this->_mnf->idAttr($this->_arg(0));
		$this->_mnf->fetch();

		if (!$this->_factory->getAllFromRegistry(EL_IS_MNF)) {
			elThrow(E_USER_WARNING, 'Could not create trade mark without manufacturer! Create at least one manufacturer first!', null, $this->_urlMnfs.$this->_mnf->ID);
		}
		$tm = $this->_factory->create(EL_IS_TM, $this->_arg(1)); 
		if (!$tm->ID) {
			$tm->mnfID = $this->_mnf->ID;
		}
		if (!$tm->editAndSave()) {
			elAppendToPagePath(array(
				'url'  => $this->_urlMnfs.'mnf/'.$this->_mnf->ID.'/',	
				'name' => $this->_mnf->name)
				);
			$this->_initRenderer();
			return $this->_rnd->addToContent($tm->formToHtml());
			
		}
		elMsgBox::put(m('Data saved'));
		elLocation($this->_urlMnfs.'mnf/'.$tm->mnfID.'/');
	}

	/**
	 * Reove trade mark (model)
	 *
	 * @return void
	 **/
	function rmTm() {
		$tm = $this->_factory->create(EL_IS_TM, $this->_arg(1) );
		if (!$tm->ID) {
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($tm->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);	
		}
		$tm->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $tm->getObjName(), $tm->name) );
	    elLocation($this->_urlMnfs.'mnf/'.$tm->mnfID.'/');
	}

	/**
	 * display product type properties
	 *
	 * @return void
	 **/
	function typeProps() {
		$this->_type->idAttr((int)$this->_arg(0));
		if (!$this->_type->fetch()) {
			header('HTTP/1.x 404 Not Found');
			elThrow(E_USER_WARNING, 'No such category',	null, $this->_urlTypes);
		}
		
		$this->_initRenderer();
		$this->_rnd->rndTypeProps($this->_type);
		
		elAppendToPagePath(array(
			'url'  => $this->_urlTypes.'type/'.$this->_type->ID.'/',	
			'name' => $this->_type->name)
			);
	}
	
	/**
	 * Create/edit products type
	 *
	 * @return void
	 **/
	function editItemsType() {
		$type = $this->_factory->create(EL_IS_ITYPE, $this->_arg(1));
		if (!$type->editAndSave()) {
			$this->_initRenderer();
			return $this->_rnd->addToContent($type->formToHtml());
		}
		elMsgBox::put( m('Data saved') );
		elLocation(EL_URL.'types/');
	}

	/**
	 * Remove empty products type
	 *
	 * @return void
	 **/
	function rmItemsType() {
		$type = $this->_factory->create(EL_IS_ITYPE, $this->_arg(1));
		if (!$type->ID) {
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($type->getObjName(), $type->ID), EL_URL.'types');
		}
		if ($this->_factory->ic->count(EL_IS_ITYPE, $type->ID)) {
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"', array($type->getObjName(), $type->name), EL_URL.'types');
		}
		$type->delete();
		elMsgBox::put(sprintf(m('Object "%s" "%s" was deleted'), $type->getObjName(), $type->name));
		elLocation(EL_URL.'types');
	}






	/**********    манипуляции со свойствами типов товаров   *******************/


  /**
   * Создание/редактирование свойства для типа товара
   *
   */
  function editProperty()
  {
    $type = $this->_factory->getItemType( (int)$this->_arg(1) );
    if ( !$type->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($type->getObjName(), $type->ID), EL_URL.'types');
    }
    if ( !$type->editProperty((int)$this->_arg(2)) )
    {
      $this->_initRenderer();
	    return $this->_rnd->addToContent( $type->formToHtml() );
    }
    elMsgBox::put( m('Data saved'));
	  elLocation(EL_URL.'types');
  }

  /**
   * Удаляет объект-свойство у типа товара
   *
   */
  function rmProperty()
  {
    $type = $this->_factory->getItemType( (int)$this->_arg(1) );
    if ( !$type->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($type->getObjName(), $type->ID), EL_URL.'types');
    }
    $pID = (int)$this->_arg(2);
    $type->removeProperty((int)$this->_arg(2));
    elLocation(EL_URL.'types');
  }

  function editPropertyDependance()
  {
    $type = $this->_factory->getItemType( (int)$this->_arg(1) );
    if ( !$type->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($type->getObjName(), $type->ID), EL_URL.'types');
    }
    if ( !$type->editPropertyDependance((int)$this->_arg(2)) )
    {
      $this->_initRenderer();
	    return $this->_rnd->addToContent( $type->formToHtml() );
    }
    elMsgBox::put( m('Data saved'));
	  elLocation(EL_URL.'types');
  }

  /**
   * Создание/редактирование товара
   *
   */
  function editItem()
  {
    $typeID = (int)str_replace('edit', '', $this->_mh);
    $item = $this->_factory->getItem( (int)$this->_arg(2), $typeID );// elPrintR($item->type);
    if ( !$item->type )
    {
      elThrow(E_USER_WARNING, 'You are trying to create %s with undefined type!', $item->getObjName(), EL_URL.'types');
    }
    $params = array(
      'parents' => $this->_cat->getTreeToArray(0, true),
      'catID'   => $this->_cat->ID,
      'mnfNfo'  => (int)$this->_conf('mnfNfo'));// echo $this->_conf('mnfNfo');
    if ( !$item->editAndSave( $params ) )
    {
      $this->_initRenderer();
      return $this->_rnd->addToContent( $item->formToHtml());
    }
    elMsgBox::put( m('Data saved') );
	  elLocation(EL_URL.$this->_cat->ID);
  }

  function rmItem()
  {
    $item = $this->_factory->getItem( (int)$this->_arg(1) );
    if ( !$item->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($item->getObjName(), $item->ID), EL_URL.$this->_cat->ID);
    }
    $item->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $item->getObjName(), $item->name) );
    elLocation(EL_URL.$this->_cat->ID);
  }

	function itemImg()
	{
		$item = $this->_factory->getItem((int)$this->_arg(1));
		if (!$item->ID)
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($item->getObjName(), $item->ID), EL_URL.$this->_cat->ID);
		}
		if (!$item->changeImage((int)$this->_arg(2), $this->_conf('tmbListSize'), $this->_conf('tmbItemCardSize')))
		{
			elThrow(E_USER_WARNING, 'Cannot add image to this item', null, EL_URL.'item/'.$this->_cat->ID.'/'.$item->ID);
		}
		elMsgBox::put(m('Data saved'));
		elLocation(EL_URL.'item/'.$this->_cat->ID.'/'.$item->ID);
	}

	function rmItemImg()
	{
		$item = $this->_factory->getItem((int)$this->_arg(1));
		if (!$item->ID)
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($item->getObjName(), $item->ID), EL_URL.$this->_cat->ID);
		}
		$img_id = (int)$this->_arg(2);
		$item->rmImage((int)$this->_arg(2));
		elMsgBox::put(m('Data saved'));
		elLocation(EL_URL.'item/'.$this->_cat->ID.'/'.$item->ID);
	}
  
  function sortItems()
  {
      if ( !$this->_cat->countItems() )
      {
        elThrow(E_USER_WARNING, 'There are no one documents in this category was found', null, EL_URL);
      }
      $item = $this->_factory->getItem( 0 );

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
      $item = $this->_factory->getItem( 0 );

      if ( !$item->removeItems($this->_cat->ID) )
      {
          $this->_initRenderer();
        $this->_rnd->addToContent( $item->formToHtml() );
      }
      else
      {
        elMsgBox::put(m('Selected documents was deleted'));
        elLocation(EL_URL.$this->_cat->ID);
      }
  }
  /*********    Манипуляции с категориями    ********************/











  function editCrossLinks()
	{
		$this->_item =  $this->_factory->getItem( (int)$this->_arg(1));
		if ( !$this->_item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($this->_item->getObjName(), $this->_arg(1)),EL_URL.$this->_cat->ID);
		}
		$clm = & $this->_getCrossLinksManager();
		if (!$clm->editCrossLinks($this->_item))
		{
		  $this->_initRenderer();
		  $this->_rnd->addToContent($clm->formToHtml());
		}
		else
		{
		  elMsgBox::put( m('Data saved') );
			elLocation( EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID.'/' );
		}
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

  function configureNav()
  {
  	$conf = &elSingleton::getObj('elXmlConf');
  	$form = & $this->_makeNavForm( $conf->get($this->pageID, 'catalogsNavs') );

  	if (!$form->isSubmitAndValid())
  	{
  		$this->_initRenderer();
		return $this->_rnd->addToContent($form->toHtml());
  	}

  	$raw = $form->getValue();
  	$data = array();
  	$data['pos']   = isset($GLOBALS['posNLRTB'][$raw['pos']]) ? $raw['pos'] : 0;
  	$data['title'] = !empty($raw['title']) ? $raw['title'] : '';
  	$data['deep']  = (int)$raw['deep'];
	$data['pIDs']  = $raw['pIDs'];

  	if (!$data['pos'])
  	{
  		$conf->drop($this->pageID, 'catalogsNavs');
  	}
  	else
  	{
  		if ( empty($data['pIDs']) )
  		{
  			$form->pushError('pIDs[]', m('You should select at least one page') );
  			$this->_initRenderer();
  			return $this->_rnd->addToContent($form->toHtml());
  		}
  		$conf->set($this->pageID, $data, 'catalogsNavs');
  	}
  	$conf->save();
  	elMsgBox::put( m('Configuration was saved') );
  	elLocation( EL_URL );

  }

   function configureSearch()
   {
      $sa = $this->_factory->getSearchAdmin(); //elPrintR($sa);
      $this->_initRenderer();
      $sa->run( $this->_rnd, $this->_args );
   }

	/**
	 * Yandex.Market configuration
	 *
	 * @return void
	 **/
	function yandexMarketConf()
	{
		if (isset($_POST['action']))
		{
			include_once EL_DIR_CORE.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
			if ($_POST['action'] == 'childs')
			{
				$ID = !empty($_POST['id']) ? trim($_POST['id']) : '';
				list($null, $ID) = explode('_', $ID, 2);
				$ID = (int)$ID;
				if (!$ID)
				{
					exit(elJSON::encode(array('error' => 'Invalid argument')));
				}

				$nodes = array();
				foreach ($this->_factory->create(EL_IS_CAT, $ID)->getChilds(1) as $child)
				{
					array_push($nodes, array(
						'id'         => 'cat_'.$child->ID,
						'name'       => $child->name,
						'has_childs' => (bool)$this->_factory->ic->count(EL_IS_CAT, $child->ID),
						'is_cat'     => true
					));
				}
				foreach ($this->_factory->ic->create(EL_IS_ITEM, $ID) as $i)
				{
					array_push($nodes, array(
						'id'         => 'item_'.$i->ID,
						'name'       => $i->name.' ('.$i->ID.')',
						'has_childs' => false,
						'ym'         => (bool)$i->ym
					));
				}
				exit(elJSON::encode($nodes));
			}
			elseif($_POST['action'] == 'save')
			{
				foreach ($_POST as $k => $v)
				{
					list($item, $id) = explode('_', $k);
					if (($item == 'item') && ($id > 0))
					{
						//print "$id:$v\n";
						$i = $this->_factory->create(EL_IS_ITEM, $id);
						$i->attr('ym', $v);
						//var_dump($i);
						$i->save();
					}
				}
				exit(elJSON::encode(array('error' => 'Data saved')));
				//exit('[{"error": "Data saved"}]');
			}
		}

		$cat = $this->_factory->create(EL_IS_CAT, 1);
		$this->_initRenderer();
		$this->_rnd->rndYandexMarket(array('id' => 'cat_'.$cat->ID, 'name' => $cat->name));
	}

	/**
	 * Yandex.Market generate xml file
	 *
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
		$yml_cur = sprintf('<currency id="%s" rate="1">', $currency->current['intCode']);

		// Categories
		$cat = $this->_factory->create(EL_IS_CAT, 1);
		$categories = array();
		$this->_yandexMarketGetCategories($cat, $categories);

		$yml_cat = '';
		$s       = "\t\t\t".'<category id="%d" parentId="%d">%s</category>'."\n";
		foreach ($categories as $c)
		{
			$yml_cat .= sprintf($s, $c['id'], $c['parentID'], $this->_ymlSC($c['name']));
		}

		// Generate simple offers
		$yml_offer = '';
		$s = <<<EOL
			<offer id="%d" available="true">
				<url>%s</url>
				<price>%.2f</price>
				<currencyId>%s</currencyId>
				<categoryId>%d</categoryId>
				<picture>%s</picture>
				<delivery>true</delivery>
				<local_delivery_cost>%.2f</local_delivery_cost>
				<name>%s</name>
				<vendor>%s</vendor>
				<vendorCode>%s</vendorCode>
				<description>%s</description>
				<country_of_origin>%s</country_of_origin>
				<barcode>%s</barcode>
			</offer>

EOL;
		foreach ($this->_factory->ic->create('yandex_market', 1) as $i)
		{
			$yml_offer .= sprintf($s,
				$i->ID,                                      // offer id
				$this->getItemUrl($i->ID),                   // url
				$currency->convert($i->price, $curOpts),     // price
				$currency->current['intCode'],               // currencyId
				array_shift($i->getCats()),                  // categoryId
				$i->getDefaultTmb('c'),                      // picture
				'',                                          // local_delivery_cost
				$this->_ymlSC($i->name),                     // name
				$this->_ymlSC($i->getMnf()->name),           // vendor
				'',                                          // vendor code
				$this->_ymlSC($i->content),                  // description
				'',                                          // country_of_origin
				''                                           // barcode
			);
		}

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

		<local_delivery_cost>%.2f</local_delivery_cost>

		<offers>
%s
		</offers>

	</shop>
</yml_catalog>
EOL;
		$full_yml = sprintf($yml
			date('Y-m-d H:i'),  // yml_catalog date
			null,  // name
			null,  // company
			null,  // url
			$yml_cur,  // currencies
			$yml_cat,  // categories
			null,  // local_delivery_cost
			$yml_offer  // offers
		);
	}

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

	/**
	 * Import 1C CommerceML DEPRECATED
	 *
	 * @return void
	 **/
	function importCommerceML()
	{
		$this->_makeImportCMLForm();
		
		if (!$this->_form->isSubmitAndValid() )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $this->_form->toHtml() );
			return;
		}
		
		$data = $this->_form->getValue();
		//elPrintR($data);
		//echo file_get_contents($data['source']['tmp_name']);
		
		$cmlParser = & elSingleton::getObj('elCommerceMLv2');
		$raw = $cmlParser->parse(file_get_contents($data['source']['tmp_name']));
		
		

		$source = array();
		for($i=0, $s=sizeof($raw); $i<$s; $i++)
		{
			
			$source[$raw[$i]['Id']] = $raw[$i];
		}
		unset($raw);
		//elPrintR($source);
		$itemsIDs = array();
		$db = & elSingleton::getObj('elDb');
		$tb = $this->_factory->tb('tbi');
		$tbi2c = $this->_factory->tb('tbi2c');
		$sql = 'SELECT code_1c FROM '.$tb; echo $sql;
		
		$itemsIDs = $db->queryToArray('SELECT code_1c FROM '.$tb, null, 'code_1c');
		//elPrintR($itemsIDs);
		
		$insert = array_diff(array_keys($source), $itemsIDs); 
		$update = array_intersect($itemsIDs, array_keys($source)); 
		//elPrintR($insert);
		elPrintR($update);
		$sql = 'INSERT INTO %s (code_1c, name, price) VALUES ("%s", "%s", "%s")';
		foreach ($insert as $id)
		{
			$db->query( sprintf($sql, $tb, $id, mysql_real_escape_string($source[$id]['ItemName']), $source[$id]['ItemPrice']));
			$itemID = $db->insertID();
			$db->query('INSERT INTO '.$tbi2c.' (i_id, c_id) VALUES ('.$itemID.', 1)');
		}
		
		$sql = 'UPDATE %s SET name="%s", price="%s" WHERE code_1c="%s"';
		foreach ($update as $id)
		{
			$db->query( sprintf($sql, $tb, mysql_real_escape_string($source[$id]['ItemName']), $source[$id]['ItemPrice'], $id));
		}
		
	}
	
	function _makeImportCMLForm()
	{
		$this->_form = & elSingleton::getObj('elForm');
		$this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$this->_form->setLabel( m('Import Commerce ML') );
		$this->_form->add( new elFileInput('source', m('CL file')) );
		
		$this->_form->setRequired('source');
	}

  //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

	/**
	 * Create module config form
	 *
	 * @return void
	 **/
	function &_makeConfForm() {
		$form     = & parent::_makeConfForm();
		$currency = &elSingleton::getObj('elCurrency');
		$attrs    = array('style' => 'width:220px');
		$cAttrs   = array('cellAttrs'=>'class="form-tb-sub"');
		$params   = array(
			'default_view' => array(
				EL_IS_VIEW_CATS  => m('Categories'), 
				EL_IS_VIEW_MNFS  => m('Manufacturers'),
				EL_IS_VIEW_TYPES => m('Products types')
			),
			'deep' => array( m('All levels'), 1, 2, 3, 4),
			'view' => array( 1=>m('One column'), 2=>m('Two columns')),
			'sort' => array(
				EL_IS_SORT_NAME  => m('By name'),
				EL_IS_SORT_CODE  => m('By code/articul'),
				EL_IS_SORT_PRICE => m('By price'),
				EL_IS_SORT_TIME  => m('By publish date')
			),
			'nums'    => array(m('All'), 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 40=>40, 50=>50, 100=>100),
			'imgSize' => array(50, 75, 100, 125, 150, 175, 200, 225, 250, 275, 300, 325, 350, 375, 400, 425, 450, 475, 500),
			'sliderSize' => range(1, 20),
			'displayCatDescrip' => array(
				EL_CAT_DESCRIP_NO      => m('Do not display'),
				EL_CAT_DESCRIP_IN_LIST => m('Only in categories list'),
				EL_CAT_DESCRIP_IN_SELF => m('Only in category itself'),
				EL_CAT_DESCRIP_IN_BOTH => m('In list and in category')
			),
			'price' => array(
				0 => m('Integer'), 
				2 => m('Double, two signs after dot')
				),
			'exchangeSrc' => array(
				'auto'   => m('from Central Bank of Russia'),
				'manual' => m('enter manually')
				)
		);

		// view
		$form->add( new elCData('c0', m('Layout')),   $cAttrs);
		$form->add( new elSelect('default_view',      m('Default view'),        $this->_conf('default_view'),      $params['default_view'], $attrs));
		$form->add( new elSelect('displayViewSwitch', m('Display view switch'), $this->_conf('displayViewSwitch'), $GLOBALS['yn'],          $attrs));
		//categories
		$form->add( new elCData('c01',                m('Categories')),                                  array('cellAttrs'=>'class="form-tb-sub"'));
		$form->add( new elSelect('deep',              m('How many levels of catalog will open at once'), $this->_conf('deep'),              $params['deep'],$attrs) );
		$form->add( new elSelect('catsCols',          m('Categories list view'),                         $this->_conf('catsCols'),          $params['view'], $attrs) );
		$form->add( new elSelect('displayCatDescrip', m('Display categories descriptions'),              $this->_conf('displayCatDescrip'), $params['displayCatDescrip'], $attrs));
		// manufacturers / tm
		$form->add( new elCData('c02',                m('Manufacturers')),                        array('cellAttrs'=>'class="form-tb-sub"'));
		$form->add( new elSelect('mnfsCols',          m('Manufacturers list view'),               $this->_conf('mnfsCols'),          $params['view'],              $attrs) );
		$form->add( new elSelect('displayEmptyMnf',   m('Display empty manufacturers'),           $this->_conf('displayEmptyMnf'),   $GLOBALS['yn'],               $attrs));
		$form->add( new elSelect('displayMnfDescrip', m('Display manufacturers descriptions'),    $this->_conf('displayMnfDescrip'), $params['displayCatDescrip'], $attrs));
		$form->add( new elSelect('tmsCols',           m('Trade marks/models list view'),          $this->_conf('tmsCols'),           $params['view'],              $attrs) );
		$form->add( new elSelect('displayEmptyTm',    m('Display empty trade marks/models'),      $this->_conf('displayEmptyTm'),    $GLOBALS['yn'],               $attrs));
		$form->add( new elSelect('displayTmDescrip',  m('Display Trade mark/model descriptions'), $this->_conf('displayTmDescrip'),  $params['displayCatDescrip'], $attrs));
		// types
		$form->add( new elCData('c03',                 m('Products types')),                       array('cellAttrs'=>'class="form-tb-sub"'));
		$form->add( new elSelect('typesCols',          m('Types list view'),                       $this->_conf('typesCols'),          $params['view'],              $attrs) );
		$form->add( new elSelect('displayEmptyTypes',  m('Display empty types'),                   $this->_conf('displayTypesMnf'),    $GLOBALS['yn'],               $attrs));
		$form->add( new elSelect('displayTypeDescrip', m('Display type descriptions'),             $this->_conf('displayTypeDescrip'), $params['displayCatDescrip'], $attrs));
		// documents
		$form->add( new elCData('c04',           m('Products')),                    $cAttrs);
		$form->add( new elSelect('itemsCols',    m('Products list view'),           $this->_conf('itemsCols'),    $params['view'], $attrs) );
		$form->add( new elSelect('itemsSortID',  m('Sort products by'),             $this->_conf('itemsSortID'),  $params['sort'], $attrs) );
		$form->add( new elSelect('itemsPerPage', m('Number of products per page'),  $this->_conf('itemsPerPage'), $params['nums'], $attrs) );
		$form->add( new elSelect('displayCode',  m('Display product code/articul'), $this->_conf('displayCode'),  $GLOBALS['yn'], $attrs) );
		// images
		$form->add( new elCData('c05',              m('Products images')),     $cAttrs);
		$form->add( new elSelect('tmbListSize',     m('Thumbnails size (px)'), $this->_conf('tmbListSize'),     $params['imgSize'], $attrs, false, false) );
		$form->add( new elSelect('tmbItemCardSize', m('Preview size (px)'),    $this->_conf('tmbItemCardSize'), $params['imgSize'], $attrs, false, false) );
		$form->add( new elSelect('sliderSize',      m('Slider size'),          $this->_conf('sliderSize'),      range(1, 25),       $attrs, false, false));
		// price
		$form->add( new elCData('c06',          m('Price')),                      $cAttrs);
		$form->add( new elSelect('pricePrec',   m('Price format'),                $this->_conf('pricePrec'),   $params['price'], $attrs) );
		$form->add( new elSelect('currency',    m('Currency'),                    $this->_conf('currency'),    $currency->getList(), $attrs));
		$form->add( new elSelect('exchangeSrc', m('Use exchange rate'),           $this->_conf('exchangeSrc'), $params['exchangeSrc'], $attrs));
		$form->add( new elText('commision',     m('Exchange rate commision (%)'), $this->_conf('commision'),   array('size' => 8)));
		$form->add( new elText('rate',          m('Exchange rate'),               $this->_conf('rate'),        array('size' => 8)));

		$js = "
		$('#currency').change(function() {
			var s = $(this).val() != '".$currency->current['intCode']."',
				c = $(this).parents('tr').eq(0).nextAll('tr'),
				i = 3;
			while (i--) {
				c.eq(i).toggle(s)
			}
			if (s) {
				$('#exchangeSrc').trigger('change');
			}
		}).change();
		$('#exchangeSrc').change(function() {
			var c = $('#commision').parents('tr').eq(0),
				r = $('#rate').parents('tr').eq(0).show();
		
			if ($(this).val() == 'auto') {
				c.show();
				r.hide();
			} else {
				r.show();
				c.hide();
			}
		});
		";
	
		elAddJs($js, EL_JS_SRC_ONREADY);
    	return $form;
	}


	/**
	 * Save new config. Update currency config if module currency is not equal to default one
	 *
	 * @params array  $newConf  new module config
	 * @return void
	 **/
	function _updateConf($newConf) {
		$conf = & elSingleton::getObj('elXmlConf');
		foreach ($newConf as $k=>$v) {
			if (isset($this->_conf[$k])) {
				$conf->set($k, $newConf[$k], $this->_confID);
			}
		}
		$conf->save();
		$currency = &elSingleton::getObj('elCurrency');
		if ($newConf['currency'] != $currency->getCode()) {
			$currency->updateConf();
		}
	}
	

  function &_makeNavForm($c)
  {
  	$cat     = & elSingleton::getObj('elCatalogCategory');
  	$cat->tb('el_menu');
  	$tree    = $cat->getTreeToArray(0, true, false); 
  	$tree[1] = m('Whole site');
	$form    = & parent::_makeConfForm();
	$form->setLabel( m('Configure navigation for catalog') );
  	$c['pos']   = isset($c['pos'])    ? $c['pos']   : '';
  	$c['title'] = !empty($c['title']) ? $c['title'] : '';
  	$c['deep']  = isset($c['deep'])   ? $c['deep']  : 0;
  	$c['all']   = isset($c['all'])    ? $c['all']   : 0;
  	$c['pIDs']  = !empty($c['pIDs'])  ? $c['pIDs']  : array();

  	
  	$form->add( new elSelect('pos', m('Display catalog navigation'), $c['pos'], $GLOBALS['posNLRTB']) );
  	$form->add( new elText('title', m('Navigation title'), $c['title']) );
  	$form->add( new elSelect('deep', m('How many levels of catalog display'), $c['deep'], array( m('All levels'), 1, 2, 3, 4 )) );
  	$form->add( new elCData('c1', m('Select pages on which catalog navigation will be displayed') ) );
  	$ms = new elMultiSelectList('pIDs', '', $c['pIDs'], $tree);
	$ms->setSwitchValue(1);
	$form->add( $ms );
	
	elAddJs("$('#pos').change(function() { $(this).parents('tr').eq(0).nextAll('tr').toggle(this.value != '0'); }).trigger('change');", EL_JS_SRC_ONLOAD);
  	return $form;
  }

	/**
	 * Add methods to create products by types
	 *
	 * @return void
	 **/
	function _initAdminMode() {
		// unset($this->_mMap['mnfs']); // fix commands order in admin menu
		parent::_initAdminMode();

		$tList = $this->_factory->getTypesList(); 
		foreach ($tList as $id=>$t) {
			$this->_mMap['edit'.$id] = array('m'=>'editItem',  'l'=>htmlspecialchars($t), 'g'=>'New item', 'ico'=>'icoOK');
		}
	}

	function _onInit() {
		parent::_onInit();

		$c = array('edit');
		foreach ($c as $n) {
			$this->_mMap[$n]['apUrl'] = $this->_cat->ID;
		}
		if ($this->_mnf->ID) {
			// elPrintR($this->_mnf);
			$this->_mMap['tm_edit']['apUrl'] = $this->_mnf->ID;
			$this->_mMap['tm_rm']['apUrl'] = $this->_mnf->ID;
		}
		if (!in_array($this->_mh, array('mnf', 'tm_edit'))) {
			// $this->_mMap['tm_edit']['apUrl'] = $this->_mnf->ID;
			unset($this->_mMap['tm_edit']);
		}
		// echo $this->_mh;
		// $hndls = array_diff( array_keys($this->_mMap), $this->_mMapNoAppendCatID );
		// foreach ($hndls as $k) {
		// 	$this->_mMap[$k]['apUrl'] = $this->_cat->ID;
		// }

		if (!$this->_cat->countItems()) {
			unset($this->_mMap['sort'], $this->_mMap['rm_group']);
		}

		if ('item' != $this->_mh && 'cross_links' != $this->_mh ) {
			$this->_removeMethods('cross_links');
		} else {
			$this->_mMap['cross_links']['apUrl'] .= '/'.($this->_arg(1)).'/';
		}
	}


}
?>
