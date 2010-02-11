<?php

class elRndOrderHistory extends elModuleRenderer
{
	var $_tpls = array(
		'list'    => 'list.html',
		'order'   => 'order.html',
		'filter'  => 'filter.html'
		);
	var $color = array(
		'send'     => 'none',
		'accept'   => '#fdfcd5',
		'deliver'  => '#cfe6fa',
		'complite' => '#ddfad3',
		'aborted'  => '#fce0e0'
		);

	function rndOrderList($orders, $pager, $filter)
	{
		$this->_setFile('list');
		$this->_rndFilter($filter);
		
		$this->_te->assignBlockVars('LIST');
		elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
		elAddJs('$("#orders").tablesorter()', EL_JS_SRC_ONREADY);
		$date = str_replace('Y', 'y', EL_DATETIME_FORMAT);
		foreach ($orders as $order)
		{		
			$order['color'] = 'style="background-color: '.$this->color[$order['state']].';"';
			if ($order['uid'] > 0)
				$order['full_name'] .= ' <a href="'.EL_URL.'user/'.$order['uid'].'" style="color: black;"><b>&rarr;</b></a>';
			$order['crtime'] = date($date, $order['crtime']);
			$order['mtime'] = date($date, $order['mtime']);
			$order['total'] = (int)$order['total'];
			$this->_te->assignBlockVars('LIST.ORDER', $order, 1);
		}
		$this->_rndPager($pager);
	}

	function rndOrder($order, $items, $status)
	{
		$this->_setFile('order');
		
		
		$order['color'] = ' style="background-color: '.$this->color[$order['state']].';"';

		if ($order['uid'] > 0)
			$order['full_name'] .= ' <a href="'.EL_URL.'user/'.$order['uid'].'" style="color: black;"><b>&rarr;</b></a>';
		$order['crtime'] = date(EL_DATETIME_FORMAT, $order['crtime']);
		$order['mtime']  = date(EL_DATETIME_FORMAT, $order['mtime']);
		$this->_te->assignBlockVars('ORDER', $order);
		foreach ($status as $s)
		{
			$a = array('status' => $s);
			if ($s == $order['state'])
				$a['selected'] = 'selected';
			$this->_te->assignBlockVars('ORDER.STATUS', $a, 1);
		}

		foreach ($items as $item)
		{
			if ($item['qnt'] < 1)
			{
				$item['name']  = '<span style="text-decoration: line-through;">'.$item['name'].'</span>';
				$item['color'] = 'style="background-color: #fce0e0;"';
			}
			
			$prop = '';
			$props = unserialize($item['props']);
			foreach ($props as $p)
			{
				$prop .= $p[0].': '.$p[1].'<br />';
			}
			$item['props'] = $prop;
			$item['subtotal'] = sprintf('%.2f', $item['qnt'] * $item['price']);
			// elPrintR($item);
			$this->_te->assignBlockVars('ORDER.ITEM', $item, 1);
		}
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
	
	function _rndFilter($period)
	{
		elAddJs('$("#period_begin").datepicker({dateFormat: $.datepicker.ISO_8601, firstDay: 1, maxDate: new Date(), numberOfMonths: [1, 2], showButtonPanel: true })', EL_JS_SRC_ONREADY);
		elAddJs('$("#period_end").datepicker(  {dateFormat: $.datepicker.ISO_8601, firstDay: 1, maxDate: new Date(), numberOfMonths: [1, 2], showButtonPanel: true })', EL_JS_SRC_ONREADY);
		$this->_setFile('filter', 'FILTER');
		$this->_te->assignBlockVars('PERIOD', $period);
		$this->_te->parse('FILTER');		
	}

}
