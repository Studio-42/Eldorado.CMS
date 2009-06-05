<?php

class elEditor extends elFormInput
{
    var $isEditor = true;
  var $attrs      = array('width'=>'100%', 'height'=>EL_DEFAULT_EDITOR_HEIGHT);
  var $value      = '';
  //var $basePath   = EL_URL_EDITOR;
  var $toolbarSet = 'el';
  var $config     = array(
                        'AutoDetectLanguage' => false,
                        'DefaultLanguage'    => EL_LANG,
                        'LinkUpload'         => false,
                        'ImageUpload'        => false,
                        'FlashUpload'        => false,
                        ) ;

  function __construct( $name=null, $label=null, $value=null, $attr=null )
  {
    parent::__construct($name, $label, $value, $attr);
    $this->config['EditorAreaCSS']   = EL_BASE_URL.'/style/css/FCKeditor.css';
    $this->config['ImageBrowserURL'] = EL_BASE_URL.'/'.EL_URL_POPUP.'/__fm__/'.$GLOBALS['core']->pageID.'/';
    $this->config['LinkBrowserURL']  = EL_BASE_URL.'/'.EL_URL_POPUP.'/__fm__/'.$GLOBALS['core']->pageID.'/';
    $this->config['FlashBrowserURL'] = EL_BASE_URL.'/'.EL_URL_POPUP.'/__fm__/'.$GLOBALS['core']->pageID.'/';
    $this->config['SmileyPath']      = EL_BASE_URL.'/core/editor/editor/images/smiley/msn/';
    $this->config['CustomConfigurationsPath'] = EL_BASE_URL.'/core/editor/elconfig.js?t='.time();
    $this->config['SpellerPagesServerScript'] = EL_BASE_URL.'/core/editor/editor/dialog/fck_spellerpages/spellerpages/server-scripts/spellchecker.php?lang='.str_replace('.UTF-8', '',  EL_LOCALE);    
    
  }

  function elEditor($name=null, $label=null, $value=null, $attr=null)
  {
    $this->__construct($name, $label, $value, $attr);
  }

  function onAddToForm( &$form )
  {
    $form->registerInput($this);
    if (!empty($form->renderer) && file_exists(EL_DIR_STYLES.'forms/simple_form_editor.html') )
    {
    	$form->renderer->tplFile = 'simple_form_editor.html';
    }
  }

  function setValue($value)
  {
    $this->value = $value;
  }

  function getValue()
  {
    //$v = $this->value;
    return ('<br />' == $this->value || '&nbsp;' == $this->value) ? '' : preg_replace('=('.EL_BASE_URL.'/)=ism', '/', $this->value);
  }

  function toHtml()
  {
    $value = preg_replace('~(href|src)="(/[^\s"]*)"~ism', "\\1=\"".EL_BASE_URL."\\2\"", $this->value);
    $HtmlValue = htmlspecialchars( $value ) ; //echo htmlspecialchars($HtmlValue);
    $name = $this->getName();
    $Html = '<div>' ;

    if ( $this->IsCompatible() )
    {
      $file = ( isset( $_GET['fcksource'] ) && $_GET['fcksource'] == "true" )
        ? 'fckeditor.original.html'
        : 'fckeditor.html' ;
      $link = EL_BASE_URL.'/core/editor/editor/'.$file.'?InstanceName='.$this->getName() ;
      if ( !empty($this->toolbarSet) )
      {
        $link .= "&amp;Toolbar={$this->toolbarSet}" ;
      }
      // Render the linked hidden field.
      $Html .= '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$HtmlValue.'" style="display:none" />' ;

      // Render the configurations hidden field.
      $Html .= '<input type="hidden" id="'.$name.'___Config" value="'.$this->GetConfigFieldString().'" style="display:none" />' ;
			//$Html .= '<link rel="StyleSheet" href="{STYLE_URL}css/FCKeditor.css" type="text/css" />';
      // Render the editor IFRAME.
      $Html .= '<iframe id="'.$name.'___Frame" src="'.$link.'" width="'.$this->getAttr('width').'" height="'.$this->getAttr('height').'" frameborder="no" scrolling="no"></iframe>' ;

    }
    else
    {
      $widthCSS = $this->getAttr('width');
      $widthCSS .= ( strpos( $widthCSS, '%' ) === false ) ? 'px' : '';

      $heightCSS = $this->getAttr('height');
      $heightCSS .= ( strpos( $heightCSS, '%' ) === false ) ? 'px' : '';

      $Html .= '<textarea name="'.$name.'" rows="4" cols="40" style="width: {$WidthCSS}; height: {$HeightCSS}">'.$HtmlValue.'</textarea>' ;
    }
    $Html .= '</div>' ;

    return $Html ;
  }

  function IsCompatible()
  {
    if ( isset( $_SERVER ) ) {
		$sAgent = $_SERVER['HTTP_USER_AGENT'] ;
	}
	else {
		global $HTTP_SERVER_VARS ;
		if ( isset( $HTTP_SERVER_VARS ) ) {
			$sAgent = $HTTP_SERVER_VARS['HTTP_USER_AGENT'] ;
		}
		else {
			global $HTTP_USER_AGENT ;
			$sAgent = $HTTP_USER_AGENT ;
		}
	}

	if ( strpos($sAgent, 'MSIE') !== false && strpos($sAgent, 'mac') === false && strpos($sAgent, 'Opera') === false )
	{
		$iVersion = (float)substr($sAgent, strpos($sAgent, 'MSIE') + 5, 3) ;
		return ($iVersion >= 5.5) ;
	}
	else if ( strpos($sAgent, 'Gecko/') !== false )
	{
		$iVersion = (int)substr($sAgent, strpos($sAgent, 'Gecko/') + 6, 8) ;
		return ($iVersion >= 20030210) ;
	}
	else if ( strpos($sAgent, 'Opera/') !== false )
	{
		$fVersion = (float)substr($sAgent, strpos($sAgent, 'Opera/') + 6, 4) ;
		return ($fVersion >= 9.5) ;
	}
	else if ( preg_match( "|AppleWebKit/(\d+)|i", $sAgent, $matches ) )
	{
		$iVersion = $matches[1] ;
		return ( $matches[1] >= 522 ) ;
	}
	else
		return false ;
  }

  function GetConfigFieldString()
  {
    $sParams = '' ;
    $bFirst = true ;

    foreach ( $this->config as $sKey => $sValue )
    {
      if ( $bFirst == false )
        $sParams .= '&amp;' ;
      else
        $bFirst = false ;

      if ( $sValue === true )
        $sParams .= $this->EncodeConfig( $sKey ) . '=true' ;
      else if ( $sValue === false )
        $sParams .= $this->EncodeConfig( $sKey ) . '=false' ;
      else
        $sParams .= $this->EncodeConfig( $sKey ) . '=' . $this->EncodeConfig( $sValue ) ;
    }
    return $sParams ;
  }

  function EncodeConfig( $valueToEncode )
  {
    $chars = array(
                  '&' => '%26',
                  '=' => '%3D',
                  '"' => '%22' ) ;
    return strtr( $valueToEncode,  $chars ) ;
  }

}

?>