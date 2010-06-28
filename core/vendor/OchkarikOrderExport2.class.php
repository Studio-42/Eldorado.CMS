<?php

/**
 * Ochkarik order exporter, exports orders from el_order* tables to Ochkarik DB
 *
 * @version 1.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/

class OchkarikOrderExport2
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
		$this->el_order_begin_id = 24843;
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
/*
﻿<?xml version="1.0" encoding="UTF-8"?>
<ДАННЫЕ ТипДанных="Экспорт заказов покупателя">
	<Документ Вид="ЗаказПокупателя" id="" amount="" discount="" delivery_price="" total="" region="" delivery="" payment="">
		<Покупатель>
			<ИнформацияОПокупателе Логин="">
				<ДополнительнаяИнформация field_id=""	value=""/>
			</ИнформацияОПокупателе>
		</Покупатель>
		<Товары>
			<Товар code=""	name=""	qnt=""	price="">
				<ДополнительнаяИнформация field_name=""	value=""/>
			</Товар>
		</Товары>
	</Документ>
</ДАННЫЕ>
*/
		$e  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$e .= "\t".'<ДАННЫЕ ТипДанных="Экспорт заказов покупателя">'."\n";
		foreach ($this->el_order as $order_id => $o)
		{
			$t  = "\t".'<Документ Вид="ЗаказПокупателя" id="%d" amount="%.2f" discount="%.2f" delivery_price="%.2f" total="%.2f" region="%s" delivery="%s" payment="%s">' . "\n";
			$e .= sprintf($t, $o['id'], $o['amount'], $o['discount'], $o['delivery_price'], $o['total'], $o['region'], $o['delivery'], $o['payment']);

			// customer
			$e .= "\t\t".'<Покупатель>'."\n";
			$e .= "\t\t\t".sprintf('<ИнформацияОПокупателе Логин="%s">', $this->_getLogin($o['customer']))."\n";
			foreach ($o['customer'] as $c)
			{
				$e .= "\t\t\t\t".sprintf('<ДополнительнаяИнформация field_id="%s" value="%s" />', $c['field_id'], $c['value'])."\n";
			}
			$e .= "\t\t\t".'</ИнформацияОПокупателе>'."\n";
			$e .= "\t\t".'</Покупатель>'."\n";
			
			// items
			$e .= "\t\t".'<Товары>'."\n";			
			foreach ($o['items'] as $i)
			{
				$e .= "\t\t\t".sprintf('<Товар code="%s" name="%s" qnt="%d" price="%.2f">', $i['code'], $i['name'], $i['qnt'], $i['price'])."\n";
				if (!empty($i['props']))
				{
					$props = unserialize($i['props']);
					foreach ($props as $p)
					{
						$e .= "\t\t\t\t".sprintf('<ДополнительнаяИнформация field_name="%s" value="%s" />', $p[0], $p[1])."\n";
					}
				}
				$e .= "\t\t\t".'</Товар>'."\n";
			}
			$e .= "\t\t".'</Товары>'."\n";			

			// end
			$e .= "\t".'</Документ>'."\n";
			$e .= '</ДАННЫЕ>'."\n";
			echo htmlspecialchars($e);
			return;
		}
	}

	function _getLogin($c = array())
	{
		foreach ($c as $e)
		{
			if ($e['field_id'] == 'login')
			{
				return $e['value'];
			}
		}
		return '';
	}

	function run()
	{
		$this->_findOrders();
		$this->_process();
	}
}

include_once dirname(__FILE__).'/../console.php';

$r = new OchkarikOrderExport2;
$r->run();


