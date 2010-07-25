<?php
/**
 * Item type property
 *
 * @package IShop
 **/ 
class elIShopProperty extends elDataMapping {
	/**
	 * main table
	 *
	 * @var string
	 **/
	var $_tb         = '';
	/**
	 * value variants table
	 *
	 * @var string
	 **/
	var $tbpval      = '';
	/**
	 * items values table
	 *
	 * @var string
	 **/
	var $tbp2i       = '';
	/**
	 * dependance table
	 *
	 * @var string
	 **/
	var $tbpdep      = '';
	/**
	 * ID
	 *
	 * @var int
	 **/
	var $ID          = 0;
	/**
	 * item type ID
	 *
	 * @var int
	 **/
	var $typeID      = 0;
	/**
	 * property type
	 *
	 * @var string
	 **/
	var $type        = EL_IS_PROP_STR;
	/**
	 * property name
	 *
	 * @var string
	 **/
	var $name        = '';
	/**
	 * position in item card
	 *
	 * @var string
	 **/
	var $displayPos  = 'table';
	/**
	 * hide property in item card
	 *
	 * @var int
	 **/
	var $isHidden    = 0;
	/**
	 * display property in items list
	 *
	 * @var int
	 **/
	var $isAnnounced = 0;  
	/**
	 * Sort index
	 *
	 * @var int
	 **/
	var $sortNdx     = 1;
	/**
	 * Depend on property ID
	 *
	 * @var int
	 **/
	var $dependID    = 0;
	/**
	 * Object name
	 *
	 * @var string
	 **/
	var $_objName    = 'Feature';
	/**
	 * IShop factory
	 *
	 * @var elIShopFactory
	 **/
	var $_factory    = null;
	/**
	 * Regexp to detect "range" construction in multilist values
	 *
	 * @var string
	 **/
	var $_rangeReg   = '/range\(([0-9\-\.]+)\,?\s*([0-9\-\.]+)\,?\s*([0-9\-\.]+)\s*\)\s*(exclude\((.+)\))?.*/si';
	/**
	 * value variants list
	 *
	 * @var array
	 **/
	var $_opts = array();
	/**
	 * default values ids list
	 *
	 * @var array
	 **/
	var $_default = array();
	/**
	 * Properties types names
	 *
	 * @var array
	 **/
	var $_types = array(
	    EL_IS_PROP_STR   => 'String',
	    EL_IS_PROP_TXT   => 'Text',
	    EL_IS_PROP_LIST  => 'Values list (one value can be selected)',
	    EL_IS_PROP_MLIST => 'Values list (any numbers of value can be selected)'
	    );
	
	/**
	 * fetch property object from db
	 *
	 * @return bool
	 **/
	function fetch() {
		if (parent::fetch()) {
			$db = $this->_db();
			$db->query(sprintf('SELECT id, value, is_default FROM %s WHERE p_id=%d', $this->tbpval, $this->ID));
			while ($r = $db->nextRecord()) {
				$this->_opts[$r['id']] = $r['value'];
				if ($r['is_default']) {
					$this->_default[] = $r['id'];
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * return all properties
	 *
	 * @return array
	 **/
	function collection($obj=false, $assoc=false, $clause=null, $sort=null, $offset=0, $limit=0, $onlyFields=null) {
		$coll = parent::collection(true, true, $clause, 't_id, sort_ndx');
		if ($coll) {
			$db  = $this->_db();
			$sql = sprintf('SELECT id, p_id, value, is_default FROM %s WHERE p_id IN (%s) ORDER BY id', $this->tbpval, implode(',', array_keys($coll)));
			$db->query($sql);
			
			while ($r = $db->nextRecord()) {
				$coll[$r['p_id']]->_opts[$r['id']] = $r['value'];
				if ($r['is_default']) {
					$coll[$r['p_id']]->_default[] = $r['id'];
				}
			}
		}
		return $coll;
	}
	
	/**
	 * return true if type is text or textarea
	 *
	 * @return bool
	 **/
	function isText() {
		return EL_IS_PROP_LIST > $this->type;
	}

	/**
	 * return true if type is select
	 *
	 * @return bool
	 **/
	function isList() {
		return EL_IS_PROP_LIST == $this->type;
	}

	/**
	 * return true if type is multiselect
	 *
	 * @return bool
	 **/
	function isMultiList() {
		return EL_IS_PROP_MLIST == $this->type;
	}

	/**
	 * Return property value for product or default value
	 *
	 * @param  array  $val  list of values ids
	 * @return string
	 **/
	function valuesToString($val=null) {
		switch ($this->type) {
			case EL_IS_PROP_LIST:
				return isset($val[0]) && isset($this->_opts[$val[0]]) ? $this->_opts[$val[0]] : (isset($this->_default[0]) && isset($this->_opts[$this->_default[0]]) ? $this->_opts[$this->_default[0]] : current($this->_opts));
			case EL_IS_PROP_MLIST:
				if (empty($val) || !is_array($val)) {
					$val = $this->_default;
				}
				$ret = array();
				foreach($val as $id) {
					if (isset($this->_opts[$id])) {
						
						$ret[] = $this->_opts[$id];
					}
				}
				return implode(', ', $ret);
			default:
				return isset($val[0]) ? $val[0] : (isset($this->_default[0]) && isset($this->_opts[$this->_default[0]]) ? $this->_opts[$this->_default[0]] : '');
		}
	}

	/**
	 * Return list of properties in human readable format (for admin mode)
	 *
	 * @return array
	 **/
	function getInfo() {
		$this->_types = array_map('m', $this->_types);
		
		$ret = array(
			'id'        => $this->ID,
			'typeID'    => $this->typeID,
			'type'      => $this->_types[$this->type],
			'name'      => $this->name,
			'position'  => m($this->displayPos),
			'announced' => m($this->isAnnounced ? 'Yes' : 'No'),
			'hidden'    => m($this->isHidden    ? 'Yes' : 'No'),
			'opts'      => $this->isText()      ? m('No') : implode(', ', $this->isMultiList() ? array_map(array($this, '_rangeToString'), $this->_opts) : $this->_opts),
			'default'   => $this->valuesToString($this->isText() ? array() : $this->_default)
			);
		return $ret;
	}

	/**
	 * returns property on which depends current property
	 *
	 * @return elIShopProperty
	 **/
	function getDependOn() {
		// if (!isset($this->_dependOn)) {
		// 	$this->_dependOn = null;
		// 	if ($this->isMultiList() && $this->dependID && false != ($m = $this->_factory->getFromRegistry(EL_IS_PROP, $this->dependID))) {
		// 		$this->_dependOn = $m;
		// 	} 
		// }
		// return $this->_dependOn;
		return $this->isMultiList() && $this->dependID ? $this->_factory->getFromRegistry(EL_IS_PROP, $this->dependID) : null;
	}

	/**
	 * Return array of dependance values (master_value => array of slave values)
	 *
	 * @return void
	 **/
	function getDependance() {
		$ret = array();
		if (false != ($master = $this->getDependOn())) {
			$db = $this->_db();
			$sql = sprintf('SELECT m_value, s_value FROM %s WHERE m_id=%d AND s_id=%d', $this->tbpdep, $master->ID, $this->ID);
			$db->query($sql);
			while ($r = $db->nextRecord()) {
				if (!isset($ret[$r['m_value']])) {
					$ret[$r['m_value']] = array();
				}
				$ret[$r['m_value']][] = $r['s_value'];
			}
		}
		return $ret;
	}

	/**
	 * Delete property
	 *
	 * @return void
	 **/
	function delete() {
		$tbpval = $this->_factory->tb('tbpval');
		$tbp2i  = $this->_factory->tb('tbp2i');
		$tbdep  = $this->_factory->tb('tbpdep');
		$tbs    = array($tbpval => 'p_id', $tbp2i => 'p_id', $tbdep => 'm_id');
		parent::delete($tbs);
		parent::delete(array($tbdep => 's_id'));
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function options() {
		return $this->_opts;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function editDependance() {
		if ($this->isMultiList() && false != ($master = $this->getDependOn())) {
			$this->_makeDependForm();
			if ($this->_form->isSubmitAndValid()) {
				$db = $this->_db();
				$data = $this->_form->getValue();
				
				$db->query(sprintf('DELETE FROM %s WHERE m_id=%d AND s_id=%d', $this->tbpdep, $master->ID, $this->ID));
				$db->optimizeTable($this->tbpdep);
				$val = array();
				$db->prepare('INSERT INTO '.$this->tbpdep.' (m_id, m_value, s_id, s_value) VALUES ', '(%d, %d, %d, %d)');
				foreach ($data as $masterVal=>$slaveVals) {
					
					if (preg_match('/^m_id_\d/', $masterVal)) {
						$masterVal = str_replace('m_id_', '', $masterVal);
						foreach ($slaveVals as $v) {
							$val[] = array($master->ID, $masterVal, $this->ID, $v);
						}
					}
				}
				if (!empty($val)) {
					$db->prepareData($val, true);
					$db->execute();
				}
				return true;
			}
			
		} 
		
	}

  	/**
  	 * overide parent method
  	 *
  	 * @return void
  	 **/
	function clean() {
		$mapping = $this->memberMapping();
		foreach ($mapping as $attr=>$member) {
			if ('type' != $attr && 'display_pos' != $attr) {
				$this->$member = '';
			}
		}
		$this->values = array();
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function toFormElement($v='', $admin=false) {
		include_once EL_DIR_CORE.'forms/elForm.class.php';
		switch ($this->type) {
			case EL_IS_PROP_STR:
				return new elText('prop_'.$this->ID, $this->name, $this->valuesToString($v));
				break;
			case EL_IS_PROP_TXT:
				return new elTextArea('prop_'.$this->ID, $this->name, $this->valuesToString($v));
				break;
			case EL_IS_PROP_LIST:
				return new elSelect('prop_'.$this->ID, $this->name, isset($v[0]) ? $v[0] : current($this->_default), $this->_opts);
				break;
			case EL_IS_PROP_MLIST:
				if ($admin) {
					return new elCheckboxesGroup('prop_'.$this->ID, $this->name, is_array($v) ? $v : $this->_default, $this->_opts);
				} else {
					$dep   = $this->getDependOn();
					$attrs = $dep ? array('depend_on' => $dep->ID) : array();
					return new elSelect('prop_'.$this->ID, $this->name, isset($v[0]) ? $v[0] : current($this->_default), $this->_opts, $attrs);
				}
				
				break;
		}
	}

	/*********************************************************/
	/***                     PRIVATE                       ***/
	/*********************************************************/

	/**
	 * For multiselect translate range(start, end, step) into human readable form
	 *
	 * @param  string
	 * @return string
	 **/
	function _rangeToString($str) {
		if (preg_match($this->_rangeReg, $str, $m)) {
			$str = sprintf(m('from %s till %s (step %s)'), $m[1], $m[2], $m[3]);
			if (!empty($m[5])) {
				$str .= ' '.sprintf(m('exclude %s'), $m[5]);
			}
		}
		return $str;
	}

	/**
	 * Create form for edit object
	 *
	 * @return void
	 **/
	function _makeForm($params = null) {
		parent::_makeForm();

		$itype        = $this->_factory->create(EL_IS_ITYPE, $this->typeID);
		$maxNdx       = count($itype->getProperties());
		$this->_types = array_map('m', $this->_types);
		$pos          = array(
			'top'    => m('top'),
			'table'  => m('table'),
			'bottom' => m('bottom')
			);

		if ($this->ID) {
			$label = 'Edit feature for type "%s"';
		} else {
			$label = 'Create new feature for type "%s"';
			$this->sortNdx = ++$maxNdx;
		}

		$opts = array();
		if (!$this->isText()) {
			foreach ($this->_opts as $id => $v) {
				$opts[$id] = array($v, (int)in_array($id, $this->_default));
			}
		}
		
		$valStr   = $this->type == EL_IS_PROP_STR   ? $this->valuesToString() : '';
		$valTxt   = $this->type == EL_IS_PROP_TXT   ? $this->valuesToString() : '';
		$valList  = $this->type == EL_IS_PROP_LIST  ? $opts : null;
		$valMList = $this->type == EL_IS_PROP_MLIST ? $opts : null;

		$this->_form->setLabel(sprintf(m($label), $itype->name));
		$this->_form->add(new elText('name',                           m('Name'),                             $this->name));
		$this->_form->add(new elSelect('type',                         m('Property type'),                    $this->type, $this->_types));
		$this->_form->add(new elText('values'.EL_IS_PROP_STR,          m('Default value'),                    $valStr, array('maxlength' => '256', 'style' => 'width:100%')));
		$this->_form->add(new elTextArea('values'.EL_IS_PROP_TXT,      m('Default value'),                    $valTxt, array('rows' => 5)));
		$this->_form->add(new elVariantsList('values'.EL_IS_PROP_LIST,  m('Values variants'),                 $valList));
		$this->_form->add(new elVariantsList('values'.EL_IS_PROP_MLIST, m('Values variants'),                 $valMList));
		$this->_form->add(new elSelect('display_pos',                  m('Display position on product page'), $this->displayPos, $pos));
		$this->_form->add(new elSelect('is_announced',                 m('Display in items list'),            $this->isAnnounced, $GLOBALS['yn'] ) );
		$this->_form->add(new elSelect('is_hidden',                    m('Hide on product page'),             $this->isHidden, $GLOBALS['yn'] ) );
		$this->_form->add(new elSelect('sort_ndx',                     m('Order position'),                   $this->sortNdx, range(1, $maxNdx), null, false, false) );
		$this->_form->setRequired('name');
	
		$id = $this->_form->getAttr('name'); 
		$js = '$("#'.$this->_form->getAttr('name').' #type").change(function() {
			$(this).parents("form").find("tr[id^=\'row_values\']").hide().filter("#row_values"+$(this).val()).show();
		}).trigger("change");';
		
		elAddJs($js, EL_JS_SRC_ONREADY);

		if ($this->isMultiList()) {
			$db     = $this->_db();
			$sql    = sprintf('SELECT id, name FROM %s WHERE t_id=%d AND type=%d AND id<>%d AND depend_id<>%d ORDER BY sort_ndx', $this->_tb, $this->typeID, $this->type, $this->ID, $this->ID);
			$depend = $db->queryToArray($sql, 'id', 'name');
			if (count($depend) > 0) {
				$this->_form->add(new elSelect('depend_id', m('Depend on'), $this->dependID, array(m('No'))+$depend));
			}
		}
	}

	/**
	 * Проверка формы редактирования полей объекта
	 * если его тип - список, то поле - варинты значений не должно быть пустым
	 *
	 * @return bool
	 */
	function _validForm() {
		$data = $this->_form->getValue(); 
		$src = $data['values'.$data['type']];
		return EL_IS_PROP_LIST <= $data['type'] && empty($src)
			? $this->_form->pushError('values'.$data['type'], m('Values list could not be empty'))
			: true;
	}

	/**
	 * Save value variants/default value, update sort indexes for other properies
	 *
	 * @return void
	 **/
	function _postSave($isNew) {
		$db = $this->_db();
		$indexes = $db->queryToArray('SELECT id, sort_ndx FROM '.$this->_tb.' WHERE t_id="'.$this->typeID.'" ORDER BY sort_ndx', 'id', 'sort_ndx');
		$i = 1;
		$s = sizeof($indexes);
		foreach ($indexes as $id=>$ndx) {
			
			if ($id != $this->ID) {
				$_ndx = $ndx;
				if ($i == $this->sortNdx) {
					$_ndx = $i == $s ? $s-1 : $i+1;
				} elseif ($i != $ndx) {
					$_ndx = $i;
				}
				if ($_ndx != $ndx) {
					$db->query(sprintf('UPDATE %s SET sort_ndx=%d WHERE id=%d LIMIT 1', $this->_tb, $_ndx, $id));
				}
			}
			$i++;
		}
		
		$data = $this->_form->getValue();
	    $src  = $data['values'.$this->type];
		$ins  = 'INSERT INTO %s (p_id, value, is_default) VALUES (%d, "%s", "%d")';
		$upd  = 'UPDATE %s SET value="%s", is_default="%d" WHERE id=%d';
		
		if ($this->isText()) {
			if ($isNew) {
				
				$sql = sprintf($ins, $this->tbpval, $this->ID, mysql_real_escape_string($src), 1);
			} else {
				$id = isset($this->_default[0]) && isset($this->_opts[$this->_default[0]]) ? $this->_default[0] : key($this->_opts);
				$sql = sprintf('DELETE FROM %s WHERE p_id=%d AND id<>%d', $this->tbpval, $this->ID,  $id);
				$db->query($sql);
				$sql = sprintf($upd, $this->tbpval, mysql_real_escape_string($src), 1, $id);
			}
			$db->query($sql);
		} else {
			$rm = array_diff(array_keys($this->_opts), array_keys($src));
			if (!empty($rm)) {
				$db->query(sprintf('DELETE FROM %s WHERE id IN (%s)', $this->tbpval, implode(',', $rm)));
			}
			foreach ($src as $id=>$v) {
				$sql = isset($this->_opts[$id])
					? sprintf($upd, $this->tbpval, mysql_real_escape_string($v[0]), $v[1], $id)
					: sprintf($ins, $this->tbpval, $this->ID, mysql_real_escape_string($v[0]), $v[1]);
				$db->query($sql);
			}
			
		}
		$db->optimizeTable($this->tbpval);
		return true;
	}

	/**
	 * Create form for edit dependance
	 *
	 * @return void
	 **/
	function _makeDependForm() {
		parent::_makeForm();
		if (false != ($master = $this->getDependOn())) {
			$dep = $this->getDependance();
			$this->_form->setLabel(sprintf(m('Edit dependance %s/%s'), $master->name, $this->name));
			$this->_form->add(new elCData2('c', $master->name, $this->name));
			$opts = array_map(array($this, '_rangeToString'), $this->_opts);
			foreach (array_map(array($this, '_rangeToString'), $master->options()) as $id=>$v) {
				$this->_form->add(new elMultiSelectList('m_id_'.$id, $v, isset($dep[$id]) ? $dep[$id] : null, $opts));
			}
		}
	}

	/**
	 * init attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		$map = array(
			'id'           => 'ID',
			't_id'         => 'typeID',
			'type'         => 'type',
			'depend_id'    => 'dependID',
			'name'         => 'name',
			'display_pos'  => 'displayPos',
			'is_hidden'    => 'isHidden',
			'is_announced' => 'isAnnounced',
			'sort_ndx'     => 'sortNdx'
		);
		return $map;
	}

} // END class 
?>
