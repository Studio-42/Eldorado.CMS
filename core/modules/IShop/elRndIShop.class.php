<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';
/**
 * IShop renderer
 *
 * @package Ishop
 **/
class elRndIShop extends elCatalogRenderer {
	/**
	 * templates
	 *
	 * @var array
	 **/
	var $_tpls    = array(
		'item'   => 'item.html',
		'search' => 'search-form.html',
		'mnfs'   => 'mnfs.html',
		'types'  => 'types.html',
		'sConf'  => 'search-conf.html'
	);
	/**
	 * currency object
	 *
	 * @var elCurrency
	 **/
	var $_currency = null;
	/**
	 * currency options from config
	 *
	 * @var array
	 **/
	var $_curOpts = array();

	var $_itemPropBlocks = array(
		'top'    => 'IS_IPROP_TOP',
		'middle' => 'IS_IPROP_MIDDLE',
		'table'  => 'IS_IPROP_TABLE',
		'bottom' => 'IS_IPROP_BOTTOM'
		);

	var $_ipBlocks = array(
		'top'    => array('IS_IPROPS_TOP', 'IP_TOP'),
		'middle' => array('IS_IPROPS_MIDDLE', 'IP_TOP')
		);

	/**
	 * initilize object
	 *
	 * @return void
	 **/
	function init($moduleName, $conf, $prnt=false, $admin=false, $tabs=null, $curTab=null) {
		parent::init($moduleName, $conf, $prnt, $admin, $tabs, $curTab);
		$this->_currency = &elSingleton::getObj('elCurrency');
		$this->_curOpts = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate'),
			'format'      => true,
			'symbol'      => 1
			);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function setViewOpts($view, $catID, $mnfID) {
		echo $view;
		if ($view != $this->_conf('default_view')) {
			
		}
	}


  function _getItemPropsBlocks($pos)
  {
    if ( empty($GLOBALS['elIShopPropPos'][$pos]) && 'order'!=$pos)
    {
      $pos = 'bottom';
    }
    $pos = strtoupper($pos);
    return array('IS_IPROPS_'.$pos, 'IP_'.$pos, 'IP_'.$pos.'_NAME');
  }

  function rndSearchForm( $formHtml, $title )
  {
	$this->_setFile('search' , 'IShopSearch');
	if ( !empty($title) )
	{
	  $this->_te->assignBlockVars( 'IS_SF_TITLE', array('IShopSFTitle'=>m($title)) );
	}
	$this->_te->assignVars('IShopSearchForm', $formHtml);
	$this->_te->parse('IShopSearch', null, false, true);
	$this->addToContent( $this->_te->getVar('IShopSearch') );
  }

  function rndSearchResult( $items )
  {
    if ( empty($items) )
    {
     // $this->addToContent( m('Nothing was found') );
      //return;
    }
    $this->_setFile();
    $m = $this->_getRndMethod('items', $this->_conf('itemsCols'));
	$this->$m($items);
  }

  function rndSearchConfForm( $groups, $elTypes )
  {
    $this->_setFile('sConf');
    
    foreach ($groups as $id=>$g)
    {
      $attrs = array('onChange'=>'popUp("'.EL_URL.EL_URL_POPUP.'/conf_search/el/'.$id.'/"+this.value, 500, 500)');
      $sel   = & new elSelect('elTypes', '', null, array( m('Add new element') )+$g['available'], $attrs);
      $data  = array('gid'    => $id,
                    'label'  => $g['label'],
                    'iTypes' => !empty($g['iTypes']) ? implode(', ', $g['iTypes']) : m('All types'),
                    'newEls' => $sel->toHtml() 
                    );
      
      $this->_te->assignBlockVars('IS_SGROUP', $data);
      foreach ($g['elements'] as $el)
      {
        $data = array('id'  => $el->ID,
                    'label' => $el->label,
                    'type'  => $elTypes[$el->type],
                    'opts'  => 'eltext' <> get_class($el->fElement) ? $el->fElement->toHtml() : m('No'),
                      );
        $this->_te->assignBlockVars('IS_SGROUP.IS_SGROUP_EL', $data, 1);
      }
    }

  }

	function renderItem($item, $linkedObjs = null)
	{
		// $currency = & elSingleton::getObj('')
		

		elAddJs('jquery.js', EL_JS_CSS_FILE);
		elAddJs('jquery.fancybox.min.js', EL_JS_CSS_FILE);
		elAddCss('fancybox.css');
		
		
		
		if ($this->_admin)
		{
			elLoadJQueryUI();
			elAddCss('elfinder.css',   EL_JS_CSS_FILE);
			elAddJs('elfinder.min.js', EL_JS_CSS_FILE);
			if (file_exists(EL_DIR.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.'elfinder'.DIRECTORY_SEPARATOR.'elfinder.'.EL_LANG.'.js'))
			{
				elAddJs('i18n'.DIRECTORY_SEPARATOR.'elfinder'.DIRECTORY_SEPARATOR.'elfinder.'.EL_LANG.'.js', EL_JS_CSS_FILE);
			}
			$js =
"	$('.ishop-sel-img').click(function(e) {
		e.preventDefault();
		var actionURL = $(this).attr('href');
		$('<div />').elfinder({
			url  : '".EL_URL."__finder__/',
			lang : '".EL_LANG."',
			editorCallback : function(url) {
				$('<form action=\"'+actionURL+'\" method=\"POST\"><input type=\"hidden\" name=\"imgURL\" value=\"'+url+'\"></form>').appendTo('body').submit();
			},
			dialog : { title : 'Select image', width : 750, modal : true }
		});
	});";

			elAddJs($js, EL_JS_SRC_ONREADY);
		}
		$this->_setFile('item');
		$this->_te->assignVars( $item->toArray() );

		// Admin menu
		if ($this->_admin) {
			$this->_te->assignBlockVars('ITEM_ADMIN', array('id'=>$item->ID, 'type_id' => $item->typeID));
		}

		if (false !== ($gallery = $item->getGallery())) {
			elAddCss('elslider.css',   EL_JS_CSS_FILE);
			elAddJs('jquery.elslider.js', EL_JS_CSS_FILE);
			$img  = current($gallery);
			$s    = @getimagesize($item->getTmbPath('c'));
			
			$vars = array(
				'id'     => $item->ID,
				'img_id' => key($gallery),
				'tmb'    => $item->getTmbURL('c'),
				'target' => EL_BASE_URL.$img,
				'alt'    => htmlspecialchars($item->name),
				'w'      => $s[0],
				'h'      => $s[1]
				);
			$this->_te->assignBlockVars('IS_ITEM_GALLERY', $vars);
			if ($this->_admin && count($gallery) == 1) {
				$this->_te->assignBlockVars('IS_ITEM_GALLERY.PREVIEW_ADMIN', $vars, 1);
			}
			
			if (count($gallery) > 1) {
				foreach ($gallery as $id=>$img) {
					$vars = array(
						'id'     => $item->ID,
						'img_id' => $id,
						'tmb'    => $item->getTmbURL('l', $img),
						'tmbc'   => $item->getTmbURL('c', $img),
						'target' => EL_BASE_URL.$img
						);
					$this->_te->assignBlockVars('IS_ITEM_GALLERY.IS_ITEM_SLIDER.IS_ITEM_TMB', $vars, 2);
					if ($this->_admin) {
						$this->_te->assignBlockVars('IS_ITEM_GALLERY.IS_ITEM_SLIDER.IS_ITEM_TMB.TMB_ADMIN', $vars, 3);
					}
				}
			}
		}


		if (!empty($this->_conf['displayCode']))
		{
			$this->_te->assignBlockVars('IS_ITEM_CODE', array('code'=>$item->code));
		}

    if ( !empty($this->_conf['mnfNfo']) )
    {
      $vars = array('mnf'=>$item->mnf, 'country'=>$item->mnfCountry, 'tm'=>$item->tm);
      if (EL_IS_USE_MNF == $this->_conf['mnfNfo'] || EL_IS_USE_MNF_TM == $this->_conf['mnfNfo'])
      {
        $this->_te->assignBlockVars('IS_ITEM_MNFTM.IS_IMNF', $vars, 1);
      }
      if (EL_IS_USE_TM == $this->_conf['mnfNfo'] || EL_IS_USE_MNF_TM == $this->_conf['mnfNfo'])
      {
        $this->_te->assignBlockVars('IS_ITEM_MNFTM.IS_ITM', $vars, 1);
      }
    }


    list($pGroups, $pOrder) = $item->getProperties(); //elPrintR($pGroups);

    foreach ($pGroups as $pos=>$props)
    {
      list($bParent, $bProp, $bName) = $this->_getItemPropsBlocks($pos);
      foreach ($props as $p)
      {
        $this->_te->assignBlockVars($bParent.'.'.$bProp, $p, 1);
        if (!empty($p['name']))
        {
          $this->_te->assignBlockVars($bParent.'.'.$bProp.'.'.$bName, $p, 2);
        }
      }
    }

    if ( 0<($item->price) )
    {
		$currency = &elSingleton::getObj('elCurrency');
		$curOpts = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate'),
			'format'      => true,
			'symbol'      => 1
			);
		$item->price = $currency->convert($item->price, $curOpts);
      $this->_te->assignBlockVars('IS_ITEM_PRICE', array('id'=>$item->ID, 'price'=>$item->price));
      $this->_te->assignBlockVars('IS_ITEM_ORDER', array('id'=>$item->ID));
      if ( !empty($pOrder) )
      {
        $f = elSingleton::getObj('elForm');
        foreach ($pOrder as $one)
        {
          //elPrintR($one);
          $attrs = $one['depend'] ? array('onChange'=>'checkOrderDepend('.$item->typeID.','.$one['id'].',this.value);') : null ;
          //$sel = new elSelect('a', 'aa', null, $one['value'], $attrs, false, false);
          //$one['value'] = $sel->toHtml();
          $vars = array(
            'id'=>$one['id'],
            'itemID'=>$item->ID,
            'name'=>$one['name'],
            'onChange' => $one['depend'] ?  'onChange="checkOrderDepend('.$item->ID.','.$one['id'].',this.value);"' : ''
            );
          $selOK = 0;
          $this->_te->assignBlockVars('IS_ITEM_ORDER.IP_ORDER', $vars, 1);
          foreach ($one['value'] as $v)
          {
            $dis = !$v[1] ? 'disabled="on"' : '';
            if ( !$selOK )
            {
              if ( $v[1] )
              {
                $sel = ' selected="on"';
                $selOK = 1;
              }
              else
              {
                $sel = '';
              }
            }
            else
            {
              $sel = '';
            }

            $this->_te->assignBlockVars('IS_ITEM_ORDER.IP_ORDER.IPO_OPT', array('val'=>$v[0], 'disable'=>$dis, 'sel'=>$sel), 2);
          }
        }
      }
    }

		$this->_rndLinkedObjs($linkedObjs);
	}


  function rndTypes($types)
  {
    $this->_setFile('types');
    foreach ( $types as $type )
    {
      $this->_te->assignBlockVars('IS_TYPE', $type->toArray());
      
      foreach ($type->props as $p)
      {
        $data = array('id'=>$p->ID, 't_id'=>$type->ID);
        $pVals = $p->toArray();
              $this->_te->assignBlockVars('IS_TYPE.IS_TYPE_PROP', $data, 1);
        foreach ( $pVals as $val)
        {
          $this->_te->assignBlockVars('IS_TYPE.IS_TYPE_PROP.IS_TYPE_P', $val, 2);
        }
        if ( $p->isDependAvailable() && $p->dependID && !empty($type->props[$p->dependID]) )
        {
          $data['dependName'] = $type->props[$p->dependID]->name ;
          $this->_te->assignBlockVars('IS_TYPE.IS_TYPE_PROP.PROP_DEPEND', $data, 2);
        }
      }
    }
  }



  function rndMnfs( $mnfsList )
  {
    $this->_setFile('mnfs');
    foreach ( $mnfsList as $mnf )
    {
      $this->_te->assignBlockVars('IS_MNF', $mnf->toArray());
      if ( (EL_IS_USE_TM == $this->_conf['mnfNfo'] || EL_IS_USE_MNF_TM == $this->_conf['mnfNfo']) && !empty($mnf->tms) )
      {
        $this->_te->assignBlockVars('IS_MNF.IS_MNF_TMS', array('id'=>$mnf->ID, 'mnf'=>$mnf->name), 1);
        foreach ( $mnf->tms as $tm )
        {
          $this->_te->assignBlockVars('IS_MNF.IS_MNF_TMS.IS_MNF_TM', $tm->toArray(), 2);
        }
      }
    }
  }


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _rndItemInList($block, $item, $css) {
		$this->_te->assignBlockVars($block.'.ITEM', $css, 1);
		$this->_te->assignBlockVars($block.'.ITEM', $item->toArray(), 2);
		$mnf = $item->getMnf();
		if ($mnf->ID) {
			$this->_te->assignBlockVars($block.'.ITEM.MNF', $mnf->toArray(), 2);
		}
		$tm = $item->getTm();
		if ($tm->ID) {
			$this->_te->assignBlockVars($block.'.ITEM.TM', $tm->toArray(), 2);
		}
		
		if ($this->_admin) {
			$this->_te->assignBlockVars($block.'.ITEM.ADMIN', array('id'=>$item->ID, 'type_id' => $item->typeID), 2);
		}
		if ($this->_conf('displayCode')) {
			$this->_te->assignBlockVars($block.'.ITEM.CODE', array('code'=>$item->code), 2);
  		}
		if ($item->price > 0) {
			$this->_te->assignBlockVars($block.'.ITEM.PRICE', array('id' => $item->ID, 'price'=>$this->_price($item->price)), 2);
  		}
	}



	/**
	 * Рисует список товаров в одну колонк
	 *
	 * @param  array  $items  массив товаров
	 * @return void
	 **/
	function _rndItemsOneColumn($items) {
		
		$i = 0;
		$currency = &elSingleton::getObj('elCurrency');
		$curOpts = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate'),
			'format'      => true,
			'symbol'      => 1
			);
		foreach ($items as $item) {
			// elPrintR($item->getMnf());
			// $data = $item->toArray();
			$css = array('cssRowClass' => $i++%2 ? 'strip-odd' : 'strip-ev');
			
			$this->_rndItemInList('ITEMS_ONECOL', $item, $css);
			continue;
			
			$data['cssRowClass'] = $i++%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ($this->_admin) {
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.ADMIN', array('id'=>$data['id'], 'type_id' => $data['type_id']), 2);
			}
			if ($this->_conf('displayCode')) {
				$this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.CODE', array('code'=>$item->code), 2 );
	  		}
			if ($item->price > 0) {
				$item->price = $currency->convert($item->price, $curOpts);
				$this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.PRICE', array('id'  => $item->ID, 'price'=>$item->price), 2 );
	  		}
			if (($img = array_shift($item->getGallery())) != false) {
				$vars = array(
		 			'id'  => $item->ID,
		 			'src' => $item->getTmbURL(),
		 			'alt' => htmlspecialchars($item->name)
		 			);
				$this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.IMG', $vars, 2 );
	  		}
			if (!empty($this->_conf['mnfNfo'])) {
				$vars = array('mnf'=>$item->mnf, 'country'=>$item->mnfCountry, 'tm'=>$item->tm);
				if (EL_IS_USE_MNF == $this->_conf['mnfNfo'] 
				||  EL_IS_USE_MNF_TM == $this->_conf['mnfNfo']) {
		  			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MNF_TM.MNF', $vars, 3);
				}
				if (EL_IS_USE_TM == $this->_conf['mnfNfo'] 
				|| EL_IS_USE_MNF_TM == $this->_conf['mnfNfo']) {
		  			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MNF_TM.TM', $vars, 3);
				}
	  		}
			if (false != ($props = $item->getAnnProperties())) {
				$this->_te->assignBlockFromArray('ITEMS_ONECOL.ITEM.ANN_PROPS.ANN_PROP', $props, 3);
	  		}
		}
  	}

	/**
	 * Рисует список товаров в две колонки
	 *
	 * @param  array  $items  массив товаров
	 * @return void
	 **/
	function _rndItemsTwoColumns($items) {
		$rowCnt = $i = 0;
		$s      = sizeof($items);
		$currency = &elSingleton::getObj('elCurrency');
		$curOpts = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate'),
			'format'      => true,
			'symbol'      => 1
			);
			
		foreach ($items as $item) {
			$data = $item->toArray();
			$data['cssLastClass'] = 'col-last';
			if (!($i++%2)) {
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s ? 'invisible' : '');
				$this->_te->assignBlockVars('ITEMS_TWOCOL', $var);
				$data['cssLastClass'] = '';
			}
			$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM', $data, 1 );
			if ($this->_admin) {
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.ADMIN', array('id'=>$data['id'], 'type_id' => $data['type_id']), 2);
			}
			if ($this->_conf('displayCode')) {
			  	$this->_te->assignBlockVars( 'ITEMS_TWOCOL.ITEM.CODE', array('code'=>$item->code), 2 );
			}
			if ($item->price > 0) {
				$item->price = $currency->convert($item->price, $curOpts);
			  	$this->_te->assignBlockVars( 'ITEMS_TWOCOL.ITEM.PRICE', array('price'=>$item->price), 2 );
			}
			if (($img = array_shift($item->getGallery())) != false) {
			  	$vars = array(
			   		'id'  => $item->ID,
			   		'src' => $item->getTmbURL(),
			   		'alt' => htmlspecialchars($item->name)
			   		);
			  	$this->_te->assignBlockVars( 'ITEMS_TWOCOL.ITEM.IMG', $vars, 2);
			}
			if (!empty($this->_conf['mnfNfo'])) {
              	$vars = array('mnf'=>$item->mnf, 'country'=>$item->mnfCountry, 'tm'=>$item->tm);
              	if (EL_IS_USE_MNF == $this->_conf['mnfNfo'] 
				|| EL_IS_USE_MNF_TM == $this->_conf['mnfNfo']) {
                	$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MNF_TM.MNF', $vars, 3);
              	}
              	if (EL_IS_USE_TM == $this->_conf['mnfNfo'] 
				|| EL_IS_USE_MNF_TM == $this->_conf['mnfNfo']) {
                	$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MNF_TM.TM', $vars, 3);
              	}
            }
			if (false != ($props = $item->getAnnProperties())) {
				$this->_te->assignBlockFromArray('ITEMS_TWOCOL.ITEM.ANN_PROPS.ANN_PROP', $props, 3);
            }
		}
	}

	/**
	 * convert/format price
	 *
	 * @param  number  $price
	 * @return string
	 **/
	function _price($price) {
		return $this->_currency->convert($price, $this->_curOpts);
	}

} // END class

?>
