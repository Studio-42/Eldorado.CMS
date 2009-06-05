<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndGoodsCatalog extends elCatalogRenderer
{
  var $_pricePrec = 0;
  var $_currInfo  = array();
	
  function setCurrency( $currencyInfo )
  {
    $this->_currInfo = $currencyInfo; 
    $this->_te->assignVars( 'currency',     $this->_currInfo['currency'] );
    $this->_te->assignVars( 'currencySign', $this->_currInfo['currencySign'] );
    $this->_te->assignVars( 'currencyName', $this->_currInfo['currencyName'] );
  }

  function setPricePrecision( $p )
  {
    $this->_pricePrec = $p;
  }

	function renderItem( $item )
	{
		$this->_setFile( 'item' );
        
		$vars = $item->toArray();
		if ( 0 < $vars['price'] )
		{
				$vars['price'] = $this->_formatPrice($vars['price']);	
				$this->_te->assignBlockVars('ITEM_PRICE', array('id'=>$vars['id'], 'price'=>$vars['price']));
		}
		$this->_te->assignVars( $vars );
	}

	//************************************************//
	//							PRIVATE METHODS										//
	//************************************************//
	
	function _formatPrice( $pr )
  	{
    	return 0 < $pr 
            ? number_format(round($pr, $this->_pricePrec), $this->_pricePrec, $this->_currInfo['decPoint'], $this->_currInfo['thousandsSep'])
            : '';
  	}
	
	/**
	 * Рисует список документов в одну колонк
	 *
	 * @param  array  $items  массив документов
	 * @return void
	 **/
	function _rndItemsOneColumn($items)
	{
		for ($i=0,$s=sizeof($items); $i<$s; $i++)
		{
			$vars = $items[$i]->toArray();
			$vars['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM', $vars, 1);
			if ( 0 < $vars['price'] )
			{
				$vars['price']    = $this->_formatPrice($vars['price']);	
				$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.ITEM_ONECOL_PRICE', array('id'=>$vars['id'], 'price'=>$vars['price']), 2);
			}
		}
	}

	/**
	 * Рисует список документов в две колонки
	 *
	 * @param  array  $items  массив документов
	 * @return void
	 **/
	function _rndItemsTwoColumns($items)
	{
		$rowCnt = 0;
		for ($i=1, $s = sizeof($items); $i<=$s; $i++ )
		{
			if ( $i%2  )
			{
				$cssRowClass = ++$rowCnt%2 ? 'strip-ev' : 'strip-odd';
				$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW', array('cssRowClass'=>$cssRowClass), 1);
			}
			$vars = $items[$i-1]->toArray();
			$vars['cssRowClass']  = $cssRowClass;
			$vars['cssLastClass'] = $i%2 ? '' : 'col-last';
			$this->_te->assignBlockVars( 'ITEMS_TWOCOL.IROW.T_ITEM', $vars, 2 );
			if ( 0 < $vars['price'] )
			{
				$vars['price'] = $this->_formatPrice($vars['price']);	
				$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.ITEM_TWOCOL_PRICE', array('id'=>$vars['id'], 'price'=>$vars['price']), 3);
			}
		}
	}

}

?>