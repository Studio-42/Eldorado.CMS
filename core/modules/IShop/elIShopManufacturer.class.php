<?php
/**
 * Manufacturer
 *
 * @package IShop
 * @todo  set lago
 **/
class elIShopManufacturer extends elDataMapping {
	var $ID       = 0;
	var $name     = '';
	var $country  = '';
	var $logo     = '';
	var $content  = '';
	var $_objName = 'Manufacturer';
	var $_factory = null;

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
		return $this->_factory->getTmsByMnf($this->ID);
	}

	/**
	 * return number of item from current manufacturer
	 *
	 * @return int
	 **/
	function countItems() {
		return $this->_factory->ic->count(EL_IS_MNF, $this->ID);
	}

	/**
	 * Return current manufacturer products
	 *
	 * @return array
	 **/
	function getItems() {
		return $this->_factory->ic->create(EL_IS_MNF, $this->ID);
	}

	/**
	 * Delete manufacturer and its trademarks/models and update items table
	 *
	 * @return void
	 **/
	function delete() {
		$tbtm = $this->_factory->tb('tbtm');
		$tbi  = $this->_factory->tb('tbi');
		parent::delete(array($tbtm => 'mnf_id'));
		$db = $this->_db();
		$db->query(sprintf('UPDATE %s SET mnf_id=0, tm_id=0 WHERE mnf_id=%s', $tbi, $this->ID));
	}

	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm($params = null) {
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
