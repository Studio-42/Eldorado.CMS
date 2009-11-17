<?php

class elOrderCustomer extends elDataMapping
{
	var $_tb      = 'el_order_customer';
	var $ID       = 0;
	var $order_id = 0;
	var $uID      = 0;
	var $label    = '';
	var $value    = 0;
	var $_objName  = 'Customer info';
	
	function _initMapping()
	{
		return array(
			'id'       => 'ID',
			'order_id' => 'order_id',
			'uid'      => 'uID',
			'label'    => 'label',
			'value'    => 'value'
			);
  	}
	
	function editAndSave()
	{
		$this->_makeForm();
		if ($this->_form->isSubmitAndValid() && $this->_validForm())
		{
			$this->attr('uid', $this->uID);			
			foreach ($this->_form->getValue() as $f => $v)
			{
				list($id, $f) = explode('__', $f);
				$this->idAttr($id);
				$this->attr('label', $f);
				$this->attr('value', $v);
				$this->save();
			}
			return true;
		}
	}
	
	function _makeForm()
	{
		$a = array();
		$a = $this->fetchMerged();
		
		// black magick
		$ats       = & elSingleton::getObj('elATS');
        $user      = & $ats->getUser();
		$uProfile  = $user->getProfile();
		$uProfSkel = $uProfile->getSkel(); 

		parent::_makeForm();
		$this->_form->setLabel(m('Edit customer info'));
		foreach ($a as $f => $v)
		{
			list($tmp, $n) = explode('__', $f);
			// elPrintR($uProfSkel[$n]['label']);
			$name = (!empty($uProfSkel[$n]['label']) ? $uProfSkel[$n]['label'] : $n);
			if ($n == 'comments')
				$this->_form->add(new elTextArea($f, m($name), $v));
			else
				$this->_form->add(new elText($f, m($name), $v));
		} 
	
	}
	
	function fetchMerged()
	{
		$c = array();
		$ci = $this->collection(false, false, 'order_id='.$this->order_id);
		$this->uID = $ci[0]['uid'];
		foreach ($ci as $n)
		{
			if (strlen($n['label']) < 1)
				continue;
			$c[$n['id'].'__'.$n['label']] = $n['value'];
		}
		return $c;
	}
	
	function searchCustomer($name = null)
	{
		if ($name == null)
			return false;
		
		$list = array();
		$where = "(label IN ('f_name', 'l_name', 'email')) AND (LOWER(value) LIKE LOWER('%%".$name."%%'))";
		$search = $this->collection(false, false, $where, false, false, false, 'order_id');		
		foreach ($search as $s)
			$list[$s['order_id']] = 1;
		return array_keys($list);
	}
	
	function getCustomerNfo($ids = null)
	{
		if ((!is_array($ids)) and (!is_int($ids)))
			return false;
			
		if (is_int($ids))
		{
			$id = $ids;
			$ids = array();
			array_push($ids, $id);
		}
		$where = 'order_id IN (' . implode(', ', $ids) . ')';
		$customersNfo = $this->collection(false, false, $where);
		$customers = array();
		foreach ($customersNfo as $nfo)
		{
			if (isset($customers[$nfo['order_id']], $customers))
			{
				$push = array();
				$push = $customers[$nfo['order_id']];
				$push[$nfo['label']] = $nfo['value'];
				$customers[$nfo['order_id']] = $push;
			}
			else
			{
				$customers[$nfo['order_id']] = array($nfo['label'] => $nfo['value']);
			}
		}
		foreach ($customers as $id => $c)
		{
			$c['full_name'] = implode(' ', array($c['l_name'], $c['f_name'], $c['s_name']));
			$customers[$id] = $c;
		}
		return $customers;
	}
}