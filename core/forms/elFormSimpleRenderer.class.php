<?php

class elFormSimpleRenderer extends elFormRenderer
{
  var $tpl = array(
                  'header'       => "<fieldset>\n",
                  'form_label'   => "<legend><b>%s</b></legend>\n",
                  'errors'       => "<div style=\"color:red;\">Some errors was found while proccessing form. Please fix it and try again.<br />%s</div><br />\n",
                  'inline_error' => "<span style=\"color:red;\">%s</span><br />\n",
                  'cdata'        => "%s%s\n",
                  'element'      => "<fieldset>%s%s%s</fieldset>\n",
                  'label'        => "<legend>%s%s:</legend>\n",
                  'required'     => '<span style="color:red;">*</span>',
                  'footer'       => "</fieldset>\n"
                  );

  var $errInline = true;
  var $_errors = array();

  function setErrorsInline( $pos=true )
  {
    $this->errInline = (bool)$pos;
  }

  function setTpl($tpl, $str)
  {
    if ( isset($this->tpl[$tpl]) )
    {
      $this->tpl[$tpl] = $str;
    }
  }

  function beginForm( $attrs, $label, $errors, $js )
  {
    $this->html = '<form'.$attrs.">\n".$this->tpl['header'];
    if ( $label )
    {
      $this->html .= sprintf($this->tpl['form_label'], $label);
    }
    if ( $errors  )
    {
      if (!$this->errInline )
	    {
	      $this->html .= sprintf($this->tpl['errors'], implode('<br />', $errors));
	    }
      else
	    {
	      $this->html .= sprintf($this->tpl['errors'], '');
	      $this->_errors = $errors;
	    }
    }
  }

  function renderHidden( &$el )
  {
    $this->html .= $el->toHtml();
  }

  function renderCData( &$el, $renderParams=null )
  {
    $this->html .= sprintf($this->tpl['cdata'], '', $el->toHtml());
  }

  function renderElement( &$el, $required, $renderParams=null )
  {
    $label = $el->getLabel();
    $err = $this->errInline && isset($this->_errors[$el->getID()]) 
      ? sprintf($this->tpl['inline_error'], $this->_errors[$el->getID()])
      : '';
    
    if ( !$label )
    {
      $this->html .= sprintf($this->tpl['cdata'], $err, $el->toHtml());
    }
    else
    {
      $l = !isset($renderParam['nolabel']) 
        ? sprintf($this->tpl['label'], $label, $required ? $this->tpl['required'] : '')
        : '';
      $this->html .= sprintf($this->tpl['element'], $l, $err, $el->toHtml());
    }
  }

  function endForm()
  {
    $this->html .= $this->tpl['footer'] . "</form>\n";
    $this->_complite = true;
  }

}

?>