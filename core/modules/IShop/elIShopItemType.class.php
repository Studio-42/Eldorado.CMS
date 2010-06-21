<?php

class elIShopItemType extends elDataMapping
{
	var $tb         = '';
	var $tbp        = '';
	var $ID         = 0;
	var $name       = '';
	var $crtime     = 0;
	var $mtime      = 0;
	var $pageID     = 0;
	var $_props      = null;
	var $_prop      = null;
	var $_objName   = 'Good type';




	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function getProperties() {
		$this->_loadProperties();
		return $this->_props;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function getAnnouncedProperties() {
		$ret = array();
		$this->_loadProperties();
		foreach ($this->getProperties() as $p) {
			if ($p->isAnnounced) {
				$ret[$p->ID] = $p;
			}
		}
		return $ret;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _loadProperties() {
		if (!isset($this->_props)) {
			
			$this->_props = array();
			$f = & elSingleton::getObj('elIShopFactory', $this->pageID);
			$props = $f->getAllFromRegistry(EL_IS_PROP);
			foreach ($props as $p) {
				if ($p->iTypeID) {
					$this->_props[$p->ID] = $p;
				}
			}
		}
		
	}





  function delete()
  {
    foreach ( $this->props as $p )
    {
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
      $factory     = & elSingleton::getObj('elIShopFactory');
      $this->_prop = $factory->getProperty($pID);
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
	function _makeForm() {
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
			'crtime' => 'crtime',  
			'mtime'  => 'mtime');
	}

}

?>