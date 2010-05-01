<?php

/**
 * Ochkarik order exporter, exports orders from el_order* tables to Ochkarik DB
 *
 * @version 1.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/

class OchkarikOrderExport
{
	var $error             = null;

	var $odb               = null;
	var $odb_serv          = '';
	var $odb_user          = '';
	var $odb_pass          = '';

	var $tb                = 'el_order_och_export';

	var $el_order          = array();
	var $el_order_begin_id = 0;

	function __construct()
	{
		$this->odb_serv = '212.248.30.146';
		$this->odb_user = 'webuser';
		$this->odb_pass = 'ph7T4uChA6';
		$this->el_order_begin_id = 20300;
		$this->_connect();
	}

	function _connect()
	{
		if ($this->odb = sybase_connect($this->odb_serv, $this->odb_user, $this->odb_pass))
		{
			return true;
		}
		else
		{
			$this->error = 'Cannot connect to server';
			return false;
		}
	}

	function _findOrders()
	{
		$sql = sprintf('SELECT * FROM el_order WHERE id>%d AND id NOT IN (SELECT order_id FROM %s WHERE ok="yes")', $this->el_order_begin_id, $this->tb);
		$eldb = & elSingleton::getObj('elDb');
		$eldb->query($sql);
		while($row = $eldb->nextRecord())
		{
			$this->el_order[$row['id']] = $row;
		}
		if (empty($this->el_order))
		{
			// return when now new orders
			return true;
		}
		
		// collect items
		$sql = sprintf('SELECT * FROM el_order_item WHERE order_id IN (%s)', implode(', ', array_keys($this->el_order)));
		$eldb->query($sql);
		while($row = $eldb->nextRecord())
		{
			$order_id = $row['order_id'];
			if (!array_key_exists('items', $this->el_order[$order_id]))
			{
				$this->el_order[$order_id]['items'] = array();
			}
			array_push($this->el_order[$order_id]['items'], $row);
		}

		// collect customer info
		$sql = sprintf('SELECT * FROM el_order_customer WHERE order_id IN (%s)', implode(', ', array_keys($this->el_order)));
		$eldb->query($sql);
		while($row = $eldb->nextRecord())
		{
			$order_id = $row['order_id'];
			if (!array_key_exists('customer', $this->el_order[$order_id]))
			{
				$this->el_order[$order_id]['customer'] = array();
			}
			array_push($this->el_order[$order_id]['customer'], $row);
		}
	}

	function _process()
	{
		$sql_u = "REPLACE INTO ".$this->tb." (order_id, ok, time) VALUES ('%d',  '%s',  '%d')";
		$eldb = & elSingleton::getObj('elDb');

		foreach ($this->el_order as $order_id => $o)
		{
			// order
			$sql_o  = "DECLARE @yes_order int, @yes_item int, @yes_customer int\n";
			$sql_o .= "EXEC spInsert_el_order @yes=@yes_order output, @id='%d', @uid='%d', @crtime='%s', @amount='%.2f', @discount='%.2f', @delivery_price='%.2f', @total='%.2f'\n";
			$sql_o .= "SELECT yes=@yes_order\n";
			$sql = sprintf($sql_o, $o['id'], $o['uid'], date('Y-m-d H:i:s', $o['crtime']), $o['amount'], $o['discount'], $o['delivery_price'], $o['total']);

			// customer
			foreach ($o['customer'] as $c)
			{
				$sql_c = "EXEC spInsert_el_order_customer @yes=@yes_customer output, @id='%d', @order_id='%d', @label='%s', @value='%s'\n";
				$sql_c = sprintf($sql_c, $c['id'], $c['order_id'], $c['label'], $c['value']);
				$sql .= iconv('UTF-8', 'CP1251//TRANSLIT', $sql_c);
			}

			// items
			foreach ($o['items'] as $i)
			{
				$sql_i = "EXEC spInsert_el_order_item @yes=@yes_item output, @id='%d', @order_id='%d', @code='%s', @name='%s', @qnt='%d', @price='%.2f', @props='%s'\n";
				$props = array();
				foreach (unserialize($i['props']) as $p)
				{
					array_push($props, $p[0].": ".$p[1]);
				}
				$sql_i = sprintf($sql_i, $i['id'], $i['order_id'], $i['code'], $i['name'], $i['qnt'], $i['price'], implode("\n", $props));
				$sql .= iconv('UTF-8', 'CP1251//TRANSLIT', $sql_i);
			}

			if ($q = sybase_query($sql))
			{
				$eldb->query(sprintf($sql_u, $o['id'], 'yes', time()));
			}
			else
			{
				$eldb->query(sprintf($sql_u, $o['id'], 'no', time()));
				$error = sybase_get_last_message();
				//echo "e: ".sybase_get_last_message()."\n";
			}
		}
	}

	function run()
	{
		$this->_findOrders();
		$this->_process();
	}
}

$a = new OchkarikOrderExport;
$a->run();

