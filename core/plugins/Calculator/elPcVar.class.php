<?php


class elPcVar extends elDataMapping {
	var $_tb    = 'el_plugin_calc_var';
	var $ID     = 0;
	var $cid    = 0;
	var $name   = '';
	var $title  = '';
	var $type   = 'input';
	var $dtype  = 'int';
	var $minVal = '';
	var $maxVal = '';
	var $unit   = '';
	var $variants    = '';
	var $_objName = 'Variable';
	
	function checkName() {
		$db = &elSingleton::getObj('elDb');
		$sql = 'SELECT id FROM '.$this->_tb.' WHERE cid=\''.$this->cid.'\' AND name="'.mysql_real_escape_string($this.name).'" AND id!="'.$this->ID.'"';
		$db->query($sql);
		return !$db->numRows();
	}
	
	function toArray($inputField = true) {
		$data = parent::toArray();
		$vars = $this->_variantsToArray();
		
		if (!$inputField) 
		{
			if ($vars) {
				$data['variants'] = $vars;
			} else {
				unset($data['vars']);
			}
		}
		else 
		{
			
			if (!$vars) {
				$data['input_field'] = '<input type="text" name="'.$this->name.'" class="required number '.$this->dType.'" />';
			} else {
				$data['input_field'] = '<select name="'.$this->name.'">';
				foreach ($vars as $value=>$label) 
				{
					$data['input_field'] .= '<option value="'.$value.'">'.$label.'</option>';
				}
				$data['input_field'] .= '</select>';
			}
		}
		return $data;
	}
	
	function _variantsToArray() 
	{
		$tmp = array();
		if ($this->type == 'select' && !empty($this->variants)) {
			$variants = explode("\n", str_replace("\r", '', $this->variants));
			
			
			foreach ($variants as $line) 
			{
				if (false != ($pos = strpos($line, ':'))) 
				{
					$value = trim(substr($line, 0, $pos));
					if (strlen($value) > 0) 
					{
						$tmp[$value] = trim(substr($line, $pos+1));
					}
				}
			}
		}
		return $tmp;
	}
	
	function _initMapping() 
	{
		return array(
			'id'     => 'ID',
			'cid'    => 'cid',
			'name'   => 'name',
			'title'  => 'title',
			'unit'   => 'unit',
			'type'   => 'type',
			'dtype'  => 'dtype',
			'minval' => 'minVal',
			'maxval' => 'maxVal',
			'variants' => 'variants'
			);
	}
}

?>