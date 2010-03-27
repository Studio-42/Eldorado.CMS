<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndGoodsCatalog extends elCatalogRenderer
{


	function renderItem( $item, $linkedObjs=null )
	{
		$this->_setFile( 'item' );
        $currency  = &elSingleton::getObj('elCurrency');
		$curOpts   = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate'),
			'format'      => true,
			'symbol'      => true
			);
		$vars = $item->toArray();
		if ( 0 < $vars['price'] )
		{
			$this->_te->assignBlockVars('ITEM_PRICE', array('id'=>$vars['id'], 'price'=>$currency->convert($vars['price'], $curOpts)));
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
	
	/**
	 * Рисует список документов в одну колонк
	 *
	 * @param  array  $items  массив документов
	 * @return void
	 **/
	function _rndItemsOneColumn($items)
	{
		$currency  = &elSingleton::getObj('elCurrency');
		$curOpts   = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate'),
			'format'      => true,
			'symbol'      => true
			);
			
		for ($i=0,$s=sizeof($items); $i<$s; $i++)
		{
			$data = $items[$i]->toArray();
			$data['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ( 0 < $data['price'] )
			{
				$price = $currency->convert($data['price'], $curOpts);	
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
		$currency  = &elSingleton::getObj('elCurrency');
		$curOpts   = array(
			'precision'   => (int)$this->_conf('pricePrec'),
			'currency'    => $this->_conf('currency'),
			'exchangeSrc' => $this->_conf('exchangeSrc'),
			'commision'   => $this->_conf('commision'),
			'rate'        => $this->_conf('rate'),
			'format'      => true,
			'symbol'      => true
			);
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
				$price = $currency->convert($data['price'], $curOpts);	
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