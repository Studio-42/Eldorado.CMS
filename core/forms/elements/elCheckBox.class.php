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
                    'element' => "<input%s%s name=\"%s\" id=\"%s\" value=\"%s\" /><label for='%s'> %s</label><br />\n"
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

  var $events  = array( 'addToForm' => 'onAddToForm',
                        'submit'    => 'onSubmit',
                        'validate'  => 'selfValidate');

  var $_switchValue = null;

  function setSwitchValue($v)
  {
    $this->_switchValue = $v;
  }

	function toHtml()
	{
		$values = $this->getValue();  
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
				$res .= $this->opts[$v].'; ';
				// echo htmlspecialchars($res).'<br>';
			}
		}
		$html = '';
		
		$html = '<div class="multiselect">
			<a href="#" class="el-collapsed"></a> <span></span>
			<div class="rounded-5 multiselect-opts" style="display:none">';
		$i = 0;
		foreach ($this->opts as $val=>$label)
		{
			// echo "$val $label<br>";
			$checked = !empty($values[$val]) ? 'checked="on"' : '';
			$switch = !is_null($this->_switchValue) && $this->_switchValue == $val ? ' switch="on"' : '';
			$html .= '<label><input type="checkbox" name="'.$name.'['.($i++).']" value="'.$val.'" '.$checked.$switch.' />'.$label.'</label>';
		}
		
		$html .= '</div></div>';

		$js = '
		$(".multiselect-opts").find(":checkbox[switch]").change(function() {
			var c = $(this).parent().siblings("label").children(":checkbox");
			if ($(this).attr("checked")) {
				c.attr("disabled", "on");
			} else {
				c.removeAttr("disabled");
			}
		}).trigger("change");
		
		$(".multiselect").mouseleave(function() {
			if ($(this).children(".multiselect-opts").css("display") == "block") {
				$(this).children("a.el-collapsed").eq(0).trigger("click");
			}
		}).children("a.el-collapsed").click(function(e) {
			e.preventDefault;
			e.stopPropagation();
			if (!this.prepared) {
				this.prepared = true;
				$(this).parents().each(function() {
					if ($(this).css("overflow") == "hidden") {
						$(this).css("overflow", "visible");
					}
				});
			}
			$(this).toggleClass("el-expanded").siblings(".multiselect-opts").eq(0).slideToggle();
			return false;
		}).end().find(":checkbox").change(function(e) {
			var s = [];
			$(this).parents(".multiselect-opts").find(":checkbox[checked]").each(function() {
				!$(this).attr("disabled") && s.push($.trim($(this).parent().text().replace(/\+\s/g, "")))
			}).end().parent().children("span").text(s.length>0 ? s.slice(0, 3).join("; ") : "'.m('No selected values').'")
			if (s.length > 3) {
				$(this).parents(".multiselect").children("span").append( " '.m('and %d more').'".replace("%d", s.length-3) );
			}
		}).trigger("change");
		
		';
		elAddJs($js, EL_JS_SRC_ONREADY);
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
    $html .= '<a href="#" class="form-varlist-ctrl">+ '.m('Add field').' +</a>';

	$js = '$(".form-varlist-ctrl").click(function(e) {
		e.preventDefault();
		var p = $(this).prev(".formVLControl"),
			c = p.children(":last").clone(),
			l = p.children().length,
			n = p.attr("name")+"["+l+"]";
		
		c.children(":text").val("").attr("name", n+"[0]");
		c.children(":checkbox").attr("name", n+"[1]").removeAttr("checked");
		p.append(c);
		c.children(":text").focus();
	})';

    elAddJs($js, EL_JS_SRC_ONREADY);

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