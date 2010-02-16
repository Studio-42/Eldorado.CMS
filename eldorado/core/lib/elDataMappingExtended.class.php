<?php

/**
 * Extended elDataMapping
 *   table struct is stored in database
 *
 * @package eldorado.core
 * @author Troex Nevelin
 **/
class elDataMappingExtended
{
	var $_tb_struct    = '';
	var $_tb_data      = '';
	var $_tb_view      = '';
	var $_id           = 0;
	var $__id__        = 'id';
	var $_label        = array();
	var $_type         = array();
	var $_options      = array();
	var $_meta         = array();
	var $_meta_js      = array();
	var $_required     = array();
	var $_rule         = array();
	var $_rule_is_func = array();	
	var $_data         = array();
	var $_form         = null;
	var $_new          = false;
	var $_formRndClass = 'elTplFormRenderer';
	var $_objName      = 'ExtendedObject';

	function __construct()
	{
		$sql = "SELECT field, label, type, options, meta, required, rule, rule_is_func "
		     . sprintf("FROM %s ORDER BY sort_index", $this->_tb_struct);
		$db  = & elSingleton::getObj('elDb');
		$db->query($sql);
		while ($r = $db->nextRecord())
		{
			if ($r['meta'] == 'no')
			{
				$this->_label[$r['field']]        = $r['label'];
				$this->_type[$r['field']]         = $r['type'];
				$this->_required[$r['field']]     = $r['required'];
				$this->_options[$r['field']]      = strtr($r['options'], array("\n" => "", "\r" => ""));
				$this->_rule[$r['field']]         = $r['rule'];
				$this->_rule_is_func[$r['field']] = $r['rule_is_func'];
			}
			elseif ($r['meta'] == 'yes')
			{
				$this->_type[$r['field']] = $r['type'];
				//$this->_meta[$r['field']] = $r['options'];
				$this->_meta[$r['field']] = strtr($r['options'], array("\n" => "", "\r" => ""));
				//var_dump($this->_meta);
			}
		}
		$this->clean();
	}

	/**
	 *
	 * Core functions compatible with elDataMapping
	 *
	 **/

	function idAttr($val = null)
	{
		if (is_null($val))
			return $this->_id;
		else
		{
			//$this->_data['id'] = $val;
			$this->_id = $val;
			return $val;
		}
	}

	function attr($attr = null, $val = null)
	{
		$map = $this->_memberMapping(); 
		
		// return current data
		if (is_null($attr))
			return $this->_data;

		// set or get one attr in data
		// TODO maybe replace $map[$attr]
		if (!is_array($attr))
		{
			if (is_null($val))
				return $this->_data[$attr];
			else
				return $this->_data[$attr] = $val;
		}

		// set all data
		foreach ($attr as $f => $v)
			if (array_key_exists($f, $map))
				$this->_data[$f] = $v;
	}

	function fetch()
	{
		if (false != ($id = $this->idAttr()))
		{
			$this->clean();
			$db  = & elSingleton::getObj('elDb');
			$db->query(sprintf(
				"SELECT field, value FROM %s WHERE %s='%s' AND field IN (%s)",
				$this->_tb_data, $this->__id__, mysql_real_escape_string($id), $this->attrsList()
				));
			if (!$db->numRows() > 0)
				return false;

			$this->_data[$this->__id__] = $id;
			while ($r = $db->nextRecord())
				$this->_data[$r['field']] = $r['value'];
			return $this->_data;
		}
	}

	function collection($obj=false, $assoc=false, $clause=null, $sort=null, $offset=0, $limit=0, $onlyFields=null)
	{
		$db  = & elSingleton::getObj('elDb');
		$sql = sprintf('SELECT %s FROM %s %s %s %s', 
			$onlyFields && $onlyFields ? $this->__id__.', '.$onlyFields : $this->attrsToString(), 
			$this->_tb_view,
			$clause ? 'WHERE '.$clause : '', 
			$sort ? 'ORDER BY '.$sort : '', 
			$limit>0 ? 'LIMIT '.intval($offset).', '.intval($limit) : ''
			);
		if ( !$obj )
			return $db->queryToArray($sql, $assoc ? $this->__id__ : null);
		
		// broken below TODO
		$ret = array();
		$db->query($sql);
		while ($r = $db->nextRecord())
			if ($assoc) 
				$ret[$r[$this->_id]] = $this->copy($r);
			else 
				$ret[] = $this->copy($r);
		return $ret;
	}

	function save()
	{
		$data = $this->_attrsForSave();
		$db   = & elSingleton::getObj('elDb');
		$db->query(sprintf("LOCK TABLES %s WRITE", $this->_tb_data));
		
		// new record
		if ($this->_new)
			$this->idAttr($this->_getNextId());	

		// for UPDATE or INSERT
		foreach ($data as $field => $value)
		{
			$sql = sprintf(
				"REPLACE INTO %s (%s, field, value) VALUES (%s, '%s', '%s')",
				$this->_tb_data, $this->__id__, $this->_id, $field, $value
				);
			if (!$db->query($sql))
			{
				$db->query("UNLOCK TABLES");
				return false;
			}
		}
		
		$db->query("UNLOCK TABLES");
		return true;
	}

	function clean()
	{
		foreach ($this->_memberMapping() as $a => $f)
			$this->_data[$a] = '';
	}


	/**
	 *
	 * Form functions
	 *
	 **/

	function _makeForm()
	{
		$form_id = 'mf'.get_class($this);
		$this->_form = & elSingleton::getObj('elForm', $form_id,  sprintf(m(!$this->_id ? 'Create object "%s"' : 'Edit object "%s"'), m($this->_objName)));
		$this->_form->setRenderer(elSingleton::getObj($this->_formRndClass));
		
		foreach ($this->_label as $field => $label)
		{
			$value        = $this->_data[$field];
			$type         = $this->_type[$field];
			$options      = $this->_options[$field];
			$required     = $this->_required[$field] == 'yes' ? true : false;
			$rule         = $this->_rule[$field];
			$rule_is_func = $this->_rule_is_func[$field] == 'yes' ? true : false;;
			
			// Dynamic data set using ajax
			switch ($type)
			{
				case 'text':
					$this->_form->add(new elText($field, $label, $value));
					break;
				case 'textarea':
					$this->_form->add(new elTextArea($field, $label, $value));
					break;
				case 'select':
					// TODO
					$list = array();
					foreach (explode(',', $options) as $k => $v)
						$list[$v] = $v;
					$this->_form->add(new elSelect($field, $label, $value, $list));
					break;
				//case 'radio':
				//	// TODO
				//	$list = explode(',', $options);
				//	$this->_form->add(new elRadioButtons($field, $label, $value, $list));
				//	break;
				default:
					$this->_form->add(new elHidden($field, $label, $value));
				break;
			}
			
			if (!empty($rule) and !$rule_is_func)
				$this->_form->setElementRule($field, $rule, $required);
			elseif ($required)
				$this->_form->setRequired($field, $required);
		}
		

		//elPrintR($js_code);
		elAddJs($this->_formMetaJs($form_id), EL_JS_SRC_ONREADY);
		//elAddJs($js_code, EL_JS_SRC_ONREADY);
	}

	// build JavaScript around meta data
	function _formMetaJs($form_id)
	{		
		// Step 1. Create JS vars with meta data
		$js_var = '';
		foreach ($this->_meta as $meta => $data)
		{
			$js_var .= "    var ".$meta." = '";
			if ($this->_type[$meta] == 'select')
				foreach (explode(',', $data) as $el)
					$js_var .= sprintf('<option value="%s">%s</option>', $el, $el);
			else
				$js_var .= $data;
			$js_var .= "';\n";
		}
		
		// Step 2. Build main logic code
		$js_code = "";
		
		// run depend rule

		$js_code .= $this->_formMetaRuleDepend($form_id);
		
		return $js_var.$js_code;

	}
	
	// Build dependencies for meta data
	function _formMetaRuleDepend($form_id)
	{
		$js_code = "";
		$db = & elSingleton::getObj('elDb');
		if ($db->query(sprintf("SELECT field, meta_rule, meta_map FROM %s WHERE meta='no' AND meta_rule<>''", $this->_tb_struct)))
		{
			$parent_child  = array();
			// generate base functions
			while ($r = $db->nextRecord())
			{
				$field = $r['field'];
				$rules = explode(',', $r['meta_rule']);
				$maps  = explode(',', str_replace("\n", "", $r['meta_map']));

				foreach ($rules as $rule)
				{
					list($rule, $value) = explode(':', $rule);
					$parent_child[$value] = $field;
					list($parent, $child, $func) = $this->_formMetaRuleDependPCF($form_id, $field, $value);

					$s0 = sprintf("
    function %s() {%%s
    }\n", $func);
						
					$else = '';
					$s1   = sprintf("
      $('%s option').remove();", $child, $child);
					foreach ($maps as $map)
					{
						list ($key, $table) = explode(':', $map);
						$s1 .= sprintf("
      %sif ($('%s :selected').text() == '%s') {
        $('%s').append(%s);
        $('%s').parent().parent().show();
      }", $else, $parent, $key, $child, $table, $child);
						$else = 'else ';
					}
					$s1 .= sprintf("
      if ($('%s').children().size() == 0) {
        $('%s').parent().parent().hide();
        $('%s').append('%s')
      }", $child, $child, $child, '<option value="" selected="on"></option>');

					$js_code .= sprintf($s0, $s1);
				}
			}
			
			// generate triggers chains
			$js_code_after = '';
			$unique = array();

			foreach ($parent_child as $value => $field)
			{
				list($parent, $child, $func) = $this->_formMetaRuleDependPCF($form_id, $field, $value);
				$s0 = sprintf("
    $('%s').change( function() {%%s
    });\n", $parent);

				$s1 = '';
				$chain = array($value);
				$ret = $this->_formMetaRuleDependChain($parent_child, $chain);
				if (!$ret)
				{
					$chain_err = implode(' => ', $chain);
					elMsgBox::put(sprintf('Loop detected in "%s": %s => LOOP!', $value, $chain_err), EL_WARNQ);
					elMsgBox::put('Check our data structure', EL_WARNQ);
					return false;
				}

				foreach ($chain as $field)
				{
					if ($field == $value)
						continue; // skip out self
    				$s1 .= sprintf("
      _%s__%s();", $form_id, $field);
      				$st = sprintf("
    _%s__%s();", $form_id, $field);
      				$unique[$field] = $st;
				}

				$js_code_after .= sprintf($s0, $s1);
			}
			
			// init run code
			foreach ($unique as $field => $st)
			{
				$js_code_after .= $st;
                $js_code_after .= sprintf("
    $('#%s #%s').val('%s');", $form_id, $field, $this->_data[$field]);
			}
			// TODO run here selected by default on edit
			//$('select#name option[value="2"]').attr('selected', 'selected');
			foreach ($unique as $field => $st)
			{
//				$js_code_after .= sprintf("
//    $('#%s #%s option[value=\"%s\"').attr('selected', true);", $form_id, $field, $this->_data[$field]);
//                $js_code_after .= sprintf("
//    alert($('#%s #%s').val('%s'));", $form_id, $field, $this->_data[$field]);
//                $js_code_after .= sprintf("
//    $('#%s #%s').val('%s');", $form_id, $field, $this->_data[$field]);

			}
			//elPrintR($this->_data);
			$js_code .= $js_code_after;
		}
		return $js_code;
	}
	
	// build this crazy chains
	function _formMetaRuleDependChain($array, &$chain)
	{
		$key = $chain[count($chain)-1];
		if (!isset($array[$key]))
			return true;
		$val = $array[$key];
		if (in_array($val, $chain))
			return false;
		$chain[] = $val;
		return $this->_formMetaRuleDependChain($array, $chain);
	}

	function _formMetaRuleDependPCF($form_id, $field, $value)
	{
		$parent = '#'.$form_id.' #'.$value;
		$child  = '#'.$form_id.' #'.$field;
		$func   = '_'.$form_id.'__'.$field;
		return array($parent, $child, $func);
	}

	//not used
	/* function _getMetaData($field = null)
	{
		if ($field == null)
			return false;

		$db = & elSingleton::getObj('elDb');
		if ($db->query(sprintf("SELECT options FROM %s WHERE field='%s'", $this->_tb_struct, $field)))
		{
			$row = $db->nextRecord();
			return explode(',', $row['options']);
		}
		return false;
	} */

	// make a local check and add output to error list
	function _formLocalCheck()
	{
		foreach ($this->_rule_is_func as $field => $rule_is_func)
		{
			$rule_is_func = (bool)(int)$rule_is_func;
			$method_name = $this->_rule[$field];
			if ($rule_is_func == true)
			{
				//elDebug(get_class($this).'::_localFormCheck() field:'.$field.' method:'.$method_name);
				if (method_exists($this, $method_name))
					$ret = $this->$method_name($field, $this->_data[$field]);
					// if return != true, put this return as error
					if (!($ret === true))
					{
						$form_field_id = $this->_form->_map[$field];
						$this->_form->validator->errors[$form_field_id] = $ret;
					}
				else
					elDebug(get_class($this).'::'.$method_name.'() does not exist');
			}
		}
	}

	function formToHtml()
	{
		if (!$this->_form)
			$this->_makeForm();
		return $this->_form->toHtml();
	}

	function editAndSave()
	{
		$this->_makeForm();
		if ($this->_form->isSubmit())
		{
			$this->_formLocalCheck();
			if (!$this->_form->hasErrors() && $this->_validForm())
			{
				//elPrintR($this->_form->getValue());
				$this->attr($this->_form->getValue());
				$this->_new = !(bool)$this->idAttr();
				if ($this->save())
					return $this->_postSave($this->_new);// true
			}
		}
		return false;
	}

	// for old compat TODO do we really need this?
	function attrsToString($prefix = '')
	{
		return !$prefix
			? implode(',', array_keys($this->_memberMapping()))
			: $prefix.'.'.implode(','.$prefix.'.', array_keys($this->_memberMapping()));
	}


	/**
	 *
	 * Private functions compatible with elDataMapping
	 *
	 **/ 

	function _initMapping()
	{
		$sql = "SELECT field, '' AS empty FROM "
		     . sprintf("%s WHERE meta='no'", $this->_tb_struct);
		$db  = & elSingleton::getObj('elDb');
		$this->_data = $db->queryToArray($sql, 'field', 'empty');
		$this->_data[$this->__id__] = '';
		return $this->_data;
	}

	function _memberMapping()
	{
		$class = get_class($this);
		if (!isset($GLOBALS['mapping'][$class]))
			$GLOBALS['mapping'][$class] = $this->_initMapping();
		return $GLOBALS['mapping'][$class];
	}

	// autoincrement for id
	function _getNextId()
	{
		$sql = sprintf("SELECT MAX(%s) + 1 AS next FROM %s", $this->__id__, $this->_tb_data);
		$db  = & elSingleton::getObj('elDb');
		$db->query($sql);
		$r = $db->nextRecord();
		return $r['next'] < 0 ? 1 : $r['next'];
	}

	function _attrsForSave()
	{
		return array_map('mysql_real_escape_string', $this->attr());
	}

	function _postSave($new)
	{
		return true;
	}

	function _validForm()
	{
		return true;
	}


	/**
	 *
	 * Private special functions
	 *
	 **/

	function attrsList()
	{
		$array = array();
		foreach (array_keys($this->_memberMapping()) as $field)
			array_push($array, '"'.$field.'"');
		return implode(', ', array_values($array));		
	}

	function _createViewTable()
	{
		$i    = 0;
		$col  = sprintf('m.%s AS %s', $this->__id__, $this->__id__);
		$join = '';

		$db   = & elSingleton::getObj('elDb');
		$db->query(sprintf("SELECT field FROM %s WHERE meta='no'", $this->_tb_struct));
		while($row = $db->nextRecord())
		{
			$i++;
			$alias = 'a'.$i;
			$col  .= sprintf(",\n  %s.value AS %s", $alias, $row['field']);
			$join .= sprintf("\nLEFT JOIN %s AS %s ON ", $this->_tb_data, $alias)
			      .  sprintf("(m.%s=%s.%s AND %s.field='%s') ", $this->__id__, $alias, $this->__id__, $alias, $row['field']);
		}
		$sql_view  = sprintf("CREATE OR REPLACE VIEW %s AS\n", $this->_tb_view)
		           . sprintf("SELECT %s \nFROM %s AS m %s \nGROUP by m.%s", $col, $this->_tb_data, $join, $this->__id__);

		return $db->query($sql_view) ? true : false;		
	}

}

