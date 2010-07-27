<?php

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';
elLoadMessages('ServiceICart');
elLoadMessages('Form');
elLoadMessages('FormConstructor');

class elModuleICartConf extends elModule {
	
	var $_mMap = array(
		'conf'       => array('m' => 'orderConf'),
		'dir_rec'    => array('m' => 'dirRecord'),
		'dir_sort'   => array('m' => 'dirSort'),
		'dir_edit'   => array('m' => 'dirEdit'),
		'dir_clean'  => array('m' => 'dirClean'),
		'edit'       => array('m' => 'edit'),
		'rm'         => array('m' => 'remove'),
		'field_edit' => array('m' => 'fieldEdit'),
		'field_rm'   => array('m' => 'fieldRemove'),
		'field_sort' => array('m' => 'fieldsSort')
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

	/**
	 * icart config summary
	 *
	 * @return void
	 **/
	function defaultMethod() {
		elLoadJQueryUI();

		$this->_initRenderer();

		$orderConf = array(
			'order_emails'  => htmlspecialchars(implode(', ', $this->_orderConf->recipients())),
			'order_confirm' => $this->_orderConf->confirm() ? m('Yes') : m('No'),
			'order_guest'   => $this->_orderConf->allowGuest() ? m('Yes') : m('No'),
			'precision'     => $this->_orderConf->precision() > 0 ? m('Double, two signs after dot') : m('Integer')
			);
		$this->_rnd->rnd($orderConf, 
						$this->_orderConf->getAll(), 
						$this->_dm->get('icart_region',   false), 
						$this->_dm->get('icart_delivery', false), 
						$this->_dm->get('icart_payment',  false),
						$this->_fc->getAdminFormHtml(),
						$this->_orderConf->precision() > 0 ? 2 : 0
						);
	}
	
	/**
	 * edit common config
	 *
	 * @return void
	 **/
	function orderConf() {
		$ec   = & elSingleton::getObj('elEmailsCollection');
		$form = & elSingleton::getObj( 'elForm', 'mf'.get_class($this));
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->setLabel(m('Configure order'));
		
		$form->add(new elCheckboxesGroup('rcpt', m('Order recipients'), array_keys($this->_orderConf->recipients()), $ec->getLabels()));
		$form->add(new elSelect('confirm', m('Send confirm to customer'), $this->_orderConf->confirm(), $GLOBALS['yn']));
		$form->add(new elSelect('allowGuest', m('Allow non-authorized users send order'), $this->_orderConf->allowGuest(), $GLOBALS['yn']));
		$formats = array(0=>m('Integer'), 2=> m('Double, two signs after dot'));
		$form->add(new elSelect('precision', m('Price format'), $this->_orderConf->precision() > 0 ? 2 : 0, $formats));
		$form->setRequired('rcpt[]');
		
		if (!$form->isSubmitAndValid()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($form->toHtml());
		} else {
			$data = $form->getValue();
			$this->_orderConf->recipients($data['rcpt']);
			$this->_orderConf->confirm($data['confirm']);
			$this->_orderConf->allowGuest($data['allowGuest']);
			$this->_orderConf->precision($data['precision']);
			elMsgBox::put(m('Data was saved'));
			elLocation(EL_URL);
		}
	}
	
	function dirRecord() {
		exit(elJSON::encode(array('value' => $this->_dm->getRecord($this->_arg(), (int)$this->_arg(1)))));
	}
	
	/**
	 * add new records/edit record
	 *
	 * @return void
	 **/
	function dirEdit() {
		if (!empty($_POST['value'])) {
			$dirID   = $this->_arg();
			$recID   = (int)$this->_arg(1);
			$value   = trim($_POST['value']);
			$res     = false;
			if ($this->_dm->directoryExists($dirID)) {
				$dir = $this->_dm->get($dirID);
				if ($recID) {
					$dir->update($recID, $value);
				} else {
					$dir->add(explode("\n", str_replace("\r", '', $value)));
				}
				elMsgBox::put(m('Data saved'));
			}
		}
		elLocation(EL_URL);
	}
	
	/**
	 * sort records in directory
	 *
	 * @return void
	 **/
	function dirSort() {
		$dirID = $this->_arg();

		if ($this->_dm->directoryExists($dirID) 
		&& !empty($_POST['dir_sort']) 
		&& is_array($_POST['dir_sort'])) {
			$dir = $this->_dm->get($dirID);
			$dir->sort($_POST['dir_sort']);
			elMsgBox::put(m('Data saved'));
		}
		elLocation(EL_URL);
	}
	

	/**
	 * clean directory
	 *
	 * @return void
	 **/
	function dirClean() {
		if (isset($_POST['id'])) {
			$dirID = $this->_arg();
			$recID  = (int)$_POST['id'];
			
			if ($this->_dm->directoryExists($dirID)) {
				$dir = $this->_dm->get($dirID);
				if ($recID) {
					$dir->deleteRecord($recID);
				} else {
					$dir->clean();
				}
				$this->_orderConf->deleteAll($this->_keys[$dirID], $recID);
				elMsgBox::put(m('Data was removed'));
			}
		}
		elLocation(EL_URL);
	}

	/**
	 * region/delivery/payment edit/create
	 *
	 * @return void
	 **/
	function edit() {
		
		if (false == ($data = $this->_orderConf->get((int)$this->_arg(), (int)$this->_arg(1), (int)$this->_arg(2)))) {
			$data = array(
				'region_id'   => 0,
				'delivery_id' => 0,
				'payment_id'  => 0,
				'fee'         => '',
				'formula'     => '',
				'comment'     => ''
				);
		}
		
		$r = $this->_dm->get('icart_region');
		$d = $this->_dm->get('icart_delivery');
		$p = $this->_dm->get('icart_payment');
		
		$regions  = $r->records();
		$delivery = $d->records();
		$payment  = $p->records();
		
		if (!$regions || !$delivery || !$payment) {
			elThrow(E_USER_WARNING, 'Regions/delivery/payments list are required', null, EL_URL);
		}
		
		$currency = & elSingleton::getObj('elCurrency');
		$form = & elSingleton::getObj('elForm', 'mf'.get_class($this));
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->setLabel(m('Delivery/payment configuration'));
		
		$form->add(new elSelect('region_id',   m('Regions'),  $data['region_id'],   $regions));
		$form->add(new elSelect('delivery_id', m('Delivery'), $data['delivery_id'], $delivery));
		$form->add(new elSelect('payment_id',  m('Payment'),  $data['payment_id'],  $payment));
		$form->add(new elText('fee', m('Delivery price').', '.$currency->getSymbol(), $data['fee'], array('size'=>'12')));
		$form->add(new elCData('c1', m('If you need to calculate delivery price based upon order amount enter here valid PHP code. Two variable - $qnt and $amount are available here. Example: <code>$amount >= 1000 ? 0 : 200;</code> - if amount is equal or more 1000 - delivery is free, else 200')));
		$form->add(new elTextArea('formula', m('Delivery price formula'), $data['formula'], array('rows' => 5)));
		$form->add(new elTextArea('comment', m('Comment'), $data['comment'], array('rows' => 5)));
		
		if (!$form->isSubmitAndValid()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($form->toHtml());
		} else {
			$data = $form->getValue();
			if ($this->_orderConf->set($data['region_id'], $data['delivery_id'], $data['payment_id'], $data['fee'], $data['formula'], $data['comment'])) {
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
					// $this->_removeUsersPref();
				} else {
					elThrow(E_USER_WARNING, 'Unable to delete data');
				}
			} else {
				$this->_orderConf->deleteAll();
				elMsgBox::put(m('Data removed'));
				// $this->_removeUsersPref();
			}
		}
		elLocation(EL_URL);
	}
	
	function fieldsSort() {
		
		if (!$this->_fc->sort()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($this->_fc->formToHtml());
		} else {
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL);
		}
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

	 **/
	function fieldRemove() {
		
		if (!empty($_POST['rm'])) {
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
			elLocation(EL_URL);
		}
		
		
		
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _removeUsersPref() {
		$ats = &elSingleton::getObj('elATS');
		$user = & $ats->getUser();
		$user->removePrefrence('icartData');
	}
	
	
	function _onInit() {
		$this->_dm = & elSingleton::getObj('elDirectoryManager');
		$this->_fc = & new elFormConstructor('icart_add_field', m('Additional fields'));
		$this->_orderConf = & elSingleton::getObj('elICartConf');
		// if (!$this->_dm->directoryExists('icart_region')) {
		// 	$this->_dm->create('icart_region', m('Regions'));
		// }
		// if (!$this->_dm->directoryExists('icart_delivery')) {
		// 	$this->_dm->create('icart_delivery', m('Delivery'));
		// }
		// if (!$this->_dm->directoryExists('icart_payment')) {
		// 	$this->_dm->create('icart_payment', m('Payment'));
		// }
	}
	
}

?>
