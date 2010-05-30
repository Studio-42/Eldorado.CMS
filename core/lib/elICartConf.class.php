<?php

class elICartConf {
	var $_confID = 'ICartConf';
	var $_tb     = 'el_icart_conf';
	var $_conf   = null;
	var $_ec     = null;
	var $_db     = null;
	
	function elICartConf() {
		$this->_conf = & elSingleton::getObj('elXmlConf');
		$this->_ec   = & elSingleton::getObj('elEmailsCollection');
		$this->_db   = & elSingleton::getObj('elDb');
	}
	
	function recipients($vals=null) {
		if (is_null($vals)) {
			return $this->_ec->getByIDs($this->_conf->get('rcpt', $this->_confID));
		} else {
			$this->_conf->set('rcpt', array_keys($this->_ec->getByIDs($vals)), $this->_confID);
			$this->_conf->save();
		}
	}

	function confirm($val=null) {
		if (is_null($val)) {
			return (bool)$this->_conf->get('confirm', $this->_confID);
		} else {
			$this->_conf->set('confirm', (bool)$val, $this->_confID);
			$this->_conf->save();
		}
	}
	
	function allowGuest($val=null) {
		if (is_null($val)) {
			return (bool)$this->_conf->get('allowGuest', $this->_confID);
		} else {
			$this->_conf->set('allowGuest', (bool)$val, $this->_confID);
			$this->_conf->save();
		}
	}

	function precision($v=null) {
		if (is_null($v)) {
			$p = (int)$this->_conf->get('precision', $this->_confID);
			return $p > 0 ? 2 : 0;
		} else {
			$this->_conf->set('precision', $v>0 ? 2 : 0, $this->_confID);
			$this->_conf->save();
		}
	}

	function get($regionID, $deliveryID, $paymentID) {
		$sql = 'SELECT c.region_id, r.value AS region, c.delivery_id, d.value AS delivery, c.payment_id, p.value AS payment, c.fee, c.formula, c.comment, c.online_payment '
				.'FROM '.$this->_tb.' AS c, el_directory_icart_region AS r, el_directory_icart_delivery AS d, el_directory_icart_payment AS p '
				.'WHERE region_id=%d AND delivery_id=%d AND payment_id=%d AND r.id=c.region_id AND d.id=c.delivery_id AND p.id=c.payment_id';
		$sql = sprintf($sql, $regionID, $deliveryID, $paymentID);
		$this->_db->query($sql);
		return $this->_db->numRows()
			? $this->_db->nextRecord()
			: array('region_id'=>0, 
					'delivery_id' => 0, 
					'payment_id'  => 0, 
					'fee'         =>'', 
					'formula'     => '', 
					'comment'     =>'', 
					'region'      => '',
					'delivery'    => '',
					'payment'     => '',
					'online_payment' => 0
					);
	}

	function getAll() {
		$sql = 'SELECT c.region_id, c.delivery_id, c.payment_id, c.fee, c.formula, c.comment, c.online_payment, '
				.'r.value AS region, d.value AS delivery, p.value AS payment '
				.'FROM '.$this->_tb.' AS c, el_directory_icart_region AS r, '
				.'el_directory_icart_delivery AS d, el_directory_icart_payment AS p '
				.'WHERE c.region_id=r.id AND c.delivery_id=d.id AND c.payment_id=p.id '
				.'ORDER BY region, delivery, payment';
		return $this->_db->queryToArray($sql);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function regionExists($regionID) {
		$sql = 'SELECT id FROM el_directory_icart_region WHERE id='.intval($regionID);
		$this->_db->query($sql);
		return $this->_db->numRows();
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function deliveryExists($regionID, $deliveryID) {
		$sql = 'SELECT region_id, delivery_id '
				.'FROM '.$this->_tb.' WHERE region_id=%d AND delivery_id=%d';
		$sql = sprintf($sql, $regionID, $deliveryID);
		$this->_db->query($sql);
		return $this->_db->numRows();
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function paymentExists($regionID, $deliveryID, $paymentID) {
		$val = $this->get($regionID, $deliveryID, $paymentID);
		return !empty($val['payment_id']);
	}

	/**
	 * return regions list
	 *
	 * @return array
	 **/
	function getRegions() {
		$sql = 'SELECT c.region_id AS id, r.value AS name '
				.'FROM '.$this->_tb.' AS c, el_directory_icart_region AS r '
				.'WHERE c.region_id=r.id '
				.'GROUP BY c.region_id '
				.'ORDER BY IF(r.sort_ndx>0, LPAD(r.sort_ndx, 4, "0"), "9999"), r.value';
		return $this->_db->queryToArray($sql);
	}

	/**
	 * return delivery list for region
	 *
	 * @return array
	 **/
	function getDelivery($regionID) {
		$sql = 'SELECT c.delivery_id AS id, d.value AS name '
				.'FROM '.$this->_tb.' AS c, el_directory_icart_delivery AS d '
				.'WHERE c.region_id='.intval($regionID).' AND d.id=c.delivery_id '
				.'GROUP BY c.delivery_id '
				.'ORDER BY IF(d.sort_ndx>0, LPAD(d.sort_ndx, 4, "0"), "9999"), d.value';
		return $this->_db->queryToArray($sql);		
	}

	/**
	 * return payment list for region/delivery
	 *
	 * @return array
	 **/
	function getPayment($regionID, $deliveryID) {
		$sql = 'SELECT c.payment_id AS id, p.value AS name '
				.'FROM '.$this->_tb.' AS c, el_directory_icart_payment AS p '
				.'WHERE c.region_id='.intval($regionID).' AND c.delivery_id='.intval($deliveryID).' AND p.id=c.payment_id '
				.'ORDER BY IF(p.sort_ndx>0, LPAD(p.sort_ndx, 4, "0"), "9999"), p.value';
		return $this->_db->queryToArray($sql);	
	}
	

	function set($regionID, $deliveryID, $paymentID, $fee, $formula, $comment, $online="0") {
		$sql = 'REPLACE INTO '.$this->_tb.' SET region_id=%d, delivery_id=%d, payment_id=%d, fee="%s", formula="%s", comment="%s", online_payment="%d"';
		$sql = sprintf($sql, $regionID, $deliveryID, $paymentID, mysql_real_escape_string($fee), mysql_real_escape_string($formula), mysql_real_escape_string($comment), $online);
		return $this->_db->query($sql);
	}

	function delete($regionID, $deliveryID, $paymentID) {
		$sql = 'DELETE FROM '.$this->_tb.' WHERE region_id=%d AND delivery_id=%d AND payment_id=%d LIMIT 1';
		$sql = sprintf($sql, $regionID, $deliveryID, $paymentID);
		return $this->_db->query($sql);
	}

	function deleteAll($key, $val) {
		if (!empty($key) && (int)$val) {
			$this->_db->query('DELETE FROM '.$this->_tb.' WHERE '.$key.'='.intval($val).' LIMIT 1');
			$this->_db->optimizeTable($this->_tb);
		} else {
			$this->_db->query('TRUNCATE '.$this->_tb);
		}
	}
	
	
}

?>