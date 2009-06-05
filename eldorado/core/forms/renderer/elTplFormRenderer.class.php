<?php

if ( !defined('EL_DIR_FORMS_TPL') )
{
  define ('EL_DIR_FORMS_TPL', 'forms/');
}

class elTplFormRenderer extends elFormRenderer
{
  var $tplEngine = null;
  var $tplDir    = EL_DIR_FORMS_TPL;
  var $tplFile   = 'simple_form.html';
  var $errInline = true;
  var $errors    = array();
  var $submit    = 'Submit';
  var $reset     = 'Drop';
	var $buttons = array();


  function __construct( $tplDir=EL_DIR_FORMS_TPL, $tplFile=null)
  {
    $this->tplEngine = & elSingleton::getObj('elTE');
    if ( $tplFile )
    {
      $this->setTpl( $tplFile );
    }
    $this->setButtonsNames( m('Submit'), m('Drop') );
  }

  function elTplFormRenderer( $tplDir=EL_DIR_FORMS_TPL, $tplFile=null)
  {
    $this->__construct($tplDir, $tplFile);
  }

  function setTpl( $fileName )
  {
    $this->tpl = $fileName;
  }

  function setButtonsNames( $submit, $reset )
  {
    $this->submit = $submit;
    $this->reset = $reset;
  }

	function addButton(&$button)
	{
		$this->buttons[] = &$button;
	}

  function beginForm($attrs, $label, $errors, $jsScripts, $jsBaseURL)
  {
    $this->tplEngine->setFile('form', $this->tplDir.$this->tplFile);
    $this->tplEngine->assignVars('form_attrs', $attrs); 
    if ( $label )
    {
      $this->tplEngine->assignBlockVars('FORM_HEAD', array('form_label'=>$label));
    }

    if ( $errors )
    { 
      if ( $this->errInline )
	    {
	      $this->errors = $errors;
	      $err = '';
	    }
      else
	    {
	      $err = implode('<br />', $errors);
	    }
      $this->tplEngine->assignBlockVars('FORM_HEAD.ERRORS', array('errors'=>$err), 1);
    }
    if ( !empty($jsScripts) )
    {
      $jsOnLoad = '';
      foreach ($jsScripts as $js)
	    {
	      if ( EL_JS_SRC_ONLOAD == $js['t'] )
        {
          $jsOnLoad .= $js['src']."\n";
        }
	      elseif ( EL_JS_CSS_FILE == $js['t'] )
        {
          $src= "<script type=\"text/javascript\" src=\"".$jsBaseURL.$js['src']."\" />\n";
          $this->tplEngine->assignVars( 'hiddens', $src, true );
        }
	      else
        {
          $src = "<script type=\"text/javascript\">\n".$js['src']."</script>\n";
          $this->tplEngine->assignVars( 'hiddens', $src, true );
        }
	    }
      if ( $jsOnLoad )
	    {
	      $this->tplEngine->assignVars('formOnLoad', "<script type=\"text/javascript\">\n".$jsOnLoad."</script>\n");
	    }
    }
  }

  function renderHidden( &$el )
  {
    $this->tplEngine->assignVars('hiddens', $el->toHtml(), true);
  }

  function _renderParams( $params )
  {
    $rAttrs = array(
                  'rowAttrs'       => !empty($params['rowAttrs'])  ? ' '.$params['rowAttrs']  : '', 
                  'cellAttrs'      => !empty($params['cellAttrs']) ? ' '.$params['cellAttrs'] : '',  
                  'cellLabelAttrs' => !empty($params['cellLabelAttrs']) ? ' '.$params['cellLabelAttrs'] : '',  
                  'cellElAttrs'    => !empty($params['cellElAttrs']) ? ' '.$params['cellElAttrs'] : '',  
                  'elAttrs'        => !empty($params['elAttrs'])   ? ' '.$params['elAttrs']   : '' 
                  );
    return $rAttrs;
  }

  function renderCdata( &$el, $renderParams=null )
  {
  	if ( !$renderParams )
    {
      $renderParams = array();
    }
    $ID = $el->getAttr('ID');
    if ( !isset($renderParams['rowAttrs']))
    {
      $renderParams['rowAttrs'] = '';
    }
    if ( !isset($renderParams['cellAttrs']))
    {
      $renderParams['cellAttrs'] = '';
    }
    $renderParams['rowAttrs']  .= ' ID="row_'.$ID.'"';
    $renderParams['cellAttrs'] .= ' ID="cell_'.$ID.'"'; //elPrintR($renderParams);
    $this->tplEngine->assignBlockVars('FORM_BODY.CDATA', 
          array('cdata'=>$el->toHtml())+$renderParams, 1);
  }

  function renderElement( &$el, $required, $renderParams=null )
  {
    $label = $el->getLabel();

    if ( !is_array($renderParams) )
    {
      $renderParams = array();
    }
    $ID = $el->getAttr('ID');
    if ( !isset($renderParams['rowAttrs']))
    {
      $renderParams['rowAttrs'] = '';
    }
    if ( !isset($renderParams['cellLabelAttrs']))
    {
      $renderParams['cellLabelAttrs'] = '';
    }
    if ( !isset($renderParams['cellElAttrs']))
    {
      $renderParams['cellElAttrs'] = '';
    }
	
    $renderParams['rowAttrs']       .= ' ID="row_'.$ID.'"';
    $renderParams['cellLabelAttrs'] .= ' ID="cell_l_'.$ID.'"';
    $renderParams['cellElAttrs']    .= ' ID="cell_el_'.$ID.'"';
    
    if ( !$label )
    {
      $this->tplEngine->assignBlockVars('FORM_BODY.CDATA', array('cdata'=>$el->toHtml()) );	  
      $this->tplEngine->assignBlockVars('FORM_BODY.CDATA', $this->_renderParams($renderParams), 2);
    }
    else
    {
    	if ( empty($el->isEditor) && !isset($renderParams['noLabelCell']) )
    	{
    		$block    = 'FORM_BODY.ELEMENT';
    		$rqBlock  = 'FORM_BODY.ELEMENT.RQ';
    		$errBlock = 'FORM_BODY.ELEMENT.ERROR';
    	}
    	else
    	{
    		$block    = 'FORM_BODY.EDITOR';
    		$rqBlock  = 'FORM_BODY.EDITOR.ED_RQ';
    		$errBlock = 'FORM_BODY.EDITOR.ED_ERROR';
    	}
      if ( isset($renderParams['nolabel']) )
	    {
	      $label = '';
			$required = '';
	    }
	    $this->tplEngine->assignBlockVars($block, array('label'=>$label, 'el'=>$el->toHtml()));
       	$this->tplEngine->assignBlockVars($block, $this->_renderParams($renderParams), 2);

	    if ( $required )
	    {
	      $this->tplEngine->assignBlockVars($rqBlock, null, 2);
	    }
    }
    $id = $el->getID();
    if ( $this->errInline && isset($this->errors[$id]) && null != ($err = $this->errors[$id]) )
    {
    	$this->tplEngine->assignBlockVars($errBlock, array('error'=>$err), 2);
    }
  }

  function endForm()
  {
    if ( $this->submit )
    {
      	$this->tplEngine->assignBlockVars('FORM_FOOT.SUBMIT', array('submit'=>$this->submit), 1);
		if ( $this->reset )
	    {
	      $this->tplEngine->assignBlockVars('FORM_FOOT.RESET', array('reset'=>$this->reset), 1);
	    }
    }
	elseif ( $this->buttons )
	{
		foreach ( $this->buttons as $b )
		{
			$this->tplEngine->assignBlockVars('FORM_FOOT.BUTTON', array('button'=>$b->toHtml()), 1);
		}
		
	}
    
    $this->tplEngine->parse('form');
    $this->html = $this->tplEngine->getVar('form');
    $this->_complite = true;
  }
}

?>