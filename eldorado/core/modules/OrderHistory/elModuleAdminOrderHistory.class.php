<?php

/**
 * Order history, displays data from IShop and TechShop orders
 *
 * @package OrderHistory
 * @version 1.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/
class elModuleAdminOrderHistory extends elModuleOrderHistory
{
	var $_mMapAdmin = array(
		'user'      => array('m' => 'searchUser'),
		'edit'      => array('m' => 'editOrder'),
		'items'     => array('m' => 'editItem'),
		'status'    => array('m' => 'changeStatus'),
		'analytics' => array('m' => 'showAnalytics', 'l' => 'Statistics', 'g' => 'Actions', 'ico' => 'icoItemTypesList')
	);

	function defaultMethod()
	{
		// get period
		if (!empty($_POST['period_begin']) && !empty($_POST['period_end']))
		{
			$ats    = & elSingleton::getObj('elATS');
			$user   = & $ats->getUser();
			$period = array(
				'period_begin' => date('Y-m-d', strtotime($_POST['period_begin'])),
				'period_end'   => date('Y-m-d', strtotime($_POST['period_end']))	
			);
			$user->setPref('period', $period);
			$this->_setPeriod();
		}
		
		$where = array();
		$filter = array();
		
		// get dates
		if (sizeof($this->_period) == 2)
		{
			$w =     '(crtime>='.strtotime($this->_period['period_begin'])
			   . ' AND crtime<'.(strtotime($this->_period['period_end'])+86400).')';
			array_push($where, $w);
			$filter = $this->_period;
		}
		
		// get search name
		if (isset($_POST['search_name']) and (!empty($_POST['search_name'])))
		{
			$sn  = $_POST['search_name'];
			$ids = $this->_searchCustomer($sn);
			$w   = '(id IN ('.implode(',', $ids).'))';
			array_push($where, $w);
			$filter['search_name'] = $sn;
		}

		// pager
		$pCurrent = (int)$this->_arg();
		$pCurrent = ($pCurrent < 1 ? 1 : $pCurrent);
		$offset   = ($pCurrent - 1) * $this->_conf['ordersOnPage'];
		
		// get orders
		$where = implode(' AND ', $where);
		list($orders, $count) = $this->_getOrder(null, $offset, $where);
		$pTotal   = ceil($count / $this->_conf['ordersOnPage']);
		$pager    = array($pCurrent, $pTotal);
		
		$this->_initRenderer();
		$this->_rnd->rndOrderList($orders, $pager, $filter);
	}

	function editOrder()
	{
		$id = $this->_checkOrderExist();		
		$customer = elSingleton::getObj('elOrderCustomer');
		$customer->order_id = $id;
		
		if (!$customer->editAndSave())
		{
			$this->_initRenderer();
			$this->_rnd->addToContent($customer->formToHtml());
		}
		else
		{
			$this->_updateMtime($id);
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL . 'show/' . $id);
		}
	}
	
	function editItem()
	{
		$id = $this->_checkOrderExist();
		
		$item = elSingleton::getObj('elOrderItem');
		$item->order_id = $id;
		
		if (!$item->editAndSave())
		{
			$this->_initRenderer();
			$this->_rnd->addToContent($item->formToHtml());
		}
		else
		{
			$this->_updateMtime($id);
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL . 'show/' . $id);
		}
	}

	function searchUser()
	{
		$uid = (int)$this->_arg();
		$where = 'uid='.$uid;
		list($orders, $count) = $this->_getOrder(null, null, $where);
		
		$this->_initRenderer();
		$this->_rnd->rndOrderList($orders, $pager, null);
	}
	
	function changeStatus()
	{
		$id = $this->_checkOrderExist();
		
		if (!isset($_POST['status']) and (!empty($_POST['status'])))
			elLocation(EL_URL . 'show/' . $id);
		
		$order = & elSingleton::getObj('elOrderHistory');
		$order->idAttr($id);
		$order->fetch();
		$order->attr('state', $_POST['status']);
		$order->save();
		$this->_updateMtime($id);
		elMsgBox::put(m('Status changed'));
		elLocation(EL_URL . 'show/' . $id);
	}

	function showAnalytics()
	{
		$order = & elSingleton::getObj('elOrderHistory');
		$item  = & elSingleton::getObj('elOrderItem');
		$thisMonthBegin = strtotime(date('Y-m-01'));
		$graph_count = array();
		$graph_sum = array();
		$graph_average_sum = array();
		// collect three arrays of data by 12 month period
		for ($i = 0; $i < 12; $i++)
		{
			$monthBegin = strtotime('-'.$i.' month', $thisMonthBegin);
			$monthEnd   = strtotime('+1 month', $monthBegin) - 1;
			$monthName  = date('M', $monthBegin);
			$year = date('Y', $monthBegin);

			$where = 'crtime >= '.$monthBegin.' AND crtime <= '.$monthEnd.' AND state<>"aborted"';
			$count = $order->count($where);
			$sum = $order->sumtotal($where);

			$name = m($monthName) . ' ' . $year;
			$graph_count[$name]        = $count;
			$graph_sum[$name]          = $sum;
			$graph_average_sum[$name]  = round($sum / $count);
		}
		
		// get top items
		$top = $item->top10();
		// array_pop($top); // pop last value if it is too big

		$chart  = & elSingleton::getObj('elFusionChart');
		$graph  = "<h2>".m('Orders')."</h2>\n";
		$graph .= $chart->graph('column', array_reverse($graph_count), 750, 300,
			array('rotateNames' => '1'));

		$chart->setColors(false);
		$graph .= "<h2>".m('Income')."</h2>\n";
		$graph .= $chart->graph('line', array_reverse($graph_sum), 750, 300,
			array('rotateNames' => '1', 'chartRightMargin' => '40'));

		$chart->setColors();
		$graph .= "<h2>".m('Top selling goods')."</h2>\n";
		$graph .= $chart->graph('pie', $top, 750, 300,
			array('showNames' => '0'));
		$graph .= $chart->legendVertical($top);

		$graph .= "<h2>".m('Average order amount')."</h2>\n";
		$graph .= $chart->graph('column', array_reverse($graph_average_sum), 750, 300,
			array('rotateNames' => '1'));

		$this->_initRenderer();
		$this->_rnd->addToContent($graph);
	}

	function &_makeConfForm()
	{
		$form = &parent::_makeConfForm();
		$form->add( new elText('ordersOnPage', m('Orders per page'), $this->_conf('ordersOnPage')) );
		return $form;
	}

}


