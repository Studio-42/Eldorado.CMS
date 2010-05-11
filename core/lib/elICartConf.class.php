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
			return (bool)$this->_conf->get('precision', $this->_confID);
		} else {
			$this->_conf->set('precision', $v>0 ? 2 : 0, $this->_confID);
			$this->_conf->save();
		}
	}

	function get($regionID, $deliveryID, $paymentID) {
		$sql = 'SELECT region_id, delivery_id, payment_id, fee, comment FROM '.$this->_tb.' WHERE region_id=%d AND delivery_id=%d AND payment_id=%d';
		$sql = sprintf($sql, $regionID, $deliveryID, $paymentID);
		$this->_db->query($sql);
		if ($this->_db->numRows()) {
			return $this->_db->nextRecord();
		}
	}

	function getAll() {
		$sql = 'SELECT c.region_id, c.delivery_id, c.payment_id, c.fee, '
				.'IF(r.value IS NULL, "'.m('All regions').'", r.value) AS region, d.value AS delivery, p.value AS payment '
				.'FROM '.$this->_tb.' AS c LEFT JOIN el_directory_icart_region AS r ON r.id=c.region_id, '
				.'el_directory_icart_delivery AS d, el_directory_icart_payment AS p '
				.'WHERE c.delivery_id=d.id AND c.payment_id=p.id '
				.'ORDER BY region, delivery, payment';
		return $this->_db->queryToArray($sql);
	}

	function set($regionID, $deliveryID, $paymentID, $fee, $comment) {
		$sql = 'REPLACE INTO '.$this->_tb.' SET region_id=%d, delivery_id=%d, payment_id=%d, fee="%s", comment="%s"';
		$sql = sprintf($sql, $regionID, $deliveryID, $paymentID, mysql_real_escape_string($fee), mysql_real_escape_string($comment));
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