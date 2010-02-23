<?php
include_once EL_DIR_CORE.'lib/elCatalogItem.class.php';

class elDCatalogItem extends elCatalogItem
{
  var $tbi2c     = '';
  var $ID        = 0;
  var $name      = '';
  var $announce  = '';
  var $content   = '';
  var $crTime    = 0;
  var $parents   = array();
  var $_objName  = 'Document';
  var $_sortVars = array('name', 'crtime DESC, name');

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  /**
   * Create edit item form object
   */
  function _makeForm( $parents )
  {
    parent::_makeForm($parents);

    $this->_form->add( new elEditor('announce', m('Announce'), $this->announce, array('rows'=>'350')) );
    $this->_form->add( new elEditor('content', m('Content'), $this->content) );
    $this->_form->add( new elDateSelector('crtime', m('Publish date'), $this->crTime) );
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
    return array( 'id'       => 'ID',
                  'name'     => 'name',
                  'announce' => 'announce',
                  'content'  => 'content',
                  'crtime'   => 'crTime'
                );
  }
  
  

}

?>