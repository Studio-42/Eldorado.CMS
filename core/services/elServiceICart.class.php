<?php

elLoadMessages('ServiceICart');
class elServiceICart extends elService
{
    var $_vdir      = '__icart__';
    var $_mMap      = array('add' => array('m' => 'addItem'));
    var $_iCart     = null;
    var $_page      = array();
    var $_steps     = array(
                        0 => array('stepDisplayCart', '_isNoEmpty',     'Shopping cart information', 1),
                        1 => array('stepDelivery',    '_checkDelivery', 'Delivery informatiom',      0),
                        2 => array('stepAddress',     '_checkAddress' , 'Customer information',      1),
                        3 => array('stepSummary',     '',               'Summary information',       1)
                        );
    var $_curStID   = 0;
    var $_maxStID   = 0;
    var $_conf      = array('excludeSteps' => array(1), 'sendConfirm' => 1, 'precision'=>2 );
    var $_tplDir    = 'services/ICart/';
    var $form       = null;
    var $_addrNfo   = array();
    var $_user      = null;
    var $_uProfile  = null;
    var $_uProfSkel = array();
    
    function init($args)
    {
        $this->_args  = $args;
        $this->_iCart = & elSingleton::getObj('elICart');
        $nav          = & elSingleton::getObj('elNavigator');
        $this->_page  = $nav->getCurrentPage();
		$conf = &elSingleton::getObj('elXmlConf');
		$tmp = $conf->getGroup('iCart');
		foreach ($tmp as $k=>$v) {
			if (isset($this->_conf[$k])) {
				$this->_conf[$k] = $v;
			}
		} 
    }
    
    
    function defaultMethod()
    {
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
            elThrow(E_USER_WARNING, 'Please, complite all required steps', null, EL_URL.'__icart__/');
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
                $this->form->add( new elSelect($k, $label, $value, $v['opts']) );
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
    
    function addItem()
    {
        $URL= EL_URL.'item/'.$this->_args[1].'/'.$this->_args[2];
        if ('IShop'== $this->_page['module'])
        {
            $itemName = $this->_iCart->addIShopItem($this->_page['id'], (int)$this->_args[2], $_POST['prop']); //echo EL_URL;
        }
        elseif ('TechShop' == $this->_page['module'])
        {
            $itemName = $this->_iCart->addTechShopItem($this->_page['id'], (int)$this->_args[2], (int)$this->_args[3]);
        }
        if ( !$itemName )
        {
            $msg = sprintf( m('Error! Could not add item to Your shopping cart! Please contact site administrator.') );
            elThrow(E_USER_WARNING, $msg, null, $URL);
        }
        $msg = sprintf( m('Item %s was added to Your shopping cart. To proceed order right now go to <a href="%s">this link</a>'),
                        $itemName, EL_URL.'__icart__/' );
        elMsgBox::put($msg);
        //elPrintR($this->_args[3]);
	
	elLocation( (isset($this->_args[3]) && $this->_args[3] == 'cat') ? EL_URL.$this->_args[1].'/' : $URL );
        
    }
    
    /**************************************************************/
    /**                      PRIVATE                             **/
    /**************************************************************/
    function _goNext()
    {
        elLocation(EL_URL.'__icart__/'.($this->_curStID + 1));
    }
    
    function _checkStep($stepID)
    {
        if ( $this->_iCart->isEmpty() )
        {
            return -1;
        }
        
        for($i=0; $i<sizeof($this->_steps)-1; $i++)
        {
            $m = $this->_steps[$i][1];
            if ( !$this->_isStepExcluded($i) && method_exists($this, $m) && !$this->$m() )
            {
                break;
            }
            else
            {
                $this->_maxStID = $i+1;
            }
        }
        $this->_curStID = ( 0<=$stepID &&  $stepID<sizeof($this->_steps) && $stepID <= $this->_maxStID ) ? $stepID : 0;
        return;    
    }
    
    function _isStepExcluded( $stepID )
    {
        $excl = $this->_conf('excludeSteps');
        if ( empty($this->_steps[$stepID][3]) && is_array($excl) && in_array($stepID, $excl) )
        {
            return true;
        }
        return false;
    }

    //     Delivery
    
    function _checkDelivery()
    {
        return true;
    }
    
    function _getDeliveryNfo()
    {
        return null;
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

?>