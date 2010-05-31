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
		{
			elThrow(E_USER_WARNING, 'Authorization required');
			return;
		}
		list($orders, $count) = $this->_getOrder(null, null, 'uid='.(int)$uid);
		$this->_initRenderer();
		$this->_rnd->rndOrderList($orders, false, false);
	}

	function showOrder()
	{
		// rewrite to full url if POST
		if (isset($_POST['id']))
			if ((int)$_POST['id'] > 0)
				elLocation(EL_URL.'show/'.$id);
		
		$id = $this->_checkOrderExist();
		$order    = $this->_getOrder($id);
		if (!$this->_isAllowed())
			if ($order['uid'] != $this->_checkAuth())
			{
				elThrow(E_USER_WARNING, 'You do not have access to page "%s"', 'Order History', EL_URL);
				return;
			}

		$reorder  = false;
		if ($order['uid'] == $this->_checkAuth())
			$reorder = true;

		$customer = array_shift($this->_getCustomerNfo(array($id)));
		$items    = $this->_getOrderItem($id);

		if ($this->_arg(1) == 'pdf')
			$this->_rndPDF($order, $customer, $items);
		else
		{
			$this->_initRenderer();
			$this->_rnd->rndOrder($order, $customer, $items, $this->_getStatus(), $reorder);
		}
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
		{ // TODO we need only name to display in list but again using custom E-mail
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
		$period = $user->prefrence('period');
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
			return false;
		return $ats->getUserID();
	}

	function _isAllowed()
	{
		$ats = elSingleton::getObj('elATS');
		return $ats->allow(EL_FULL);
	}

	// Generate PDF, all text will be in cp1251 encoding as FPDF do not understand UTF-8 (stone age)
	function _rndPDF($order, $customer, $items)
	{
		define('FPDF_FONTPATH','core/vendor/fpdf/font/');
		elSingleton::incLib('vendor/fpdf/fpdf.php');
		elSingleton::incLib('vendor/fpdf/scripts/mc_table/mc_table.php');

		$conf = elSingleton::getObj('elXmlConf');

		$pdf = new PDF_MC_Table();
		$pdf->SetTitle($conf->get('siteName'), true);
		$pdf->SetSubject(m('Order').' '.$order['id'], true);
		$pdf->AddFont('ArialMT', '', 'Arial.php');
		$pdf->AddPage();

		// header
		$pdf->SetFont('ArialMT', '', 24);
		$pdf->Cell(0, 6, $this->_c($conf->get('siteName')), 0, 1, 'C');
		$pdf->Ln(6);

		$txt = implode(' ', array(
			$this->_c('Order'),
			$order['id'],
			$this->_c('from'),
			date(EL_DATETIME_FORMAT, $order['crtime']))
		);
		$pdf->SetFont('ArialMT', '', 18);
		$pdf->Cell(0, 6, $txt, 0, 1, 'C');
		$pdf->Ln(15);

		// customer info
		$pdf->SetFont('ArialMT', '', 14);
		$pdf->Cell(0, 6, $this->_c('Customer info'), 0, 1, 'L');
		$pdf->Ln(2);
		unset($customer['full_name']);
		$pdf->SetFont('ArialMT', '', 10);
		foreach ($customer as $l => $v)
		{
			$pdf->Cell(5,   5, '', 0, 0);
			$pdf->Cell(40,  5, $this->_c($l), 0, 0, 'L');
			$pdf->Cell(140, 5, $this->_c($v), 0, 0, 'L');
			$pdf->Ln();
		}
		$pdf->Ln(10);

		// order
		$pdf->SetFont('ArialMT', '', 14);
		$pdf->Cell(0, 6, $this->_c('Order'), 0, 1, 'L');
		$pdf->Ln(2);

		// TABLE
		$pdf->SetFont('ArialMT', '', 10);
		// cell widths and aligns
		$w = array(18, 50, 65, 15, 20, 20);
		$a = array('C', 'L', 'L', 'C', 'R', 'R');

		// table header
		$header = array('Code', 'Name', 'Options', 'Qnt', 'Price', 'Sum');
		$pdf->SetFillColor(238, 238, 238);
		for($i = 0; $i < count($header); $i++)
		{
			$header[$i] = $this->_c($header[$i]);
			$pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
		}
		$pdf->Ln();

		// table items
		$pdf->SetAligns($a);
		$pdf->SetWidths($w);
		foreach ($items as $item)
		{
			$prop = '';
			$props = unserialize($item['props']);
			foreach ($props as $p)
				$prop .= $p[0].': '.$p[1]."\n";
			$item['props'] = $prop;

			foreach ($item as $k => $v)
				$item[$k] = $this->_c($v);

			$item['subtotal'] = sprintf('%.2f', $item['qnt'] * $item['price']);
			//elPrintR($item);
			$pdf->Row(array($item['code'], $item['name'], $item['props'], $item['qnt'], $item['price'], $item['subtotal']));
		}

		// table footer
		$space = '                  ';
		$total_w = 0;
		foreach ($w as $v)
			$total_w += $v;

		$pdf->Cell($total_w, 0.5, '', 1, 0, 'C', true);
		$pdf->Ln();
		$pdf->SetFillColor(255);
		$pdf->Cell(($total_w - 20), 5, $space.$this->_c('Discount'), 1, 0, 'L', true);
		$pdf->Cell(20, 5, $order['discount'], 1, 0, 'R', true);
		$pdf->Ln();
		$pdf->Cell(($total_w - 20), 5, $space.$this->_c('Delivery'), 1, 0, 'L', true);
		$pdf->Cell(20, 5, $order['delivery_price'], 1, 0, 'R', true);
		$pdf->Ln();
		$pdf->SetFillColor(238, 238, 238);
		$pdf->Cell(($total_w - 20), 5, $this->_c('Total').'  ', 1, 0, 'R', true);
		$pdf->Cell(20, 5, $order['total'], 1, 0, 'R', true);

		$pdf->Output(sprintf('order-%d.pdf', $order['id']),'I');
		exit();
	}

	// translate and convert to cp1251 for _rndPDF
	function _c($string)
	{
		return iconv('UTF-8', 'CP1251//TRANSLIT', m($string));
	}
}

