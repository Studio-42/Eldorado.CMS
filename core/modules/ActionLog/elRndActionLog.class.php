<?php

class elRndActionLog extends elModuleRenderer
{
	var $_tpls = array(
		'list'    => 'list.html'
		);
	var $color = array(
		'edit'     => '#fdfcd5',
		'new'      => '#ddfad3',
		'sort'     => '#cfe6fa',
		'delete'   => '#fce0e0',
		'items delete' => '#fce0e0'		
		);

	function rndActionLogList($rec, $pager)
	{
		$this->_setFile('list');
		foreach ($rec as $r)
		{
			// elPrintR($r);
			$r['color'] = $this->color[$r['action']] ? 'style="background-color: '.$this->color[$r['action']].';"' : '';
			$r['time'] = date(EL_DATETIME_FORMAT, $r['time']);
			$this->_te->assignBlockVars('RECORD', $r);
		}
		$this->_rndPager($pager);
	}

	function _rndPager($pager = null)
	{
		if (($pager == null) or ($pager[1] < 2))
			return;
		$current = $pager[0];
		$total   = $pager[1];
		$this->_te->setFile('PAGER', 'common/pager.html');
		$url = EL_URL;
		$l = $current - 3;
		$r = $current + 3;
		$l = ($l < 1 ? 1 : $l);
		$r = ($r > $total ? $total : $r);

		if ($l > 1)
		{
			$this->_te->assignBlockVars('PAGER.PAGE', array('url' => $url, 'num' => 1));
			$this->_te->assignBlockVars('PAGER.DUMMY');
		}
		
		for ($i = $l; $i <= $r; $i++)
			$this->_te->assignBlockVars($i != $current ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('num' => $i, 'url' => $url));
		
		if ($r < $total)
		{
			$this->_te->assignBlockVars('PAGER.DUMMY');
			$this->_te->assignBlockVars('PAGER.PAGE', array('url' => $url, 'num' => $total));
		}
		
		$this->_te->parse('PAGER');
	}
}