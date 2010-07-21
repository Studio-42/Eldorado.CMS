<?php

class elIShopItemType extends elDataMapping
{
	var $tb         = '';
	var $tbp        = '';
	var $ID         = 0;
	var $name       = '';
	var $descrip    = '';
	var $crtime     = 0;
	var $mtime      = 0;
	var $_props     = null;
	var $_prop      = null;
	var $_objName   = 'Product type';
	var $_factory   = null;

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
	 * Return all properties
	 *
	 * @return array
	 **/
	function getProperties() {
		return $this->_getProperties();
	}

	/**
	 * Return properties which marked for announce
	 *
	 * @return array
	 **/
	function getAnnouncedProperties() {
		$ret = $this->_getProperties();
		foreach ($ret as $p) {
			if (!$p->isAnnounced) {
				unset($ret[$p->ID]);
			}
		}
		return $ret;
	}

	/**
	 * Load properties if not loaded and return
	 *
	 * @return array
	 **/
	function _getProperties() {
		if (!isset($this->_props)) {
			$this->_props = array();
			$props = $this->_factory->getAllFromRegistry(EL_IS_PROP);
			foreach ($props as $p) {
				if ($p->typeID) {
					$this->_props[$p->ID] = $p;
				}
			}
		}
		return $this->_props;
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

  function getProperyFormElement($pID)
  {
    return !empty($this->props[$pD]) ? $this->props[$pD]->getFormElement() : null;
  }

  function editProperty($pID)
  {
    if ( !empty($this->props[$pID]) )
    {
      $this->_prop = $this->props[$pID];
    }
    else
    {
      $this->_prop = $this->_factory->getProperty($pID);
      $this->_prop->attr('t_id', $this->ID);
    }
    return $this->_prop->editAndSave( array('itName'=>$this->name, 'maxSortNdx'=>sizeof($this->props)) );
  }

  function removeProperty($pID)
  {
    if ( !$this->isPropertyExists($pID) )
    {
      return elThrow( E_USER_WARNING, 'There is no object "%s" with ID="%d"', array(m('Property'), $pID) );
    }
    $this->props[$pID]->delete();
    elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), m('Property'), $this->props[$pID]->name) );
    unset($this->props[$pID]);
    return true;
  }

  function editPropertyDependance($pID)
  {
    if ( !$this->isPropertyExists($pID) )
    {
      return elThrow( E_USER_WARNING, 'There is no object "%s" with ID="%d"', array(m('Property'), $pID) );
    }
    $this->_prop = $this->props[$pID];
    if ( !$this->_prop->isDependAvailable()
    && !empty($this->_prop->dependID)
    && !empty($this->props[$this->_prop->dependID]) )
    {
      return elThrow( E_USER_WARNING, 'Could not edit property dependance' );
    }
    return $this->_prop->editDependance( $this->name, $this->props[$this->_prop->dependID] );

  }

  function formToHtml()
  {
    return $this->_form ? $this->_form->toHtml() : ( $this->_prop ? $this->_prop->formToHtml() : '' );
  }

	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm($params = null) {
		parent::_makeForm();
		$this->_form->add( new elText('name', m('Type name'), $this->name));
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
			'id'     => 'ID', 
			'name'   => 'name', 
			'descrip' => 'descrip',
			'crtime' => 'crtime',  
			'mtime'  => 'mtime');
	}

}

?>
