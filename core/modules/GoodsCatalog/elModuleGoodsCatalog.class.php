<?php
/**
 * version: 1.0.1
 * 
 */
include_once(EL_DIR_CORE.'lib/elCatalogModule.class.php');

class elModuleGoodsCatalog extends elCatalogModule
{
	var $tbc      = 'el_gcat_%d_cat';
  var $tbi      = 'el_gcat_%d_item';
  var $tbi2c      = 'el_gcat_%d_i2c';
  var $_itemClass = 'elGCatalogItem';
  var $_mMap      = array(
  	'item'  => array('m' => 'viewItem'),
  	'order' => array('m' => 'order') );
  var $_conf      = array(
  	'deep'              => 0, 
  	'catsCols'          => 1,
  	'itemsCols'         => 2,
  	'itemsSortID'       => 1,
  	'itemsPerPage'      => 5,
  	'displayCatDescrip' => 1,
  	'currency'          => '',
	'exchangeSrc'       => 'auto',
	'commision'         => 0,
	'rate'              => 1,
    'pricePrec'         => 2,
  	'orderRcpt'         => 0
  	);


  function order()
  {
  	$this->_item = $this->_getItem();
    if ( !$this->_item->ID )
    {
      elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists', 
              array($this->_item->getObjName(), $this->_item->ID), EL_URL.$this->_cat->ID);
    }
    $this->_initRenderer();
    if ( $this->_order() )
    {
    	elMsgBox::put( m('Dear customer, Your order was recievied. We contacts You as soon as posible.'));
    	elLocation(EL_URL.$this->_cat->ID);
    }
    $this->_rnd->addToContent( $this->_form->toHtml() );
  }
 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  function _order()
 {
		$this->_loadCustomerInfo();	 	
		$this->_createOrderForm();
		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue(); 
			$this->_user->prefrence('customerInfo', $data);
			$this->_loadCustomerInfo();
			$postman = & elSingleton::getObj('elPostman');
			$ec = & elSingleton::getObj('elEmailsCollection');
			$from = $ec->formatEmail($data['email']);
			$to = $ec->getEmailByID( $this->_conf('orderRcpt') );

			$msg = m('Order')."\n";
			$msg .= sprintf( m('Item: %s'), $this->_item->name)."\n";
			foreach ($this->_customerInfo as $k=>$v)
			{
				$msg .= m($v['label']).': '.$v['value']."\n";
			}
			$postman->newMail($from, $to, m('Order'), $msg);
			if ( !$postman->deliver() )
			{
				elThrow(E_USER_WARNING, 'Could not send email');
				return false;
			}
			return true;
		}
		return false;
 }
 
  function _loadCustomerInfo()
  {
  	elLoadMessages('UserProfile');
  	$ats         = &elSingleton::getObj('elATS');
  	$this->_user =&$ats->getUser();
    $cInfo       = $this->_user->prefrence('customerInfo');
    $profile     = & $this->_user->getProfile();
    $profileSkel = $profile->getSkel(); unset($profileSkel['login']);
    $this->_customerInfo = array();
    foreach ( $profileSkel as $k=>$v )
    {
      $this->_customerInfo[$k] = $v;
      $this->_customerInfo[$k]['value'] = isset($cInfo[$k]) ? $cInfo[$k] : $profile->getAttr($k);
    }
    $this->_customerInfo['comments'] = array('label' => 'Comments',
                                             't'     => 'textarea',
                                             'value' => !empty($cInfo['comments']) ? $cInfo['comments'] : '',
                                             'rq'    => 0
                                            );


  }
 
  
  function _createOrderForm( )
  {
  	$lable = sprintf(m('Order %s'), $this->_item->name);
    $this->_form = & elSingleton::getObj( 'elForm', 'mf', $lable );
    $profile     = & $this->_user->getProfile();
    $profileSkel = $profile->getSkel() ;
    
    $this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );

    foreach ( $this->_customerInfo as $k=>$v )
    {

      if ( !empty($v['t']) && 'textarea' == $v['t'] )
      {
        $this->_form->add( new elTextArea($k, m($v['label']), $v['value'], array('rows'=>4)) );
      }
      elseif ( !empty($v['t']) && 'select' == $v['t'] )
      {
        $this->_form->add( new elTextSelect($k, m($v['label']), $v['value'], $v['opts']) );
      }
      else
      {
        $this->_form->add( new elText($k, m($v['label']), $v['value']) );
      }

      if ( $v['rq'] > 0)
      {
        if ( 'email' == $k )
        {
          $this->_form->setElementRule('email', 'email', 1 );
        }
        else
        {
          $this->_form->setRequired($k);
        }
      }
    }

  }
         

	function _onInit() {
		parent::_onInit();

		if (empty($this->_conf['currency'])) {
			$conf = & elSingleton::getObj('elXmlConf');
			$this->_conf['currency'] = $cur->current['intCode'];
			$conf->set('currency', $cur->current['intCode'], $this->pageID);
			$conf->save();
		}
	}
                 
	function _initRenderer()
  {
    parent::_initRenderer();
    // $this->_rnd->setCurrency( elGetCurrencyInfo() ); 
    // $this->_rnd->setPricePrecision( $this->_conf('pricePrecision') );
  }

}




?>
