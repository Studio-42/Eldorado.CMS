<?php

class elQuickFormRenderer extends elFormSimpleRenderer
{
  var $tpl = array(
		   'header'       => "<table class=\"form_tb\">\n",
		   'form_label'   => "<tr><td colspan=\"2\" class=\"form_label\"><b>%s</b></td></tr>\n",
		   'errors'       => "<tr><td colspan=\"2\" style=\"color:red;font-weight:bold;\">Some errors was found while proccessing form. Please fix it and try again.<br />%s</td></tr>\n",
		   'inline_error' => "<tr><td colspan=\"2\" style=\"color:red;\">%s</td></tr>\n",
		   'cdata'        => "%s<tr><td colspan=\"2\">%s</td></tr>\n",
		   'element'      => "%s<tr><td>%s%s</td><td>%s</td></tr>\n",
		   'label'        => "<legend>%s%s:</legend>\n",
		   'required'     => '<span style="color:red;">*</span>',
		   'footer'       => "<tr><td colspan=\"2\" class=\"form_footer\"><input type=\"submit\" value=\"%s\" />%s</td></tr></table>\n"
		   );

  function __construct( $submit='Submit', $reset=null )
  {
    $this->tpl['footer'] = sprintf($this->tpl['footer'], $submit, $reset ? '<input type="reset" value="'.$reset.'" />' : '');
  }

  function elQuickFormRenderer( $submit='Submit', $reset=null )
  {
    $this->__construct($submit, $reset);
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
      $l = !isset($renderParam['nolabel']) ? $label : '';
      $rq = $required ? $this->tpl['required'] : '';
      $this->html .= sprintf($this->tpl['element'], $err, $l, $rq, $el->toHtml());
    }
  }

}

?>