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
	var $pageID   = 0;
	var $_objName = 'Manufacturer';

	/**
	 * add count items to default array
	 *
	 * @return array
	 **/
	function toArray() {
		$ret = parent::toArray();
		$ret['itemsCnt'] = $this->countItems();
		return $ret;
	}

	/**
	 * return manufacturer trademarks
	 *
	 * @return array
	 **/
	function getTms() {
		$f = & elSingleton::getObj('elIShopFactory', $this->pageID);
		return $f->getTmsByMnf($this->ID);
	}

	/**
	 * return number of item from current manufacturer
	 *
	 * @return int
	 **/
	function countItems() {
		$c = & elSingleton::getObj('elIShopItemsCollection', $this->pageID);
		return $c->count(EL_IS_MNF, $this->ID);
	}

	/**
	 * Return current manufacturer products
	 *
	 * @return array
	 **/
	function getItems() {
		$c = & elSingleton::getObj('elIShopItemsCollection', $this->pageID);
		return $c->create(EL_IS_MNF, $this->ID);
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