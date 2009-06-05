<?php

class elTplGridFormRenderer extends elFormRenderer
{
  var $tplEngine = null;
  var $tplFile   = 'forms/grid_form.html';
  var $cellNum   = 2;
  var $_rspan    = 0 ;
  var $_cspan    = 0;
  var $_cellCnt  = 0;
  var $errInline = false;
  var $errors    = array();
  var $buttons   = array();

  function __construct( $cellNum = 2, $tplFile='forms/grid_form.html')
  {
    $this->cellNum = $cellNum; 
    $this->setTpl( $tplFile );
  }

  function elTplGridFormRenderer( $cellNum = 2, $tplFile='forms/grid_form.html')
  {
    $this->__construct($cellNum, $tplFile);
  }

  function addButton( $button )
  {
    if ( is_array($button) )
    {
      foreach ( $button as $one )
      {
        array_push($this->buttons, $one);
      }
    }
    else
    {
      array_push($this->buttons, $button);
    }
  }

  function setErrorsInline( $pos )
  {
    $this->errInline = (bool)$pos;
  }

  function setTpl( $fileName )
  {
    $this->tplFile = $fileName; 
  }

  function beginForm( $attrs, $label, $errors, $js )
  {
    $this->tplEngine = & new elTE();
    $this->tplEngine->setFile(   'form',       $this->tplFile);
    $this->tplEngine->assignVars('form_attrs', $attrs);
    $this->tplEngine->assignVars('cellNum',    $this->cellNum);
    if ( $label )
    {
      $this->tplEngine->assignBlockVars('FORM_HEAD', array('form_label'=>$label));
    }

    if ( $errors )
    {
      if ( !$this->errInline )
      {
        $err = implode('<br />', $errors);
      }
      else
      {
        $this->errors = $errors;
        $err = '';
      }
      $this->tplEngine->assignBlockVars('FORM_HEAD.ERRORS', array('errors'=>$err), 1);
    }
  }

  function renderHidden( &$el )
  {
    $this->tplEngine->assignVars('hiddens', $el->toHtml(), true);
  }

  function renderCData( &$el, $renderParams=null )
  {
    $this->renderElement( $el, false, $renderParams);
  }

  function renderElement( &$el, $required, $renderParams=null )
  {
    if ( 0 == $this->_cellCnt )
    {
      $this->tplEngine->assignBlockVars('FORM_BODY.ELS_ROW', null);
    }

    $colspan = !empty($renderParams['colspan']) && $renderParams['colspan'] > 1
      ? $renderParams['colspan']
      : 1;
    $this->_cellCnt += $colspan;
    if ( empty($renderParams['rowspan']) || $renderParams['rowspan'] <= 1 )
    {
      $this->_rspan = 1;
    }
    else
    {
      $this->_rspan = $renderParams['rowspan'] ;
      $this->_cspan = $colspan;
    }

    $l = isset($renderParams['label']) ? $el->getLabel() : '';

    $this->tplEngine->assignBlockVars('FORM_BODY.ELS_ROW.ELEMENT',
          array('label'=>$l, 'el'=>$el->toHtml(), 'params'=>$this->_params($renderParams) ), 2 );

    if ( $required )
    {
      $this->tplEngine->assignBlockVars('FORM_BODY.ELS_ROW.ELEMENT.RQ', null, 4);
    }
    if ( isset($this->_errors[$el->getID()]) )
    {
      $this->tplEngine->assignBlockVars('FORM_BODY.ELS_ROW.ELEMENT.ERROR', array('error'=>$this->_errors[$el->getID()]), 4);
    }
    if ( $this->_cellCnt >= $this->cellNum )
    {
      $this->_rspan = $this->_rspan ? $this->_rspan-1 : 0;
      if ( !$this->_rspan )
      {
        $this->_cspan = 0;
      }
      $this->_cellCnt = $this->_cspan;
    }
  }


  function endForm()
  { 
    if ( $this->buttons )
    {
      foreach ( $this->buttons as $one )
      {
        $this->tplEngine->assignBlockVars('FORM_FOOT.BUTTON', array('button'=>$one->toHtml()), 1);
      }
    }
    $this->tplEngine->parse('form');
    $this->html = $this->tplEngine->getVar('form');
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