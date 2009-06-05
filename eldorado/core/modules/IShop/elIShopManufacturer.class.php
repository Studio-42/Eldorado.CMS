<?php

class elIShopManufacturer extends elMemberAttribute
{
  var $ID       = 0;
  var $name     = '';
  var $country  = '';
  var $content  = '';
  var $_objName = 'Manufacturer';
  var $tms      = array();

  function getCollection($field=null, $orderBy=null, $offset=0, $limit=0, $where=null )
  {
    $coll = parent::getCollection($field, 'name', $offset, $limit, $where);
    if ( !empty($coll) )
    {
      $factory = & elSingleton::getObj('elIShopFactory');
      $tm = $factory->getTm(0);
      $tms = $tm->getCollection(null, 'name');
      foreach ( $tms as $one )
      {
        if (!empty($coll[$one->mnfID]))
        {
          $coll[$one->mnfID]->tms[] = $one;
        }
      }
    }
    return $coll;
  }

  function makeForm()
  {
    parent::makeForm();

    $this->form->add( new elText('name',      m('Name'),        $this->name) );
    $this->form->add( new elText('country',   m('Country'),     $this->country) );
    $this->form->add( new elEditor('content', m('Description'), $this->content) );
    $this->form->setRequired('name');
  }

  function _initMapping()
  {
    return array('id' => 'ID', 'name' => 'name', 'country' => 'country', 'content' => 'content');
  }

}
?>