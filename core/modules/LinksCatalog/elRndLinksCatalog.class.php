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
			$data = $items[$i]->toArray();
			$data['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ( !empty($data['url']) )
      		{
        		$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.URL', array('url'=>$data['url']), 3);
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
			if ( !empty($data['url']) )
      		{
        		$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.URL', array('url'=>$data['url']), 3);
      		}
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
			}
		}
		
	}

}

?>