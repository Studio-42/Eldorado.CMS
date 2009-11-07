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
			$data = $items[$i]->toArray();
			$data['filename'] = basename($data['f_url']);
			$data['cssRowClass'] = $i%2 ? 'strip-odd' : 'strip-ev';
			$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM', $data, 1);
			if ($displayLmd)
			{
			  $this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.LMD', array('mtime'=>date(EL_DATETIME_FORMAT, $data['mtime'])), 2 );
			}
			if ($displayCnt)
			{
			  $this->_te->assignBlockVars( 'ITEMS_ONECOL.ITEM.CNT', array('cnt'=>$data['cnt']), 2 );
			}
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_ONECOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
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
		for ($i=0, $s = sizeof($items); $i<$s; $i++ )
		{
			$data = $items[$i]->toArray();
			$data['filename'] = basename($data['f_url']);
			$data['cssLastClass'] = 'col-last';
			if (!($i%2))
			{
				$var = array('cssRowClass' => $rowCnt++%2 ? 'strip-ev' : 'strip-odd', 'hide' => $i == $s-1 ? 'invisible' : '');
				$this->_te->assignBlockVars('ITEMS_TWOCOL', $var);
				$data['cssLastClass'] = '';
			}
			$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM', $data, 1 );
			if ($displayLmd)
			{
			  $this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.LMD', array('mtime'=>date(EL_DATETIME_FORMAT, $data['mtime'])), 3 );
			}
			if ($displayCnt)
			{
			  $this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.CNT', array('cnt'=>$data['cnt']), 3 );
			}
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('ITEMS_TWOCOL.ITEM.ADMIN', array('id'=>$data['id']), 2);
			}
		}
	}

}

?>