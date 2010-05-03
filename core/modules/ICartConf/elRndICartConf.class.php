<?php

class elRndICartConf extends elModuleRenderer {
	
	function rnd($orderConf, $deliveryConf, $regions, $deliveries, $payments, $form) {
		$this->_setFile();

		$this->_te->assignVars($orderConf);
		$this->_te->assignVars('icart_form', $form);
		$this->_te->assignBlockFromArray('DELIVERY_PAYMENT_CONF',   $deliveryConf);
		$this->_te->assignBlockFromArray('REGION',   $regions);
		$this->_te->assignBlockFromArray('DELIVERY', $deliveries);
		$this->_te->assignBlockFromArray('PAYMENT',  $payments);
		// $this->_te->assignVars('icart_form', $form->toHtml());
		// foreach($elements as $e) {
		// 	if ($e->isCData) {
		// 		
		// 	} else {
		// 		$this->_te->assignBlockVars('ICART_FORM_EL', array(
		// 			'id'      => $e->getAttr('name'),
		// 			'label'   => $e->label,
		// 			'element' => $e->toHtml()
		// 			));
		// 	}
		// }
	}
	
}

?>