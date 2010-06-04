<?php
/**
 * Manufacturer
 *
 * @package IShop
 **/
class elIShopManufacturer extends elDataMapping {
	var $ID       = 0;
	var $name     = '';
	var $country  = '';
	var $logo     = '';
	var $content  = '';
	var $_objName = 'Manufacturer';

	/**
	 * return manufacturer trademarks
	 *
	 * @return array
	 **/
	function getTms() {
		$f = & elSingleton::getObj('elIShopFactory');
		return $f->getTmsByMnf($this->ID);
	}

	/**
	 * return number of item from current manufacturer
	 *
	 * @return int
	 **/
	function countItems() {
		$f = & elSingleton::getObj('elIShopFactory');
		return $f->countMnfItems($this->ID);
	}

	/**
	 * Return current manufacturer products
	 *
	 * @return array
	 **/
	function getItems() {
		$f = & elSingleton::getObj('elIShopFactory');
		$i = $f->create(EL_IS_ITEM);
		return $i->collection(true, true, 'mnf_id='.intval($this->ID));
	}


	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm() {
		parent::_makeForm();
		$this->_form->add( new elText('name',      m('Name'),        $this->name) );
		$this->_form->add( new elText('country',   m('Country'),     $this->country) );
		$this->_form->add( new elEditor('content', m('Description'), $this->content) );
		$this->_form->setRequired('name');
	}

	/**
	 * create attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		return array(
			'id'      => 'ID', 
			'name'    => 'name', 
			'logo'    => 'logo',
			'country' => 'country', 
			'content' => 'content'
			);
	}

} // END class 
?>