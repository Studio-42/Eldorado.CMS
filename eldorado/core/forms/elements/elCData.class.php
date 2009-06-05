<?php

class elCData extends elFormElement
{
  var $value   = '';
  var $tpl     = '';
  var $isCData = true;

  function __construct($name, $str)
  {
    $this->setName($name);
    $this->_generateID();
    $this->value = $str;
  }

  function elCData($name, $str)
  {
    $this->__construct($name, $str);
  }
  
  function toHtml()
  {
    return $this->tpl ? sprintf($this->tpl, $this->value) : $this->value;
  }
}


class elCData2 extends elFormElement
{
  var $value   = '';
  var $tpl     = '';
  //var $isCData = true;

  function __construct($name, $label, $val)
  {
    $this->setName($name);
    $this->_generateID();
    $this->label = $label;
    $this->value = $val;
  }

  function elCData2($name, $label, $val)
  {
    $this->__construct($name, $label, $val);
  }
  
  function toHtml()
  {
    return $this->tpl ? sprintf($this->tpl, $this->value) : $this->value;
  }
}


?>