<?php
elLoadMessages('ModuleAdminDocsCatalog');

class elModuleAdminTechShop extends elModuleTechShop
{
	var $_mMapAdmin = array(
		'mnfs'       => array('m' => 'displayManufacturers', 'g'=>'Actions', 'ico'=>'icoMnfList',       'l'=>'Manufacturers list'),
		'edit_mnf'   => array('m'=>'editManufacturer', 'g'=>'Actions', 'ico'=>'icoMnfNew',       'l'=>'New manufacturer'   ),
		'edit_cat'   => array('m'=>'editCat',          'g'=>'Actions', 'ico'=>'icoCatNew',       'l'=>'New category', 'apUrl'=>1),
		'edit_item'  => array('m'=>'editItem',         'g'=>'Actions', 'ico'=>'icoGoodNew',      'l'=>'New item',     'apUrl'=>1),
		'sort_items' => array('m'=>'sortItems',        'g'=>'Actions', 'ico'=>'icoSortAlphabet', 'l'=>'Sort items in current category' ),
		'edit_model' => array('m'=>'editModel'),
		'edit_model_img' => array('m'=>'editModelImage'),
		'up'         => array('m' => 'moveUp'),
		'down'       => array('m' => 'moveDown'),
		'rm_cat'     => array('m' => 'rmCat'),
		'rm_item'    => array('m' => 'rmItem'),
		'rm_group'   => array('m' => 'rmItems',         'g'=>'Actions', 'ico'=>'icoDocGroupRm',  'l'=>'Delete group of items', 'apUrl'=>'1'),
		'rm_mnf'     => array('m' => 'rmManufacturer'),
		'rm_model'   => array('m' => 'rmModel'),
		'rm_ftg'     => array('m' => 'rmFtGroup'),
		'rm_ft'      => array('m' => 'rmFt'),
		'ftg'        => array('m' => 'displayFtList',   'g'=>'Actions', 'ico'=>'icoFtList',        'l'=>'Features list' ),
		'edit_ftg'   => array('m' =>'editFtGroup',      'g'=>'Actions', 'ico'=>'icoFtGroupNew',    'l'=>'New features group'),
		'edit_ft'    => array('m' =>'editFt',           'g'=>'Actions', 'ico'=>'icoFtNew',         'l'=>'New feature'),
		'sort_ftg'   => array('m' => 'sortFtGroups',    'g'=>'Actions', 'ico'=>'icoSortNumeric',   'l'=>'Sort features groups'),
		'set_ft'     => array('m' => 'setFt'),
		'item_ft'    => array('m' => 'changeFtList'),
		'update'     => array('m' => 'uploadPrice',     'g'=>'Actions', 'ico'=>'icoPriceFromFile', 'l'=>'Update prices from file'  ),
    	'cross_links'=> array('m' => 'editCrossLinks',  'g'=>'Actions', 'ico'=>'icoCrosslinks',    'l'=>'Edit linked objects list' ),

    'sort_fts'   => array('m' => 'sortFts'),
	);
	var $_mMapConf  = array(
	   'conf'            => array('m'=>'configure',            'ico'=>'icoConf',           'l'=>'Configuration'),
	   'conf_nav'        => array('m'=>'configureNav',         'ico'=>'icoNavConf',        'l'=>'Configure navigation for catalog'),
	   'conf_mnav'       => array('m'=>'configureManufactNav', 'ico'=>'icoNavConf',        'l'=>'Configure manufacturers navigation for catalog'),
	   'conf_crosslinks' => array('m'=>'configureCrossLinks',  'ico'=>'icoCrosslinksConf', 'l'=>'Linked objects groups configuration')
	   );

	var $_mMapAppUrl = array('edit_cat', 'edit_item', 'rm_group', 'sort_items');


	function editCrossLinks()
	{
	  $this->_initCat( $this->_arg() );
		$this->_item =  $this->_factory->create( EL_TS_ITEM, $this->_arg(1) );
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



	function uploadPrice()
  {
    $form = & $this->_makeUploadPriceForm();

    if (!$form->isSubmitAndValid())
    {
      $this->_initRenderer();
      return $this->_rnd->addToContent($form->toHtml());
    }
    $v = $form->getValue(); //elPrintR($v); //return;
    $charset = !empty($GLOBALS['EL_CHARSETS'][$v['csvCharset']]) && 'UTF-8' != $v['csvCharset'] && function_exists('iconv')
      ? $v['csvCharset'] : null;
    list($sep, $delim) = $this->_csvParams($v['csvSep'], $v['csvDelim']);

    $price = $this->_csvToArray($v['csvFile']['tmp_name'], $sep, $delim, $charset); //elPrintR($price);
    if (empty($price))
    {
      elThrow(E_USER_ERROR, 'File "%s" is empty or has incorrect format', $v['csvFile']['tmp_name'], EL_URL);
    }
    list($iUpd, $mUpd, $nFnd, $pRows) = $this->_updatePrice($price);
    elMsgBox::put( sprintf( m('There are %d items and %d models was updated, %d records was not found. Total found %d records in csv file.'), $iUpd, $mUpd, $nFnd, $pRows));
    elLocation(EL_URL);
  }


	/** category manipulation */
	function editCat()
	{
		$this->_initCat( $this->_arg() );
		$cat =  $this->_factory->create( EL_TS_CAT, $this->_arg(1) );
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
		$this->_initCat( $this->_arg() );
		$cat =  $this->_factory->create( EL_TS_CAT, $this->_arg(1) );
		if ( !$cat->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($cat->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
		}
		if ( !$cat->isEmpty() )
		{
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"',
				array($cat->getObjName(), $cat->name),	EL_URL.$this->_cat->ID);
		}
		$cat->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $cat->getObjName(), $cat->name) );
		elLocation(EL_URL.$this->_cat->ID);
	}

	function moveUp()
	{
		$this->_initCat( $this->_arg() );
		$cat =  $this->_factory->create( EL_TS_CAT, $this->_arg(1) );
		if ( !$cat->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($cat->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
		}
		if ( !$cat->move() )
		{
			elThrow(E_USER_NOTICE, 'Can not move object "%s" "%s" up',
				array($cat->getObjName(), $cat->name),	EL_URL.$this->_cat->ID );
		}
		elMsgBox::put( m('Data saved') );
		elLocation(EL_URL.$this->_cat->ID);
	}

	function moveDown()
	{
		$this->_initCat( $this->_arg() );
		$cat =  $this->_factory->create( EL_TS_CAT, $this->_arg(1) );
		if ( !$cat->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($cat->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
		}
		if ( !$cat->move(false) )
		{
			elThrow(E_USER_WARNING, 'Can not move object "%s" "%s" up',
				array($cat->getObjName(), $cat->name), EL_URL.$this->_cat->ID );
		}
		elMsgBox::put( m('Data saved') );
		elLocation(EL_URL.$this->_cat->ID);
	}

	/** item manipulation */
	function editItem()
	{
		$this->_initCat( $this->_arg() );
		$item =  $this->_factory->create( EL_TS_ITEM, $this->_arg(1) );
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
			if ('mnf' == $this->_arg(2))
			{
			   elLocation(EL_URL.'mnf_items/'.$item->mnfID.'/');
			}
			elseif ('i' == $this->_arg(2))
			{
			  elLocation(EL_URL.'item/'.$this->_cat->ID.'/'.$item->ID.'/');
			}
			elLocation(EL_URL.$this->_cat->ID);
		}
	}

	function rmItem()
	{
		$this->_initCat( $this->_arg() );
		$item =  $this->_factory->create( EL_TS_ITEM, $this->_arg(1) );
		if ( !$item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($item->getObjName(), $this->_arg(1)),EL_URL.$this->_cat->ID);
		}
		$item->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $item->getObjName(), $item->name) );
		if ('mnf' == $this->_arg(2))
		{
		   elLocation(EL_URL.'mnf_items/'.$item->mnfID.'/');
		}
		elLocation(EL_URL.$this->_cat->ID);
	}

	function sortItems()
	{
		$this->_initCat( $this->_arg() );
		if ( !$this->_cat->countItems() )
		{
			elThrow(E_USER_WARNING, 'There are no one documents in this category was found', null, EL_URL);
		}
		$item =  $this->_factory->create( EL_TS_ITEM );

		if ( !$item->sortItems($this->_cat->ID, $this->_conf('itemsSortID')) )
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

	function rmItems()
	{
		$this->_initCat($this->_arg());
		if ( !$this->_cat->countItems() )
		{
			elThrow(E_USER_WARNING, 'There are no one documents in this category was found', null, EL_URL);
		}
		$item =  $this->_factory->create( EL_TS_ITEM );
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


	/***  Model manipulation  */

	function editModel()
	{
		$this->_initCat($this->_arg());
		$this->_item = $this->_factory->create(EL_TS_ITEM, $this->_arg(1));
		if ( !$this->_item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($this->_item->getObjName(), $this->_arg(1)),EL_URL.$this->_cat->ID);
		}
		$model = $this->_factory->create(EL_TS_MODEL, $this->_arg(2));
		if (!$model->ID)
		{
			$model->iID = $this->_item->ID;
		}
		if ( !$model->editAndSave( $this->_item->name) )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $model->formToHtml() );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			if ('mnf' == $this->_arg(2))
		   {
		       elLocation(EL_URL.'mnf_items/'.$item->mnfID.'/');
		    }
			elLocation(EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID);
		}
	}

	function editModelImage()
	{
	   $this->_initCat($this->_arg());
	   $this->_item = $this->_factory->create(EL_TS_ITEM, $this->_arg(1));
	   if ( !$this->_item->ID )
	   {
	      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
	        array($this->_item->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
	   }
	   $mID = (int)$this->_arg(2);
	   if (!$this->_item->isModelExists($mID))
	   {
	      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array(m('Model'), $mID), EL_URL.$this->_cat->ID);
	   }

	   if (!$this->_item->changeModelImage($mID, $this->_conf('modelsTmbSize')))
	   {
	      $this->_initRenderer();
	      return $this->_rnd->addToContent( $this->_item->formToHtml() );
	   }
	   elMsgBox::put( m('Data saved') );
	   $URL = 'item'==$this->_arg(3) ? EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID : EL_URL.$this->_cat->ID;
	   elLocation($URL);
	}

	function rmModel()
	{
		$this->_initCat($this->_arg());
		$this->_item = $this->_factory->create(EL_TS_ITEM, $this->_arg(1));
		if ( !$this->_item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($this->_item->getObjName(), $this->_arg(1)),EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID);
		}
		$model = $this->_factory->create(EL_TS_MODEL, $this->_arg(2));
		if (!$model->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($model->getObjName(), $this->_arg(2)),EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID);
		}
		$this->_item->deleteModel($model);
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $model->getObjName(), $model->name) );
		elLocation(EL_URL.'item/'.$this->_cat->ID.'/'.$this->_item->ID);
	}

	/***  Manufacturer manipulation  **/

	function editManufacturer()
	{
		$mnf =  $this->_factory->create( EL_TS_MNF, $this->_arg(0) );
		if ( !$mnf->editAndSave() )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $mnf->formToHtml() );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.'mnfs/');
		}
	}

	function rmManufacturer()
	{
		$mnf =  $this->_factory->create( EL_TS_MNF, $this->_arg(0) );
		if ( !$mnf->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($mnf->getObjName(), $this->_arg(0)),	EL_URL.'mnfs/');
		}
		if (!$mnf->isEmpty())
		{
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"',
				array($mnf->getObjName(), $mnf->name), EL_URL.'mnfs/');
		}
		$mnf->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $mnf->getObjName(), $mnf->name) );
		elLocation(EL_URL.'mnfs/');
	}

	/*** features manipulation ***/

	function displayFtList()
	{
		$ftg = $this->_factory->create(EL_TS_FTG);
		$this->_initRenderer();
		$this->_rnd->rndFtList( $ftg->getCollection() );
		elAppendToPagePath( array('url'=>'ftg/',	'name'=>m('Features')) );
	}

	function editFtGroup()
	{
		$ftg = $this->_factory->create( EL_TS_FTG, $this->_arg(0) );
		if ( !$ftg->editAndSave() )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $ftg->formToHtml() );
			elAppendToPagePath( array('url'=>'ftg/',	'name'=>m('Features')) );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.'ftg/');
		}
	}

	function rmFtGroup()
	{
		$ftg = $this->_factory->create( EL_TS_FTG, $this->_arg(0) );
		if (!$ftg->ID)
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($ftg->getObjName(), $this->_arg()),	EL_URL.'ftg/');
		}
		if (!$ftg->isEmpty())
		{
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"',
				array($ftg->getObjName(), $ftg->name), EL_URL.'ftg/');
		}
		$ftg->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $ftg->getObjName(), $ftg->name) );
		elLocation(EL_URL.'ftg/');
	}

	/**
	 * Сортировка групп теххарактеристик
	 * Сортируются - хар-ки с индексом >0 в порядке индексов, остальные сортируются по имени
	 *
	 */
	function sortFtGroups()
	{
	  $ftg = $this->_factory->create( EL_TS_FTG );

	  if ( !$ftg->sortGroups() )
	  {
	    $this->_initRenderer();
	    return $this->_rnd->addToContent( $ftg->formToHtml() );
	  }
    elMsgBox::put( m('Data saved') );
    elLocation( EL_URL.'ftg/');
	}


	function editFt()
	{
		$ft = $this->_factory->create( EL_TS_FT, $this->_arg(0) );
		if ( !$ft->editAndSave() )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $ft->formToHtml() );
			elAppendToPagePath( array('url'=>'ftg/',	'name'=>m('Features')) );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.'ftg/');
		}
	}

	function rmFt()
	{
		$ft = $this->_factory->create( EL_TS_FT, $this->_arg(0) );
		if (!$ft->ID)
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($ft->getObjName(), $this->_arg()),	EL_URL.'ftg/');
		}
		if (!$ft->isEmpty())
		{
			elThrow(E_USER_WARNING, 'You can not delete non empty object "%s" "%s"',
				array($ft->getObjName(), $ft->name), EL_URL.'ftg/');
		}
		$ft->delete();
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $ft->getObjName(), $ft->name) );
		elLocation(EL_URL.'ftg/');
	}

	/**
	 * Сортирует теххарактеристики внутри выбранной группы
	 * Порядок сортировки - такой же как у групп
	 *
	 */
  function sortFts()
  {
    $ftg = $this->_factory->create( EL_TS_FTG, $this->_arg(0) );
		if (!$ftg->ID)
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
				array($ftg->getObjName(), $this->_arg()),	EL_URL.'ftg/');
		}
		if ($ftg->isEmpty())
		{
			elThrow(E_USER_WARNING, 'Features group "%s" is empty',	$ftg->name, EL_URL.'ftg/');
		}

		if ($ftg->sortFeatures() )
		{
		  elMsgBox::put( m('Data saved') );
      elLocation( EL_URL.'ftg/');
		}
		$this->_initRenderer();
		$this->_rnd->addToContent( $ftg->formToHtml() );
  }

	function setFt()
	{
		$this->_initCat( $this->_arg() );
		$item =  $this->_factory->create( EL_TS_ITEM, $this->_arg(1) );
		if ( !$item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
							array($item->getObjName(), $this->_arg(1)),EL_URL.$this->_cat->ID);
		}
		if ( !$item->setFt($this->_cat->ID) )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $item->formToHtml() );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.'item/'.$this->_cat->ID.'/'.$item->ID);
		}

	}

	function changeFtList()
	{
		$this->_initCat( $this->_arg() );
		$item =  $this->_factory->create( EL_TS_ITEM, $this->_arg(1) );
		if ( !$item->ID )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
							array($item->getObjName(), $this->_arg(1)),EL_URL.$this->_cat->ID);
		}
		if (!$item->changeFtList())
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $item->formToHtml() );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.'set_ft/'.$this->_cat->ID.'/'.$item->ID.'/');
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

  function configureManufactNav()
  {
    $conf = &elSingleton::getObj('elXmlConf');
    $form = & $this->_makeMNavForm( $conf->get($this->pageID, 'techShopsMNavs') );
    if (!$form->isSubmitAndValid())
    {
      $this->_initRenderer();
      $this->_rnd->addToContent( $form->toHtml() );
    }
    else
    {
      $raw = $form->getValue(); //elPrintR($data);
      $data['pos']   = isset($GLOBALS['posNLRTB'][$raw['pos']]) ? $raw['pos'] : 0;
      $data['pids']  = is_array($raw['pids']) && !empty($raw['pids']) ? $raw['pids'] : null;
      $data['view']  = (int)$raw['view'];
      $data['title'] = $raw['title'];
      if (!$data['pos'])
		  {
			 $conf->drop($this->pageID, 'techShopsMNavs');
		  }
		  else
		  {
		    if ( empty($data['pids']) )
        {
          $form->pushError('pids[]', m('You should select at least one page') );
				  $this->_initRenderer();
				  return $this->_rnd->addToContent($form->toHtml());
        }
        $conf->set($this->pageID, $data, 'techShopsMNavs');
		  }
      $conf->save();
		  elMsgBox::put( m('Configuration was saved') );
		  elLocation( EL_URL );
    }
  }

	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//

	function _onBeforeStop()
	{
	  parent::_onBeforeStop();
	  if ( !empty($this->_item) )
	  {
	    $this->_mMap['cross_links']['apUrl'] = $this->_cat->ID.'/'.$this->_item->ID;
	  }
	  else
	  {
	    $this->_removeMethods('cross_links');
	  }

	}

	function &_makeMNavForm($c)
	{
	   $form = &parent::_makeConfForm();
	
		$js = "if (this.value != '0') {
			$(this).parents('tr').eq(0).nextAll('tr').show();
		} else {
			$(this).parents('tr').eq(0).nextAll('tr').hide();
		}";
	    $form->add( new elSelect('pos', m('Display manufacturers navigation'), isset($c['pos']) ? $c['pos'] : '', $GLOBALS['posNLRTB'], array('onChange'=>$js)) );
		$p = & new elMultiSelectList('pids', m('Pages'), !empty($c['pids']) ? $c['pids'] : null,  elGetNavTree('+', m('Whole site')));
		$p->setSwitchValue(1);
	    $form->add( $p );
	    $form->add( new elSelect('view', m('Navigation view'), isset($c['view']) ? (int)$c['view'] : 1,    array( 1=>m('Complite list'), 0=>m('Only title') ) ) );
	    $form->add( new elText('title', m('Title'), isset($c['title']) ? $c['title'] : '') );
		elAddJs("$('#pos').trigger('change');", EL_JS_SRC_ONLOAD);

	   return $form;
	}

	function &_makeConfForm()
	{
		$form = &parent::_makeConfForm();

		$form->add( new elSelect('deep', m('How many levels of catalog will open at once'),
		$this->_conf('deep'), array( m('All levels'), 1, 2, 3, 4 ) ) );

		$views = array( 1=>m('One column'), 2=>m('Two columns'));

		$form->add( new elSelect('catsCols', m('Categories list view'),  $this->_conf('catsCols'), $views ) );
		$form->add( new elSelect('itemsCols', m('Items list view'),      $this->_conf('itemsCols'), $views ) );
		$sort = array(m('By name'), m('By publish date') );
		$form->add( new elSelect('itemsSortID', m('Sort documents by'), (int)$this->_conf('itemsSortID'), $sort) );
		$nums = array(m('All'), 10=>10, 15=>15, 20=>20, 25=>25, 30=>30, 40=>40, 50=>50, 100=>100);
		$form->add( new elSelect('itemsPerPage', m('Number of documents per page'), $this->_conf('itemsPerPage'), $nums ) );
		$nums = range(0, 15); unset($nums[0], $nums[1], $nums[2]);
    	$form->add( new elSelect('modelsInRow', m('Number of models in row'), (int)$this->_conf('modelsInRow'),  $nums) );
		$form->add( new elSelect('displayCatDescrip', m('Display categories descriptions in categories list'), $this->_conf('displayCatDescrip'), $GLOBALS['yn']) );
		$form->add( new elSelect('displayManufact', m('Display manufacturer info in items'),  $this->_conf('displayManufact'), $GLOBALS['yn']) );


    	$form->add( new elSelect('eshopFunc', m('E-shop functions'), $this->_conf('eshopFunc'), array(m('Disabled'), m('Only display prices'), m('Enabled')), 
			array('OnChange'=>'if (this.value == \'0\') { $(this).parents(\'tr\').eq(0).nextAll(\'tr\').hide() } else { $(this).parents(\'tr\').eq(0).nextAll(\'tr\').show() }')  ) );
    	$form->add( new elSelect('pricePrec', m('Price format'), (int)$this->_conf('pricePrec'),  array( m('Integer'), 2=>m('Double, two signs after dot'))) );
	    $form->add( new elSelect('priceDownl', m('Allow download price-list as file'), (int)$this->_conf('priceDownl'), $GLOBALS['yn']) );
		elAddJs("$('#eshopFunc').trigger('change');", EL_JS_SRC_ONLOAD);
	    return $form;
	}


	function _getFtCollection()
	{
		$ftc        = elSingleton::getObj('elTSFeaturesCollection');
		$ftc->tbftg = $this->tbftg;
		$ftc->tbft  = $this->tbft;
		return $ftc;
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
		

		$js = "if (this.value != '0') {
			$(this).parents('tr').eq(0).nextAll('tr').show();
		} else {
			$(this).parents('tr').eq(0).nextAll('tr').hide();
		}";
		
		$form->add( new elSelect('pos', m('Display catalog navigation'), $c['pos'],  $GLOBALS['posNLRTB'], array('onChange'=>$js)) );
		$form->add( new elText('title', m('Navigation title'), $c['title']) );
		$form->add( new elSelect('deep', m('How many levels of catalog display'), $c['deep'], array( m('All levels'), 1, 2, 3, 4 )) );
		$js = "if(this.value == '0'){ $(this).parents('tr').eq(0).nextAll('tr').show() } else { $(this).parents('tr').eq(0).nextAll('tr').hide(); } ";
		$form->add( new elSelect('all', m('Display navigation on all pages'), $c['all'],  $GLOBALS['yn'], array('onChange'=>$js)) );
		$form->add( new elCData('c1', m('Select pages on which catalog navigation will be displayed') ) );
		$form->add( new elCheckboxesGroup('pIDs', '', $c['pIDs'], $tree) );

		elAddJs("$('#pos').trigger('change');", EL_JS_SRC_ONLOAD);

		return $form;
	}

	function _initCat($ID)
	{
		parent::_initCat($ID);
		if (1<>$this->_cat->ID)
		{
			foreach ($this->_mMapAppUrl as $k)
			{
				if (!empty($this->_mMap[$k]))
				{
					$this->_mMap[$k]['apUrl'] = $this->_cat->ID;
				}
			}
		}
		
	}

	function _csvToArray($fileName, $sep, $delim, $inCharset=null)
  {
    if (false == ($fp = fopen($fileName, 'r')))
    {
      return false;
    }

    $csv  = array();

    while ( false != ($data = fgetcsv($fp, 1024, $sep, $delim)) )
    {
      if (3<= sizeof($data))
      {
        $code  = $inCharset ? iconv($inCharset, 'UTF-8//TRANSLIT', $data[0]) : $data[0];
        $name  = $inCharset ? iconv($inCharset, 'UTF-8//TRANSLIT', $data[1]) : $data[1];
        $price = floatval( str_replace(',', '.', $data[2]) );
        $csv[] = array(trim($code), trim($name), $price);
      }
    }
    fclose($fp);
    return $csv;
  }

  function _updatePrice($price)
  {
    list($iCodes, $mCodes) = $this->_factory->getCodes(); // elPrintR($iCodes); elPrintR($mCodes); return;
    $sql  = 'UPDATE %s SET price="%f" WHERE code="%s"';
    $db   = & elSingleton::getObj('elDb');
    $tbi  = $this->_factory->tb('tbi');
    $tbm  = $this->_factory->tb('tbm');
    $iUpd = $mUpd = $nFnd = $pRows = 0;
    foreach ($price as $one)
    {
      if (!empty($iCodes[$one[0]]))
      { 
        $db->query( sprintf($sql, $tbi, (float)str_replace(',','.',$one[2]),$one[0]));
        $iUpd += $db->affectedRows();
      }
      elseif (!empty($mCodes[$one[0]]))
      {
       $db->query( sprintf($sql, $tbm, (float)str_replace(',','.',$one[2]),$one[0]) );
        $mUpd += $db->affectedRows();
      }
      else
      {
      ++$nFnd;
      }
      ++$pRows;
    }
    return array($iUpd, $mUpd, $nFnd, $pRows);
  }

  function &_makeUploadPriceForm()
  {
    $form = &elSingleton::getObj('elForm');
    $form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
    $f = & new elFileInput('csvFile', m('File in csv format (comma-separated text)'));
    $f->setFileExt('csv');
    $form->add( $f );

    if ( function_exists('iconv') && !empty($GLOBALS['EL_CHARSETS']) )
    {
      $form->add( new elSelect('csvCharset', m('File character set'), 'UTF-8', $GLOBALS['EL_CHARSETS']) );
    }
    else
    {
      $form->add( new elCData('c1', m('Attention! File must be in UTF-8 charset!')) );
    }

    $sp = $dl = array();
    foreach ($this->_csvSep as $k=>$v)
    {
      $sp[$k] = m($v[1]);
    }
    foreach ($this->_csvDelim as $k=>$v)
    {
      $dl[$k] = m($v[1]);
    }

    $form->add( new elSelect('csvSep',   m('Fields separated by'), 0, $sp) );
    $form->add( new elSelect('csvDelim', m('Text delimiter'),      1, $dl) );
    return $form;
  }

  function _csvParams($sp, $dl)
  {
    $sp = !empty($this->_csvSep[$sp])   ? $this->_csvSep[$sp][0]   : $this->_csvSep[0][0];
    $dl = !empty($this->_csvDelim[$dl]) ? $this->_csvDelim[$dl][0] : $this->_csvDelim[1][0];
    return array($sp, $dl);
  }
}
?>