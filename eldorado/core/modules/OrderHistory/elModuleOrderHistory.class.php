<?php

/**
 * Order history, displays data from IShop and TechShop orders
 *
 * @package OrderHistory
 * @version 1.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/
class elModuleOrderHistory extends elModule
{
	
	var $_mMap = array(
		'show'     => array('m' => 'showOrder'),
		'user'     => array('m' => 'searchUser'),
		'edit'     => array('m' => 'editOrder'),
		'items'    => array('m' => 'editItem'),
		'status'   => array('m' => 'changeStatus')
		);
	
	var $_conf = array(
		'ordersOnPage' => 50
		);
	
	var $_period = array();
	
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
	
	function showOrder()
	{
		// rewrite to full url is POST
		$id = (int)$_POST['id'];
		if ($id > 0)
			elLocation(EL_URL.'show/'.$id);
		
		$id = $this->_checkOrderExist();
		$order = $this->_getOrder($id);
		$items = $this->_getOrderItem($id);
		$this->_initRenderer();
		$this->_rnd->rndOrder($order, $items, $this->_getStatus());
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
	
	function _getOrder($id = null, $offset = null, $where = null)
	{
		$order = & elSingleton::getObj('elOrderHistory');
		// get one
		if ($id > 0)
		{
			$order->idAttr($id);
			if (!$order->fetch())
				return false;
			$o = $order->toArray();
			$i = $this->_getCustomerNfo(array($id));
			return array_merge($o, $i[$id]);
		}
		else // get many
		{
			$orders = array();
			$orders = $order->collection(false, false, $where, 'crtime DESC', $offset, $this->_conf['ordersOnPage']);
		}
		$count = $order->count($where); // $where
		unset($order);

		$ids = array();
		foreach ($orders as $order)
			array_push($ids, $order['id']);
			
		if (sizeof($ids) < 1)
		 	return array();
		
		$customerNfo = $this->_getCustomerNfo($ids);
		foreach ($orders as $id => $order)
		{ // we need only name to display in list
			$order['full_name'] = $customerNfo[$order['id']]['full_name'];
			$order['email']     = $customerNfo[$order['id']]['email'];
			$orders[$id] = $order;
		}
		unset($customerNfo);
		return array($orders, $count);
	}
	
	function _updateMtime($id = null)
	{
		$order = & elSingleton::getObj('elOrderHistory');
		$order->updateMtime($id);
	}
	
	function _searchCustomer($name = null)
	{
		$customer = elSingleton::getObj('elOrderCustomer');
		return $customer->searchCustomer($name);
	}
	
	function _getCustomerNfo($ids = null)
	{
		$customer = elSingleton::getObj('elOrderCustomer');
		return $customer->getCustomerNfo($ids);
	}
	
	function _getOrderItem($id = null)
	{
		$item  = elSingleton::getObj('elOrderItem');
		$where = 'order_id='.$id;
		$items = $item->collection(false, false, $where, 'crtime DESC');
		return $items;
	}
	
	function _onInit()
	{
		$this->_setPeriod();
	}
	
	function _setPeriod()
	{
		$ats    = & elSingleton::getObj('elATS');
		$user   = & $ats->getUser();
		$period = $user->getPref('period');
		if (
			is_array($period)
			&& sizeof($period) == 2 
			&& preg_match('/\d{4}\-\d{2}\-\d{2}/i', $period['period_begin'])
			&& preg_match('/\d{4}\-\d{2}\-\d{2}/i', $period['period_end'])
			)			
			$this->_period = $period;
	}
	
	function _getStatus()
	{
		$order = & elSingleton::getObj('elOrderHistory');
		return $order->status;
	}
	
	function _checkOrderExist()
	{
		$id = $this->_arg(0);
		$order = $this->_getOrder($id);
		if (($id <= 0) or ($order == false))
			elThrow(E_USER_WARNING, 'Order #%s not found', $id, EL_URL);
		return $id;
	}
	
	function &_makeConfForm()
	{
		$form = &parent::_makeConfForm();
		$form->add( new elText('ordersOnPage', m('Orders per page'), $this->_conf('ordersOnPage')) );
		return $form;
	}
}