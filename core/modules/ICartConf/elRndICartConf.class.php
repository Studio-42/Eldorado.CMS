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
		// elPrintr($regions);
		$this->_te->assignBlockFromArray('REGION',   $regions->records(false));
		$this->_te->assignBlockFromArray('DELIVERY', $deliveries->records(false));
		$this->_te->assignBlockFromArray('PAYMENT',  $payments->records(false));
		$this->_te->assignVars('icart_form', $form);
		$this->_te->assignVars($orderConf);
	}
	
}

?>