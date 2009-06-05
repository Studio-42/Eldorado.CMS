<?php

class elModuleAdminVacancyCatalog extends elModuleVacancyCatalog
{
  var $_mMapAdmin = array(
	  	'edit'      => array('m'=>'editCat',  'g'=>'Actions', 'ico'=>'icoCatNew',  'l'=>'New category'),
	  	'edit_item' => array('m'=>'editItem', 'g'=>'Actions', 'ico'=>'icoUserNew', 'l'=>'New vacancy'),
	  	'rm'        => array('m'=>'rmCat'),
	  	'up'        => array('m'=>'moveUp'),
	  	'down'      => array('m'=>'moveDown'),
	  	'rm_item'   => array('m'=>'rmItem'),
	  	'sort'      => array('m'=>'sortItems',       'g'=>'Actions', 'ico'=>'icoSortAlphabet', 'l'=>'Sort documents in current category'),
	  	'rm_group'  => array('m'=>'rmItems',         'g'=>'Actions', 'ico'=>'icoDocGroupRm',   'l'=>'Delete group of documents'),
    	'edit_el'   => array('m'=>'editFormElement', 'g'=>'Actions', 'ico'=>'icoFormElNew',    'l'=>'Create form element', 'c'=>'conf_form'),
    	'rm_el'     => array('m'=>'rmFormElement'),
	  );

	var $_mMapConf  = array(
		'conf'      => array('m'=>'configure',           'l'=>'Configuration',                    'ico'=>'icoConf'),
		'conf_nav'  => array('m'=>'configureNav',        'l'=>'Configure navigation for catalog', 'ico'=>'icoNavConf'),
		'conf_form' => array('m'=>'configureResumeForm', 'l'=>'Form constructor',                 'ico'=>'icoFormConstructor'),
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

  /**
   * Конструктор формы
   *
   */
  function configureResumeForm()
  {
    $this->_loadFormElements();
    $this->_makeResumeFormConstructor();
		$this->_initRenderer();
		$this->_rnd->addToContent( $this->_form->toHtml() );
  }

  /**
   * Редактирует элемент формы резюме
   *
   * @return void
   */
  function editFormElement()
	{
		$element = & $this->_getFormElement();
		if ( !$element->editAndSave() )
		{
			$this->_initRenderer();
			return $this->_rnd->addToContent( $element->formToHtml() );
		}
		elMsgBox::put( m('Data saved') );
		elLocation(EL_URL.'conf_form');
	}

	/**
	 * Удаляет элемент формы резюме
	 *
	 */
	function rmFormElement()
	{
		$element = & $this->_getFormElement();
		if (!$element->getUniqAttr())
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
			 array($element->getObjName(), $this->_arg(1)), EL_URL.'conf_form');
		}
		$element->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $element->getObjName(), $element->getAttr('flabel')));
		elLocation(EL_URL.'conf_form');
	}
 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  /**
   * Создает форму конструктора
   *
   */
	function _makeResumeFormConstructor()
	{
		$this->_form = &elSingleton::getObj('elForm');
		$rnd = &elSingleton::getObj('elTplGridFormRenderer', 3);
		$this->_form->setRenderer($rnd);
		$this->_form->setLabel( m('Resume form') );

		$tpl = "<a href=\"".EL_URL."edit_el/1/%d/\">"
					."<img src=\"{icoEdit}\" title=\"".m('Edit')."\" class=\"icons\" /></a>"
					."<a href=\"".EL_URL."rm_el/1/%d/\" onClick=\"return confirm('".m('Do You really want to delete ')."?');\">"
					."<img src=\"{icoDelete}\" title=\"".m('Delete')."\" class=\"icons\" /></a>";

		foreach ($this->_fList as $el)
		{
			$edit = sprintf($tpl, $el->ID, $el->ID);

			if ( 'comment' == $el->type || 'subtitle' == $el->type )
			{
			  $cssClass = 'comment' == $el->type ? 'formCData' : 'formSubheader';
				$obj = $el->toFormElement();
				$this->_form->add( new elCData($el->ID, $obj->value ), array('colspan'=>2, 'class'=>$cssClass) );
				$this->_form->add( new elCData($el->ID.'_a', $edit),  array('width'=>'50', 'class'=>$cssClass, 'style'=>'white-space:nowrap') );
			}
			else
			{
				$this->_form->add( new elCData($el->ID.'_l', $el->label) );
				$this->_form->add( $el->toFormElement() );
				$this->_form->add( new elCData($el->ID.'_a', $edit), array('width'=>'50', 'style'=>'white-space:nowrap') );
			}
			if ( 'none' != $el->valid )
			{
				$this->_form->setElementRule($el->ID, $el->valid, true, null, $el->errorMsg);
			}
		}
		$rnd->addButton(new elSubmit('submit', null, m('Submit')) );
		$rnd->addButton(new elReset('reset', null, m('Drop')));
	}

  function &_makeConfForm()
  {
    $form   = & parent::_makeConfForm();
    $ec     = & elSingleton::getObj('elEmailsCollection');

    $levels = array( m('All levels'), 1, 2, 3, 4 );
    $views  = array( 1=>m('One column'), 2=>m('Two columns'));
    $sort   = array(m('By name'), m('By publish date') );
    $nums   = array(m('All'), 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 40=>40, 50=>50, 100=>100);
    $rForms = array(1=>m('Use default'), 0=>m('Create in form constructor'));

    $form->add( new elSelect('deep', m('How many levels of catalog display'), $this->_conf('deep'), $levels ) );
    $form->add( new elSelect('catsCols',     m('Categories list view'), $this->_conf('catsCols'), $views ) );
    $form->add( new elSelect('itemsCols',    m('Items list view'),      $this->_conf('itemsCols'), $views ) );
    $form->add( new elSelect('itemsSortID',  m('Sort documents by'),    (int)$this->_conf('itemsSortID'), $sort) );
    $form->add( new elSelect('itemsPerPage', m('Number of documents per page'),	$this->_conf('itemsPerPage'), $nums ) );
    $form->add( new elSelect('displayCatDescrip', m('Display current category description'),
    						$this->_conf('displayCatDescrip'), $GLOBALS['yn']) );

    $form->add( new elCData('c1', m('Resume form configuration')), array('cellAttrs'=>'class="formSubheader"') );
    $form->add( new elCheckBoxesGroup('rcptIDs', m('Recipients list'), $this->_conf('rcptIDs'), $ec->getLabels()) );
    $form->add( new elSelect('useDefaultForm', m('Resume form'), $this->_conf('useDefaultForm'), $rForms) );
    $form->add( new elSelect('allowAttach', m('Allow send resume in attached file (only for default form)'),
      $this->_conf('allowAttach'), $GLOBALS['yn']) );
    $form->add( new elText('replyMsg', m('Reply message'), $this->_conf('replyMsg')) );
		$form->setRequired('rcptIDs[]');
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
  	$c['all']   = isset($c['all'])    ? $c['all']   : 0;
  	$c['pIDs']  = !empty($c['pIDs'])  ? $c['pIDs']  : array();

  	$form->setLabel( m('Configure navigation for catalog') );
  	$form->add( new elSelect('pos', m('Display catalog navigation'), $c['pos'],
  														$GLOBALS['posNLRTB'], array('onChange'=>'checkNavForm();')) );
  	$form->add( new elText('title', m('Navigation title'), $c['title']) );
  	$form->add( new elSelect('deep', m('How many levels of catalog display'), $c['deep'],
  														array( m('All levels'), 1, 2, 3, 4 )) );
  	$form->add( new elSelect('all', m('Display navigation on all pages'), $c['all'],
  														$GLOBALS['yn'], array('onChange'=>'checkNavForm();')) );
  	$form->add( new elCData('c1', m('Select pages on which catalog navigation will be displayed') ) );
  	$form->add( new elCheckboxesGroup('pIDs', '', $c['pIDs'], $tree) );
  	elAddJs( 'CatalogsAdminCommon.lib.js', EL_JS_CSS_FILE);
  	elAddJs( 'checkNavForm();', EL_JS_SRC_ONLOAD);
  	return $form;
  }

  function _onInit()
  {
    parent::_onInit();
    if (! $this->_cat->countItems())
    {
    	unset($this->_mMap['sort'], $this->_mMap['rm_group']);
    }
    if ($this->_conf('useDefaultForm'))
    {
      unset($this->_mMap['conf_form'], $this->_mMap['edit_el'], $this->_mMap['rm_el']);
    }
    elseif ('conf_form' <> $this->_mh && 'edit_el' <> $this->_mh && 'rm_el' <> $this->_mh )
    {
      unset($this->_mMap['edit_el'], $this->_mMap['rm_el']);
    }
  }

}

?>