<?php

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';

class elOrderConf {
	var $_confID = 'orderConf';
	var $_tb     = 'el_icart_conf';
	var $_conf   = null;
	var $_ec     = null;
	var $_db     = null;
	
	function elOrderConf() {
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

class elModuleICartConf extends elModule {
	
	var $_mMap = array(
		'conf'       => array('m' => 'orderConf'),
		'dir_rec'    => array('m' => 'dirRecord'),
		'dir_edit'   => array('m' => 'dirEdit'),
		'dir_clean'  => array('m' => 'dirClean'),
		'edit'       => array('m' => 'edit'),
		'rm'         => array('m' => 'remove'),
		'field_edit' => array('m' => 'fieldEdit'),
		'field_rm'   => array('m' => 'fieldRemove'),
		'sort'       => array('m' => 'sort')
		);
	
	var $_mMapConf  = array();
	var $_dm        = null;
	var $_fc        = null;
	var $_orderConf = null;
	
	var $_keys = array(
		'icart_region'   => 'region_id',
		'icart_delivery' => 'delivery_id',
		'icart_payment'  => 'payment_id',
		);

		
	function defaultMethod() {
		elLoadJQueryUI();

		$this->_initRenderer();

		$orderConf = array(
			'order_emails'  => htmlspecialchars(implode(', ', $this->_orderConf->recipients())),
			'order_confirm' => $this->_orderConf->confirm() ? m('Yes') : m('No'),
			'order_guest'   => $this->_orderConf->allowGuest()  ? m('Yes') : m('No')
			);
		$this->_rnd->rnd($orderConf, 
						$this->_orderConf->getAll(), 
						$this->_dm->get('icart_region', false), 
						$this->_dm->get('icart_delivery', false), 
						$this->_dm->get('icart_payment', false),
						$this->_fc->getAdminFormHtml()
						);
	}
	
	function orderConf() {
		$ec   = & elSingleton::getObj('elEmailsCollection');
		$form = & elSingleton::getObj( 'elForm', 'mf'.get_class($this));
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->setLabel(m('Configure order'));
		
		$form->add(new elCheckboxesGroup('rcpt', m('Recipients'), array_keys($this->_orderConf->recipients()), $ec->getLabels()));
		$form->add(new elSelect('confirm', m('Send confirm to customer'), $this->_orderConf->confirm(), $GLOBALS['yn']));
		$form->add(new elSelect('allowGuest', m('Allow non-authorized users send order'), $this->_orderConf->allowGuest(), $GLOBALS['yn']));
		$form->setRequired('rcpt[]');
		
		if (!$form->isSubmitAndValid()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($form->toHtml());
		} else {
			$data = $form->getValue();
			$this->_orderConf->recipients($data['rcpt']);
			$this->_orderConf->confirm($data['confirm']);
			$this->_orderConf->allowGuest($data['allowGuest']);
			elMsgBox::put(m('Data was saved'));
			elLocation(EL_URL);
		}
	}
	
	function dirRecord() {
		exit(elJSON::encode(array('value' => $this->_dm->getRecord($this->_arg(), (int)$this->_arg(1)))));
	}
	
	function dirEdit() {
		if (!empty($_POST['value'])) {
			$dir   = $this->_arg();
			$id    = (int)$this->_arg(1);
			$value = str_replace("\r", '', strip_tags(trim($_POST['value'])));
			$res   = false;
			if ($this->_dm->directoryExists($dir)) {
				if ($id) {
					$res = $this->_dm->updateRecord($dir, $id, $value);
				} else {
					$v    = array();
					$_tmp = explode("\n", $value);
					foreach ($_tmp as $val) {
						$val = trim($val);
						if (!empty($val)) {
							$v[] = $val;
						}
					}
					$res = !empty($v) && $this->_dm->addRecords($dir, $v);
				}
				
				if ($res) {
					elMsgBox::put(m('Data was saved'));
				} else {
					elThrow(E_USER_WARNING, 'Unable to save data');
				}
				elLocation(EL_URL);
			}
		}
	}
	
	function dirClean() {
		if (isset($_POST['id'])) {
			$dir = $this->_arg();
			$id  = (int)$_POST['id'];
			$res = false;
			
			if ($this->_dm->directoryExists($dir)) {
				$res = $id ? $this->_dm->deleteRecord($dir, $id) : $this->_dm->clean($dir);
				$this->_orderConf->deleteAll($this->_keys[$dir], $id);
			}
			if ($res) {
				elMsgBox::put(m('Data was removed'));
			} else {
				elThrow(E_USER_WARNING, 'Unable to delete data');
			}
		}
		elLocation(EL_URL);
	}

	function edit() {
		
		if (false == ($data = $this->_orderConf->get((int)$this->_arg(), (int)$this->_arg(1), (int)$this->_arg(2)))) {
			$data = array(
				'region_id'   => 0,
				'delivery_id' => 0,
				'payment_id'  => 0,
				'fee'         => '',
				'comment'     => ''
				);
		}
		
		$regions    = $this->_dm->get('icart_region');
		$deliveries = $this->_dm->get('icart_delivery');
		$payments   = $this->_dm->get('icart_payment');
		
		$form = & elSingleton::getObj('elForm', 'mf'.get_class($this));
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->setLabel(m('Configure delivery/payment'));
		
		$form->add(new elSelect('region_id',   m('Regions'),  $data['region_id'], array(m('All regions')) + $regions));
		$form->add(new elSelect('delivery_id', m('Delivery'), $data['delivery_id'], $deliveries));
		$form->add(new elSelect('payment_id',  m('Payment'),  $data['payment_id'], $payments));
		$form->add(new elText('fee', m('Fee'), $data['fee']));
		$form->add(new elText('comment', m('Comment'), $data['comment']));
		
		if (!$form->isSubmitAndValid()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($form->toHtml());
		} else {
			$data = $form->getValue();
			if ($this->_orderConf->set($data['region_id'], $data['delivery_id'], $data['payment_id'], $data['fee'], $data['comment'])) {
				elMsgBox::put(m('Data was saved'));
			} else {
				elThrow(E_USER_WARNING, 'Unable to save data');
			}
			elLocation(EL_URL);
		}

	}
	
	function remove() {
		if (!empty($_POST['rm'])) {
			$regionID   = $this->_arg();
			$deliveryID = $this->_arg(1);
			$paymentID  = $this->_arg(2);
			if ($deliveryID && $paymentID) {
				if ($this->_orderConf->delete($regionID, $deliveryID, $paymentID)) {
					elMsgBox::put(m('Data was removed'));
				} else {
					elThrow(E_USER_WARNING, 'Unable to delete data');
				}
			} else {
				$this->_orderConf->deleteAll();
				elMsgBox::put(m('Data was removed'));
			}
		}
		elLocation(EL_URL);
	}
	
	/**
	 * create/edit icart additional field
	 *
	 * @return void
	 **/
	function fieldEdit() {
		if ($this->_fc->edit((int)$this->_arg())) {
			elMsgBox::put(m('Data was saved'));
			elLocation(EL_URL);
		} else {
			$this->_initRenderer();
			$this->_rnd->addToContent($this->_fc->formToHtml());
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function fieldRemove() {
		$id = $this->_arg();
		if (empty($id)) {
			$this->_fc->clean();
			elMsgBox::put(m('Data was removed'));
		} else {
			$id = (int)$id;
			if ($this->_fc->fieldExists($id)) {
				$this->_fc->delete($id);
				elMsgBox::put(m('Data was removed'));
			} else {
				elThrow(E_USER_WARNING, '');
			}
		}
		
	}
	
	function _onInit() {
		$this->_dm = & elSingleton::getObj('elDirectoryManager');
		$this->_fc = & new elFormConstructor('icart_add_field', m('Additional fields'));
		$this->_orderConf = & elSingleton::getObj('elOrderConf');
		if (!$this->_dm->directoryExists('icart_region')) {
			$this->_dm->create('icart_region', m('Regions'));
		}
		if (!$this->_dm->directoryExists('icart_delivery')) {
			$this->_dm->create('icart_delivery', m('Delivery'));
		}
		if (!$this->_dm->directoryExists('icart_payment')) {
			$this->_dm->create('icart_payment', m('Payment'));
		}
	}
	
}

?>