<?php

class elOrderHistory extends elDataMapping
{
	var $_tb      = 'el_order';
	var $ID       = 0;
	var $uID      = 0;
	var $crtime   = 0;
	var $mtime    = 0;
	var $state    = '';
	var $amount   = 0;
	var $discount = 0;
	var $delivery_price = 0;
	var $total    = 0;
	
	var $status   = array('send', 'accept', 'deliver', 'complite', 'aborted');
	
	function _initMapping()
	{
		return array(
			'id'       => 'ID',
			'uid'      => 'uID',
			'crtime'   => 'crtime',
			'mtime'    => 'mtime',
			'state'    => 'state',
			'amount'   => 'amount',
			'discount' => 'discount',
			'delivery_price' => 'delivery_price',
			'total'    => 'total'
      );
	}

	function count($where = null)
	{
		$sql  = 'SELECT COUNT(id) AS count FROM '.$this->_tb;
		$sql .= (!is_null($where) ? ' WHERE '.$where : '');
		$db   = & elSingleton::getObj('elDb');
		$db->query($sql);
		$row = $db->nextRecord();
		return $row['count'];
	}
	
	function sumTotal($where = null)
	{
		$sql  = 'SELECT SUM(total) AS total FROM '.$this->_tb;
		$sql .= (!is_null($where) ? ' WHERE '.$where : '');
		$db   = & elSingleton::getObj('elDb');
		$db->query($sql);
		$row = $db->nextRecord();
		return (int)$row['total'];
	}

	function updateMtime($id = null)
	{
		$this->idAttr($id);
		$this->fetch();
		$this->attr('mtime', time());
		$this->save();
	}

}
