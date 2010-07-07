<?php
/**
 * ishop trademark
 *
 * @package Ishop
 **/
class elIShopTm extends elDataMapping {
	var $ID       = 0;
	var $mnfID    = 0;
	var $name     = '';
	var $content  = '';
	var $_factory = null;
	var $_objName = 'Trade mark/model';

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
	 * count items with this trademark
	 *
	 * @return int
	 **/
	function countItems() {
		return $this->_factory->ic->count(EL_IS_TM, $this->ID);
	}

	/**
	 * return products with current trademark
	 *
	 * @return array
	 **/
	function getItems() {
		return $this->_factory->ic->create(EL_IS_TM, $this->ID);
	}

	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm() {
		parent::_makeForm();
		$opts = array();
		//$mnfs = $this->_factory->getMnfs();
		//foreach ($mnfs as $id => $m) {
		//	$mnfs[$id] = $m->name;
		//}

		$mnfs = $this->_factory->getAllFromRegistry(EL_IS_MNF);
		foreach ($mnfs as $id => $m) {
			$mnfs[$id] = $m->name;
		}

		$this->_form->add( new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $mnfs) );
		$this->_form->add( new elText('name', m('Name'), $this->name) );
		$this->_form->add( new elEditor('content', m('Description'), $this->descrip) );
	}

	/**
	 * init attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		return array(
			'id'      => 'ID', 
			'mnf_id'  => 'mnfID', 
			'name'    => 'name', 
			'content' => 'content'
		);
	}

} // END class

?>
