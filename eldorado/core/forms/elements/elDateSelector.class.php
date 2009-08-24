<?php

class elDateSelector extends elFormContainer
{
 var $events = array( 'addToForm' => 'onAddToForm', 
                      'validate'  => 'selfValidate', 
                      'submit'    => 'onSubmit');
  var $value    = array();
  var $validate = true;
  var $tpl      = array(
                      'header'  => '', 
                      'label'   => '', 
                      'element' => '%s<br />', 
                      'footer'  => ''
                      );
	var $_dateTimeFormat = false;

  function __construct($name=null, $label=null, $value=null, $attrs=null, $offsetLeft=1, $offsetRight=2, $dtFormat=false)
  {
    parent::__construct($name, $label, $attrs);
    $this->_dateTimeFormat = (bool)$dtFormat;
    $this->setValue( is_numeric($value) ? $value : time() );

    $d = range(0, 31); unset($d[0]); $d = array_map('elFormatDateTimeArrays', $d);
    $m = range(0, 12); unset($m[0]); $m = array_map('elFormatDateTimeArrays', $m);
    
    $y = array();
    $curYear = date('Y');
    for ( $i=$curYear-$offsetLeft, $max=$curYear+$offsetRight; $i <= $max; $i++ )
    {
      $y[$i] = $i;
    }
    $this->IDs['day']   = $this->add( new elSelect($this->getName().'[day]',   '', $this->value['day'],   $d) ); 
    $this->IDs['month'] = $this->add( new elSelect($this->getName().'[month]', '', $this->value['month'], $m) );
    $this->IDs['year']  = $this->add( new elSelect($this->getName().'[year]',  '', $this->value['year'],  $y) );
    if ( $this->_dateTimeFormat )
    {
    	$h = array_map('elFormatDateTimeArrays', range(0, 23));
    	$i = array_map('elFormatDateTimeArrays', range(0, 59));	
    	$this->IDs['hour'] = $this->add( new elSelect($this->getName().'[hour]', '', $this->value['hour'], $h) );
    	$this->IDs['min']  = $this->add( new elSelect($this->getName().'[min]',  '', $this->value['min'],  $i) );
    	
    }
  }

  function elDateSelector($name=null, $label=null, $value=null, $attrs=null, $offsetLeft=1, $offsetRight=2, $dtFormat=false)
  {
    $this->__construct($name, $label, $value, $attrs, $offsetLeft, $offsetRight, $dtFormat);
  }

  function setValue( $value )
  {
    $this->value = array('day'   => date('j', $value),
                         'month' => date('n', $value),
                         'year'  => date('Y', $value),
                         'hour'  => $this->_dateTimeFormat ? date('H', $value) : 0,
                         'min'   => $this->_dateTimeFormat ? date('i', $value) : 0
                         );
  }

  function onAddToForm( &$form )
  {
    $form->registerInput($this);
  }

  function onSubmit($source)
  {
    foreach ( $this->childs as $k=>$child )
    {
      $this->childs[$k]->onSubmit( $source );
    }
  }

  function toHtml()
  {
    $html = '';
    $html .= $this->childs[0]->toHtml().".\n";
    $html .= $this->childs[1]->toHtml().".\n";
    $html .= $this->childs[2]->toHtml()."&nbsp;\n";
    if ( $this->_dateTimeFormat )
    {
    	$html .= $this->childs[3]->toHtml().":\n";
    	$html .= $this->childs[4]->toHtml()."\n";
    }
    return $html;
  }

  function _fetchValue()
  {
    $map = array_flip( $this->IDs );
    foreach ( $this->childs as $child )
    {
      $this->value[$map[$child->getID()]] = $child->getValue();
    }
  }

  function getValue()
  {
    $this->_fetchValue();
    return  mktime($this->value['hour'],$this->value['min'],0,$this->value['month'], $this->value['day'], $this->value['year']);
  }

  function selfValidate( &$errors, $args )
  {
    $this->_fetchValue();
    if ( !checkdate($this->value['month'], $this->value['day'], $this->value['year']) )
    {
      $errors[$this->getID()] = $args[2];
      $this->setValue(time());
      return false;
    }
    return true;
  }
}

function elFormatDateTimeArrays($num)
{
	return 10 <= $num ? $num : '0'.$num;
}

?>