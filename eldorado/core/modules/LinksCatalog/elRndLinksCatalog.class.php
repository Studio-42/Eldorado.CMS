<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndLinksCatalog extends elCatalogRenderer
{
  	/**
	 * Рисует список документов в одну колонку
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
			if ( !empty($vars['url']) )
      		{
        		$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM.OC_URL', array('url'=>$vars['url']), 2);
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
			if ( !empty($vars['url']) )
      		{
        		$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW.T_ITEM.TC_URL', array('url'=>$vars['url']), 3);
      		}
		}
		
	}

}

?>