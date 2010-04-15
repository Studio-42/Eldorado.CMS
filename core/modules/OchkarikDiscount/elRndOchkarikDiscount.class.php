<?php

class elRndOchkarikDiscount extends elModuleRenderer
{
	var $_tpls = array(
		'default'  => 'default.html',
		'discount' => 'discount.html'
	);

	function rndDefault()
	{
		$this->_setFile('default');
	}

	function rndDiscount($data)
	{
		$this->_setFile('discount');
		$this->_te->assignBlockVars('DISCOUNT', $data);
		$this->_te->parse('PAGE', 'PAGE', true, true);
		return $this->_te->getVar('PAGE');
	}
}
