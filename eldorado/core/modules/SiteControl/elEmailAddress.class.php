<?php

class elEmailAddress extends elMemberAttribute
{
  var $tb        = 'el_email';
  var $ID        = 0;
  var $label     = '';
  var $email     = '';
  var $isDefault = 0;
  var $_objName  = 'E-mail address';

  function makeForm()
    {
      parent::makeForm();

      $this->form->add( new elText(  'label',      m('Label'),            $this->label) );
      $this->form->add( new elText(  'email',      m('E-mail address'),   $this->email) );
      $this->form->add( new elSelect('is_default', m('Use as default'),   $this->isDefault, array(m('No'), m('Yes'))) );

      $this->form->setElementRule('email', 'email', 1);
      $this->form->setRequired('label');
    }

  function _initMapping()
    {
      return array('id'=>'ID', 'label'=>'label', 'email'=>'email', 'is_default'=>'isDefault');
    }

  function save()
    {
      if ( !parent::save() )
	{
	  return false;
	}
      if ( $this->isDefault )
	{
	  $db = & $this->_getDb();
	  $db->query('UPDATE '.$this->tb.' SET is_default=\'0\' WHERE id<>\''.$this->ID.'\'');
	}
      return true;
    }

}
?>