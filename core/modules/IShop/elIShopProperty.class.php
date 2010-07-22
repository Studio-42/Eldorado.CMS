<?php




function elIShopParsePropValue($str)
{
  static $reg = '/range\(([0-9\-\.]+)\,?\s*([0-9\-\.]+)\,?\s*([0-9\-\.]+)\s*\)\s*(exclude\((.+)\))?.*/si';
  return preg_replace($reg, sprintf(m('from %s till %s (step %s)'), "\\1", "\\2", "\\3"), $str);
}

class elIShopProperty extends elDataMapping {
	var $_tb         = '';
	var $tbpval      = '';
	var $tbp2i       = '';
	var $tbpdep      = '';
	var $ID          = 0;
	var $typeID      = 0;
	var $type        = EL_IS_PROP_STR;
	var $name        = '';
	var $displayPos  = 'table';
	var $isHidden    = 0;
	var $isAnnounced = 0;  
	var $sortNdx     = 1;
	
	var $dependID    = 0;
	var $depend      = array();
	var $_objName    = 'Property';
	var $_factory    = null;
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
	function valuesToString($val) {
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
		return $this->isMultiList() && $this->dependID ? $this->_factory->getFromRegistry(EL_IS_PROP, $this->dependID) : null;
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
			}
			return true;
		} 
		
	}


  // function inDepend()
  // {
  //   if ( !$this->isDependAvailable() )
  //   {
  //     return false;
  //   }
  //   if ( $this->dependID )
  //   {
  //     return true;
  //   }
  //   $db = & elSingleton::getObj('elDb');
  //   $db->query('SELECT id FROM '.$this->tbpdep.' WHERE s_id='.$this->ID.' OR m_id='.$this->ID);
  //   return $db->numRows();
  // }

  // function editDependance( $iTypeName, $master )
  // {
  //   parent::_makeForm();
  //   $rnd = & elSingleton::getObj('elTplGridFormRenderer');
  //   $rnd->addButton( new elSubmit('submit', '', m('Submit')) );
  //   $rnd->addButton( new elReset('reset', '', m('Drop')) );
  //   $this->_form->setRenderer( $rnd );
  //   $l = sprintf(m('Item type: %s. Edit properties dependance'), $iTypeName);
  //   $this->_form->setLabel($l);
  //   $this->_form->add( new elCData('myname', $this->name), array('class'=>'formSubheader') );
  //   $this->_form->add( new elCData('two', $master->name), array('class'=>'formSubheader') );
  // 
  //   $mVals = array();
  //   foreach ( $master->values as $ID=>$v)
  //   {
  //     $mVals[$ID] = elIShopParsePropValue($v[0]);
  //   }
  //   $dep = $this->getDependance(); //elPrintR( $dep );
  //   foreach ( $this->values as $ID=>$v)
  //   {
  //     $this->_form->add( new elCData('s_'.$ID, elIShopParsePropValue($v[0])) );
  //     //$vals = !empty($dep[$ID]) ? $dep[$ID] : null;
  //     $this->_form->add( new elMultiSelectList('mvals['.$ID.']', '', !empty($dep[$ID]) ? $dep[$ID] : null, $mVals, null, false, true, 'span') );
  //   }
  // 
  //   if ( $this->_form->isSubmitAndValid() )
  //   {
  //     $data = $this->_form->getValue(); //elPrintR($data);
  //     $db   = & elSingleton::getObj('elDb');
  //     $db->query( 'DELETE FROM '.$this->tbpdep.' WHERE s_id='.$this->ID );
  //     $db->optimizeTable( $this->tbpdep );
  //     $db->prepare('INSERT INTO '.$this->tbpdep.' (s_id, s_value, m_id, m_value) VALUES ', '(%d, %d, %d, %d)');
  //     foreach ( $data['mvals'] as $sVal=>$mData )
  //     {
  //       if ( !empty($mData) )
  //       {
  //         $exec = 1;
  //         foreach ( $mData as $mVal )
  //         {
  //           $db->prepareData( array($this->ID, $sVal, $master->ID, $mVal) ) ;
  //         }
  //       }
  //     }
  //     if ( !empty( $exec) )
  //     {
  //       $db->execute();
  //     }
  //     return true;
  //   }
  // 
  //   return false;
  // }

//   function getDependance()
//   {
//     if ( !$this->_dependLoad )
//     {
//       $this->_dependLoad = 1;
//       if ( EL_IS_PROP_MLIST == $this->type || $this->isDependAvailable() )
//       {
//         $db = & elSingleton::getObj('elDb');
//         $db->query( 'SELECT s_value, m_value FROM '.$this->tbpdep.' WHERE s_id='.$this->ID.' AND m_id='.$this->dependID );
//         while ( $r = $db->nextRecord() )
//         {
// //          if ( empty($this->depend[$r['s_value']]) )
// //          {
// //            $this->depend[$r['s_value']] = array();
// //          }
//           $this->depend[$r['s_value']][] = $r['m_value'];
//         }
//       }
//     }
//     return $this->depend;
//   }


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
			$label = 'Edit property for type "%s"';
		} else {
			$label = 'Create new property for type "%s"';
			$this->sortNdx = ++$maxNdx;
		}

		$test = array('a' => array('letter a', 1), 2 => array('letter 2', 0));
		
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
		$this->_form->add(new elText('name',                           m('Name'),                           $this->name));
		$this->_form->add(new elSelect('type',                         m('Property type'),                  $this->type, $this->_types));
		$this->_form->add(new elText('values'.EL_IS_PROP_STR,          m('Default value'),                  $valStr, array('maxlength' => '256', 'style' => 'width:100%')));
		$this->_form->add(new elTextArea('values'.EL_IS_PROP_TXT,      m('Default value'),                  $valTxt, $this->valuesToString(), array('rows' => 5)));
		$this->_form->add(new elVariantsList('values'.EL_IS_PROP_LIST,  m('Values variants'),               $valList));
		$this->_form->add(new elVariantsList('values'.EL_IS_PROP_MLIST, m('Values variants'),               $valMList));
		$this->_form->add(new elSelect('display_pos',                  m('Display position in item card'),  $this->displayPos, $pos));
		$this->_form->add(new elSelect('is_announced',                 m('Announce propery in items list'), $this->isAnnounced, $GLOBALS['yn'] ) );
		$this->_form->add(new elSelect('is_hidden',                    m('Display only in order form'),     $this->isHidden, $GLOBALS['yn'] ) );
		$this->_form->add(new elSelect('sort_ndx',                     m('Order position'),                 $this->sortNdx, range(1, $maxNdx), null, false, false) );
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
			? $this->_form->pushError('values'.$data['type'], 'Could not be empty')
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
				$sql = sprintf('DELETE FROM %s WHERE p_id=%d AND id<>%d', $this->tbpval, $this->ID, !empty($this->_default[0]) && isset($this->_opts[$this->_default[0]]) ? $this->_default[0] : key($this->_opts) );
				$db->query($sql);
				$sql = sprintf($upd, $this->tbpval, mysql_real_escape_string($src), 1, $this->ID);
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
			$this->_form->setLabel(sprintf(m('Edit dependance %s/%s'), $master->name, $this->name));
			$this->_form->add(new elCData2('c', $master->name, $this->name));
			$opts = array_map(array($this, '_rangeToString'), $this->_opts);
			foreach (array_map(array($this, '_rangeToString'), $master->options()) as $id=>$v) {
				$this->_form->add(new elMultiSelectList('m_id_'.$id, $v, null, $opts));
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

}
?>
