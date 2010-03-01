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
		if ($this->_admin)
			$this->_rndFilter($filter);

		if (count($orders) == 0)
			$this->_te->assignBlockVars('NOT_FOUND');
		else
		{
			$this->_te->assignBlockVars('LIST');
			elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
			elAddJs('$("#orders").tablesorter()', EL_JS_SRC_ONREADY);
			$date = str_replace('Y', 'y', EL_DATETIME_FORMAT);
			foreach ($orders as $order)
			{
				$order['color'] = 'style="background-color: '.$this->color[$order['state']].';"';
				if (($this->_admin) and ($order['uid'] > 0))
					$order['full_name'] .= ' <a href="'.EL_URL.'user/'.$order['uid'].'" style="color: black;"><b>&rarr;</b></a>';
				$order['crtime'] = date($date, $order['crtime']);
				$order['mtime'] = date($date, $order['mtime']);
				$order['total'] = (int)$order['total'];
				$this->_te->assignBlockVars('LIST.ORDER', $order, 1);
			}
			$this->_rndPager($pager);
		}
	}

	function rndOrder($order, $customer, $items, $status, $reorder)
	{
		$this->_setFile('order');
		
		$order['color']  = ' style="background-color: '.$this->color[$order['state']].';"';
		$order['crtime'] = date(EL_DATETIME_FORMAT, $order['crtime']);
		$order['mtime']  = date(EL_DATETIME_FORMAT, $order['mtime']);
		$order['full_name'] = $customer['full_name'];
		if (($this->_admin) and ($order['uid'] > 0))
			$order['full_name'] .= ' <a href="'.EL_URL.'user/'.$order['uid'].'" style="color: black;"><b>&rarr;</b></a>';

		$this->_te->assignBlockVars('ORDER', $order);
		if ($this->_admin)
		{
			$this->_te->assignBlockVars('ORDER.ADMIN_EDIT',   $order, 1);
			$this->_te->assignBlockVars('ORDER.ADMIN_ITEMS',  $order, 1);
			$this->_te->assignBlockVars('ORDER.ADMIN_STATUS', $order, 1);
			foreach ($status as $s)
			{
				$a = array('status' => $s);
				if ($s == $order['state'])
					$a['selected'] = 'selected';
				$this->_te->assignBlockVars('ORDER.ADMIN_STATUS.OPTIONS', $a, 2);
			}
		}
		else
		{
			$this->_te->assignBlockVars('ORDER.STATUS', array('status' => $order['state']), 1);
		}

		// User profile
		// TODO not the best solution to use non fixed fields
		unset($customer['First name']);
		unset($customer['Second name']);
		unset($customer['Last name']);
		unset($customer['full_name']);
		//elPrintR($customer);
		foreach ($customer as $l => $v)
		{
			$this->_te->assignBlockVars('ORDER.PROFILE', array('label' => $l, 'value' => $v), 1);
		}

		// Ordered items
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
				$prop .= $p[0].': '.$p[1].'<br />';
			$item['props'] = $prop;
			$item['subtotal'] = sprintf('%.2f', $item['qnt'] * $item['price']);
			$this->_te->assignBlockVars('ORDER.ITEM', $item, 1);
		}

		// repeat order
		if ($reorder)
		{
			$this->_te->assignBlockVars('ORDER_REPEAT');
			$i = 0;
			foreach ($items as $item)
			{
				if ($item['qnt'] < 1)
					continue;
				$i++;
				$item['i'] = $i;
				$item['props'] = htmlspecialchars($item['props']);
				$this->_te->assignBlockVars('ORDER_REPEAT.ITEM', $item, 1);
			}
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
