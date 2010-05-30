<?php

elLoadMessages('ModuleAdminDocsCatalog');

class elModuleAdminIShop extends elModuleIShop
{
   var $_mMapAdmin = array(
	  	'edit'        => array('m'=>'editCat',          'g'=>'Actions', 'ico'=>'icoCatNew',         'l'=>'New category'),
	  	'rm'          => array('m'=>'rmCat'),
	  	'move'        => array('m'=>'moveCat'),
	  	'rm_item'     => array('m'=>'rmItem'),
	  	'item_img'    => array('m'=>'itemImg'),
	  	'rm_img'      => array('m'=>'rmItemImg'),
	  	'types'       => array('m'=>'displayItemsTypes', 'g'=>'Actions', 'ico'=>'icoItemTypesList', 'l'=>'Items types list'),
	  	'edit_type'   => array('m'=>'editItemsType',     'g'=>'Actions', 'ico'=>'icoItemTypeNew',   'l'=>'New items type'),
	  	'mnfs_list'   => array('m'=>'mnfList',           'g'=>'Actions', 'ico'=>'icoMnfList',       'l'=>'Manufacturers list' ),
	  	'edit_mnf'    => array('m'=>'editMnf',           'g'=>'Actions', 'ico'=>'icoMnfNew',        'l'=>'New manufacturer' ),
		'rm_mnf'      => array('m'=>'rmMnf'),
	  	'edit_tm'     => array('m'=>'editTm',            'g'=>'Actions', 'ico'=>'icoTmNew',         'l'=>'New trade mark'),
      	'rm_tm'       => array('m'=>'rmTm'),
		'sort'        => array('m'=>'sortItems',         'g'=>'Actions', 'ico'=>'icoSortAlphabet',  'l'=>'Sort documents in current category'),
	  	'rm_group'    => array('m'=>'rmItems',           'g'=>'Actions', 'ico'=>'icoDocGroupRm',    'l'=>'Delete group of documents',),
		'cross_links' => array('m'=>'editCrossLinks',    'g'=>'Actions', 'ico'=>'icoCrosslinks',    'l'=>'Edit linked objects list' ),
      	'rm_type'     => array('m'=>'rmItemsType'),
	  	'edit_prop'   => array('m'=>'editProperty'),
	  	'edit_pdep'   => array('m'=>'editPropertyDependance'),
	  	'rm_prop'     => array('m'=>'rmProperty')
      
	);

	var $_mMapNoAppendCatID = array('types', 'edit_type', 'rm_type', 'edit_prop', 'rm_prop');

	var $_mMapConf  = array(
		'conf'            => array('m'=>'configure',           'ico'=>'icoConf',          'l'=>'Configuration'),
		'conf_search'     => array('m'=>'configureSearch',     'ico'=>'icoSearchConf',    'l'=>'Configure advanced search'),
		'conf_nav'        => array('m'=>'configureNav',        'ico'=>'icoNavConf',       'l'=>'Configure navigation for catalog'),
		'conf_crosslinks' => array('m'=>'configureCrossLinks', 'ico'=>'icoCrosslinksConf','l'=>'Linked objects groups configuration'),
		'import_comml'    => array('m'=>'importCommerceML',    'ico'=>'icoConf',          'l'=>'Import from 1C')
	);

	/**********    манипуляции с типами товаров   *******************/

	/**
	 * Показывает список типов товаров с их свойствами
	 *
	 */
	function displayItemsTypes()
	{
    $this->_initRenderer();
    $this->_rnd->rndTypes( $this->_factory->getItemsTypes() );
	}

	/**
	 * Создание/редактирование типа товара
	 *
	 */
	function editItemsType()
	{
	   $type = $this->_factory->getItemType( (int)$this->_arg(1) );
	   if ( !$type->editAndSave() )
	   {
	     $this->_initRenderer();
	     return $this->_rnd->addToContent( $type->formToHtml() );
	   }
	   elMsgBox::put( m('Data saved') );
	   elLocation(EL_URL.'types');
	}

	/**
	 * Удаление типа товаров,
	 * если не существует товаров данного типа
	 *
	 */
  function rmItemsType()
  {
    $type = $this->_factory->getItemType( (int)$this->_arg(1) );
    if ( !$type->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($type->getObjName(), $type->ID), EL_URL.'types');
    }
    if ( $this->_factory->countItemsByType($type->ID) )
    {
      elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"', array($type->getObjName(), $type->name), EL_URL.'types');
    }
    $type->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $type->getObjName(), $type->name) );
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

  /**
   * Создание/редактирование категории
   *
   */
	function editCat()
  {
    $cat =  $this->_factory->getCategory( (int)$this->_arg(1) );
    $cat->parentID = $this->_cat->ID;

    if ( !$cat->editAndSave() )
    {
      $this->_initRenderer() ;
      return$this->_rnd->addToContent( $cat->formToHtml() );
    }
    elMsgBox::put( m('Data saved') );
    elLocation( EL_URL.$this->_cat->ID );
  }

  /**
   * Удаление непустой категории
   *
   */
  function rmCat()
  {
    $cat =  $this->_factory->getCategory( (int)$this->_arg(1) );
    if ( !$cat->ID )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($cat->getObjName(), $cat->ID), EL_URL.$this->_cat->ID);
    }

    if ( !$cat->isEmpty() )
    {
      elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"',
              array($cat->getObjName(),$cat->name),
              EL_URL.$this->_cat->ID);
    }
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
  /**
   * Перемещение категории вверх/вниз в пределах родительского узла
   * если 2-ой аргумент в URL непустой - перемещаем вверх
   *
   */
  function moveCat()
  {
    $cat =  $this->_factory->getCategory( (int)$this->_arg(1) );
  	if ( !$cat->ID )
  	{
  		elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($cat->getObjName(), $cat->ID), EL_URL.$this->_cat->ID);
  	}
  	$moveUp = (bool)$this->_arg(2);
  	if ( !$cat->move( $moveUp ) )
  	{
  	  $msg = $moveUp ? 'Can not move object "%s" "%s" up' : 'Can not move object "%s" "%s" down';
  		elThrow(E_USER_NOTICE, $msg, array($cat->getObjName(), $cat->name),	EL_URL.$this->_cat->ID );
  	}
  	elMsgBox::put( m('Data saved') );

  	elLocation(EL_URL.$this->_cat->ID);
  }

  function mnfList()
  {
    $this->_initRenderer();
    $this->_rnd->rndMnfs( $this->_factory->getMnfs() );
  }

  function editMnf()
  {
    $mnf = $this->_factory->getMnf( (int)$this->_arg(1) ); 
    if ( !$mnf->editAndSave() )
    {
      $this->_initRenderer();
      return $this->_rnd->addToContent( $mnf->formToHtml() );
    }
    elMsgBox::put( m('Data saved') );
    elLocation( EL_URL.'mnfs_list/'.$this->_cat->ID );
  }

	function rmMnf()
	{
		$mnf = $this->_factory->getMnf( (int)$this->_arg(1) ); 
		if (!$mnf->ID)
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($mnf->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);	
		}
		$mnf->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $mnf->getObjName(), $mnf->name) );
	    elLocation( EL_URL.'mnfs_list/'.$this->_cat->ID );
	}

  function editTm()
  {
    $mnfs = $this->_factory->getMnfs();
    if ( empty($mnfs) )
    {
      elThrow(E_USER_WARNING, 'Could not create trade mark without manufacturer! Create at least one manufacturer first!', null, EL_URL.$this->_cat->ID);
    }
    $tm = $this->_factory->getTm( (int)$this->_arg(1) ); //elPrintR($mnf);
    if ( !$tm->editAndSave() )
    {
      $this->_initRenderer();
      return $this->_rnd->addToContent( $tm->formToHtml() );
    }
    elMsgBox::put( m('Data saved') );
    elLocation( EL_URL.'mnfs_list/'.$this->_cat->ID );
  }

	function rmTm()
	{
		$tm = $this->_factory->getTm( (int)$this->_arg(1) );
		if (!$tm->ID)
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array($tm->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);	
		}
		$tm->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $tm->getObjName(), $tm->name) );
	    elLocation( EL_URL.'mnfs_list/'.$this->_cat->ID );
	}

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


  function &_makeConfForm()
  {
    $form = &parent::_makeConfForm();

	$params = array(
		'deep' => array( m('All levels'), 1, 2, 3, 4),
		'view' =>  array( 1=>m('One column'), 2=>m('Two columns')),
		'sort' => array(
			EL_IS_SORT_NAME  => m('By name'),
			EL_IS_SORT_CODE  => m('By code/articul'),
			EL_IS_SORT_PRICE => m('By price'),
			EL_IS_SORT_TIME  => m('By publish date')
			),
		'mnf' =>  array(
			m('Do not use'),
			EL_IS_USE_MNF    => m('Use only manufacturers info'),
			EL_IS_USE_TM     => m('Use only trade marks info'),
			EL_IS_USE_MNF_TM => m('Use both - manufacturers and trade marks info')
			),
		'nums' => array(m('All'), 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 40=>40, 50=>50, 100=>100),
		'imgSize' => array(50, 75, 100, 125, 150, 175, 200, 225, 250, 275, 300, 325, 350, 375, 400, 425, 450, 475, 500),
		'posLR'   => array(EL_POS_LEFT=>m('left'), EL_POS_RIGHT=>m('right')),
		'posLTR'  => array(EL_POS_LEFT=>m('left'), EL_POS_TOP=>m('top'), EL_POS_RIGHT=>m('right')),
		'displayCatDescrip' => array(
			EL_CAT_DESCRIP_NO      => m('Do not display'),
			EL_CAT_DESCRIP_IN_LIST => m('Only in categories list'),
			EL_CAT_DESCRIP_IN_SELF => m('Only in category itself'),
			EL_CAT_DESCRIP_IN_BOTH => m('In list and in category')
			),
		'searchPos' => array( m('Only on IShop first page'), m('On all pages') )
		);

    $form->add( new elSelect('deep', m('How many levels of catalog will open at once'), $this->_conf('deep'), $params['deep'] ) );
    $form->add( new elSelect('catsCols',  m('Categories list view'), $this->_conf('catsCols'), $params['view'] ) );
    $form->add( new elSelect('itemsCols', m('Items list view'),      $this->_conf('itemsCols'), $params['view'] ) );
    $form->add( new elSelect('itemsSortID', m('Sort documents by'),  $this->_conf('itemsSortID'), $params['sort']) );
    $form->add( new elSelect('itemsPerPage', m('Number of documents per page'), $this->_conf('itemsPerPage'), $params['nums'] ) );
	$form->add( new elSelect('displayCatDescrip', m('Display categories descriptions'), (int)$this->_conf('displayCatDescrip'), $params['displayCatDescrip']));
    // $form->add( new elSelect('displayCatDescrip', m('Display categories descriptions in categories list'), $this->_conf('displayCatDescrip'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayCode', m('Display item code/articul'), $this->_conf('displayCode'), $GLOBALS['yn']) );
    $form->add( new elSelect('mnfNfo', m('Manufacturers / Trade marks'), $this->_conf('mnfNfo'), $params['mnf']) );

    $form->add( new elCData('c1', m('Item image')), array('cellAttrs'=>'class="form-tb-sub"'));
    // $form->add( new elSelect('tmbListPos', m('Image position in items list'), $this->_conf('tmbListPos'), $params['posLR']) );
    $form->add( new elSelect('tmbListSize', m('Max image size in items list (px)'), $this->_conf('tmbListSize'), $params['imgSize'], null, false, false) );
    // $form->add( new elSelect('tmbItemCardPos', m('Image position in item card'), $this->_conf('tmbItemCardPos'), $params['posLTR']) );
    $form->add( new elSelect('tmbItemCardSize', m('Max image size in item card (px)'), $this->_conf('tmbItemCardSize'), $params['imgSize'], null, false, false) );
    
   $form->add( new elCData('c3',      m('Advanced search')), array('cellAttrs'=>'class="form-tb-sub"'));
   $form->add( new elSelect('search', m('Use advanced search'), $this->_conf('search'), $GLOBALS['yn']) );
   $form->add( new elText('searchTitle', m('Search form title'), $this->_conf('searchTitle')) );
   $form->add( new elText('searchTypesLabel', m('Items types list label'), $this->_conf('searchTypesLabel')) );
   $form->add( new elSelect('searchOnAllPages', m('Display search form'), $this->_conf('searchOnAllPages'), $params['searchPos']) );
   $form->add( new elSelect('searchColumnsNum', m('Numbers of columns in form'), $this->_conf('searchColumnsNum'), range(2,7), null, false, false) );


    return $form;
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
   * добавляет методы для редактирования товаров
   * в зависимости от созданных объектов- типов товаров
   *
   */
  function _initAdminMode()
  {
    parent::_initAdminMode();

    $tList = $this->_factory->getTypesList();
    foreach ($tList as $id=>$t)
    {
      $this->_mMap['edit'.$id] = array('m'=>'editItem',  'l'=>htmlspecialchars($t), 'g'=>'New item', 'ico'=>'icoOK');
    }
    if ( !$this->_conf('search') )
    {
      $this->_removeMethods('conf_search');
    }
  }

  function _onInit()
  {
    	parent::_onInit();

    	$hndls = array_diff( array_keys($this->_mMap), $this->_mMapNoAppendCatID );
    	foreach ( $hndls as $k )
		{
		  $this->_mMap[$k]['apUrl'] = $this->_cat->ID;
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
      		$this->_mMap['cross_links']['apUrl'] .= '/'.($this->_arg(1)).'/';
    	}
  }


}
?>
