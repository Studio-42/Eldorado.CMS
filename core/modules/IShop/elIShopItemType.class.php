<?php
/**
 * Product type
 *
 * @package iShop
 **/
class elIShopItemType extends elDataMapping {
	var $tb       = '';
	var $tbp      = '';
	var $ID       = 0;
	var $name     = '';
	var $descrip  = '';
	var $crtime   = 0;
	var $mtime    = 0;
	var $_props   = null;
	var $_objName = 'Product type';
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
	 * return number of item from current manufacturer
	 *
	 * @return int
	 **/
	function countItems() {
		return $this->_factory->ic->count(EL_IS_ITYPE, $this->ID);
	}
	
	/**
	 * Load properties if not loaded and return its
	 *
	 * @return array
	 **/
	function getProperties() {
		if (!isset($this->_props)) {
			$this->_props = array();
			$props = $this->_factory->getAllFromRegistry(EL_IS_PROP);
			foreach ($props as $p) {
				if ($p->typeID == $this->ID) {
					$this->_props[$p->ID] = $p;
				}
			}
		}
		return $this->_props;
	}

	/**
	 * Return properties which marked for announce
	 *
	 * @return array
	 **/
	function getAnnouncedProperties() {
		$ret = $this->getProperties();
		foreach ($ret as $p) {
			if (!$p->isAnnounced) {
				unset($ret[$p->ID]);
			}
		}
		return $ret;
	}

	/**
	 * Return product type with properties
	 *
	 * @return void
	 **/
	function delete($ref = null) {
		foreach ($this->props as $p) {
			$p->delete();
		}
		parent::delete();
	}

	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm($params = null) {
		parent::_makeForm();
		$this->_form->add(new elText('name', m('Type name'), $this->name));
		$this->_form->setRequired('name');
	}

	/**
	 * prepare attr for save
	 *
	 * @return array
	 **/
	function _attrsForSave() {
		$attrs = parent::_attrsForSave();
		if (!$this->ID || !$attrs['crtime']) {
			$attrs['crtime'] = time();
		}
		$attrs['mtime'] = time();
		return $attrs;
	}

	/**
	 * init attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		return array(
			'id'      => 'ID', 
			'name'    => 'name', 
			'descrip' => 'descrip',
			'crtime'  => 'crtime',  
			'mtime'   => 'mtime');
	}

} // END class 

?>
