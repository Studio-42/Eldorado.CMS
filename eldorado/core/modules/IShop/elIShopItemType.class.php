<?php

class elIShopItemType extends elMemberAttribute
{
  var $tb         = '';
  var $tbp        = '';
  var $ID         = 0;
  var $name       = '';
  var $crtime     = 0;
  var $mtime      = 0;
  var $props      = array();
  var $_prop      = null;
  var $_objName   = 'Good type';


  function getCollection()
  {
    $coll    = parent::getCollection();
    if ( !empty($coll) )
    {
      $factory = &elSingleton::getObj('elIShopFactory');
      $props   = $factory->getProperties();
      foreach ( $props as $p)
      {
        if ( !empty($coll[$p->iTypeID]) )
        {
          $coll[$p->iTypeID]->setProperty($p);
        }
      }
    }
    return $coll;
  }


  function isPropertyExists($pID)
  {
    return !empty($this->props[$pID]);
  }

  function getProperties()
  {
    return $this->props;
  }

  function setProperty($p)
  {
    $this->props[$p->ID] = $p;
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
      $this->_prop->setAttr('t_id', $this->ID);
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
    return $this->form ? $this->form->toHtml() : ( $this->_prop ? $this->_prop->formToHtml() : '' );
  }

  function makeForm()
  {
    parent::makeForm();
    $this->form->add( new elText('name', m('Type name'), $this->name));
    $this->form->setRequired('name');
  }

  function _attrsForSave()
  {
    $attrs = parent::_attrsForSave();
    if (!$this->ID || !$attrs['crtime'])
    {
      $attrs['crtime'] = time();
    }
    return $attrs;
  }




  function _initMapping()
  {
    return array('id'=>'ID', 'name'=>'name', 'crtime'=>'crtime',  'mtime'=>'mtime');

  }

}

?>