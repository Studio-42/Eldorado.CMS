<?php

class elPage extends elMemberAttribute
{
  var $tb       = 'el_page';
  var $ID       = 0;
  var $content  = '';
  var $_objName = 'Simple page';

  function makeForm()
  {
    parent::makeForm();
    $this->form->add( new elEditor('content', '', $this->content) );
  }

  function _initMapping()
  {
    return  array('id'=>'ID', 'content'=>'content');
  }
}
?>