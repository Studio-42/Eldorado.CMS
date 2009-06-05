<?php

class elExpandBox extends elFormContainer 
{
	var $attrs = array('swLabel' => 'Switch on');
	var $_switchOn = 0;
	var $tpl = array(
		'header' => '<table width="100%%" cellspacing="0" class="form_tb"><tr><td style="white-space:nowrap">%s</td><td width="80%%">%s</td></tr></table>',
		'div'    => '<div id="%s_cont" style="%s">',
		'table'  => '<table width="100%%" cellspacing="0" class="form_tb">',
		'row'    => '<tr><td>%s</td><td>%s</td></tr>'
		);
	
	function elExpandBox($name=null, $label=null, $attrs=null)
	{
		
		parent::elFormElement($name, $label, $attrs);
		
		$ch = & new elCheckBox($this->getName(), $this->getAttr('swLabel'), 1);
		$ch->setAttr('onClick', 'c=document.getElementById("'.$this->getName().'_cont");  c.style.display=this.checked?"":"none";');
		if ($this->getAttr('checked'))
		{
			$ch->setAttr('checked', 'on');
		}
		
		$this->add($ch);
	}

	function setAttr($attr, $val)
	{
		if ('checked' == $attr && !empty($this->childs[0]))
		{
			$this->childs[0]->setAttr('checked', 'on');
		}
		parent::setAttr($attr, $val);
	}
	
	function toHtml()
	{
		$html = '';
		$html .= sprintf($this->tpl['header'], m($this->getAttr('swLabel')), $this->childs[0]->toHtml() );
		$style = $this->childs[0]->getAttr('checked') ? '' : ' display:none';
		$html .= sprintf($this->tpl['div'], $this->getName(), $style);
		$html .= $this->tpl['table'];
		for ($i=1, $s=sizeof($this->childs); $i<$s; $i++)
		{
			$html .= sprintf($this->tpl['row'], $this->childs[$i]->getLabel(), $this->childs[$i]->toHtml());
		}
		$html .= '</table>';
		$html .= '</div>';

		return $html;	
	}
	
}
?>