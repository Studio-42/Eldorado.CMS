<?php

class elCatalogRenderer extends elModuleRenderer
{
	var $_tpls      = array('item'=>'item.html' );
	var $_admTpls   = array();
	var $_tplLayout = array(
							'cats'  => array('_rndCatsOneColumn',  '_rndCatsTwoColumns'),
							'items' => array('_rndItemsOneColumn', '_rndItemsTwoColumns')
							 );
	var $_cssClassPrefix = 'dc';
	var $_catID = 1;
	
	function setCatID( $ID )
	{
		$this->_catID = $ID;
		$this->_te->assignVars( 'catID', $ID );
	}


	function render($cats, $items, $total, $current, $catName, $catDescrip)
	{
		$this->_setFile();

		if ( $this->_conf('displayCatDescrip') && !empty($catDescrip) )
		{
			$var = array('curCatName'=>$catName, 'curCatDescrip' => $catDescrip);//elPrintR($var);
			$this->_te->assignBlockVars('CUR_CAT_DESCRIP', $var );
		}
		if ( $cats )
		{
			$m = $this->_getRndMethod('cats', $this->_conf('catsCols'));
			$this->$m($cats);
			if ( $items )
			{
				$this->_te->assignBlockVars('DC_HDELIM');
			}
		}
		if ( $items )
		{
			$m = $this->_getRndMethod('items', $this->_conf('itemsCols'));
			$this->$m($items);
			if ( 1 < $total )
			{
				$this->_rndPager($total, $current);
			}
		}
	}

	function renderItem( $item, $linkedObjs=null )
	{
		$this->_setFile( 'item' );
		
		$this->_te->assignVars( $item->toArray() );
		$this->_rndLinkedObjs($linkedObjs);
	}

	//*****************************************//
	//					PRIVATE METHODS
	//*****************************************//

	function _rndLinkedObjs( $lObjs )
	{
	  if ( !empty($lObjs) && is_array($lObjs) && $this->_te->isBlockExists('LINKED_OBJS') )
	  {
		elAddJs('jquery.js', EL_JS_CSS_FILE);
	    foreach ( $lObjs as $group )
	    {
	      $this->_te->assignBlockVars('LINKED_OBJS.LOBJS_GROUP', array('name'=>$group['name']), 1);
	      foreach ( $group['items'] as $item )
	      {
	        $this->_te->assignBlockVars('LINKED_OBJS.LOBJS_GROUP.LOBJ', $item, 2);
	      }
	    }
	  }
	}

	function _getRndMethod( $type, $cols)
	{
		return !empty( $this->_tplLayout[$type][$cols-1])
			? $this->_tplLayout[$type][$cols-1]
			: $this->_tplLayout[$type][0];
	}

	/**
	 * Рисует категории в одну колонку
	 *
	 * @param  array  $cats  массив категорий
	 * @return void
	 **/
	function _rndCatsOneColumn($cats)
	{
		for ( $i=0,$s=sizeof($cats); $i<$s; $i++ )
		{
			$data = $cats[$i]->toArray();
			if ($this->_conf('displayCatDescrip'))
			{
			   $data['descrip'] = '';
			}
			$data['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$data['cssClass']    = 'cat-level-'.($cats[$i]->level<3 ? $cats[$i]->level : 3);
			
			$this->_te->assignBlockVars('CATS_ONECOL.O_CAT', $data, 1);
		}
	}

	/**
	 * Рисует категории в две колонки
	 *
	 * @param  array  $cats  массив категорий
	 * @return void
	 **/
	function _rndCatsTwoColumns($cats)
	{
		$cnt = $rcnt = 0; 
		for ($i=0, $s=sizeof($cats); $i < $s ; $i++) 
		{ 
			$last = 'col-last';
			if (1 == $cats[$i]->level && ++$cnt%2 && ++$rcnt)
			{
				$cssRowClass = $rcnt%2 ? 'strip-ev' : 'strip-odd';
				$last = '';
				$this->_te->assignBlockVars('CATS_TWOCOL.ROW', array('cssRowClass'=>$cssRowClass), 1);
			}
			
			$data = $cats[$i]->toArray();
			if ($this->_conf('displayCatDescrip'))
			{
			   $data['descrip'] = '';
			}
			$data['cssRowClass']  = $cssRowClass;
			$data['cssClass']     = 'cat-level-'.($cats[$i]->level<3 ? $cats[$i]->level : 3);
			$data['cssLastClass'] = $last;
			if ( 1 == $cats[$i]->level )
			{
				$this->_te->assignBlockVars('CATS_TWOCOL.ROW.T_CAT', $data, 2);//elPrintR($cats[$i]);
			}
			else
			{
				$this->_te->assignBlockVars('CATS_TWOCOL.ROW.T_CAT.SUBCATS.SUBCAT', $data, 4);//elPrintR($cats[$i]);
			}
			if ( $cnt%2 )
			{
				$this->_te->assignBlockVars('CATS_TWOCOL.ROW.T_CAT.CDELIM', null, 3);
			}
			
		}
	}

	function _rndItemsOneColumn($items)	{ }

	function _rndItemsTwoColumns($items) { }

	function _rndPager( $total, $current )
	{
		$this->_te->setFile('PAGER', 'common/pager.html');
		$url = EL_URL.$this->_catID.'/';
		if ( $current > 1 )
		{
			$this->_te->assignBlockVars('PAGER.PREV', array('url' => $url, 'num'=>$current-1 ));
		}
		for ( $i=1; $i<=$total; $i++ )
		{
			$this->_te->assignBlockVars($i != $current ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('num'=>$i, 'url'=>$url));
		}
		if ( $current < $total )
		{
			$this->_te->assignBlockVars('PAGER.NEXT', array('url'=>$url, 'num'=>$current+1 ));
		}
		$this->_te->parse('PAGER');
	}

}

?>