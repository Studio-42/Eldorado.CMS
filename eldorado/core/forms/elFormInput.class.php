<?php

/**
 * абстрактный класс. Родительский для всех эл-тов типа input, textarea, file etc.
 */
class elFormInput extends elFormElement
{
  /**
   * @param	string тип input'a (аттрибут)
   */
  var $type = '';
  var $frozen = false;
  var $events = array('addToForm'=>'onAddToForm', 'submit'=>'onSubmit');
  var $validate = true;
  var $append = '';
  var $prepend = '';


  function __construct($name=null, $label=null, $value=null, $attr=null, $frozen=false)
  {
    parent::__construct($name, $label, $attr);
    $this->setValue($value);

    if ( $this->type )
    {
      $this->setAttr('type', $this->type);
    }
    if ( $frozen )
    {
      $this->freeze();
    }
  }

  function elFormInput($name=null, $label=null, $value=null, $attr=null, $frozen=false)
  {
    $this->__construct($name, $label, $value, $attr,  $frozen);
  }

  function freeze()
  {
    $this->frozen = true;
    if ( 'hidden' != $this->getAttr('type') )
    {
      $this->setAttr('disabled', 'on');
    }
  }

  function unfreeze()
  {
    $this->frozen = false;
    $this->dropAttr('disabled');
  }

  function getValue()
  {
    return $this->getAttr('value');
  }

  function setValue( $value )
  {
    $this->setAttr('value', $value);
  }

  function onAddToForm( &$form )
  {
    $form->registerInput($this);
  }

  /**
   * обновляет значение эл-та из массива $source ($_POST/$_GET)
   */
  function onSubmit( $source )
  {
    if ( $this->frozen )
    {
      return;
    }
    $name = preg_replace('/(\[\])+$/', '', $this->getName());
    if ( false === ($pos = strpos($name, '[')) )
    {
      $val = isset($source[$name]) ? $source[$name] : NULL;
    }
    else
    {
      $n1 = substr($name, 0, $pos);
      $n2 = substr($name, $pos);
      $n2 = preg_replace("/\[([^']+)\]/", "['\\1']", $n2);
      eval('$val = isset($source[\''.$n1.'\']'.$n2.') ? $source[\''. $n1. '\']' . $n2 .' : NULL;');
    }
    $val = ( !is_array($val) && !is_null($val) ) ?  trim($val) : $val;
    $this->setValue( $val );
  }

  function toHtml()
  {
    return $this->prepend.'<input '.$this->attrsToString().' />'.$this->append;
  }
}

class elHidden extends elFormInput
{
  var $type = 'hidden';
}

class elText extends elFormInput
{
  var $type  = 'text';
  var $attrs = array('size'=>EL_DEFAULT_TEXT_SIZE);
}

class elPassword extends elFormInput
{
  var $type  = 'password';
  var $attrs = array('size'=>EL_DEFAULT_TEXT_SIZE);
}

class elSubmit extends elFormInput
{
  var $type     = 'submit';
  var $validate = false;
  var $events   = array('addToForm'=>'onAddToForm');
}

class elReset extends elFormInput
{
  var $type     = 'reset';
  var $validate = false;
  var $events   = array('addToForm'=>'onAddToForm');
}

class elFormImage extends elFormInput
{
  var $type     = 'image';
  var $validate = false;

  function setValue( $val )
  {
    $this->setAttr( 'src', $val );
  }
}
?>