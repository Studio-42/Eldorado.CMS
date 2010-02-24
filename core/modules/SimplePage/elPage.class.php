<?php

class elPage extends elDataMapping
{
  var $_tb      = 'el_page';
  var $ID       = 0;
  var $content  = '';
  var $_objName = 'Simple page';

  function _makeForm()
  {
    parent::_makeForm();
    $this->_form->add( new elEditor('content', '', $this->content) );
  }

  function _initMapping()
  {
    return  array('id'=>'ID', 'content'=>'content');
  }
}
?>