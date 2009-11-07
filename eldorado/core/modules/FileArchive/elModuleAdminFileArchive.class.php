<?php
elLoadMessages('ModuleAdminDocsCatalog');

class elModuleAdminFileArchive extends elModuleFileArchive
{
  var $_mMapAdmin = array(
	  'edit'      => array('m'=>'editCat',   'l'=>'New category', 'ico'=>'icoCatNew', 'g'=>'Actions'),
	  'edit_item' => array('m'=>'editItem',  'l'=>'New item',     'ico'=>'icoNew',    'g'=>'Actions'),
	  'rm'        => array('m'=>'rmCat'),
	  'up'        => array('m'=>'moveUp'),
	  'down'      => array('m'=>'moveDown'),
	  'rm_item'   => array('m'=>'rmItem'),
	  'sort'      => array('m'=>'sortItems', 'l'=>'Sort documents in current category', 'ico'=>'icoSortAlphabet', 'g'=>'Actions'),
	  'rm_group'  => array('m'=>'rmItems',   'l'=>'Delete group of documents',          'ico'=>'icoDocGroupRm',   'g'=>'Actions')
	  );

	var $_mMapConf  = array(
		'conf'     => array('m'=>'configure',    'ico'=>'icoConf',    'l'=>'Configuration'),
		'conf_nav' => array('m'=>'configureNav', 'ico'=>'icoNavConf', 'l'=>'Configure navigation for catalog')
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


  function configureNav()
  {
  	$conf = &elSingleton::getObj('elXmlConf');
  	$form = & $this->_makeNavForm( $conf->get($this->pageID, 'catalogsNavs') );
  	//elPrintR($tree);

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
  	$data['all']   = (int)$raw['all'];
  	$data['pIDs']  = !$data['all'] && is_array($raw['pIDs']) ? $raw['pIDs'] : '';

  	if (!$data['pos'])
  	{
  		$conf->drop($this->pageID, 'catalogsNavs');
  	}
  	else
  	{
  		if ( empty($data['all']) && empty($data['pIDs']) )
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


	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//


	function &_makeConfForm()
	{
		$form = &parent::_makeConfForm();

    $form->add( new elSelect('deep', m('How many levels of catalog will open at once'),
                            $this->_conf('deep'), array( m('All levels'), 1, 2, 3, 4 ) ) );
		$views = array( 1=>m('One column'), 2=>m('Two columns'));
    $form->add( new elSelect('catsCols', m('Categories list view'),  $this->_conf('catsCols'),  $views ) );
    $form->add( new elSelect('itemsCols', m('Items list view'),      $this->_conf('itemsCols'), $views ) );

    $sort = array( m('By name'), m('By file name'), m('By modification time'), m('By popularity') );
    $form->add( new elSelect('itemsSortID', m('Sort documents by'), (int)$this->_conf('itemsSortID'), $sort));
		$nums = array(m('All'), 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 40=>40, 50=>50, 100=>100);
    $form->add( new elSelect('itemsPerPage', m('Number of documents per page'),
    												$this->_conf('itemsPerPage'), $nums ) );
    $form->add( new elSelect('displayCatDescrip', m('Display categories descriptions in categories list'),
    						$this->_conf('displayCatDescrip'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayLmd', m('Display file last modify date'),
    						$this->_conf('displayLmd'), $GLOBALS['yn']) );
    $form->add( new elSelect('displayCnt', m('Display download counter'),
    						$this->_conf('displayCnt'), $GLOBALS['yn']) );

   	return $form;
	}

	function &_makeNavForm($c)
  {
  	$cat     = & elSingleton::getObj('elCatalogCategory');
  	$cat->tb = 'el_menu';
  	$tree    = $cat->getTreeToArray(0, true, true);
  	$form    = & parent::_makeConfForm();

  	$c['pos']   = isset($c['pos'])    ? $c['pos']   : '';
  	$c['title'] = !empty($c['title']) ? $c['title'] : '';
  	$c['deep']  = isset($c['deep'])   ? $c['deep']  : 0;
  	$c['all']   = isset($c['all'])   ? $c['all']   : 0;
  	$c['pIDs']  = !empty($c['pIDs'])  ? $c['pIDs']  : array();

  	$form->setLabel( m('Configure navigation for catalog') );
	$js = "if (this.value != '0') {
		$(this).parents('tr').eq(0).nextAll('tr').show();
	} else {
		$(this).parents('tr').eq(0).nextAll('tr').hide();
	}";
  	$form->add( new elSelect('pos', m('Display catalog navigation'), $c['pos'],	$GLOBALS['posNLRTB'], array('onChange'=>$js)) );
  	$form->add( new elText('title', m('Navigation title'), $c['title']) );
  	$form->add( new elSelect('deep', m('How many levels of catalog display'), $c['deep'], array( m('All levels'), 1, 2, 3, 4 )) );
	$js = "if(this.value == '0'){ $(this).parents('tr').eq(0).nextAll('tr').show() } else { $(this).parents('tr').eq(0).nextAll('tr').hide(); } ";
  	$form->add( new elSelect('all', m('Display navigation on all pages'), $c['all'], $GLOBALS['yn'], array('onChange'=>$js)) );
  	$form->add( new elCData('c1', m('Select pages on which catalog navigation will be displayed') ) );
  	$form->add( new elCheckboxesGroup('pIDs', '', $c['pIDs'], $tree) );
	elAddJs("$('#pos').trigger('change');", EL_JS_SRC_ONLOAD);
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