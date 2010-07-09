<?php

/**
 * Ochkarik order exporter, exports orders from el_order* tables to Ochkarik DB
 *
 * @version 1.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/

/* Table struct
CREATE TABLE IF NOT EXISTS `el_order_och_export` (
  `order_id` int(11) NOT NULL,
  `ok` enum('no','yes') COLLATE utf8_bin NOT NULL,
  `request` text COLLATE utf8_bin NOT NULL,
  `response` text COLLATE utf8_bin NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
*/
class OchkarikOrderExport
{
	var $error             = null;

	var $tb                = 'el_order_och_export';

	var $post_url          = '';
	var $http_user         = '';
	var $http_pass         = '';

	var $el_order          = array();
	var $el_order_begin_id = 0;

	function __construct()
	{
		$this->el_order_begin_id = 30299;
		$this->post_url  = 'http://82.204.249.186:33333/ws/remoteorderim.1cws';
		$this->http_user = 'WEB';
		$this->http_pass = '1';
	}

	function _findOrders()
	{
		$sql = sprintf('SELECT * FROM el_order WHERE id>%d AND id NOT IN (SELECT order_id FROM %s WHERE ok="yes") ORDER BY id DESC', $this->el_order_begin_id, $this->tb);
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
		foreach ($this->el_order as $order_id => $o)
		{
			$xml = $this->_genXML($o);
			$soap_data = $this->_soapEnvelope($xml);
			list($status, $message) = $this->_postData($soap_data);
			if ($status) // parse data if we have server response
			{
				list($status, $message) = $this->_parseResponse($message);
			}
			$st = ($status ? 'yes' : 'no');
			$sql = "REPLACE INTO %s (order_id, ok, request, response, time) VALUES (%d, '%s', '%s', '%s', %d)";
			$sql = sprintf($sql, $this->tb, $o['id'], $st, mysql_real_escape_string($soap_data), mysql_real_escape_string($message), time());
			$eldb = & elSingleton::getObj('elDb');
			$eldb->query($sql);
			//print "$order_id ==> $st ($sql)\n";
		}
	}

	function _parseResponse($m)
	{
		if (preg_match('!<m:return.*>(.+)</m:return>!is', $m, $match))
		{
			return array(true, $match[1]);
		}
		elseif (preg_match('!<faultstring>(.+)</faultstring>!is', $m, $match))
		{
			return array(false, $match[1]);
		}
		return array(false, $m);
	}

	function _postData($data)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,            $this->post_url);
		curl_setopt($ch, CURLOPT_POST,           1);
		curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/xml', 'SOAPAction: '));
		curl_setopt($ch, CURLOPT_POSTFIELDS,     $data);
		curl_setopt($ch, CURLOPT_HEADER,         0);
		curl_setopt($ch, CURLOPT_HTTPAUTH,       CURLAUTH_ANY); // CURLAUTH_ANYSAFE
		curl_setopt($ch, CURLOPT_USERPWD,        $this->http_user.':'.$this->http_pass);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FORBID_REUSE,   1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT,  1);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$output = curl_exec($ch);

		if (curl_errno($ch))
		{
			return array(false, curl_error($ch));
		}
		else
		{
			curl_close($ch); 
			return array(true, $output);
        }
	}

	function _soapEnvelope($data)
	{
		$data = htmlspecialchars($data);
		$r = <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
	<soap:Header/>
	<soap:Body>
		<m:СформироватьЗаказССайта xmlns:m="http://www.sample-package.org">
			<m:ЗаказXML>$data</m:ЗаказXML>
		</m:СформироватьЗаказССайта>
	</soap:Body>
</soap:Envelope>
EOL;
		return $r;
	}

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
	function _genXML($o)
	{
		$e  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$e .= "\t".'<ДАННЫЕ ТипДанных="Экспорт заказов покупателя">'."\n";
		$t  = "\t".'<Документ Вид="ЗаказПокупателя" id="%d" amount="%.2f" discount="%.2f" delivery_price="%.2f" total="%.2f" region="%s" delivery="%s" payment="%s">' . "\n";
		$e .= sprintf($t, $o['id'], $o['amount'], $o['discount'], $o['delivery_price'], $o['total'], $o['region'], $o['delivery'], $o['payment']);

		// customer
		$e .= "\t\t".'<Покупатель>'."\n";
		$e .= "\t\t\t".sprintf('<ИнформацияОПокупателе Логин="%s">', $this->_getLogin($o['customer']))."\n";
		foreach ($o['customer'] as $c)
		{
			$e .= "\t\t\t\t".sprintf('<ДополнительнаяИнформация field_id="%s" value="%s" />', $c['field_id'], str_replace('"', "'", $c['value']))."\n";
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
		return $e;
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

$pidfile = './tmp/OchkarikOrderExport.pid';
if (file_exists($pidfile))
{
	$pid = file_get_contents($pidfile);
	if ((!empty($pid)) && (file_exists('/proc/'.$pid))) // this is the linux way only!
	{
		//print "Alredy running, exiting\n";
		exit;
	}
	//print "Pidfile exist but no process is running, starting up\n";
}

$pid = getmypid();
if (file_put_contents($pidfile, $pid))
{
	include_once dirname(__FILE__).'/../console.php';
	$r = new OchkarikOrderExport;
	$r->run();
	@unlink($pidfile);	
}

