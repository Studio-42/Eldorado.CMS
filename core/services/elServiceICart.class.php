<?php
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';
elLoadMessages('ServiceICart');

class elServiceICart extends elService
{
	var $_mMap      = array(
		'delivery'     => array('m' => 'delivery'),
		'address'      => array('m' => 'address'),
		'confirm'      => array('m' => 'confirm'),
		'payment'      => array('m' => 'onlinePayment'),
		'info'         => array('m' => 'deliveryInfo')
	);
    var $_iCart     = null;
	var $_ats       = null;
	var $_conf      = null;
	var $_url       = '';
    var $_user      = null;
	var $_steps     = array(
		'cart' => array(
			'label'  => 'Products',
			'enable' => true,
			'allow'  => true
			),
		'delivery' => array(
			'label'  => 'Delivery/payment',
			'enable' => true,
			'allow'  => true,
			),
		'address' => array(
			'label'  => 'Customer information',
			'enable' => true,
			'allow'  => false
			),
		'confirm' => array(
			'label'  => 'Confirmation',
			'enable' => true,
			'allow'  => false
			),
		'payment' => array(
			'label'  => 'Online payment',
			'enable' => false,
			'allow'  => false
			)
			
		);
    
    function init($args) {
        $this->_args  = $args;
		$this->_ats   = & elSingleton::getObj('elATS');
		$this->_user  = & $this->_ats->getUser();
		$this->_iCart = & elSingleton::getObj('elICart');
		$this->_conf  = & elSingleton::getObj('elICartConf');
        $nav          = & elSingleton::getObj('elNavigator');
        $page         = $nav->getCurrentPage();
		$this->_url   = $page['url'];
		elAddCss('services/ICart.css', EL_JS_CSS_FILE);
        $this->_rnd = & elSingleton::getObj('elICartRnd');
		$this->_rnd->url = $this->_url;

		if (!$this->_conf->allowGuest() && !$this->_ats->isUserAuthed() ) {
			return $this->_ats->auth(EL_URL.'__icart__/');
		}

		if ($this->_user->prefrence('order_complete')) {
			$this->_user->removePrefrence('order_complete');
		} elseif ($this->_iCart->isEmpty()) {
			return elMsgBox::put(m('Your shopping cart is empty'));
		}
		
		elAppendToPagePath(array('url' => '__icart__', 'name' => 'icart'), true);
		// $this->_user->removePrefrence('icartData');
		$this->_userData = $this->_user->prefrence('icartData');

		if (!$this->_conf->getAll()) {
			$this->_steps['delivery']['enable'] = false;
			$this->_steps['address']['allow'] = true;
		} elseif (!empty($this->_userData['delivery']['region_id']) 
				&& !empty($this->_userData['delivery']['delivery_id'])
				&& !empty($this->_userData['delivery']['payment_id'])) {
					
			$test = $this->_conf->get($this->_userData['delivery']['region_id'], 
									$this->_userData['delivery']['delivery_id'], 
									$this->_userData['delivery']['payment_id']);
			if ($test['region_id']) {
				$this->_steps['address']['allow'] = true;
			}
			if (!empty($test['online_payment'])) {
				$this->_steps['payment']['enable'] = true;
			}
		}

		if (!empty($this->_userData['address'])) {
			$this->_steps['confirm']['allow'] = true;
		}

		if ($this->_steps['payment']['enable'] 
		&& !empty($this->_userData['confirm'])) {
			$this->_steps['payment']['allow'] = true;
		}
		
		$this->_rnd->steps = $this->_steps;
		// elPrintR($this->_steps);
    }
    
    /**
	 * display cart/products manipulate
	 *
	 * @return void
	 **/
    function defaultMethod() {

		if (!empty($_POST['action'])) {
			
			switch($_POST['action']) {
				case 'next':
					$this->_iCart->update($_POST['qnt']);
					elLocation($this->_url.'__icart__/'.($this->_steps['delivery']['enable'] ? 'delivery/' : 'address/'));
					break;
				case 'delete':
					if (!empty($_POST['id'])) {
						$this->_iCart->deleteItem($_POST['id']);
						elLocation($this->_url.'__icart__');
					}
					break;
				case 'clean':
					$this->_iCart->clean();
					elLocation($this->_url.'__icart__');
					break;
				case 'update':
					$this->_iCart->update($_POST['qnt']);
					elLocation($this->_url.'__icart__');
					break;
			}
			
		} 
		if (!$this->_iCart->isEmpty()) {
			$this->_rnd->rndICart($this->_iCart);
		}

    }
    
	/**
	 * proccess delivery/payment selection step
	 *
	 * @return void
	 **/
	function delivery()	{

		if (!$this->_steps['delivery']['allow']) {
			elLocation($this->_url.'__icart__/');
		} elseif (!$this->_steps['delivery']['enable']) {
			elLocation($this->_url.'__icart__/address/');
		}
		
		if (!empty($_POST['action']) && $_POST['action'] == 'next') {

			$regionID   = (int)$_POST['region_id'];
			$deliveryID = (int)$_POST['delivery_id'];
			$paymentID  = (int)$_POST['payment_id'];
			$val = $this->_conf->get($regionID, $deliveryID, $paymentID);
			if ($val['region_id'] && $val['delivery_id'] && $val['payment_id']) {
				$data = array(
					'region_id'   => $regionID,
					'delivery_id' => $deliveryID,
					'payment_id'  => $paymentID
					);
				$this->_updateUserData('delivery', $data);
				elLocation($this->_url.'__icart__/address/');
			}
		}
	
		$regions = $this->_conf->getRegions();
	
		if (!empty($this->_userData['delivery']['region_id']) 
		&&  $this->_userData['delivery']['region_id'] > 0) {
			$regionID   = (int)$this->_userData['delivery']['region_id'];
		} else {
			$profile = $this->_user->getProfile();
			foreach ($profile->_elements as $e) {
				if ($e->type == 'directory' 
				&& $e->directory == 'icart_region') {
					$regionID   = $this->_user->attr($e->ID);
				}
			}
		}
		
		if (!$regionID || !$this->_conf->regionExists($regionID)) {
			$regionID = $regions[0]['id'];
		}
		
		$delivery   = $this->_conf->getDelivery($regionID);
		
		if (!empty($this->_userData['delivery']['delivery_id'])
		&& $this->_conf->deliveryExists($regionID, $this->_userData['delivery']['delivery_id'])) {
			$deliveryID = (int)$this->_userData['delivery']['delivery_id'];
		} else {
			$deliveryID = $delivery[0]['id'];
		}

		$payment    = $this->_conf->getPayment($regionID, $deliveryID);
		if (!empty($this->_userData['delivery']['payment_id'])
		&& $this->_conf->paymentExists($regionID, $deliveryID, $this->_userData['delivery']['payment_id'])) {
			$paymentID = (int)$this->_userData['delivery']['payment_id'];
		} else {
			$paymentID = $payment[0]['id'];
		}

		$val = $this->_conf->get($regionID, $deliveryID, $paymentID);
		if ($data['fee']) {
			
		}
		$val['fee'] = $this->_fee($val);
		
		$this->_rnd->rndDelivery($regions, $delivery, $payment, $val);
	}

	/**
	 * called via ajax
	 *
	 * @return void
	 **/
	function deliveryInfo() {
		
		$ret = array(
			'delivery' => array(),
			'payment'  => array(),
			'fee'      => '',
			'comment'  => ''
			);
		$currency   = & elSingleton::getObj('elCurrency');
		$regionID   = isset($_GET['region_id'])   ? (int)$_GET['region_id']   : 0;	
		$deliveryID = isset($_GET['delivery_id']) ? (int)$_GET['delivery_id'] : 0;	
		$paymentID  = isset($_GET['payment_id'])  ? (int)$_GET['payment_id']  : 0;	
		$changed    = !empty($_GET['change'])     ? $_GET['change']           : '';
		
		switch ($changed) {
			case 'region_id':
				$ret['delivery'] = $this->_conf->getDelivery($regionID);
				if (!empty($ret['delivery'])) {
					$deliveryID = $ret['delivery'][0]['id'];
				}

			case 'delivery_id':
				$ret['payment']  = $this->_conf->getPayment($regionID, $deliveryID);
				if (!empty($ret['payment'])) {
					$paymentID = $ret['payment'][0]['id'];
				}

			case 'payment_id':
				$data           = $this->_conf->get($regionID, $deliveryID, $paymentID);
				$ret['fee']     = $this->_fee($data);
				$ret['comment'] = $data['comment'];
		}
		exit(elJson::encode($ret));
	}

	/**
	 * customer address
	 *
	 * @return void
	 **/
	function address() {
		if (!$this->_steps['address']['allow']) {
			elLocation($this->_url.'__icart__/delivery/');
		}

		$a = & new elICartAddress($this->_user, $this->_userData['address'], $this->_steps['delivery']['enable'], $this->_userData['delivery']['region_id']); 
		$f = $a->getForm();
		$f->setRenderer(new elTplFormRenderer('', 'address.html'));
		if ($f->isSubmitAndValid()) {
			$this->_updateUserData('address', $a->toArray());
			elLocation($this->_url.'__icart__/confirm/');
		} else {
			$this->_rnd->rndAddress($f->toHtml());
		}
		
		
		return;
		$address = & new elICartAddress($this->_user, $this->_userData['address'], $this->_userData['delivery']['region_id']);
		$form = $address->getForm();
		$form->setRenderer(new elTplFormRenderer('', 'address.html'));
		if ($form->isSubmitAndValid()) {
			$this->_updateUserData('address', $address->toArray());
			elLocation($this->_url.'__icart__/confirm/');
		} else {
			$this->_rnd->rndAddress($form->toHtml());
		}
	}
	

	/**
	 * display order data to confirm / complete order
	 *
	 * @return void
	 **/
	function confirm() {
		if (!$this->_steps['confirm']['allow']) {
			elLocation($this->_url.'__icart__/address/');
		}
		list($delivery, $total) = $this->_delivery();
		

		if (empty($_POST['action'])) {
			$this->_rnd->rndConfirm($this->_iCart, $delivery, $this->_userData['address'], $total);
		} elseif (false == ($orderID = $this->_completeOrder($delivery))) {
			elThrow(E_USER_WARNING, 'Unable to comlete order! Please, contacts site administrator', null, $this->_url.'__icart__/confirm/');
		} elseif ($this->_steps['payment']['enable']) {
			$this->_userData['confirm'] = 1;
			elLocation($this->_url.'__icart__/payment/');
		} else {
			$this->_complete($delivery, $orderID, $total);
			elMsgBox::put(m('Dear customer! Thank You for your order. We contact You as soon as possible'));
			elLocation($this->_url.'__icart__/');
		}
		
	}

	/**
	 * placeholder for online payment
	 *
	 * @return void
	 **/
	function onlinePayment() {
		if (!$this->_steps['payment']['enable'] || !$this->_steps['payment']['allow']) {
			elLocation($this->_url.'__icart__/');
		}
	}

    /**************************************************************/
    /**                      PRIVATE                             **/
    /**************************************************************/

	/**
	 * move order data to order tables 
	 *
	 * @param  array  $delivery
	 * @return int
	 **/
	function _completeOrder($delivery) {
		$db = & elSingleton::getObj('elDb');
		$sql = 'INSERT INTO el_order (uid, crtime, mtime, amount, delivery_price, total, region, delivery, payment) '
				.'VALUES (%d, %d, %d, "%s", "%s", "%s", "%s", "%s", "%s")';
		$sql = sprintf($sql, $this->_user->UID, time(), time(), $this->_iCart->amount, $delivery['fee'], 
							$this->_iCart->amount+$delivery['fee'], $delivery['region'], $delivery['delivery'], $delivery['payment']);
		$db->query($sql);
		if (false == ($orderID = $db->insertID())) {
			return false;
		}

		$db->prepare( 'INSERT INTO el_order_item (order_id, uid, page_id, i_id, m_id, code, name, qnt, price, props, crtime) VALUES ',
		 	'(%d, %d, %d, %d, %d, "%s", "%s", %d, "%s", "%s", %d)');
		foreach ($this->_iCart->getItems() as $i) {
			$i['props'] = serialize($i['props']);
			$db->prepareData(array($orderID, $this->_user->UID, $i['page_id'], $i['i_id'], $i['m_id'], $i['code'], $i['name'], $i['qnt'], $i['price'], $i['props'], time()));
		}
		$db->execute();

		$db->prepare('INSERT INTO el_order_customer (order_id, uid, field_id, label, value) VALUES ', '(%d, %d, "%s", "%s", "%s")');
		foreach ($this->_userData['address'] as $v) {
			$db->prepareData( array($orderID, $this->_user->UID, $v['id'], $v['label'], $v['value']));
		}
		$db->execute();
		
		
		return $orderID;
	}

	/**
	 * send order by mail
	 *
	 * @param  array  $delivery
	 * @param  int  $orderID
	 * @param  int  $total
	 * @return void
	 **/
	function _complete($delivery, $orderID, $total) {
		$msg = $this->_rnd->rndMailContent($this->_iCart, $delivery, $this->_userData['address'], $total, $orderID);
		$this->_user->prefrence('order_complete', 1);
		$this->_sendMessage($orderID, $msg);
		$this->_iCart->clean();
	}

	/**
	 * send order and confirmation
	 *
	 * @param  int     $orderID
	 * @param  string  $msg
	 * @return void
	 **/
	function _sendMessage($orderID, $msg) {
		$postman = & elSingleton::getObj('elPostman');
		$ec      = & elSingleton::getObj('elEmailsCollection');
		$subj    = sprintf(m('Order N %d from %s'), $orderID, EL_BASE_URL );
		$sender  = $ec->getDefault();
		$rcpt    = $this->_conf->recipients();

		if (empty($rcpt)) {
			$rcpt = $sender;
		}
		
		$postman->newMail($sender, $rcpt, $subj, $msg, true);
		if (!$postman->deliver()) {
			elDebug(m('Unable to send order message'));
		}
		
		if ($this->_conf->confirm()) {
			$userEmail = '';
			foreach($this->_userData['address'] as $v) {
				if ('email' == $v['id']) {
					$userEmail = $v['value'];
					break;
				}
			}
			if ($userEmail) {
				$subj = m('Order confirmation');
				$msg = m('Dear customer! We get your order and contact You as soon as posible. This is confirmation message and You dont need to answer on it.')."\n"
					.'<br/>'
	                .$msg;
				$postman->newMail($sender, $userEmail, $subj, $msg, true);
				if (!$postman->deliver()) {
					elDebug(m('Unable to send order confirmation'));
				}
			}
		}
	}

	/**
	 * update part of user data 
	 *
	 * @return void
	 **/
	function _updateUserData($var, $val) {
		$this->_userData[$var] = $val;
		$this->_user->prefrence('icartData', $this->_userData);
	}

	/**
	 * return delivery/payment info and order amount
	 *
	 * @return array
	 **/
	function _delivery() {
		$delivery = $this->_conf->get(
				$this->_userData['delivery']['region_id'], 
				$this->_userData['delivery']['delivery_id'], 
				$this->_userData['delivery']['payment_id']
				);

		if ($delivery['fee']) {
			$total = $this->_iCart->amount + $this->_fee($delivery, false);
			$total = $this->_formatPrice($total);
			$delivery['price'] = $this->_fee($delivery, true, false);
		} else {
			$total = $this->_iCart->amountFormated;
			$delivery['price'] = '';
		}
		
		return array($delivery, $total);
	}

	/**
	 * Calculate delivery price and format it
	 *
	 * @return string
	 **/
	function _fee($data, $format=true, $symbol=true) {
		$fee = 0;
		

		if ($data['fee'] > 0) {
			$fee = $data['fee'];
		} elseif ($data['formula']) {
			$f = create_function('$qnt, $amount', 'return '.$data['formula']);
			$fee = $f($this->_iCart->qnt, $this->_iCart->amount);
		}
		
		if ($format) {
			return $fee
				? $this->_formatPrice($fee, $symbol)
				: m('Free');
		}
		return $fee; 
	}

	/**
	 * format price
	 *
	 * @return string
	 **/
	function _formatPrice($price, $symbol=true) {
		$currency  = & elSingleton::getObj('elCurrency');
		return $currency->format($price, array('precision'=>$this->_conf->precision(), 'symbol'=>false));
	}
 
    /**
	 * return argument by index
	 *
	 * @return string
	 **/
    function _arg($ndx=0) {
        return isset($this->_args[$ndx]) ? $this->_args[$ndx] : null;
    }
    

} // END class


/**
 * combine user data and icart additional fields to create and proccess form
 *
 * @package icart
 **/
class elICartAddress extends elFormConstructor {
	
	var $_elements = array();
	var $_form     = null;
	
	/**
	 * constructor
	 *
	 * @param  elUser $user
	 * @param  array  $data - address from user prefrence
	 * @param  bool   $deliveryEnable
	 * @param  int	  $regionID
	 * @return void
	 **/
	function elICartAddress($user, $data, $deliveryEnable, $regionID) {
		$profile = $user->getProfile();
		$this->_elements = $profile->_elements;
		$fc = & new elFormConstructor('icart_add_field', m('Additional fields'));
		foreach ($fc->_elements as $e) {
			if (!isset($this->_elements[$e->ID])) {
				$this->_elements[$e->ID] = $e;
			}
		}

		if (is_array($data)) {
			foreach ($data as $v) {
				if (isset($v['id']) && isset($this->_elements[$v['id']])) {
					if ($this->_elements[$v['id']]->type == 'directory'
					|| $this->_elements[$v['id']]->type == 'slave-directory') {
						$this->_elements[$v['id']]->setValue($v['value_id']);
					} else {
						$this->_elements[$v['id']]->setValue($v['value']);
					}
					
				}
			}
		}
		
		if ($deliveryEnable && false != ($e = $this->findElementDirectory('icart_region'))) {
			$this->_elements[$e->ID]->freeze = true;
			$this->_elements[$e->ID]->setValue($regionID);
		}
		
	}
	
	/**
	 * return array of user address data get from form
	 *
	 * @return array
	 **/
	function toArray() {
		$data = $this->_form->getValue();
		$dm   = &elSingleton::getObj('elDirectoryManager');
		$ret = array();
		foreach ($this->_elements as $e) {
			
			if ($e->type == 'slave-directory') {
				if (!isset($data[$e->ID])) {
					continue;
				}
				
				$valueID = $data[$e->ID];
				$value   = '';
				if (false != ($m = $this->findElementDirectory($e->directory))) {
					$mvID = isset($data[$m->ID]) ? $data[$m->ID] : $m->getValue();
					if (false != ($dir = $dm->findSlave($m->directory, $mvID))) {
						$value = $dir->record($valueID);
					}
				}
				
			} elseif ($e->type == 'directory') {
				$valueID = isset($data[$e->ID]) ? $data[$e->ID] : $e->getValue();
				$value   = $dm->getRecord($e->directory, $valueID);
			} else {
				$valueID = '';
				$value   = isset($data[$e->ID]) ? $data[$e->ID] : $e->getValue();
			}
			$ret[] = array(
				'id'       => $e->ID,
				'label'    => $e->label,
				'value'    => $value,
				'value_id' => $valueID
				);
		}
		return $ret;
	}
	
	
} // END class




?>
