<?php
/**
 * ishop trademark
 *
 * @package Ishop
 **/
class elIShopTm extends elDataMapping {
	var $ID      = 0;
	var $mnfID   = 0;
	var $name    = '';
	var $content = '';

	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm() {
		parent::_makeForm();
		$f = & elSingleton::getObj('elIShopFactory');
		$opts = array();
		$mnfs = $f->getMnfs();
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