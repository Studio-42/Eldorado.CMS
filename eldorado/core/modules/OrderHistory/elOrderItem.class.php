<?php

class elOrderItem extends elDataMapping
{
	var $_tb      = 'el_order_item';
	var $ID       = 0;
	var $order_id = 0;
	var $uID      = 0;
	var $label    = '';
	var $value    = 0;
	
	function _initMapping()
	{
		return array(
			'id'       => 'ID',
			'order_id' => 'order_id',
			'code'     => 'code',
			'name'     => 'name',
			'qnt'      => 'qnt',
			'price'    => 'price',
			'props'    => 'props'
			);
  	}

	function editAndSave()
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
	
	function _makeForm($order)
	{
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
		$this->_form->add(new elCData('del_1',  ''));
		$this->_form->add(new elCData('del_s',  m('Delivery')),     array('colspan' => '3'));
		$this->_form->add(new elText('del_del', '', $order['delivery_price'], array('size' => 6)));
		
		// discount
		$this->_form->add(new elCData('dis_1',  ''));
		$this->_form->add(new elCData('dis_s',  m('Discount')),     array('colspan' => '3'));
		$this->_form->add(new elText('dis_dis', '', $order['discount'], array('size' => 6)));

		// footer
		$this->_form->add(new elCData('n_l',  ''),             array('colspan' => '3'));
		$this->_form->add(new elSubmit('s_s', '', m('Submit'), array('class'=>'submit')));
		$this->_form->add(new elReset( 'r_r', '', m('Drop'),   array('class'=>'submit')));
	}

	function fetchMerged()
	{
		$items = $this->collection(false, false, 'order_id='.$this->order_id);		
		return $items;
	}
}