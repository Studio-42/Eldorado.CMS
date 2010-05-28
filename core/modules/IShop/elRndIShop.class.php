<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndIShop extends elCatalogRenderer
{
  var $_tpls    = array(
						'item'   => 'item.html',
						'img'    => 'itemImg.html',
						'search' => 'search-form.html',
						'mnfs'  => 'mnfs.html',
						'types' => 'types.html',
						'sConf' => 'search-conf.html'
						);
  var $_admTpls = array(
						'item'  => 'adminItem.html',
						'types' => 'adminTypes.html',
						'mnfs'  => 'adminMnfs.html',
                        'sConf' => 'adminSearchConf.html'
                        );
  
  var $_cssClassPrefix = 'is';

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
//    elPrintR($groups);
    
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
        //elPrintR($el);
        $data = array('id'    => $el->ID,
                    'label' => $el->label,
                    'type'  => $elTypes[$el->type],
                    'opts'  => 'eltext' <> get_class($el->fElement) ? $el->fElement->toHtml() : m('No'),
                      );
        $this->_te->assignBlockVars('IS_SGROUP.IS_SGROUP_EL', $data, 1);
      }
    }

    return;
    $attrs = array('onChange'=>'popUp("'.EL_URL.EL_URL_POPUP.'/conf_search/add/"+this.value, 500, 500)');
    $sel   = & new elSelect('elTypes', '', null, array( m('Add new element') )+$elTypes[1], $attrs);
    $this->_te->assignVars('selTypes', $sel->toHtml());
    
    foreach ( $elements as $el )
    {
      $data = array('id'    => $el->ID,
                    'label' => $el->label,
                    'type'  => $elTypes[0][$el->type],
                    'opts'  => 'eltext' <> get_class($el->fElement) ? $el->fElement->toHtml() : m('No'),
                    );
      
      $this->_te->assignBlockVars('IS_SE', $data);
      if ('prop' == $el->type)
      {
        $this->_te->assignBlockFromArray('IS_SE.IS_SE_ITYPE', $el->getUsedItemsTypes(), 1);
      }
      else
      {
        $this->_te->assignBlockVars('IS_SE.IS_SE_ITYPE_ALL', null, 1);
      }
    }
  }

	function renderItem($item, $linkedObjs = null)
	{
		elAddJs('jquery.js', EL_JS_CSS_FILE);
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.fancybox.min.js', EL_JS_CSS_FILE);
		elAddCss('fancybox.css');
		
		$this->_setFile('item');
		$this->_te->assignVars( $item->toArray() );

		// Admin menu
		if ($this->_admin)
		{
			$this->_te->assignBlockVars('ITEM_ADMIN', array('id'=>$item->ID, 'type_id' => $item->typeID));
		}

		// Main image
		if (($img = $item->getImg()) != false)
		{
			$p = $this->_conf('tmbItemCardPos');
			$s = @getimagesize('.'.$img);
			$vars = array(
				'id'     => $item->ID,
				'src'    => $item->getTmbURL('c'),
				'target' => EL_BASE_URL.$img,
				'alt'    => htmlspecialchars($item->name),
				'w'      => 120 + $s[0],
				'h'      => 150 + $s[1]
			);

			if (EL_POS_TOP == $p)
			{
				$block = 'ITEM_IMG_TOP';
			}
			else
			{
				$block = 'ITEM_IMG';
				$vars['pos'] = EL_POS_RIGHT == $p ? 'right' : 'left';
			}
			$this->_te->assignBlockVars($block, $vars);
			if ($this->_admin && (!$item->getGallery()))
			{
				$this->_te->assignBlockVars($block.'.ADMIN', array(), 1);
			}
		}

		// Gallery
		if (($gallery = $item->getGallery()) != false)
		{
			$block = 'ITEM_GALLERY';
			$this->_te->assignBlockVars($block, array());
			foreach ($gallery as $img_id => $src)
			{
				$img = array(
					'tmb'     => $item->getTmbURL('l', $src),
					'target'  => $src,
					'img_id'  => $img_id
				);
				$this->_te->assignBlockVars($block.'.GALLERY_IMG', $img, 1);
				if ($this->_admin)
				{
					$this->_te->assignBlockVars($block.'.GALLERY_IMG.GALLERY_ADMIN', $img, 2);
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
	 * Рисует список товаров в одну колонк
	 *
	 * @param  array  $items  массив товаров
	 * @return void
	 **/
	function _rndItemsOneColumn($items)
	{
		$i = 0;
		foreach ($items as $item)
		{
			$data = $item->toArray();
			$data['cssRowClass'] = $i++%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.ADMIN', array('id'=>$data['id'], 'type_id' => $data['type_id']), 2);
			}
			if ( $this->_conf('displayCode'))
	  		{
				$this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.CODE', array('code'=>$item->code), 2 );
	  		}
			if ( $item->price > 0 )
	  		{
				$this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.PRICE', array('id'  => $item->ID, 'price'=>$item->price), 2 );
	  		}
			if (($img = $item->getImg()) != false)
	  		{
				$vars = array(
		 			'id'  => $item->ID,
		 			'src' => $item->getTmbURL(),
		 			'pos' => EL_POS_RIGHT == $this->_conf('tmbListPos') ? 'right' : 'left',
		 			'alt' => htmlspecialchars($item->name)
		 			);
				$this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.IMG', $vars, 2 );
	  		}
			if ( !empty($this->_conf['mnfNfo']) )
	  		{
				$vars = array('mnf'=>$item->mnf, 'country'=>$item->mnfCountry, 'tm'=>$item->tm);
				if (EL_IS_USE_MNF == $this->_conf['mnfNfo'] || EL_IS_USE_MNF_TM == $this->_conf['mnfNfo'])
	   			{
		  			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MNF_TM.MNF', $vars, 3);
				}
				if (EL_IS_USE_TM == $this->_conf['mnfNfo'] || EL_IS_USE_MNF_TM == $this->_conf['mnfNfo'])
				{
		  			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.MNF_TM.TM', $vars, 3);
				}
	  		}
			if ( false != ($props = $item->getAnnProperties()) )
	  		{
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
	function _rndItemsTwoColumns($items)
	{
		// elPrintR($this->_conf);
		$rowCnt = $i = 0;
		$s = sizeof($items);
		foreach ($items as $item)
		{
			$data = $item->toArray();
			$data['cssLastClass'] = 'col-last';
			if (!($i++%2))
			{
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s ? 'invisible' : '');
				$this->_te->assignBlockVars('ITEMS_TWOCOL', $var);
				$data['cssLastClass'] = '';
			}
			$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM', $data, 1 );
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.ADMIN', array('id'=>$data['id'], 'type_id' => $data['type_id']), 2);
			}
			if ( $this->_conf('displayCode'))
			{
			  	$this->_te->assignBlockVars( 'ITEMS_TWOCOL.ITEM.CODE', array('code'=>$item->code), 2 );
			}
			if ( $item->price > 0 )
			{
			  	$this->_te->assignBlockVars( 'ITEMS_TWOCOL.ITEM.PRICE', array('price'=>$item->price), 2 );
			}
			if (($img = $item->getImg()) != false)
			{
			  	$vars = array(
			   		'id'  => $item->ID,
			   		'src' => $item->getTmbURL(),
			   		'pos' => EL_POS_RIGHT == $this->_conf('tmbListPos') ? 'right' : 'left',
			   		'alt' => htmlspecialchars($item->name)
			   		);
			  	$this->_te->assignBlockVars( 'ITEMS_TWOCOL.ITEM.IMG', $vars, 2);
			}
			if ( !empty($this->_conf['mnfNfo']) )
            {
              	$vars = array('mnf'=>$item->mnf, 'country'=>$item->mnfCountry, 'tm'=>$item->tm);
              	if (EL_IS_USE_MNF == $this->_conf['mnfNfo'] || EL_IS_USE_MNF_TM == $this->_conf['mnfNfo'])
              	{
                	$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MNF_TM.MNF', $vars, 3);
              	}
              	if (EL_IS_USE_TM == $this->_conf['mnfNfo'] || EL_IS_USE_MNF_TM == $this->_conf['mnfNfo'])
              	{
                	$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.MNF_TM.TM', $vars, 3);
              	}
            }
			if ( false != ($props = $item->getAnnProperties()) )
            {
				$this->_te->assignBlockFromArray('ITEMS_TWOCOL.ITEM.ANN_PROPS.ANN_PROP', $props, 3);
            }
		}
	}

}

?>
