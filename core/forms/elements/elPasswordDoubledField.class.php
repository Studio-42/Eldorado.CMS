<?php

class elPasswordDoubledField extends elFormContainer
{
  var $events = array('addToForm' => 'onAddToForm', 
                      'validate'  => 'selfValidate', 
                      'submit'    => 'onSubmit');
  var $value    = array();
  var $validate = true;
  var $tpl      = array(
                        'header'  => '', 
                        'label'   => '', 
                        'element' => '%s<br />', 
                        'footer'  => ''
                        );

  function __construct($name=null, $label=null, $attrs=null)
    {
      parent::__construct($name, $label, $attrs);
      $this->add( new elPassword($this->getName().'[1]') );
      $this->add( new elPassword($this->getName().'[2]') );
    }

  function elPasswordDoubledField($name=null, $label=null, $attrs=null)
  {
    $this->__construct($name, $label, $attrs);
  }

  function onAddToForm( &$form )
  {
    $form->registerInput($this);
  }

  function onSubmit($source)
  {
    $this->setValue( array_map( 'trim', $source[$this->getName()]) );
  }

  function setValue( $value )
  {
    $this->value = $value;
  }
  
  function getValue()
  {
    return $this->value[1];
  }

  function toHtml()
  {
    $html = sprintf($this->tpl['header'], $this->attrsToString());
    if ( $this->label )
    {
      $html .= sprintf($this->tpl['label'], $this->label);
    }
    foreach ( $this->childs as $child )
    {
      $html .= sprintf($this->tpl['element'], $child->toHtml() );
    }
    $html .= $this->tpl['footer'];
    return $html;
  }

  function selfValidate(&$errors, $args)
  {
    $required = $args[0];
    $regexp   = $args[1];
    $errMsg   = $args[2];
    if ( !preg_match($regexp, $this->value[1]) )
    {
      $errors[$this->getID()] = $errMsg;
    }
    elseif ( $this->value[1] != $this->value[2])
    {
      $errors[$this->getID()] = m('Passwords are not equal');
    }
    return true;
  }
}

?>