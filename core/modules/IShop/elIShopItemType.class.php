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
	 * Sort properties
	 *
	 * @return bool
	 **/
	function sortProps() {
		$this->_makeSortForm();
		if ($this->_form->isSubmitAndValid()) {
			$db    = $this->_db();
			$props = $this->getProperties();
			$data  = $this->_form->getValue();
			asort($data);
			$i = 1;
			foreach ($data as $id => $ndx) {
				if (isset($props[$id])) {
					$db->query(sprintf('UPDATE %s SET sort_ndx=%d WHERE id=%d', $this->tbp, $i++, $id));
				}
			}
			return true;
		}
	}

	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm($params = null) {
		parent::_makeForm();
		$this->_form->add(new elText('name', m('Name'), $this->name));
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
	 * Create sort properties form
	 *
	 * @return void
	 **/
	function _makeSortForm() {
		parent::_makeForm();
		$this->_form->setLabel(sprintf(m('Sort properties for %s'), $this->name));
		$props = $this->getProperties();
		if ($props) {
			$ndxs = range(1, count($props));
			foreach ($props as $id => $p) {
				$this->_form->add(new elSelect($id, $p->name, $p->sortNdx, $ndxs, null, false, false));
			}
		}
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
