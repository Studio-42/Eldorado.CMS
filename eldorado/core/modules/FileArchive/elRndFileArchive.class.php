<?php
include_once EL_DIR_CORE.'lib/elCatalogRenderer.class.php';

class elRndFileArchive extends elCatalogRenderer
{

	/**
	 * Рисует список файлов в одну колонку
	 *
	 * @param  array  $items  массив файлов
	 * @return void
	 **/
	function _rndItemsOneColumn($items)
	{
		$displayLmd = $this->_conf('displayLmd');
		$displayCnt = $this->_conf('displayCnt');
		for ($i=0,$s=sizeof($items); $i<$s; $i++)
		{
			$vars = $items[$i]->toArray();
			$vars['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.O_ITEM', $vars, 1);
			if ($displayLmd)
			{
			  $this->_te->assignBlockVars( 'ITEMS_ONECOL.O_ITEM.OI_LMD', array('mtime'=>date(EL_DATETIME_FORMAT, $vars['mtime'])), 2 );
			}
			if ($displayCnt)
			{
			  $this->_te->assignBlockVars( 'ITEMS_ONECOL.O_ITEM.OI_CNT', array('cnt'=>$vars['cnt']), 2 );
			}
		}
		
	}


	/**
	 * Рисует список файлов в две колонки
	 *
	 * @param  array  $items  массив файлов
	 * @return void
	 **/
	function _rndItemsTwoColumns($items)
	{
		$displayLmd = $this->_conf('displayLmd');
		$displayCnt = $this->_conf('displayCnt');
		$rowCnt = 0;
		for ($i=1, $s = sizeof($items); $i<=$s; $i++ )
		{
			if ( $i%2  )
			{
				$cssRowClass = ++$rowCnt%2 ? 'strip-ev' : 'strip-odd';
				$this->_te->assignBlockVars('ITEMS_TWOCOL.IROW', array('cssRowClass'=>$cssRowClass), 1);
			}
			$vars = $items[$i-1]->toArray(); 
			$vars['filename']     = basename($vars['f_url']);
			$vars['cssRowClass']  = $cssRowClass;
			$vars['cssLastClass'] = $i%2 ? '' : 'col-last';
			$this->_te->assignBlockVars( 'ITEMS_TWOCOL.IROW.T_ITEM', $vars, 2 );
			if ($displayLmd)
			{
			  $this->_te->assignBlockVars( 'ITEMS_TWOCOL.IROW.T_ITEM.TI_LMD', array('mtime'=>date(EL_DATETIME_FORMAT, $vars['mtime'])), 3 );
			}
			if ($displayCnt)
			{
			  $this->_te->assignBlockVars( 'ITEMS_TWOCOL.IROW.T_ITEM.TI_CNT', array('cnt'=>$vars['cnt']), 3 );
			}
		}
		
	}



}

?>