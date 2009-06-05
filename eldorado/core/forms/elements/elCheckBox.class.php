<?php

class elCheckbox extends elFormInput
{
  var $type = 'checkbox';

  function __construct($name=null, $label=null, $value = null, $attr=null, $frozen=false)
  {
    parent::__construct($name, $label, $value, $attr, $frozen);
    if ( !$this->attrs['value'] )
    {
      $this->setAttr('value', 1);
    }
  }

  function elCheckBox($name=null, $label=null, $value = null, $attr=null, $frozen=false)
  {
    $this->__construct($name, $label, $value, $attr, $frozen);
  }

  function onSubmit($source)
  {
    $name = $this->getName();
    if ( false === ($pos = strpos($name, '[')) )
    {
      $val = isset($source[$name]) ? trim($source[$name]) : null;
    }
    else
    {
      $n1 = substr($name, 0, $pos);
      $n2 = substr($name, $pos);
      $str = '$val = isset($source[\''.$n1.'\']'.$n2.') ? trim($source[\''. $n1. '\']'. $n2 .') : null;';
      eval($str);
    }
    if ( null != $val )
    {
      $this->setAttr('checked', 'true');
    }
    else
    {
      $this->dropAttr('checked');
    }
  }

  function getValue()
  {
    return ($this->getAttr('checked')) ? $this->getAttr('value') : null;
  }
}


class elCheckBoxesGroup extends elFormInput
{
  var $type  = 'checkbox';
  var $value = array();
  var $opts  = array();
  var $tpl   = array(
                    'header'  => '',
                    'footer'  => '',
                    'element' => "<input%s%s name=\"%s\" id=\"%s\" value=\"%s\" /><label for='%s'>%s</label><br />\n"
                    );

  function __construct($name=null, $label=null, $value=null, $opts=null, $attrs=null, $frozen=false, $use_keys=true)
  {
    parent::__construct($name.'[]', $label, $value, $attrs, $frozen);
    $this->add($opts, $use_keys);
  }

  function elCheckBoxesGroup($name=null, $label=null, $value=null, $opts=null, $attrs=null, $frozen=false, $use_keys=true)
  {
    $this->__construct($name, $label, $value, $opts, $attrs, $frozen, $use_keys);
  }

  function getValue()
  {
    return $this->value;
  }

  function setValue($val)
  {$this->value = array();
    if ( is_array($val) )
    {
      foreach ($val as $v)
      {
        $v = (string)$v;
        $this->value[$v] = $v;
      }
    }
    elseif ( is_null($val) )
    {
      $this->value = array();
    }
    else
    {
      $this->value = array( (string)$val => (string)$val );
    }
  }

  function add( $opts, $use_keys=true )
  {
    if ( is_array($opts) )
    {
      foreach ( $opts as $key=>$val )
        {
          $this->opts[$use_keys ? $key : ''.$val] = $val;
        }
    }
  }

  function toHtml()
  {
    $html  = sprintf($this->tpl['header'], str_replace('[]', '', $this->getName()), $this->label);
    $attrs = $this->attrsToString();
    $name  = substr($this->getName(), 0, -2);
    $i = 0;
//elPrintR($this->opts);
    foreach ( $this->opts as $val=>$label )
    {
    	$id = $name.'['.$i.']';
      $checked = isset($this->value[$val]) ? ' checked="on"' : '';
      $html .= sprintf($this->tpl['element'], $checked, $attrs, $id, $id, $val, $id, $label );
      $i++;
    }
    return $html . $this->tpl['footer'];
  }

  function attrsToString()
  {
    $str = '';
    foreach ($this->attrs as $k=>$v)
    {
    	if ('name'<>$k && 'ID'<>$k)
    	{
      	$str .= ' '.$k .'="'.htmlspecialchars($v).'"';
    	}
    }
    return $str;
  }
}


class elMultiSelectList extends elCheckBoxesGroup
{
	var $tpl   = array(
	'header'  => '<div id="msl_%s">',
	'footer'  => '</div>',
	'element' => "<input%s%s name=\"%s\" id=\"%s\" value=\"%s\" /><label for='%s'>%s</label><br />\n",
	'hidden'  => '<input type="hidden" name="%s" id="%s" value="%s" />',
	'switch'  => '<div style="border:1px solid gray;width:14px;font-size:12px;font-weight:bold;color:gray;cursor:default"
	 							align="center" onClick="return mslControl(\'%s\', %d);" title="%s">%s</div>',
  'res' => '<table width="100%%" border="0" id="%sResultView" style="border:none">
            <tr><td width="14" valign="top" style="border:none">%s</td>
            <td style="border:none" id="%sResult">%s</td></tr></table>',
  'sel' => '<table width="100%%" border="0" id="%sSelect" style="display:none">
            <tr><td width="14" valign="top" style="border:none">%s</td>
            <td style="border:none">%s</td></tr></table>',
  'val' => 'div'
           	);
  var $events  = array( 'addToForm' => 'onAddToForm',
                        'submit'    => 'onSubmit',
                        'validate'  => 'selfValidate');

  var $_switchValue = null;

  function elMultiSelectList($name=null, $label=null, $value=null, $opts=null, $attrs=null, $frozen=false, $use_keys=true, $vTpl='div')
  {
    parent::__construct($name, $label, $value, $opts, $attrs, $frozen, $use_keys);
    $this->tpl['val'] = $vTpl;
  }

  function setSwitchValue($v)
  {
    $this->_switchValue = $v;
  }

	function onAddToForm( &$form )
	{
		$form->registerInput($this);
		$js = 'function mslControl(id, size) {
			r = document.getElementById(id+"ResultView");
			s = document.getElementById(id+"Select");

			if (r.style.display == "")
			{
				r.style.display = "none";
				s.style.display = "";
			}
			else
			{
				r.style.display = "";
				s.style.display = "none";

				res = document.getElementById(id+"Result");

				len = res.childNodes.length;
				for (i=0; i<len;i++)
				{
					res.removeChild( res.firstChild );
				}

				for (i=0;i<size;i++)
				{
					e = document.getElementById(id+"["+i+"]");
					if (e.checked)
					{
						h = document.getElementById("hid_"+id+"["+i+"]");
						n = document.createElement("'.$this->tpl['val'].'");
						n.appendChild( document.createTextNode(h.value+"; ") );
						res.appendChild(n);
					}
				}

				if (!res.firstChild)
				{
					n = document.createElement("div");
					n.appendChild( document.createTextNode("'.m('No selected values').'" ) );
					res.appendChild(n);
				}
			}

			return false;} ';

		if (!is_null($this->_switchValue))
		{
		  $js .= "\n";
		  $js .= 'function mslCheckValue(checkbox)
		  {
        switchValue = "'.$this->_switchValue.'";
        s = document.getElementById("msl_'.str_replace('[]', '', $this->getName()).'");
        len = s.childNodes.length; //alert(len);

		    if (checkbox.value == switchValue )
		    {
		      if (checkbox.checked)
		      {
            for (i=0;i<len;i++)
            {
              if (s.childNodes[i].tagName == "INPUT" && s.childNodes[i].getAttribute("type") == "checkbox"
                  && s.childNodes[i].value!=checkbox.value )
              {
                s.childNodes[i].checked = true;
              }
            }
          }
		    }
		    else
		    {
          for (i=0;i<len;i++)
          {
            if (s.childNodes[i].tagName == "INPUT" && s.childNodes[i].getAttribute("type") == "checkbox"
              && s.childNodes[i].value==switchValue )
            {
              s.childNodes[i].checked = false;
              return;
            }
          }
		    }
		  }';
		}

		$form->addJsSrc($js);
	}

	function toHtml()
	{
		$values = $this->getValue();  //echo 'OPTS<br>'; elPrintR($this->opts); echo 'VALS<br>'; elPrintR($this->value);
		$name   = substr($this->getName(), 0, -2);
		$size   = sizeof($this->opts);
		$res    = '';
		if ( empty($values) )
		{
			$res = '<div>'.m('No selected values').'</div>';
		}
		else
		{
			foreach ($values as $v )
			{
				$res .= '<'.$this->tpl['val'].'>'.$this->opts[$v].'; </'.$this->tpl['val'].'>';
			}
		}
    if (!is_null($this->_switchValue))
    {
      $this->setAttr('onClick', 'mslCheckValue(this)');
    }
		$sw1 = 	sprintf($this->tpl['switch'], $name, $size, m('Open list of values'), '+');
		$sw2 = 	sprintf($this->tpl['switch'], $name, $size, m('Close list of values'), '&ndash;');

		$html  = sprintf($this->tpl['res'], $name, $sw1, $name, $res);
		$html .= sprintf($this->tpl['sel'], $name, $sw2, parent::toHtml());

		$i = 0;
    foreach ( $this->opts as $val=>$label )
    {
    	$id = 'hid_'.$name.'['.$i.']';
      $html .= sprintf($this->tpl['hidden'], $id, $id, $label );
      $i++;
    }

		return $html;
	}

	function selfValidate(&$errors, $args)
	{
		if ( !empty($args[0]) && !$this->getValue() )
		{
			$errors[$this->getID()] = $args[2];
		}
		return true;
	}
}


class elVariantsList extends elFormInput
{
  var $value = array(  );
  var $js = "
    function elVLControl(name, isCheckbox)
    {
      var ID   = name+'_body';
      div      = document.getElementById(ID);
      var d = new Date;
      var inputID = d.getTime();

      t  = document.createElement('input');
      t.setAttribute('type', 'text');
      t.setAttribute('name', name+'['+inputID+'][0]');
      t.setAttribute('value', '');

      c  = document.createElement( 'input');
      c.setAttribute('type', 'checkbox');
      c.setAttribute('name', name+'['+inputID+'][1]');
      c.setAttribute('value', 1);

      nd = document.createElement('div');
      nd.appendChild( t );
      nd.appendChild( document.createTextNode(' ') );
      nd.appendChild( c );
      div.appendChild(nd);
      return false;
    }";

  function elVariantsList($name=null, $label=null, $value=null, $attrs=null, $frozen=false)
  {
    parent::__construct($name, $label, $value, $attrs, $frozen);
  }

  function setValue($value)
  {
    $this->value = array();
    if (is_array($value))
    {
      //$this->value = $value;
      foreach ($value as $ID=>$v)
      {
        if (isset($v[0]) && '' != $v[0])
        {
          $this->value[$ID] = array( $v[0], intval(!empty($v[1])) );
        }
      }
    }

  }

  function getValue()
  {
    //return $this->value;
    $ret = array();
    foreach ($this->value as $ID=>$v)
    {
      if (isset($v[0]) && '' != $v[0])
      {
        $ret[$ID] = array( $v[0], intval(!empty($v[1])) );
      }
    }
    return $ret;
  }

  function toHtml()
  {
    if (empty($this->value))
    {
      $this->value = array( array('', 0), array('', 0), array('', 0));
    }

    $name  = $this->getName();
    $html  = '<div id="'.$name.'_body" '.$this->attrsToString().' class="formVLControl">';
    foreach ($this->value as $ID=>$v)
    {
      $html .= '<div>';
      $html .= '<input type="text" name="'.$name.'['.$ID.'][0]'.'" value="'.htmlspecialchars($v[0]).'" /> ';
      $html .= '<input type="checkbox" name="'.$name.'['.$ID.'][1]" value="1" '.($v[1] ? 'checked="on"' : '').' />';
      $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<a href="" onClick="return elVLControl(\''.$name.'\', 0);">+ '.m('Add field').' +</a>';

    elAddJs($this->js, EL_JS_CSS_SRC);

    return $html;
  }

}


class elMultiVariantsList extends elVariantsList
{
  var $value = array( array(), array() );

  function setValue($value)
  {
    $this->value = array( array(), array() ); //echo $this->getName(); elPrintR($value);
    if ( !empty($value[0]) && is_array($value[0]) )
    {
      for ($i=0, $s=sizeof($value[0]); $i<$s; $i++ )
      {
        $v = isset($value[0][$i]) ? trim($value[0][$i]) : '';
        if ( 0 < strlen($v) )
        {
          $this->value[0][] = $v;
          if ( !empty($value[1]) && is_array($value[1]) && in_array($i, $value[1]))
          {
            $this->value[1][] = $i;
          }
        }
      }
    }
  }



  function toHtml()
  {
    if ( empty($this->value[0]) )
    {
      $this->value = array( array('', '', ''), array() );
    }
    $sel = array_flip($this->value[1]);

    $name  = $this->getName();
    $html  = '<div id="'.$name.'_body" '.$this->attrsToString().' class="formVLControl">';
    for ($i=0, $s=sizeof($this->value[0]); $i<$s; $i++)
    {
      $html .= '<div>';
      $html .= '<input type="text" name="'.$name.'[0]['.$i.']'.'" value="'.htmlspecialchars($this->value[0][$i]).'" /> ';
      $html .= '<input type="checkbox" name="'.$name.'[1]['.$i.']" value="'.$i.'" '.(isset($sel[$i]) ? 'checked="on"' : '').' /> ';
      $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<a href="" onClick="return elVLControl(\''.$name.'\', 1);">+ '.m('Add field').' +</a>';

    elAddJs($this->js, EL_JS_CSS_SRC);

    return $html;
  }


}



//
//
//class elVariantsList extends elFormInput
//{
//  var $value = array( array(), null );
//  var $js = "
//    function elVLControl(name, isCheckbox)
//    {
//      var ID   = name+'_body';
//      div      = document.getElementById(ID);
//      var size = div.childNodes.length;
//
//      nd = document.createElement('div');
//      t  = document.createElement('input');
//      t.setAttribute('type', 'text');
//      t.setAttribute('name', name+'[0]['+size+']');
//      t.setAttribute('value', '');
//      c  = document.createElement( 'input');
//      if ( isCheckbox )
//      {
//        c.setAttribute('type', 'checkbox');
//        c.setAttribute('name', name+'[1]['+size+']');
//        c.setAttribute('value', 1);
//      }
//      else
//      {
//        c.setAttribute('type', 'radio');
//        c.setAttribute('name', name+'[1]');
//        c.setAttribute('value', size);
//      }
//      nd.appendChild( t );
//      nd.appendChild( document.createTextNode(' ') );
//      nd.appendChild( c );
//      div.appendChild(nd);
//      return false;
//    }";
//
//  function elVariantsList($name=null, $label=null, $value=null, $attrs=null, $frozen=false)
//  {
//    parent::__construct($name, $label, $value, $attrs, $frozen);
//  }
//
//  function setValue($value)
//  {
//    $this->value = array(array(), null); //elPrintR($value);
//    if ( empty($value[0]) || !is_array($value[0]) )
//    {
//      return;
//    }
//    $sel = isset($value[1])
//      ? (!is_array($value[1])) ? array_flip( array($value[1]) ) : array_flip( $value[1] )
//      : array();
////echo 'sel='; elPrintR($sel);
//    for ($i=0, $s=sizeof($value[0]); $i<$s; $i++ )
//    {
//      $v = isset($value[0][$i]) ? trim($value[0][$i]) : '';
//      if ( 0 < strlen($v))
//      {
//        $this->value[0][] = $v;
//        if ( isset($sel[$i]) )
//        {
//          $this->value[1] = sizeof($this->value[0])-1;
//        }
//      }
//    }
//  }
//
//  function getValue()
//  {
//    return $this->value;
//
//  }
//
//  function toHtml()
//  {
//    if (empty($this->value[0]))
//    {
//      $this->value = array( array('', '', ''), null);
//    }
//
//    $name  = $this->getName();
//    $html  = '<div id="'.$name.'_body" '.$this->attrsToString().' class="formVLControl">';
//    for ($i=0, $s=sizeof($this->value[0]); $i<$s; $i++)
//    {
//      $html .= '<div>';
//      $html .= '<input type="text" name="'.$name.'[0]['.$i.']'.'" value="'.htmlspecialchars($this->value[0][$i]).'" /> ';
//      $html .= '<input type="radio" name="'.$name.'[1]" value="'.$i.'" '.($i===$this->value[1] ? 'checked="on"' : '').' />';
//      $html .= '</div>';
//    }
//    $html .= '</div>';
//    $html .= '<a href="" onClick="return elVLControl(\''.$name.'\', 0);">+ '.m('Add field').' +</a>';
//
//    elAddJs($this->js, EL_JS_CSS_SRC);
//
//    return $html;
//  }
//
//}
//
//
//class elMultiVariantsList extends elVariantsList
//{
//  var $value = array( array(), array() );
//
//  function setValue($value)
//  {
//    $this->value = array( array(), array() ); //echo $this->getName(); elPrintR($value);
//    if ( !empty($value[0]) && is_array($value[0]) )
//    {
//      for ($i=0, $s=sizeof($value[0]); $i<$s; $i++ )
//      {
//        $v = isset($value[0][$i]) ? trim($value[0][$i]) : '';
//        if ( 0 < strlen($v) )
//        {
//          $this->value[0][] = $v;
//          if ( !empty($value[1]) && is_array($value[1]) && in_array($i, $value[1]))
//          {
//            $this->value[1][] = $i;
//          }
//        }
//      }
//    }
//  }
//
//
//
//  function toHtml()
//  {
//    if ( empty($this->value[0]) )
//    {
//      $this->value = array( array('', '', ''), array() );
//    }
//    $sel = array_flip($this->value[1]);
//
//    $name  = $this->getName();
//    $html  = '<div id="'.$name.'_body" '.$this->attrsToString().' class="formVLControl">';
//    for ($i=0, $s=sizeof($this->value[0]); $i<$s; $i++)
//    {
//      $html .= '<div>';
//      $html .= '<input type="text" name="'.$name.'[0]['.$i.']'.'" value="'.htmlspecialchars($this->value[0][$i]).'" /> ';
//      $html .= '<input type="checkbox" name="'.$name.'[1]['.$i.']" value="'.$i.'" '.(isset($sel[$i]) ? 'checked="on"' : '').' /> ';
//      $html .= '</div>';
//    }
//    $html .= '</div>';
//    $html .= '<a href="" onClick="return elVLControl(\''.$name.'\', 1);">+ '.m('Add field').' +</a>';
//
//    elAddJs($this->js, EL_JS_CSS_SRC);
//
//    return $html;
//  }
//
//
//}




?>