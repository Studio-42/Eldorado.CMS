<?php

class elGridFormRenderer extends elFormRenderer
{
  var $x         = 2;
  var $row       = '';
  var $rspan     = 0 ;
  var $cspan     = 0;
  var $cellNum   = 0;
  var $errInline = false;
  var $_errors   = array();

  var $tpl = array(
                  'header'       => "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n",
                  'form_label'   => "\t<tr><td colspan=\"%d\" align=\"center\"><b>%s</b></td></tr>\n",
                  'footer'       => "</table>\n",
                  'row'          => "\t<tr>\n%s\n</tr>\n",
                  'cell'         => "\t\t<td%s>\n\t\t\t%s%s%s%s\n\t\t</td>", //param, inline error,label, required, element
                  'required'     => '<span style="color:red">*</span>',
                  'errors'       => "\t<tr><td colspan=\"%d\" style=\"color:red\">Some errors was occured while proccessing form. Please fix it and try again.<br />%s</td></tr>\n",
                  'inline_error' => "\t\t<span style=\"color:red;\">%s</span><br />\n"
                  );
  
  function __construct($x=2)
  {
    $this->x = $x;
  }

  function elGridFormRenderer($x=2)
  {
    $this->__construct($x);
  }

  function setErrorsInline( $pos )
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
    $this->html = '<form'.$attrs.">\n" . $this->tpl['header'];
    if ( $label )
    {
      $this->html .= sprintf($this->tpl['form_label'], $this->x, $label);
    }
    if ( $errors  )
    {
      if (!$this->errInline )
	    {
	      $this->html .= sprintf($this->tpl['errors'], $this->x, implode('<br />', $errors));
	    }
      else
	    {
	      $this->html   .= sprintf($this->tpl['errors'], $this->x, '');
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
    $this->renderElement($el, false, $renderParams);
  }

  function renderElement( &$el, $required, $renderParams=null )
  {
    $colspan = isset($renderParams['colspan']) ? $renderParams['colspan'] : 1; 
    $this->cellNum += $colspan;
    if ( isset($renderParams['rowspan']) )
    {
      $this->rspan = $renderParams['rowspan'];
      $this->cspan = $colspan;
    }
    $l = isset($renderParams['label']) ? $el->getLabel() : '';
    $rq = $required ? $this->tpl['required'] : '';
    $err = isset($this->_errors[$el->getID()]) ? sprintf($this->tpl['inline_error'], $this->_errors[$el->getID()]) : '';
    
    $this->row .= sprintf($this->tpl['cell'], $this->_params($renderParams), $err, $l, $rq, $el->toHtml());

    if ( $this->cellNum >= $this->x )
	 {
      $this->rspan = $this->rspan ? $this->rspan-1 : 0;
      if ( !$this->rspan )
      {
        $this->cspan = 0;
      }
      $this->cellNum = $this->cspan;
      $this->html   .= sprintf($this->tpl['row'], $this->row);
      $this->row     = '';
    }
  }

  function endForm()
  {
    if ( $this->row )
    {
      $this->html .= sprintf($this->tpl['row'], $this->row);
    }
    $this->html     .= $this->tpl['footer'] . "</form>\n";
    $this->_complite = true;
  }

  function _params( $params )
  {
    $str = '';
    if ( is_array($params) )
    {
      foreach ( $params as $k=>$v )
	    {
	      if ( 'label' != $k )
        {
          $str .= ' '.$k.'="'.$v.'"';
        }
	    }
    }
    return $str;
  }
}

?>