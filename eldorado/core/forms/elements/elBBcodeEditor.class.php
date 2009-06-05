<?php

class elBBcodeEditor extends elFormInput
{
	var $value = '';
  	var $attrs = array(
		'rows'  => 20, 
        'cols'  => EL_DEFAULT_TA_COLS, 
        'style' => 'width:100%');

  function setValue($value)
  {
    $this->value = $value;
  }

  function getValue()
  {
    return $this->value;
  }

  function toHtml()
  {
	$bbcode = & elSingleton::getObj('elBBCode');
	$groups = $bbcode->getGroups();
	
	elAddJs('jquery.elBBcodeEditor.js', EL_JS_CSS_FILE);
	elAddJs("$('div.bbeditor').elBBcodeEditor()", EL_JS_SRC_ONREADY);
	
	$html = '<div class="bbeditor">';
	$i = 1;
	$s = sizeof($groups);
	foreach ( $groups as $name=>$group )
	{
		$html .= '<div class="bb-group clearfix '.($s == $i++ ? 'last' : '').'">';
		foreach ($group as $bb)
		{
			if ('text' != $name || 'color' != $bb)
			{
				
				$html .= '<div class="bb-button rounded-3"><a href="javascript:void(0)" class="bb-'.$bb.'" meta="'.$bb.'"></a></div>';
			}
			else
			{
				$colorsList = $bbcode->getColors();
				$colors  = '<select name="color">';
				$colors .= '<option value="">'.m('Font color').'</option>';
				foreach ($colorsList as $c)
				{
					$colors .= '<option value="'.$c.'" style="color:'.$c.';">'.m($c).'</option>';
				}
				$colors .= '</select>';
				$html   .= '<div class="bb-sel">'.$colors.'</div>';
			}
		}
		$html .= '</div>';	
	}
	
	if ( false != ($smiley = $bbcode->getSmiley()))
	{
		$html .= '<div class="bb-smiley">';
		foreach ($smiley as $sm=>$img)
		{
			$html .= '<a href="#" meta="'.$sm.'"><img src="{STYLE_URL}images/forum/smiley/'.$img.'" /></a>';
		}
		$html .= '</div>';
		
	}
	$html .= '<textarea '.$this->attrsToString().">\n".htmlspecialchars($this->value)."</textarea>\n";
	$html .= '</div>';
    return $html;
  }
}

?>