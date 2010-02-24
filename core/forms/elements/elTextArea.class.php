<?php

class elTextArea extends elFormInput
{
  var $value = '';
  var $attrs = array( 'rows'  => EL_DEFAULT_TA_ROWS, 
                      'cols'  => EL_DEFAULT_TA_COLS, 
                      'style' => 'width:100%');

  function setValue($value)
  {
    $this->value = $value;
  }

  function getValue()
  {
    return $this->value;
  }

  function toHtml()
  {
    return '<textarea '.$this->attrsToString().">\n".htmlspecialchars($this->value)."</textarea>\n";
  }
}

?>