<?php
include_once EL_DIR_CORE.'lib/elCatalogItem.class.php';

class elLCatalogItem extends elCatalogItem
{
  var $ID        = 0;
  var $parentID  = 1;
  var $name      = '';
  var $content   = '';
  var $URL       = '';
  var $crTime    = 0;
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
    $this->_form->add( new elEditor('content', m('Content'), $this->content, array('rows'=>'35;')) );
    $this->_form->add( new elText('url', m('URL'), $this->URL) );
    $this->_form->add( new elDateSelector('crtime', m('Publish date'), $this->crTime) );
    $this->_form->setElementRule('url', 'url', false);
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
    return array('id'        => 'ID',
                  'name'      => 'name',
                  'content'   => 'content',
                  'url'       => 'URL',
                  'crtime'    => 'crTime'
                );
  }

}

?>