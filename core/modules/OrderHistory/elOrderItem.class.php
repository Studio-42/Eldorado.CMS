<?php

class elOrderItem extends elDataMapping
{
	var $_tb      = 'el_order_item';
	var $ID       = 0;
	var $order_id = 0;
	var $uID      = 0;
	var $label    = '';
	var $value    = 0;

	var $i_id;
	var $shop;
	var $code;
	var $name;
	var $qnt;
	var $price;
	var $props;

	function _initMapping()
	{
		return array(
			'id'       => 'ID',
			'order_id' => 'order_id',
			'page_id'  => 'page_id',
			'i_id'     => 'i_id',
			'code'     => 'code',
			'name'     => 'name',
			'qnt'      => 'qnt',
			'price'    => 'price',
			'props'    => 'props'
			);
  	}

	function editAndSave($params = null)
	{
		$order = & elSingleton::getObj('elOrderHistory');
		$order->idAttr($this->order_id);
		$order->fetch();
		$this->_makeForm($order->toArray());
		
		if ($this->_form->isSubmitAndValid() && $this->_validForm())
		{
			// $this->attr('uid', $this->uID);
			$total  = 0;
			$amount	= 0;
			foreach ($this->_form->getValue() as $f => $v)
			{
				list($f, $id) = explode('_', $f);
				if (($f == 'id') and ($id > 0))
				{
					$this->idAttr($id);
					$this->fetch();
					$this->attr('qnt', $v);
					
					$amount += $this->attr('price') * $v;
					$total += $this->attr('price') * $v;

					$this->save();
				}
				elseif (($f == 'del') and ($id == 'del'))
				{
					$order->attr('delivery_price', $v);
					$total += $v;
				}
				elseif (($f == 'dis') and ($id == 'dis'))
				{
					$order->attr('discount', $v);
					$total -= $v;
				}
				else
					continue;
				
			}
			$order->attr('amount', $amount);
			$order->attr('total',  $total);
			$order->save();

			return true;
		}
		return false;
	}
	
	function _makeForm($params = null)
	{
		$order = $params;
		$items = array();
		$items = $this->fetchMerged();
		
		parent::_makeForm();
		$rnd = & elSingleton::getObj('elGridFormRenderer', 5);
		$rnd->tpl['header'] = "<table class=\"grid-tb\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
		$this->_form->setRenderer($rnd);
		$this->_form->setLabel(m('Edit order'));
		
		// header
		$class = array('style' => 'font-weight: bold;');
		$this->_form->add(new elCData('code',  m('Code')), $class);
		$this->_form->add(new elCData('name',  m('Name')), $class);
		$this->_form->add(new elCData('prop',  m('Description')), $class);
		$this->_form->add(new elCData('id',    m('Quantity')), $class);
		$this->_form->add(new elCData('price', m('Price')), $class);
		
		foreach ($items as $i)
		{
			$id = $i['id'];
			$props = unserialize($i['props']);
			$prop = '';
			foreach ($props as $p)
			{
				$prop .= $p[0].': '.$p[1].'<br />';
			}
			$this->_form->add(new elCData('code_'.$id,  $i['code']));
			$this->_form->add(new elCData('name_'.$id,  $i['name']));
			$this->_form->add(new elCData('prop_'.$id,  $prop));
			$this->_form->add(new elText('id_'.$id, '', $i['qnt'], array('size' => 2)));
			$this->_form->add(new elCData('price_'.$id, $i['price']));
		}
	
		// delivery 
		$this->_form->add(new elCData('del_1',   ''));
		$this->_form->add(new elCData('del_s',   m('Delivery')),     array('colspan' => '3'));
		$this->_form->add(new elText( 'del_del', '', $order['delivery_price'], array('size' => 6)));
		
		// discount
		$this->_form->add(new elCData('dis_1',   ''));
		$this->_form->add(new elCData('dis_s',   m('Discount')),     array('colspan' => '3'));
		$this->_form->add(new elText( 'dis_dis', '', $order['discount'], array('size' => 6)));

		// footer
		$this->_form->add(new elCData( 'n_l', ''),             array('colspan' => '3'));
		$this->_form->add(new elSubmit('s_s', '', m('Submit'), array('class'=>'submit')));
		$this->_form->add(new elReset( 'r_r', '', m('Drop'),   array('class'=>'submit')));
	}

	function fetchMerged()
	{
		$items = $this->collection(false, false, 'order_id='.$this->order_id);		
		return $items;
	}

	function top10()
	{
		$top = array();
		$qnt = 0;
		$sum = 0;
		$sql =
'SELECT i.name, SUM(i.price) AS sum, SUM(i.qnt) AS qnt, i.price
FROM el_order_item AS i, el_order AS o
WHERE i.order_id = o.id AND o.state <> "aborted"
GROUP BY i.code ORDER BY qnt DESC LIMIT 12';
		$db   = & elSingleton::getObj('elDb');
		$db->query($sql);
		while ($r = $db->nextRecord())
		{
			$qnt += $r['qnt'];
			$sum += $r['sum'];
			$name = $r['name'] . ' ('. m('Quantity') . ': '. $r['qnt'] . ', '
			      . m('Total') . ': '. (int)$r['sum'] . ', '
			      . m('Price') . ': ' . $r['price'] . ')';
			$top[$name] = $r['qnt'];
		}
		$sql =
'SELECT SUM(i.price) AS sum, SUM(i.qnt) AS qnt
FROM el_order_item AS i, el_order AS o
WHERE i.order_id = o.id AND o.state <> "aborted" LIMIT 1';
		$db->query($sql);
		$r = $db->nextRecord();
		$name = m('Other goods') . ' ('. m('Quantity') . ': '. ($r['qnt'] - $qnt) . ', '
		      . m('Total') . ': '. ($r['sum'] - $sum) . ')';
		$top[$name] = $r['qnt'] - $qnt;
		return $top;
	}

	/**
	 * Best selling products for period (used in SpecialOffer plugin)
	 *
	 * @param  int   $shop_id   IShop ID = Page ID
	 * @param  int   $limit     how many of best selling items to return
	 * @param  int   $period    search within this period (in days)
	 * @return array
	 **/
	function bestSellers($shop_id = 0, $limit = 10, $period = 30)
	{
		$where = array();
		if ($period > 0)
		{
			array_push($where, 'crtime>='.(time() - ($period * 86400)));
		}
		if ($shop_id > 0)
		{
			array_push($where, 'page_id='.$shop_id);
		}

		$sql = 'SELECT i_id, SUM(qnt) AS qnt FROM '.$this->_tb;

		if (!empty($where))
		{
			$sql .= ' WHERE '.implode(' AND ', $where);
		}
		$sql .= ' GROUP BY i_id ORDER BY qnt DESC';

		if ($limit > 0)
		{
			$sql .= ' LIMIT 0, '.intval($limit);
		}

		$ret = array();
		$db  = & elSingleton::getObj('elDb');
		$db->query($sql);
		while ($r = $db->nextRecord())
		{
			array_push($ret, $r['i_id']);
		}
		return $ret;
	}

}
