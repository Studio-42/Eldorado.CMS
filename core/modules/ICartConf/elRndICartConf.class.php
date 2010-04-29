<?php

class elRndICartConf extends elModuleRenderer {
	
	function rnd($orderConf, $deliveryConf, $regions, $deliveries, $payments, $form) {
		$this->_setFile();

		$this->_te->assignVars($orderConf);

		$this->_te->assignBlockFromArray('DELIVERY_PAYMENT_CONF',   $deliveryConf);
		$this->_te->assignBlockFromArray('REGION',   $regions);
		$this->_te->assignBlockFromArray('DELIVERY', $deliveries);
		$this->_te->assignBlockFromArray('PAYMENT',  $payments);
		$this->_te->assignVars('icart_form', $form->toHtml());
	}
	
}

?>