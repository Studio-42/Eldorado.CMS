<?php

class elCatalogRenderer extends elModuleRenderer
{
	var $_tpls      = array('item'=>'item.html' );
	var $_tplLayout = array(
							'cats'  => array('_rndCatsOneColumn',  '_rndCatsTwoColumns'),
							'items' => array('_rndItemsOneColumn', '_rndItemsTwoColumns')
							 );
	var $_catID = 1;
	
	function setCatID( $ID )
	{
		$this->_catID = $ID;
		$this->_te->assignVars('catID', $ID);
	}


	function render($cats, $items, $total, $current, $cat)
	{
		$this->_setFile();
		if ( !empty($cat->descrip) && $this->_conf['displayCatDescrip'] >= EL_CAT_DESCRIP_IN_SELF )
		{
			$this->_te->assignBlockVars('CUR_CAT_DESCRIP', array('name' => $cat->name, 'descrip' => $cat->descrip));
		}
		if ( $cats )
		{
			$m = $this->_getRndMethod('cats', $this->_conf('catsCols'));
			$this->$m($cats);
		}
		if ($cats && $items) {
			$this->_te->assignBlockVars('DC_HDELIM');
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
		if ($this->_admin)
		{
			$this->_te->assignBlockVars('ITEM_ADMIN', array('id' => $item->ID) );
		}
		$this->_rndLinkedObjs($linkedObjs);
	}

	//*****************************************//
	//					PRIVATE METHODS
	//*****************************************//

	function _rndLinkedObjs( $lObjs )
	{
		$this->_te->setFile('LINKED_OBJS', 'common/linked-objs.html');
		$view = isset($this->_conf['crossLinksView']) ? $this->_conf['crossLinksView'] : 0;
	  	if ( !empty($lObjs) && is_array($lObjs) && $this->_te->isBlockExists('LINKED_OBJS') )
	  	{
			elAddJs('jquery.js', EL_JS_CSS_FILE);
			$i = 0;
	    	foreach ( $lObjs as $group )
	    	{
				$class    = "hide";
				$expClass = '';
				if ($view == 2 || ($i++ == 0 && $view == 1))
				{
					$class    = '';
					$expClass = 'el-expanded';
				}
	      		$this->_te->assignBlockVars('LINKED_OBJS.LOBJS_GROUP', array('name'=>$group['name'], 'class' => $class, 'expClass' => $expClass), 1);
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
		$descrip = $this->_conf('displayCatDescrip') == EL_CAT_DESCRIP_IN_LIST || $this->_conf('displayCatDescrip') == EL_CAT_DESCRIP_IN_BOTH;

		for ( $i=0,$s=sizeof($cats); $i<$s; $i++ )
		{
			$data = $cats[$i]->toArray();
			if (!$descrip)
			{
			   $data['descrip'] = '';
			}
			$data['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$data['cssClass']    = 'cat-level-'.($cats[$i]->level<3 ? $cats[$i]->level : 3);
			
			$this->_te->assignBlockVars('CATS_ONECOL.CAT', $data, 1);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('CATS_ONECOL.CAT.ADMIN', $data, 2);
			}
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
		$rowCnt = $cnt = 0;
		$descrip = $this->_conf('displayCatDescrip') == EL_CAT_DESCRIP_IN_LIST || $this->_conf('displayCatDescrip') == EL_CAT_DESCRIP_IN_BOTH;
		
		for ($i=0, $s=sizeof($cats); $i < $s ; $i++) 
		{
			$data = $cats[$i]->toArray();
			$data['cssLastClass'] = 'col-last';
			if (!$descrip) 
			{
				$data['descrip'] = '';
			}
			if (1 == $cats[$i]->level && !($cnt++%2)) 
			{
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s-1 ? 'invisible' : '');
				$this->_te->assignBlockVars('CATS_TWOCOL', $var);
				$data['cssLastClass'] = '';
			}
			if (1 == $cats[$i]->level)
			{
				$this->_te->assignBlockVars('CATS_TWOCOL.CAT', $data, 1);
				if ($this->_admin)
				{
					$this->_te->assignBlockVars('CATS_TWOCOL.CAT.ADMIN', $data, 2);
				}
			}
			else
			{
				$this->_te->assignBlockVars('CATS_TWOCOL.CAT.SUB_CATS.SUB_CAT', $data, 3);
				if ($this->_admin)
				{
					$this->_te->assignBlockVars('CATS_TWOCOL.CAT.SUB_CATS.SUB_CAT.ADMIN2', $data, 4);
				}
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