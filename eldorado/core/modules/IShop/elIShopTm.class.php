<?php

class elIShopTm extends elMemberAttribute
{
  var $tb      = '';
  var $tbmnf   = '';
  var $ID      = 0;
  var $mnfID   = 0;
  var $name    = '';
  var $content = '';


  function makeForm()
  {
    parent::makeForm();

    $db   = & elSingleton::getObj('elDb');
    $mnfs = $db->queryToArray('SELECT id, name FROM '.$this->tbmnf.' ORDER BY name', 'id', 'name');
    $this->form->add( new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $mnfs) );
    $this->form->add( new elText('name', m('Name'), $this->name) );
    $this->form->add( new elEditor('content', m('Description'), $this->descrip) );
  }

  function _initMapping()
  {
    return array('id' => 'ID', 'mnf_id'=>'mnfID', 'name'=>'name', 'content'=>'content');
  }

}

?>