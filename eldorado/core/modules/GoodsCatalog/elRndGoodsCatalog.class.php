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

	function renderItem( $item, $linkedObjs=null )
	{
		$this->_setFile( 'item' );
        
		$vars = $item->toArray();
		if ( 0 < $vars['price'] )
		{
			$vars['price'] = $this->_formatPrice($vars['price']);	
			$this->_te->assignBlockVars('ITEM_PRICE', array('id'=>$vars['id'], 'price'=>$vars['price']));
		}
		if ($this->_admin)
		{
			$this->_te->assignBlockVars('ITEM_ADMIN', array('id'=>$vars['id']));
		}
		$this->_te->assignVars( $vars );
		$this->_rndLinkedObjs($linkedObjs);
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
			$data = $items[$i]->toArray();
			$data['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ( 0 < $data['price'] )
			{
				$price = $this->_formatPrice($data['price']);	
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.PRICE', array('id'=>$data['id'], 'price'=>$price), 2);
			}
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
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
		for ($i=0, $s = sizeof($items); $i<$s; $i++ )
		{
			$data = $items[$i]->toArray();
			$data['cssLastClass'] = 'col-last';
			if (!($i%2))
			{
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s-1 ? 'invisible' : '');
				$this->_te->assignBlockVars('ITEMS_TWOCOL', $var);
				$data['cssLastClass'] = '';
			}
			$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM', $data, 1 );
			if ( 0 < $data['price'] )
			{
				$price = $this->_formatPrice($data['price']);	
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.PRICE', array('id'=>$data['id'], 'price'=>$price), 2);
			}
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
			}
		}
	}

}

?>