<?php

/**
 * Order history, displays data from IShop and TechShop orders
 *
 * @package OrderHistory
 * @version 1.3
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/
class elModuleOrderHistory extends elModule
{
	var $_mMap = array(
		'show'      => array('m' => 'showOrder')
		);

	var $_conf = array(
		'ordersOnPage' => 50
		);

	var $_period = array();

	function defaultMethod()
	{
		if (!$uid = $this->_checkAuth())
			return;

		list($orders, $count) = $this->_getOrder(null, null, 'uid='.(int)$uid);
		$this->_initRenderer();
		$this->_rnd->rndOrderList($orders, false, false);
	}

	function showOrder()
	{
		// rewrite to full url is POST
		$id = (int)$_POST['id'];
		if ($id > 0)
			elLocation(EL_URL.'show/'.$id);
		
		$id = $this->_checkOrderExist();
		$order    = $this->_getOrder($id);
		$customer = array_shift($this->_getCustomerNfo(array($id)));
		$items    = $this->_getOrderItem($id);
		$this->_initRenderer();
		$this->_rnd->rndOrder($order, $customer, $items, $this->_getStatus());
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
			return $o;
			//$i = $this->_getCustomerNfo(array($id));
			//return array_merge($o, $i[$id]);
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

	function _checkAuth()
	{
		$ats = elSingleton::getObj('elATS');
		if (!$ats->isUserAuthed())
		{
			elLoadMessages('Auth');
			elMsgBox::put(m('Authorization required'));
			return false;
		}
		return $ats->getUserID();
	}

}

