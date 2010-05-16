<?php

class elRndICartConf extends elModuleRenderer {
	
	function rnd($orderConf, $deliveryConf, $regions, $deliveries, $payments, $form, $precision) {
		$this->_setFile();

		$currency = & elSingleton::getObj('elCurrency');
		$this->_te->assignVars('currencySymbol', $currency->getSymbol());
		foreach($deliveryConf as $one) {
			$one['fee'] = $one['fee'] > 0 
				? $currency->format($one['fee'], array('precision' => $precision)) 
				: ($one['formula'] ? '/formula/' : m('Free')) ;
			
			$this->_te->assignBlockVars('DELIVERY_PAYMENT_CONF', $one);
		}
		
		$this->_te->assignBlockFromArray('REGION',   $regions);
		$this->_te->assignBlockFromArray('DELIVERY', $deliveries);
		$this->_te->assignBlockFromArray('PAYMENT',  $payments);
		$this->_te->assignVars('icart_form', $form);
		$this->_te->assignVars($orderConf);
	}
	
}

?>