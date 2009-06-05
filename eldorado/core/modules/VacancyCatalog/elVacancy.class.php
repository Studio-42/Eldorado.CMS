<?php
include_once EL_DIR_CORE.'lib/elCatalogItem.class.php';

class elVacancy extends elCatalogItem
{
  var $tbi2c     = '';
  var $ID        = 0;
  var $name      = '';
  var $descrip   = '';
  var $req       = '';
  var $func      = '';
  var $cond      = '';
  var $salary    = '';
  var $crTime    = 0;
  var $parents   = array();
  var $_objName  = 'Vacancy';
  var $_sortVars = array('name', 'crtime DESC, name');

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  function toArray()
  {
     $attrs = parent::toArray();
     $attrs['publishDate'] = !empty($attrs['crtime']) ? date(EL_DATE_FORMAT, $attrs['crtime']) : '';
     $attrs['req']  = nl2br($attrs['req']);
     $attrs['func'] = nl2br($attrs['func']);
     $attrs['cond'] = nl2br($attrs['cond']);
     return $attrs;
  }
 
  /**
   * Create edit item form object
   */
  function makeForm( $parents )
  {
    parent::makeForm($parents);

    $this->form->add( new elTextArea('req',        m('Competitor requirements'), $this->req) );
    $this->form->add( new elTextArea('func',       m('Functions'),               $this->func) );
    $this->form->add( new elTextArea('cond',       m('Working conditions'),      $this->cond) );
    $this->form->add( new elTextArea('salary',     m('Salary'),                  $this->salary) );
    $this->form->add( new elEditor('descrip',      m('Description'),             $this->descrip, array('rows'=>'35', 'height'=>'250px')) );
    $this->form->add( new elDateSelector('crtime', m('Publish date'),            $this->crTime) );
  }


 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  /**
   * Create attributes to members map
   * @ returns array
   */
  function _initMapping()
  {
    return array( 'id'        => 'ID',
                  'name'      => 'name',
                  'descrip'   => 'descrip',
                  'req'       => 'req',
                  'func'      => 'func',
                  'cond'      => 'cond',
                  'salary'    => 'salary',
                  'crtime'    => 'crTime'
                );
  }
  
  

}

?>