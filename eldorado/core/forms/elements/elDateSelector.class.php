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
	var $_yearMin = 0;
	var $_yearMax = 0;

  function __construct($name=null, $label=null, $value=null, $attrs=null, $offsetLeft=1, $offsetRight=2, $dtFormat=false)
  {
    parent::__construct($name, $label, $attrs);
    $this->_dateTimeFormat = (bool)$dtFormat;

    $this->_yearMin = (int)$offsetLeft * -1;
	$this->_yearMax = (int)$offsetRight;

	if (is_numeric($value) && ($value != 0)) // this is hack, if we generate new elDateSelector with value=0 (e.g. modules/News)
	    $this->setValue($value);
	else
		$this->setValue(time());

    $this->IDs['date']     = $this->add( new elText($this->getName().'__date__', '', $this->value['date'], array('size' => 10)) );
    if ( $this->_dateTimeFormat )
    {
    	$this->IDs['time'] = $this->add( new elText($this->getName().'__time__', '', $this->value['time'], array('size' => 10)) );
    }
  }

  function elDateSelector($name=null, $label=null, $value=null, $attrs=null, $offsetLeft=1, $offsetRight=2, $dtFormat=false)
  {
    $this->__construct($name, $label, $value, $attrs, $offsetLeft, $offsetRight, $dtFormat);
  }

  function setValue( $value )
  {
    $this->value = array('date' => date('Y-m-d', $value),
                         'time' => $this->_dateTimeFormat ? date('H:i', $value) : 0
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
	elLoadJQueryUI();
	elAddJs('$("#'.$this->getName().'__date__").datepicker({dateFormat: $.datepicker.ISO_8601, firstDay: 1, minDate: \''.$this->_yearMin.'Y\', maxDate: \''.$this->_yearMax.'Y\', changeYear: true })', EL_JS_SRC_ONREADY);
    $html = '';
    $html .= $this->childs[0]->toHtml()."&nbsp;\n";
    if ( $this->_dateTimeFormat )
    {
		elAddJs('jquery.timepicker.js', EL_JS_CSS_FILE);
		elAddJs('$("#'.$this->getName().'__time__").timepicker()', EL_JS_SRC_ONREADY);
    	$html .= $this->childs[1]->toHtml()."\n";
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
	if ( $this->_dateTimeFormat )
		return strtotime($this->value['date'] . ' ' . $this->value['time']);
	else
		return strtotime($this->value['date']);
  }

  function selfValidate( &$errors, $args )
  {
    $this->_fetchValue();
		
	list($year, $month, $day) = explode('-', $this->value['date'], 3);
	// elPrintR($year);
	// elPrintR($month);
	// elPrintR($day);
	
    if ( !checkdate($month, $day, $year) )
    {
      $errors[$this->getID()] = $args[2];
      $this->setValue(time());
      return false;
    }

	if ( $this->_dateTimeFormat )
	{
		if (!preg_match('/^\d{2}\:\d{2}$/', $this->value['time']))
		{
			$errors[$this->getID()] = $args[2];
	      	$this->setValue(time());
			return false;
		}
	}
    		
	return true;

  }
}

?>