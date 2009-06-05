<?php

class elFileInput extends elFormInput
{
  var $type    = 'file';
  var $value   = array();
  var $maxSize = 0;
  var $fileExt = array();
  var $events  = array( 'addToForm' => 'onAddToForm', 
                        'submit'    => 'onSubmit', 
                        'validate'  => 'selfValidate');

  function onAddToForm( &$form )
  {
    $form->registerInput($this);
    $form->setAttr('method',  'POST');
    $form->setAttr('enctype', 'multipart/form-data');
  }

  function setMaxSize($maxSize)
  {
    $this->maxSize = $maxSize;
  }

  function setFileExt( $ext )
  {
    if ( is_array($ext) )
    {
      $this->fileExt = $ext;
    }
    else
    {
      $this->fileExt[] = $ext;
    }
  }

  function isUploaded()
  {
    if (isset($this->value['tmp_name']) && is_uploaded_file($this->value['tmp_name']) )
    {
      return true;
    }
    return false;
  }

  function moveUploaded($name=null, $dir='./', $perm=0666)
  {
    if ( !$this->isUploaded() )
    {
      return false;
    }
    $name = $name ? $name : $this->value['name'];
    if ($dir)
    {
      $name = ('/' != substr($dir, -1) ? $dir.'/' : $dir) . $name;
    }
    
    if ( !move_uploaded_file($this->value['tmp_name'], $name) )
    {
      return false;
    }
    $save = umask(0);
    chmod($name, $perm);
    umask($save);

    return $name;
  }

  function setValue( $val ) {}

  function getValue()
  {
    return $this->value;
  }

  function getFileName()
  {
    return isset($this->value['name']) ? $this->value['name'] : null;
  }

  function getExt()
  {
  	if ( $this->isUploaded() && false !== ($p = strrpos($this->value['name'], '.')))
  	{
  		return strtolower(substr($this->value['name'], $p+1));
  	}
  	return null;
  }
  
  function onSubmit(&$obj)
  {
    $name = $this->getName(); 
    if ( false === ($pos = strpos($name, '[')) )
    {
      $val = isset( $_FILES[$name] ) ? $_FILES[$name] : array(); 
    }
    else
    {
      //echo 'elFileInput class. Whats happened here?';
    }
    $this->value = isset($val['error']) && 0 == $val['error'] ? $val : null;
  }

  function selfValidate( &$errors, $args)
  { 
     
    $maxSize  = !empty($this->maxSize) ? $this->maxSize : ini_get('upload_max_filesize');
    $maxSize  = $maxSize*1024*1024;
    $required = $args[0]; 
    $value    = $this->getValue();
    
    if ( !$required && !$this->isUploaded() )
    {
      return true;
    }

    if ( $required && !$this->isUploaded() )
    {
      $errors[$this->getID()] = m('File must be uploaded');
    }
    elseif ( 3 == $value['error'] )
    {
      $errors[$this->getID()] = m('File uploaded partialy');
    }
    elseif ( $value['size'] > $maxSize ) //$value['error'] > 0 && $value['size'] > $maxSize )
    {
      $errors[$this->getID()] = sprintf(m('Maximum file size %s Kb'), ceil($maxSize/1024));
    }
    elseif ( $this->fileExt )
    {
      $filename = $this->value['name']; 
      $exts     = array_map('preg_quote', $this->fileExt);
      $regex    = '/('.implode('|', $exts).')$/i' ; 
      if ( !preg_match($regex, $filename) )
      {
      	$errors[$this->getID()] = sprintf( m('File must have one of the following extensions: %s'), 
      																		 implode(', ', $this->fileExt));
      }
    }
    return true;
  }

  function toHtml()
  {
    $html = '<input'.$this->attrsToString().' /> <br />';
    $html .= !empty($this->maxSize)
      ? sprintf( m('File size must be not greater then %s'), $this->maxSize.'Mb')
      : sprintf( m('File size must be not greater then %s'), ini_get('upload_max_filesize'));
    if (!empty($this->fileExt))
    {
       $html .= '<br />'.sprintf(m('Allowed file extensions is: %s'), implode(', ', $this->fileExt));
    }
    return $html;
  }
}

class elImageUploader extends elFileInput
{
  var $imgSrc       = '';
  var $fileExt      = array('gif', 'jpg', 'jpeg', 'png');
  var $_replaceMode = false;
  var $_imgTypes    = array( 
		  											IMAGETYPE_GIF  => 'gif',
														IMAGETYPE_JPEG => 'jpg',
														IMAGETYPE_PNG  => 'png'
														);

  function __construct($name=null, $label=null, $imgSrc=null, $attrs=null)
  {
    parent::__construct($name, $label, $attrs);
    $this->imgSrc = $imgSrc;
  }
  
  function elImageUploader($name=null, $label=null, $imgSrc=null, $attrs=null)
  {
    $this->__construct($name, $label, $imgSrc, $attrs);
  }

  function getExt()
  {
  	if ($this->isUploaded() && false != ($s = getimagesize($this->value['tmp_name'])) && !empty($this->_imgTypes[$s[2]]) )
  	{
  		return $this->_imgTypes[$s[2]];
  	}
  	return parent::getExt();
  }
  
  function setReplaceMode($flag)
  {
  	$this->_replaceMode = (bool)$flag;
  }
  
  function isMarkedToDelete()
  {
    return isset($_GET['del_'.$this->getName()]) || isset($_POST['del_'.$this->getName()]);
  }

  function selfValidate ( &$errors, $args)
  {
  	parent::selfValidate( $errors, $args);
  	if (!empty($errors) || !$this->isUploaded())
  	{
  		return true;
  	}
  	if ( false == ($s = getimagesize($this->value['tmp_name'])) || empty($this->_imgTypes[$s[2]]) )
  	{
  		$errors[$this->getID()] = sprintf( m('File "%s" is not an image or has unsupported type'), $this->value['name'] );
  	}
  	return true;
  }
  
  function toHtml()
  {
    $html  = '<fieldset><legend>'.m('Upload image')."</legend>\n";
    $html .= parent::toHtml();
    $html .= '</fieldset><br />';

    if ( $this->imgSrc )
    {
      if (!$this->_replaceMode)
      {
      	$html .= '<fieldset><legend>'.m('Delete image').'</legend>';
      	$html .= '<input type="checkbox" name="del_'.$this->getName().'" value="1" /> ';
      	$html .= m('Delete image');
      	$html .= "\n";
      }
      $html .= "</fieldset>\n";
      $html .= '<img src="'.$this->imgSrc.'" />';
    }
    return $html;
  }
}


?>