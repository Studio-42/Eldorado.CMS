<?php

class elSelect extends elFormInput
{
  var $opts = array();
  var $value;
  var $_defSize = 10;

  function __construct($name=null, $label=null, $value=null, $opts=null, $attrs=null, $frozen=false, $use_keys=true)
  {
    parent::__construct($name, $label, $value, $attrs, $frozen);
    $this->add($opts, $use_keys);
  }

  function elSelect($name=null, $label=null, $value=null, $opts=null, $attrs=null, $frozen=false, $use_keys=true)
  {
    $this->__construct($name, $label, $value, $opts, $attrs, $frozen, $use_keys);
  }

  function getValue()
  {
    return $this->value;
  }

  function setValue($val)
  {
    $this->value = $val;
    if ( is_array($val) )
    {
      $this->setAttr('multiple', 'on');
    }
  }

  function setAttr($attr, $val)
  {
    parent::setAttr($attr, $val);
    if ('multiple' == $attr)
    {
      $name = $this->getName();
      if ( false == strpos($name, '[') )
	    {
	      $this->setName($name.'[]');
	    }
      if ( $this->getAttr('size') <= 1 )
	    {
	      $this->setAttr('size', $this->_defSize);
	    }
    }
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

  function toHtml()
  {
  	if ('on' == $this->getAttr('multiple'))
  	{
  		if ( sizeof($this->opts) < $this->_defSize )
  		{
  			$this->setAttr('size', sizeof($this->opts));
  		}
  	}
    $html = '<select'.$this->attrsToString().">\n";
    foreach ( $this->opts as $k=>$v )
    {
      $sel = $this->_isSelected($k) ? ' selected="on"' : '';
      $html .= '<option value="'.$k.'"'.$sel.'>'.$v."</option>\n";
    }
    $html .= "</select>\n";
    return $html;
  }

  function _isSelected($opt)
  {
    return !is_array($this->value) ? $opt ==$this->value : in_array($opt, $this->value);
  }

}



class elExtSelect extends elSelect
{
  var $groups      = array();
  var $_curGroupID = 0;

  function getCurrentGroupID()
  {
    return $this->_curGroupID;
  }

  function setCurrentGroupID( $ID )
  {
    $this->_curGroupID = isset($this->groups[$ID]) ? $ID : 0;
  }

  function setCurrentGroupLabel( $label )
  {
    $this->groups[$this->_curGroupID]['label'] = $label;
  }

  function addGroup( $label, $opts=null, $useKeys=true )
  {
    $this->groups[] = array('label'=>$label, 'opts'=>array());
    $this->_curGroupID = sizeof($this->groups)-1;
    if ( !empty($opts) )
    {
      $this->add($opts, $this->_curGroupID, $useKeys);
    }
    return $this->_curGroupID;
  }

  function add($opts, $groupID=null, $useKeys=true )
  {
    if ( is_array($opts) )
    {
      if ( $groupID )
      {
        $this->setCurrentGroupID($groupID);
      }
      foreach ( $opts as $key=>$val )
      {
        $this->groups[$this->_curGroupID]['opts'][$useKeys ? $key : $val] = $val;
      }
    }
  }

  function toHtml()
  {
    $html = '<select'.$this->attrsToString().">\n";
    for ($i=0; $i < sizeof($this->groups); $i++ )
    {
      $html .= '<optgroup label="'.$this->groups[$i]['label']."\">\n";
      foreach ( $this->groups[$i]['opts'] as $k=>$v )
      {
        $sel = $this->_isSelected($k) ? ' selected="on"' : '';
        $html .= '<option value="'.$k.'"'.$sel.'>'.$v."</option>\n";
      }
      $html .= "</optgroup>\n";
    }
    $html .= "</select>\n";
    return $html;
  }

}

?>