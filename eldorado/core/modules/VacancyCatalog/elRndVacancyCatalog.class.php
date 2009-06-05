<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndVacancyCatalog extends elCatalogRenderer 
{ 

	function _rndItemsOneColumn($items)
	{
		for ($i=0,$s=sizeof($items); $i<$s; $i++)
		{
			$vars = $items[$i]->toArray();
			
			$vars['cssClass'] = $i%2 ? 'dcILight' : 'dcIDark';
			$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM', $vars, 1);
		}
	}

	function _rndItemsTwoColumns($items)
	{
		$j=0;
		for ($i=0, $s = sizeof($items); $i<$s; $i++ )
		{
			if ( ($i+1)%2 )
			{
				$l = 1;
				$j++;
			}
			else
			{
				$l = 2;
			}
			$vars = $items[$i]->toArray();
			$vars['cssClass'] = ($j%2) ? 'dcILight' : 'dcIDark';
			$this->_te->assignBlockVars( 'ITEMS_TWOCOL.IROW.T_ITEM', $vars, $l );
			if ( 1 == $l )
			{
				$this->_te->assignBlockVars( 'ITEMS_TWOCOL.IROW.T_ITEM.IDELIM', null, 3 );
			}
		}
	}

}

?>