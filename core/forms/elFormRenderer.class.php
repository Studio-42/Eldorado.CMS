<?php

class elFormRenderer
{
  var $form      = null;
  var $html      = '';
  var $_complite = false;

  function getHtml()
  {
    return $this->html;
  }

  function renderComplite()
  {
    return $this->_complite;
  }

  function beginForm( $attrs, $label, $errors, $jsSrc, $jsBaseURL ) {}

  function renderHidden( &$el ) {}

  function renderCData( &$el, $renderParams=null ) {}

  function renderElement( &$el, $required, $renderParams=null )  {}

  function endForm() { }
}
?>