<?php
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';
elLoadMessages('ServiceICart');

class elServiceICart extends elService
{
	var $_mMap      = array(
		'repeat_order' => array('m' => 'repeatOrder'),
		'delivery'     => array('m' => 'delivery'),
		'address'      => array('m' => 'address'),
		'confirm'     => array('m' => 'confirm'),
		'info'         => array('m' => 'deliveryInfo')
	);
    var $_iCart     = null;
	var $_ats       = null;
	var $_conf      = null;
	// var $_currency  = null;
	var $_url       = '';

    var $_user      = null;
    // var $_uProfile  = null;
    // var $_uProfSkel = array();
	var $_steps = array(
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
			'label'  => 'Address',
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

		if ($this->_iCart->isEmpty()) {
			return elMsgBox::put('Your shopping cart is empty');
		}

		if (!$this->_conf->allowGuest() && !$this->_ats->isUserAuthed() ) {
			return $this->_ats->auth(EL_URL.'__icart__/');
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
    
    
    function defaultMethod() {
	
		if (!empty($_POST['action'])) {
			
			switch($_POST['action']) {
				case 'next':
					$this->_iCart->update($_POST['qnt']);
					elLocation($this->_url.'__icart__/'.($this->_steps['delivery']['enable'] ? 'delivery/' : 'address/'));
					// $this->_go('delivery');
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
				$this->_go('address');
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
		if (!$this->_checkStep('address')) {
			elLocation($this->_url.'__icart__/delivery/');
		}

		$address = & new elICartAddress($this->_user, $this->_userData['address'], $this->_userData['delivery']['region_id']);
		$form = $address->getForm();
		$form->setRenderer(new elTplFormRenderer('', 'address.html'));
		if ($form->isSubmitAndValid()) {
			// $data = $form->getValue();
			// elPrintR($address->toArray());
			$this->_updateUserData('address', $address->toArray());
			$this->_go('confirm');
		} else {
			$this->_rnd->rndAddress($form->toHtml());
		}
		
		return;

		$form = & new elForm('icartAddr');
		$rnd  = & new elTplFormRenderer('', 'address.html');
		$form->setRenderer($rnd);
		$profile = $this->_user->getProfile();
		$fc = & new elFormConstructor('icart_add_field', m('Additional fields'));
		
		$elements = $profile->_elements+$fc->_elements;
		// elPrintR($this->_userData);
		foreach ($elements as $e) {
			// elPrintR($e);
			if ($e->type == 'directory' && $e->directory == 'icart_region') {
				$dm = & elSingleton::getObj('elDirectoryManager');
				$value = $dm->getRecord($e->directory, $this->_userData['delivery']['region_id'], true);
				$form->add(new elCData2($e->ID, $e->label, $value));
			} else {
				$form->add($e->toFormElement());
				if ($e->rule) {
					$form->setElementRule($e->ID, $e->rule, $e->required, null, $e->error);
				} elseif ($e->required) {
					$form->setRequired($e->ID);
				}
			}
			
		}

		// $p = $this->_user->getProfile();
		// $form = $this->_user->getForm();
		// $form->label = m('Address');
		// $form->label = '';
		// // $renderer = & new elTplFormRenderer('./style/services/ICart/', 'address.html');
		// $form->renderer->setTpl('address.html');
		// // elPrintR($form->renderer);
		// // $form->renderer->submit = '';
		// $form->renderer->reset = '';
		// 
		// $fc = & new elFormConstructor('icart_add_field', m('Additional fields'));
		// foreach ($fc->_elements as $e) {
		// 	// elPrintR($e);
		// 	$form->add($e->toFormElement());
		// 	if ($e->rule) {
		// 		$form->setElementRule($e->ID, $e->rule, $e->required, null, $e->error);
		// 	} elseif ($e->required) {
		// 		$form->setRequired($e->ID);
		// 	}
		// }
		// $els = $fc->getElements();
		// // elPrintR($els);
		// foreach ($els as $e) {
		// 	echo $e->getAttr('name').'<br>';
		// 	// $form->add($e);
		// }
		// elPrintR($p->getSkel());

		if ($form->isSubmitAndValid()) {
			$data = $form->getValue();
			elPrintR($data);
		} else {
			$this->_rnd->rndAddress($form->toHtml());
		}
		
		
	}
	

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function confirm() {
		// elPrintR($this->_rnd);
		// $this->_rnd->rndAddress();
		$this->_rnd->rndConfirm();
	}

    function stepDisplayCart()
    {
        if ( !empty($_POST['qnt']) && is_array($_POST['qnt']) )
        {
            $this->_iCart->updateQnt( $_POST['qnt'] );
			if (empty($_POST['recalc'])) 
			{
				$this->_goNext();
			}
			else
			{
				elLocation(EL_URL.'__icart__');
			}
        }
        else
        {
            if ( !empty($_GET['rmID']) && 0 < $_GET['rmID'] )
            {
                if ( $this->_iCart->removeItem((int)$_GET['rmID']) )
                {
                    elMsgBox::put( m('Item was removed from shopping cart') );
                    elLocation( EL_URL.'__icart__/' );
                }
                else
                {
                    elThrow(E_USER_WARNING, m('Could not remove item from shopping cart'), null, EL_URL.'__icart__/' );
                }
            }
            $this->_initRenderer(); 
	        $this->_rnd->rndICart( $this->_iCart->getItems() );
        }
		
    }
    
    function stepDelivery()
    {
        if ( !empty($_POST['delivery']) )
        {
            $this->_goNext();
        }
        else
        {
            $this->_initRenderer();
            $this->_rnd->rndDelivery();
        }
    }
    
    function stepAddress()
    {
        $this->form = & elSingleton::getObj( 'elForm', 'mf' , '&nbsp;' );
        $formRnd    = & elSingleton::getObj('elTplFormRenderer');
        $formRnd->setButtonsNames( m('Continue'), '');
		$this->form->setRenderer( $formRnd );

        foreach ($this->_uProfSkel as $k=>$v)
        {
            $label = m($v['label']);
            $value = $this->_getAddrField($k);
            if ('select' == $v['type'])
			{
				if (strpos($v['opts'], 'directory:') !== false)
				{
					elSingleton::incLib('modules/Directory/elDirectory.class.php');
					$dir = new elDirectory();
					$opts = $dir->getOpts($v['opts']);
				}
				else
				{
					$opts = $v['opts'];
				}
				$this->form->add( new elSelect($k, $label, $value, $opts) );
			}
            elseif ('textarea' == $v['type'])
            {
                $this->form->add( new elTextArea($k, $label, $value, array('rows'=>5) ) );
            }
            else
            {
                $this->form->add( new elText($k, $label, $value) );
            }
            if ( $v['is_func'] )
            {
                $this->form->registerRule($v['rule'], 'func', $v['rule'], null);
            }
            $this->form->setElementRule($k, $v['rule'], $v['rq']-1);
        }
        
        if ( !$this->form->isSubmitAndValid() )
        {
            $this->_initRenderer();
            $this->_rnd->rndAddressForm( $this->form->toHtml() );    
        }
        else
        {
            $this->_setAddrNfo( $this->form->getValue() );
            $this->_goNext();
        }
    }
    
    function stepSummary()
    {
        if ( !empty($_POST['icartSummary']) )
        {
            if ( $this->_send() )
            {
                $this->_user->dropPref('iCartAddr');
                elMsgBox::put( m('Thank You for your order! We contact You as soon as possible') );
                elLocation(EL_URL);
            }
            else
            {
                elThrow(E_USER_ERROR, 'Error! Could not send order! Please, contact site administrator.');
            }
            return;
        }
        
        $this->_initRenderer();
        $addr = array();
		foreach ($this->_uProfSkel as $k=>$v) {
			$addr[] = array('label'=>m($v['label']), 'value'=>$this->_addrNfo[$k]);
		}
        $this->_rnd->rndSummary( $this->_iCart->getItems(), $this->_getDeliveryNfo(), $this->_getSummaryAmount(), $addr );
    }
    
    function toXml()
    {
        $retCode = false;
        if ('rm' == $this->_args[0] && !empty($this->_args[1]) )
        {
            $retCode = $this->_iCart->removeItem((int)$this->_args[1]);
        }
        $items = $this->_iCart->getItems();  //elPrintR($items);
        
        $reply  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $reply .= "\n<response>\n";
        $reply .= "<method>rmItem</method>\n";
        $reply .= "<result>\n";
        $reply .= "<code>".($retCode ? 'true' : 'false')."</code>\n";
        $reply .= "<items>\n";
        foreach ($items as $i)
        {
            $reply .= "<item>\n";
            $reply .= "<id>".$i['id']."</id>\n";
            $reply .= "<name>".$i['name']."</name>\n";
            $reply .= "<qnt>".$i['qnt']."</qnt>\n";
            $reply .= "<price>".$i['price']."</price>\n";
            $reply .= "<sum>".$i['sum']."</sum>\n";
            $reply .= "</item>\n";
        }
        $reply .= "</items>\n";
        $reply .= "</result>\n";
        $reply .= "</response>\n";
		
        return $reply;
    }
    
    
 
	// Repeat order from elModuleOrderHistory
	// In fact this works like addItem but adds many items at once using POST
	// TODO for now works only with IShop
	function repeatOrder()
	{
		// get IShop pageID and virtual dir, only get first
		$db = elSingleton::getObj('elDb');
		$db->query('SELECT id, dir FROM el_menu WHERE module="IShop" LIMIT 1');	
		if ($db->numRows())
			$r = $db->nextRecord();
		else
			elThrow(E_USER_WARNING, 'Critical error in module! %s', 'IShop module not found', EL_URL);
		$pageID = $r['id'];
		$dir    = $r['dir'];

		// re-arrange _POST
		$post = array();
		foreach ($_POST as $l => $v)
		{
			list($id, $l) = explode('_', $l, 2);
			$post[$id][$l] = $v;
		}

		// Add items to ICart
		$add_ok   = array();
		$add_fail = array();
		foreach ($post as $v)
			if ($v['shop'] == 'IShop')
				for ($i = 1; $i <= $v['qnt']; $i++)
				{
					$itemName = false;
					$itemName = $this->_iCart->addIShopItem($pageID, (int)$v['i_id'], $v['props']);
					//elPrintR($itemName.' '.$v['i_id'].' '.$v['props']);
					if ($itemName)
						$add_ok[$itemName]   = $itemName;
					else
						$add_fail[$itemName] = $itemName;
				}

		$msg_ok   = m('Next items "%s" were added to Your shopping cart');
		$msg_fail = m('Next items "%s" NOT were added to Your shopping cart');
		if (!empty($add_ok))
			elMsgBox::put(sprintf($msg_ok,   implode(', ', $add_ok)));
		if (!empty($add_fail))
			elMsgBox::put(sprintf($msg_fail, implode(', ', $add_fail)));
		elLocation(EL_BASE_URL.'/'.$dir.'/__icart__/');
	}
   
    /**************************************************************/
    /**                      PRIVATE                             **/
    /**************************************************************/

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _go($step) {
		$this->_user->prefrence('icartData', $this->_userData);
		elLocation($this->_url.'__icart__/'.$step.'/');
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
	 * undocumented function
	 *
	 * @return void
	 **/
	function _checkStep($step) {
		return $this->_userData['steps'][$step];
	}

	/**
	 * Calculate delivery price and format it
	 *
	 * @return string
	 **/
	function _fee($data) {
		$fee = 0;
		$currency   = & elSingleton::getObj('elCurrency');
		if ($data['fee'] > 0) {
			$fee = $data['fee'];
		} elseif ($data['formula']) {
			$f = create_function('$qnt, $amount', 'return '.$data['formula']);
			$fee = $f($this->_iCart->qnt, $this->_iCart->amount);
		}
		
		return $fee 
			? $currency->format($fee, array('precision'=>$this->_conf->precision(), 'symbol'=>true)) 
			: m('Free');
	}


 
    
    function _send()
    {
        $this->_initRenderer();
        $addr = array();
        $addrField = array();
        foreach ($this->_addrNfo as $k=>$v)
        {
            $addr[]      = array('label'=>$this->_uProfSkel[$k]['label'], 'value'=>$v);
            $addrField[] = array('label'=>$this->_uProfSkel[$k]['field'], 'value'=>$v);
        }
		$orderID  = $this->_iCart->compliteOrder($addrField, 0);
        $msg      = $this->_rnd->getSummaryRnd( $this->_iCart->getItems(), $this->_getDeliveryNfo(), $this->_getSummaryAmount(), $addr, false, $orderID );
        $subj     = sprintf(m('Order from %s'), EL_BASE_URL );
        $emails   = & elSingleton::getObj('elEmailsCollection');
        $postman  = & elSingleton::getObj('elPostman');
        $to       = $emails->getDefault();
        $from     = $emails->formatEmail($this->_addrNfo['f_name'].' '.$this->_addrNfo['l_name'], $this->_addrNfo['email']); 
        
        $postman->newMail($from, $to, $subj, $msg, false );

//        echo $subj.nl2br($msg); return;
        if ( $postman->deliver() )
        {
			if ( $this->_conf('sendConfirm') )
	        {
	            $msg = m('Dear customer! We get your order and contact You as soon as posible. This is confirmation message and You dont need to answer on it.')."\n"
	                .m('Here is order\'s content.')."\n"
	                .$msg;
	            $subj = $subj.' ('.m('Confirmation').')';
	            $postman->newMail($to, $from, $subj, $msg, false );
	            $postman->deliver();
	        }
			return true;
        }
        return false;
    }
    
    function _loadConf()
    {
        
    }
    
    function _conf($param)
    {
        return isset($this->_conf[$param]) ? $this->_conf[$param] : null;
    }
    
    function _arg($ndx=0)
    {
        return isset($this->_args[$ndx]) ? $this->_args[$ndx] : null;
    }
    
    function _isNoEmpty()
    {
        return !$this->_iCart->isEmpty();
    }
    
    function _initRenderer()
    {
		elAddCss('services/ICart.css', EL_JS_CSS_FILE);
        $this->_rnd = & elSingleton::getObj('elICartRnd');
		$this->_rnd->init('none', $this->_conf);
		$this->_rnd->setDir($this->_tplDir);
        $steps = array();
        $excl = $this->_conf('excludeSteps'); 
        if ( !is_array($excl) )
        {
            $excl = array();
        }
        for ($i=0; $i<sizeof($this->_steps); $i++)
        {
            if ( $this->_steps[$i][3] || !in_array($i, $excl) )
            {
                $steps[$i] = m($this->_steps[$i][2]);
            }
        }
        $this->_rnd->setSteps($steps, $this->_curStID, $this->_maxStID);
    }
    
}



class elICartAddress {
	var $_elements = array();
	var $_user = null;
	var $_data = array();
	var $_regionID = 0;
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function elICartAddress($user, $data, $regionID) {
		$this->_user = $user;
		$this->_data = $data;
		$this->_regionID = $regionID;
		$fc = & new elFormConstructor('icart_add_field', m('Additional fields'));
		$profile = $this->_user->getProfile();
		$this->_elements = $profile->_elements + $fc->_elements;
	}
	
	function getForm() {
		$this->form = & new elForm('icartAddr');
		foreach ($this->_elements as $e) {

			if ($e->type == 'directory' && $e->directory == 'icart_region' && $this->_regionID) {
				$dm = & elSingleton::getObj('elDirectoryManager');
				// $value = $dm->getRecord($e->directory, $this->_regionID, true);
				$this->form->add(new elCData2($e->ID, $e->label, $dm->getRecord($e->directory, $this->_regionID, true)));
			} else {
				$this->form->add($e->toFormElement());
				if ($e->rule) {
					$this->form->setElementRule($e->ID, $e->rule, $e->required, null, $e->error);
				} elseif ($e->required) {
					$this->form->setRequired($e->ID);
				}
			}
			
		}
		return $this->form;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function toArray() {
		$ret = array();
		if ($this->form) {
			$data = $this->form->getValue();
			foreach ($this->_elements as $e) {
				$value = isset($data[$e->ID]) ? $data[$e->ID] : '';
				if ($e->type == 'directory' && $e->directory == 'icart_region' && $this->_regionID) {
					$dm = & elSingleton::getObj('elDirectoryManager');
					$value = $dm->getRecord($e->directory, $this->_regionID, true);
				}
				$ret[] = array(
					'id'    => $e->ID,
					'label' => $e->label,
					'value' => $value
					);
			}
		}
		
		return $ret;
	}
	
	
}


?>