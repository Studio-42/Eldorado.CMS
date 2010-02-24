<?php

class elRadioButtons extends elFormInput
{
  var $type  = 'radio';
  var $value = '';
  var $opts  = array();
  var $tpl  = array(
                    'header'=>'', 
                    'footer'=>'', 
                    'element'=>"<label for='%s'><input%s%s value=\"%s\" />&nbsp;%s</label><br />\n"
                    );

  function __construct($name=null, $label=null, $value=null, $opts=null, $attrs=null, $frozen=false, $use_keys=true)
  {
    parent::__construct($name, $label, $value, $attrs, $frozen); 
    $this->add($opts, $use_keys);
  }

  function elRadioButtons($name=null, $label=null, $value=null, $opts=null, $attrs=null, $frozen=false, $use_keys=true)
  {
    $this->__construct($name, $label, $value, $opts, $attrs, $frozen, $use_keys);
  }

  function add( $opts, $use_keys=true )
  {
    if ( is_array($opts) )
    {
      foreach ( $opts as $key=>$val )
      {
        $this->opts[$use_keys ? $key : $val] = $val;
      }
    }
  }

  function setValue($val)
  {
    $this->value = $val;
  }

  function getValue()
  {
    return $this->value;
  }

  function toHtml()
  {
    $html = sprintf($this->tpl['header'], $this->label);
    foreach ($this->opts as $val=>$label)
    {
      $checked = $val == $this->value ? ' checked="on"' : '';
      $ID      = $this->attrs['name'].'_'.$val;
      $attrs   = str_replace('ID="'.$this->attrs['name'].'"', 'ID="'.$ID.'"', $this->attrsToString());
      $html   .= sprintf($this->tpl['element'], $ID, $checked, $attrs, $val,  $label);
    }
    return $html . $this->tpl['footer'];
  }

}

?>