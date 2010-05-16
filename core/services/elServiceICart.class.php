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
		'complete'     => array('m' => 'complete'),
		'info'         => array('m' => 'deliveryInfo')
	);
    var $_iCart     = null;
	var $_ats       = null;
	var $_conf      = null;
	// var $_currency  = null;
	var $_url       = '';

    var $_user      = null;
    var $_uProfile  = null;
    var $_uProfSkel = array();
	var $_steps = array();
    
    function init($args)
    {
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

		elAppendToPagePath(array('url' => '__icart__', 'name' => 'icart'), true);
		// elAppendToPageTitle('ICart', 1);

		$this->_userData = $this->_user->getPref('icartData');

		if (empty($this->_userData['steps']) 
		|| empty($this->_userData['delivery'])) {
			$this->_userData = array(
				'steps' => array(
					'cart'     => true,
					'delivery' => true,
					'address'  => false,
					'confirm'  => false
					),
				'delivery' => array(
					'region_id'   => 0,
					'delivery_id' => 0,
					'payment_id'  => 0
					),
				'address' => array()
				);
				
			$this->_user->setPref('icartData', $this->_userData);
		}

		if ($this->_iCart->isEmpty()) {
			return elMsgBox::put('Your shopping cart is empty');
		}

		if (!$this->_conf->allowGuest() && !$this->_ats->isUserAuthed() ) {
			return $this->_ats->auth(EL_URL.'__icart__/');
		}
		
		if ($this->_userData['delivery']['region_id'] 
		&& $this->_userData['delivery']['delivery_id'] 
		&& $this->_userData['delivery']['payment_id']) {
			$this->_userData['steps']['address'] = true;
		} else {
			$this->_userData['steps']['address'] = false;
			$this->_userData['steps']['confirm'] = false;
		}
		
		if ($this->_userData['steps']['address'] && false) {
			$this->_userData['steps']['confirm'] = true;
		} else {
			$this->_userData['steps']['confirm'] = false;
		}
		$this->_rnd->stepStates = $this->_userData['steps'];
		// elPrintR($this->_userData);
    }
    
    
    function defaultMethod() {
	
		if (!empty($_POST['action'])) {
			// elPrintR($_POST);
			
			switch($_POST['action']) {
				case 'next':
					$this->_iCart->update($_POST['qnt']);
					// $this->_allowStep('delivery');
					$this->_go('delivery');
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
		
		
		
		return;
        $ats              = & elSingleton::getObj('elATS');
        $this->_user      = & $ats->getUser();
        $this->_uProfile  = & $this->_user->getProfile();
        $this->_uProfSkel = $this->_uProfile->getSkel();

		$flist = array('f_name', 's_name', 'l_name', 'email', 'phone', 'address', 'company');
		foreach ($this->_uProfSkel as $k=>$v) {
			if (!in_array($k, $flist)) {
				unset($this->_uProfSkel[$k]);
			}
		}
		$this->_uProfSkel['email']['rule'] = 'email';
		$this->_uProfSkel['email']['is_func'] = false;
		
        $this->_uProfSkel['comments'] = array('field'=>'comments', 'rq'=>1, 'label'=>'Comments', 'type'=>'textarea', 'sort_ndx'=>100, 'is_func'=>'', 'rule'=>'');
        $this->_loadAddrNfo();
        elLoadMessages('UserProfile');
        $stepID = (int)$this->_arg(0);
        $this->_checkStep($stepID);
        if ( 0 == $this->_maxStID )
        {
            elThrow(E_USER_WARNING, 'Your shopping cart is empty', null, EL_URL);
        }
        if ( $stepID <> $this->_curStID )
        {
            elThrow(E_USER_WARNING, 'Please, complete all required steps', null, EL_URL.'__icart__/');
        }
        //elDebug('step='.$this->_curStID.' maxStep='.$this->_maxStID.'<br/>');
        if ( $this->_isStepExcluded($this->_curStID) )
        {
            $this->_goNext();
        }
        
        $m = $this->_steps[$stepID][0]; 
        if ( !method_exists($this, $m) )
        {
            elThrow(E_USER_ERROR, 'Unexpected error!', null, EL_URL.'__icart__/');
        }

        $this->$m();
        $conf = &elSingleton::getObj('elXmlConf');
        $conf->set('navPathInPTitle', 3, 'layout');

        elAppendToPagePath( array( 'url'=>'__icart__/', 'name'=>m('Shopping cart'))  );
    }
    
    
	/**
	 * proccess delivery/payment selection step
	 *
	 * @return void
	 **/
	function delivery()	{

		if (!$this->_checkStep('delivery')) {
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
	
	
		$regionID   = (int)$this->_userData['delivery']['region_id'];
		$deliveryID = (int)$this->_userData['delivery']['delivery_id'];
		$paymentID  = (int)$this->_userData['delivery']['payment_id'];
		
		$regions    = $this->_conf->getRegions();
		
		$val = $this->_conf->get($regionID, $deliveryID, $paymentID);
		if ($val['region_id'] && $val['delivery_id'] && $val['payment_id'] ) {
			$delivery   = $this->_conf->getDelivery($regionID);
			$payment    = $this->_conf->getPayment($regionID, $deliveryID);
		} else {
			$delivery   = $this->_conf->getDelivery($regions[0]['id']);
			$payment    = $this->_conf->getPayment($regions[0]['id'], $delivery[0]['id']);
			$val        = $this->_conf->get($regions[0]['id'], $delivery[0]['id'], $payment[0]['id']);
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
	 * undocumented function
	 *
	 * @return void
	 **/
	function address() {
		if (!$this->_checkStep('address')) {
			elLocation($this->_url.'__icart__/delivery/');
		}

		$p = $this->_user->getProfile();

		elPrintR($p->getSkel());


		$this->_rnd->rndAddress();
		
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
		$this->_user->setPref('icartData', $this->_userData);
		elLocation($this->_url.'__icart__/'.$step.'/');
	}



	/**
	 * update part of user data 
	 *
	 * @return void
	 **/
	function _updateUserData($var, $val) {
		$this->_userData[$var] = $val;
		$this->_user->setPref('icartData', $this->_userData);
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


    //     Address
    
    function _checkAddress()
    {
        foreach ($this->_uProfSkel as $k=>$v)
        {
            if ( 2==$v['rq'] && empty($this->_addrNfo[$k]) )
            {
                return false;
            }
        }
        return true;
    }
    
    function _loadAddrNfo()
    {
        $addrNfo          = $this->_user->getPref('iCartAddr'); //elPrintR($addrNfo);
        $this->_addrNfo   = is_array( $addrNfo ) ? $addrNfo : array();
        if ( empty($this->_addrNfo) && $this->_user->UID )
        {
            $this->_setAddrNfo( $this->_user->getProfileAttrs() );
        }
//        $this->_user->setPref('iCartAddr', $this->_addrNfo);
    }
    
   
    function _getAddrField($name)
    {
        return isset($this->_addrNfo[$name]) ? $this->_addrNfo[$name] : '';
    }
    
    function _setAddrNfo( $data )
    {
        foreach ($this->_uProfSkel as $k=>$v)
        {
            if ( isset($data[$k]) )
            {
                $this->_addrNfo[$k] = $data[$k];
            }
        }
        $this->_user->setPref('iCartAddr', $this->_addrNfo);
    }
    
    function _getSummaryAmount()
    {
        return $this->_iCart->getAmount();
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




