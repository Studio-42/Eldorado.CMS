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

	var $_propBlocks = array(
		'top'    => 'IS_ITEM_PROP_TOP',
		'table'  => 'IS_ITEM_PROP_TABLE',
		'bottom' => 'IS_ITEM_PROP_BOTTOM'
		);

	/**
	 * initilize object
	 *
	 * @param  string       directory under style/modules name
	 * @param  array        module configuration
	 * @param  bool         is in admin mode
	 * @return void
	 **/
	function init($dirname, $conf, $admin=false) {
		parent::init($dirname, $conf, $admin);
		
		if ($this->_view != $this->_conf('default_view')) {
			$this->_te->assignVars('ishopView', $this->_view == EL_IS_VIEW_MNFS ? 'mnfs/' : 'cats/');
		}
		
		$this->_te->assignVars(array(
			'catID'    => $this->_cat->ID,
			'mnfID'    => $this->_mnf->ID,
			'parentID' => $this->_view == EL_IS_VIEW_MNFS ? $this->_mnf->ID : $this->_cat->ID,
			'ishopURL' => $this->_url,
			'ishopCatsURL' => $this->_urlCats,
			'ishopMnfsURL' => $this->_urlMnfs
			));
		
		
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
	 * render default view - categories
	 *
	 * @param  array  $cats     categories
	 * @param  array  $items    items
	 * @param  int    $total    number of pages
	 * @param  int    $current  current page number
	 * @return void
	 **/
	function render($cats, $items, $total, $current) {
		$this->_setFile();
		$this->_rndViewSwitch();

		if ($this->_conf('displayCatDescrip') > EL_CAT_DESCRIP_IN_LIST && $this->_cat->content) {
			$this->_te->assignBlockVars('PARENT_CAT', array('name' => $this->_cat->name, 'content' => $cat->descrip));
		}

		if ($cats) {
			if ($this->_conf('catsCols') > 1) {
				$this->_rndCatsTwoColumns($cats);
			} else {
				$this->_rndCatsOneColumns($cats);
			}
		}
		if ($cats && $items) {
			$this->_te->assignBlockVars('DC_HDELIM');
		}
		if ($items) {
			$this->_rndItems($items, $total, $current);
		}
	}

	/**
	 * Render manufacturers one/two column list
	 *
	 * @param  array  $mnfs  manufacturers
	 * @return void
	 **/
	function rndMnfs($mnfs) {
		$this->_setFile();
		$this->_rndViewSwitch();
		
		// remove empty manufacturers if required
		if (!$this->_admin && !$this->_conf('displayEmptyMnf')) {
			foreach ($mnfs as $id => $mnf) {
				if (!$mnd->countItems()) {
					unset($mnfs[$id]);
				}
			}
		}
		
		$descrip = $this->_conf('displayMnfDescrip') == EL_CAT_DESCRIP_IN_LIST || $this->_conf('displayMnfDescrip') == EL_CAT_DESCRIP_IN_BOTH;
		$i = 0;
		if ($this->_conf('mnfsCols') > 1) { // two columns
			$rowCnt = 0;
			$s      = sizeof($mnfs);
			foreach ($mnfs as $mnf) {
				$css = array('cssLastClass' => 'col-last');
				if (!($i++%2)) {
					$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s ? 'invisible' : '');
					$this->_te->assignBlockVars('MNFS_TWOCOL', $var);
					$css['cssLastClass'] = '';
				}
				!$descrip && $mnf->content = '';
				$this->_rndMnfInList('MNFS_TWOCOL', $mnf, $css);
			}
		} else {  // one column
			foreach ($mnfs as $mnf) {
				$css = array('cssRowClass' => $i++%2 ? 'strip-odd' : 'strip-ev');
				!$descrip && $mnf->content = '';
				$this->_rndMnfInList('MNFS_ONECOL', $mnf, $css);
			}
		}
	}

	/**
	 * Render manufacturer
	 *
	 * @param  elIShopManufacturer  $mnf
	 * @return void
	 **/
	function rndMnf($mnf, $items, $total, $current) {
		$this->_setFile();
		$this->_rndViewSwitch();
		
		$this->_te->assignBlockVars('PARENT_MNF_TM', $mnf->toArray());
		if ($this->_conf('displayMnfDescrip') > EL_CAT_DESCRIP_IN_LIST && $mnf->content) {
			$this->_te->assignBlockVars('PARENT_MNF_TM.DESCRIP', array('content' => $mnf->content));
		}
		
		$tms = $mnf->getTms(); 
		// remove empty tms
		if (!$this->_admin || !$this->_conf('displayEmptyTm')) {
			foreach ($tms as $id => $tm) {
				if (!$tm->countItems()) {
					unset($tms[$id]);
				}
			}
		}
		
		// render trademarks
		if ($tms) {
			$descrip = $this->_conf('displayTmDescrip') == EL_CAT_DESCRIP_IN_LIST 
					|| $this->_conf('displayTmDescrip') == EL_CAT_DESCRIP_IN_BOTH;
			
			$i = 0;
			if ($this->_conf('tmsCols')>1) {
				$rowCnt = 0;
				$s      = sizeof($tms);
				foreach ($tms as $tm) {
					$css = array('cssLastClass' => 'col-last');
					if (!($i++%2)) {
						$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s ? 'invisible' : '');
						$this->_te->assignBlockVars('TMS_TWOCOL', $var);
						$css['cssLastClass'] = '';
					}
					!$descrip && $tm->content = '';
					$this->_rndTmInList('TMS_TWOCOL', $tm, $css);
				}
			} else {
				foreach ($tms as $tm) {
					$css = array('cssRowClass' => $i++%2 ? 'strip-odd' : 'strip-ev');
					!$descrip && $tm->content = '';
					$this->_rndTmInList('TMS_ONECOL', $tm, $css);
				}
			}
		}
		
		if ($tms && $items) {
			$this->_te->assignBlockVars('DC_HDELIM');
		}
		
		// render items
		if ($items) {
			$this->_rndItems($items, $total, $current);
		}
	}

	/**
	 * render trademark
	 *
	 * @return void
	 **/
	function rndTm($mnf, $tm, $items, $total, $current) {
		$this->_setFile();
		$this->_rndViewSwitch();
		
		$this->_te->assignBlockVars('PARENT_MNF_TM', $tm->toArray());
		if ($this->_conf('displayMnfDescrip') > EL_CAT_DESCRIP_IN_LIST && $tm->content) {
			$this->_te->assignBlockVars('PARENT_MNF_TM.DESCRIP', array('content' => $tm->content));
		}

		// render items
		if ($items) {
			$this->_rndItems($items, $total, $current);
		}
		
	}

  	/**
	 * render item
	 *
	 * @param  elIShopItem  $item
	 * @param  array        $linkedObjs  not implemented yet
	 * @return void
	 **/
	function rndItem($item, $linkedObjs=null) {
		
		elAddJs('jquery.js', EL_JS_CSS_FILE);
		elAddJs('jquery.fancybox.min.js', EL_JS_CSS_FILE);
		elAddCss('fancybox.css');
		$this->_setFile('item');
		$this->_te->assignVars( $item->toArray() );
		$this->_te->assignVars('ishopSliderSize', (int)$this->_conf('ishopSliderSize'));
		
		if (!empty($this->_conf['displayCode'])) {
			$this->_te->assignBlockVars('IS_ITEM_CODE', array('code'=>$item->code));
		}
		
		$mnf = $item->getMnf();
		if ($mnf->ID) {
			$this->_te->assignBlockVars('IS_ITEM_MNF', $mnf->toArray());
		}
		$tm = $item->getTm();
		if ($tm->ID) {
			$this->_te->assignBlockVars('IS_ITEM_TM', $tm->toArray());
		}
		if ($item->price > 0) {
			$this->_te->assignBlockVars('IS_ITEM_PRICE', array('id'=>$item->ID, 'price'=>$this->_price($item->price)));
		    $this->_te->assignBlockVars('IS_ITEM_ORDER', array('id'=>$item->ID));
  		}
		
		$props = $item->getProperties();
		foreach ($props as $pos=>$p) {
			if ($pos == 'order') {
				
			} elseif (isset($this->_propBlocks[$pos])) {
				$this->_te->assignBlockFromArray($this->_propBlocks[$pos].'.PROP', $p, 1);
			}
		}
		
		$gallery = $item->getGallery();
		$gsize   = count($gallery);
		if ($gsize) {
			elAddCss('elslider.css',   EL_JS_CSS_FILE);
			elAddJs('jquery.elslider.js', EL_JS_CSS_FILE);
			$img  = current($gallery);
			$s    = @getimagesize($item->getTmbPath(key($gallery), 'c'));
			$vars = array(
				'id'     => $item->ID,
				'img_id' => key($gallery),
				'tmb'    => $item->getTmbURL(key($gallery), 'c'),
				'target' => EL_BASE_URL.$img,
				'alt'    => htmlspecialchars($item->name),
				'w'      => $s[0],
				'h'      => $s[1]
				);

			$this->_te->assignBlockVars('IS_ITEM_GALLERY', $vars);
			
			if ($gsize == 1 && $this->_admin) {
				$this->_te->assignBlockVars('IS_ITEM_GALLERY.PREVIEW_ADMIN', $vars, 1);
			}
			if ($gsize > 1) {
				foreach ($gallery as $id=>$img) {
					$vars = array(
						'id'     => $item->ID,
						'img_id' => $id,
						'tmb'    => $item->getTmbURL($id, 'l'),
						'alt'    => htmlspecialchars($item->name),
						'target' => EL_BASE_URL.$img
						);
					$this->_te->assignBlockVars('IS_ITEM_GALLERY.IS_ITEM_SLIDER.IS_ITEM_TMB', $vars, 2);
					if ($this->_admin) {
						$this->_te->assignBlockVars('IS_ITEM_GALLERY.IS_ITEM_SLIDER.IS_ITEM_TMB.TMB_ADMIN', $vars, 3);
					}
				}
			}
		}
		
		if ($this->_admin) {
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
			$this->_te->assignBlockVars('ITEM_ADMIN', array('id'=>$item->ID, 'type_id' => $item->typeID));
		}
		$this->_rndLinkedObjs($linkedObjs);
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













	/**********************************************/
	/*****              PRIVATE              ******/
	/**********************************************/

	/**
	 * render view switch tabs if enabled
	 *
	 * @return void
	 **/
	function _rndViewSwitch() {
		if ($this->_conf('displayViewSwitch')) {
			$this->_te->assignBlockVars('ISHOP_VIEW_SWITCH.VIEW_CATS', array('cssClass' => $this->_view == EL_IS_VIEW_CATS ? 'current' : ''), 1);
			$this->_te->assignBlockVars('ISHOP_VIEW_SWITCH.VIEW_MNFS', array('cssClass' => $this->_view == EL_IS_VIEW_MNFS ? 'current' : ''), 1);
		}
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _rndItems($items, $total, $current) {
		if ($this->_conf('itemsCols') > 1) {
			$this->_rndItemsTwoColumns($items);
		} else {
			$this->_rndItemsOneColumn($items);
		}
		if ($total > 1) {
			$this->_rndPager($total, $current);
		}
	}

	/**
	 * Render intem in one/two col list
	 *
	 * @param  string  $block  root block name
	 * @param  object  $item   item to display
	 * @param  array   $css    css for block
	 * @return void
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

		if (false != ($img = $item->getDefaultTmb())) {
			$vars = array(
	 			'id'  => $item->ID,
	 			'src' => $img,
	 			'alt' => htmlspecialchars($item->name)
	 			);
			$this->_te->assignBlockVars($block.'.ITEM.IMG', $vars, 2 );
		}

		$this->_te->assignBlockFromArray($block.'.ITEM.ANN_PROPS.ANN_PROP', $item->getAnnouncedProperties(), 3);
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
			$css = array('cssLastClass' => 'col-last');
			$data['cssLastClass'] = 'col-last';
			if (!($i++%2)) {
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s ? 'invisible' : '');
				$this->_te->assignBlockVars('ITEMS_TWOCOL', $var);
				$css['cssLastClass'] = '';
				$data['cssLastClass'] = '';
			}
			
			$this->_rndItemInList('ITEMS_TWOCOL', $item, $css);
			continue;
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
	 * render manufacturer in one/two column list
	 *
	 * @param  string  $block  root block name
	 * @param  object  $mnf    manufacturer to display
	 * @param  array   $css    css for block
	 * @return void
	 **/
	function _rndMnfInList($block, $mnf, $css) {
		$this->_te->assignBlockVars($block.'.MNF', $css, 1);
		
		$this->_te->assignBlockVars($block.'.MNF', $mnf->toArray(), 2);
		if ($mnf->logo) {
			$this->_te->assignBlockVars($block.'.MNF.LOGO', array('logo' => substr($mnf->logo, 1)), 2);
		}
		if ($mnf->content) {
			$this->_te->assignBlockVars($block.'.MNF.DESCRIP', array('content' => $mnf->content), 2);
		}
		if ($this->_admin) {
			$this->_te->assignBlockVars($block.'.MNF.ADMIN', array('id' => $mnf->ID), 2);
		}
	}
	
	/**
	 * Render trademark in one/two column list
	 *
	 * @param  string  $block  root block name
	 * @param  object  $tm     trademark to display
	 * @param  array   $css    css for block
	 * @return void
	 **/
	function _rndTmInList($block, $tm, $css) {
		$this->_te->assignBlockVars($block.'.TM', $css, 1);
		$this->_te->assignBlockVars($block.'.TM', $tm->toArray(), 2);
		if ($tm->descrip) {
			$this->_te->assignBlockVars($block.'.TM.DESCRIP', array('content' => $tm->content), 2);
		}
		if ($this->_admin) {
			$this->_te->assignBlockVars($block.'.TM.ADMIN', array('id' => $tm->ID), 2);
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
