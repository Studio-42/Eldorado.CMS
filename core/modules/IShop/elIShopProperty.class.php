<?php


$GLOBALS['elIShopPropTypes'] = array(
    EL_IS_PROP_STR   => array( m('String'), 'elText', array('maxlength'=>256)),
    EL_IS_PROP_TXT   => array( m('Text'), 'elTextArea', array('rows'=>10)),
    EL_IS_PROP_LIST  => array( m('Values list (one value can be selected)'), 'elVariantsList', null),
    EL_IS_PROP_MLIST => array( m('Values list (any numbers of value can be selected)'), 'elVariantsList', null)
    );

$GLOBALS['elIShopPropPos'] = array(
                                   'top'    => m('top'),
                                   'middle' => m('middle'),
                                   'table'  => m('table'),
                                   'bottom' => m('bottom')
                                   );

function elIShopParsePropValue($str)
{
  static $reg = '/range\(([0-9\-\.]+)\,?\s*([0-9\-\.]+)\,?\s*([0-9\-\.]+)\s*\)\s*(exclude\((.+)\))?.*/si';
  return preg_replace($reg, sprintf(m('from %s till %s (step %s)'), "\\1", "\\2", "\\3"), $str);
}

class elIShopProperty extends elDataMapping {
	var $_tb           = '';
	var $tbpval        = '';
	var $tbp2i         = '';
	var $ID            = 0;
	var $typeID       = 0;
	var $type          = EL_IS_PROP_STR;
	var $name          = '';
	var $displayPos    = 'table';
	var $isHidden      = 0;
	var $isAnnounced   = 0;  
	var $sortNdx       = 1;

	var $dependID      = 0;
	var $depend        = array();
	var $_objName    = 'Property';
	var $_factory    = null;
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
	 * return true if type is multi select
	 *
	 * @return bool
	 **/
	function isMultiList() {
		return EL_IS_PROP_MLIST == $this->type;
	}

	/**
	 * return default values ids
	 *
	 * @return array
	 **/
	function defaultIDs() {
		return !empty($this->_default) ? $this->_default : array(key($this->_opts));
	}

	/**
	 * return options as string
	 *
	 * @return string
	 **/
	function toString() {
		return $this->_toString(array_keys($this->_opts));
	}

	/**
	 * return default values as string
	 *
	 * @return string
	 **/
	function defaultToString() {
		return $this->_toString($this->defaultIDs());
	}

	/**
	 * fetch values by ids and concat in string
	 *
	 * @param  array  $ids
	 * @return string
	 **/
	function valuesToString($ids) {
		if ($this->type == EL_IS_PROP_MLIST) {
			return $this->_toString($ids);
		} elseif ($this->type == EL_IS_PROP_LIST) {
			return $this->_toString($ids ? $ids : $this->defaultIDs());
		}
		return !empty($ids[0]) ? $ids[0] : current($this->_opts);
	}

	/**
	 * return options
	 *
	 * @return array
	 **/
	function opts() {
		return $this->_opts;
	}

	/**
	 * override parent method
	 *
	 * @return array
	 **/
	function toArray() {
		$ret = parent::toArray();
		$ret['default'] = $this->defaultToString();
		$ret['opts'] = $this->_opts;
		// $ret['opts'] = $this->toString();
		return $ret;
	}

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
		$coll = parent::collection(true, true, $clause);
		if ($coll) {
			$db  = $this->_db();
			$sql = sprintf('SELECT id, p_id, value, is_default FROM %s WHERE p_id IN (%s) ORDER BY value', $this->tbpval, implode(',', array_keys($coll)));
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
   * Возвращает поля объекта в виде массива
   *
   * @return array
   */
  // function toArray()
  // {
  //   $ret = array(
  //               array('l'=>m('Name'), 'v'=>$this->name),
  //               array('l'=>m('Property type'), 'v'=>$GLOBALS['elIShopPropTypes'][$this->type][0]),
  //               );
  //   if ( EL_IS_PROP_LIST <= $this->type )
  //   {
  //     $ret[] = array('l'=>m('Value variants'), 'v'=>$this->valuesToString());
  //   }
  //   $ret[] = array('l'=>m('Default value'), 'v'=>$this->valuesToString(true));
  //   if ( EL_IS_PROP_MLIST == $this->type && $this->isHidden )
  //   {
  //     $ret[] = array('l'=>m('Display only in order form'), 'v'=>m('Yes'));
  //   }
  //   else
  //   {
  //     $ret[] = array('l'=>m('Display position in item card'), 'v'=>m($this->displayPos) );
  //     $ret[] = array('l'=>m('Display property name in item card'), 'v'=>$GLOBALS['yn'][$this->displayName]);
  //     $ret[] = array('l'=>m('Announce propery in items list'), 'v'=>$GLOBALS['yn'][$this->isAnnounced]);
  //   }
  //   return $ret;
  // }

  // function isDependAvailable()
  // {
  //   if ( EL_IS_PROP_MLIST == $this->type )
  //   {
  //     $db = & elSingleton::getObj('elDb');
  //     $sql = 'SELECT id FROM '.$this->_tb.' WHERE t_id=\''.$this->iTypeID.'\' AND type=\''.$this->type.'\' AND id<>\''.$this->ID.'\'';
  //     $db->query( $sql );
  //     return $db->numRows();
  //   }
  //   return false;
  // }


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



  // function getValuesByIDs( $IDs, $h=false )
  // {
  //   $ret = array();
  //   foreach ( $IDs as $ID )
  //   {
  //     if ( isset($this->values[$ID]) )
  //     {
  //       $ret[$ID] = (EL_IS_PROP_MLIST == $this->type && $h)
  //         ? elIShopParsePropValue($this->values[$ID][0])
  //         : $this->values[$ID][0];
  //     }
  //   }
  //   return $ret;
  // }


	function _editAndSave( $params=null )
	{
		$this->_makeForm( $params );

		if ( $this->_form->isSubmitAndValid() && $this->_validForm() )
		{
			elPrintR($this->_form->getValue());
			// $this->attr( $this->_form->getValue() );
			// return $this->save($params);
		}
	}

  /**
   * Создает объект-форму для редактирования своих полей
   *
   */
	function _makeForm($params = null) {
		parent::_makeForm();

		$itype = $this->_factory->create(EL_IS_ITYPE, $this->iTypeID);
		$maxNdx = count($itype->getProperties());
		$types = array(
		    EL_IS_PROP_STR   => m('String'),
		    EL_IS_PROP_TXT   => m('Text'),
		    EL_IS_PROP_LIST  => m('Values list (one value can be selected)'),
		    EL_IS_PROP_MLIST => m('Values list (any numbers of value can be selected)')
		    );
		$pos = array(
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

		$this->_form->setLabel(sprintf(m($label), $itype->name));
		$this->_form->add(new elText('name',                           m('Name'),                           $this->name));
		$this->_form->add(new elSelect('type',                         m('Property type'),                  $this->type, $types));
		$this->_form->add(new elText('values'.EL_IS_PROP_STR,          m('Default value'),                  '', array('maxlength' => '256', 'style' => 'width:100%')));
		$this->_form->add(new elTextArea('values'.EL_IS_PROP_TXT,      m('Default value'),                  '', array('rows' => 5)));
		$this->_form->add(new elVariantsList('values'.EL_IS_PROP_LIST,  m('Values variants')));
		$this->_form->add(new elVariantsList('values'.EL_IS_PROP_MLIST, m('Values variants')));
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
	return;


    if (EL_IS_PROP_MLIST == $this->type)
    {
      $db = & elSingleton::getObj('elDb');
      // $sql = 'SELECT id, name FROM '.$this->_tb.' WHERE t_id=\''.$this->iTypeID.'\' AND type=\''.$this->type.'\' AND id<>\''.$this->ID.'\' ORDER BY sort_ndx';
      $dList = array(0=>'none') + $db->queryToArray('SELECT id, name FROM '.$this->_tb.' WHERE t_id=\''.$this->iTypeID.'\' AND type=\''.$this->type.'\' AND id<>\''.$this->ID.'\' ORDER BY sort_ndx', 'id', 'name');
      $this->_form->add( new elSelect('depend_id', m('Depend on'), $this->dependID, $dList) );
    }


	}



  /**
   * Возвращает объект-элемент формы в зав-ти от собственного типа
   * Используется в объекте elIShopItem
   *
   * @param array $itemValues
   * @return object
   */
  // function getFormElement( $itemValues=null )
  // {
  //   //echo $this->type.'<br>'; elPrintR($itemValues);
  //   if ( $this->hasTextType() )
  //   {
  //     if ( isset($itemValues[0]) )
  //     {
  //       $value = $itemValues[0];
  //     }
  //     else
  //     {
  //       $cur   = current($this->values);
  //       $value = isset($cur[0]) ? $cur[0] : '';
  //     }
  //     $class = EL_IS_PROP_STR == $this->type ? 'elText' : 'elTextArea';
  //     return new $class('props['.$this->ID.']', $this->name, $value);
  //   }
  //   else
  //   {
  //     $opts = $default = array();
  //     foreach ( $this->values as $ID=>$value )
  //     {
  //       $opts[$ID] = elIShopParsePropValue($value[0]);
  //       if ( !empty($value[1]) )
  //       {
  //         $default[] = $ID;
  //       }
  //     }
  //     if ( EL_IS_PROP_LIST == $this->type )
  //     {
  //       $values = !empty($itemValues) ? current($itemValues) : current($default);
  //       $class = 'elSelect';
  //     }
  //     else
  //     {
  //       $values = !empty($itemValues) ? $itemValues : $default; //elPrintR($values);
  //       $class  = 'elMultiSelectList';
  //     }
  // 
  //     return new $class('props['.$this->ID.']', $this->name, $values, $opts, null, false, true, 'span');
  //   }
  // }


  /**
   * Удаляет поля объекта из всех таблиц в БД
   */
  // function delete()
  // {
  //   $db  = &elSingleton::getObj('elDb');
  //   $sql = 'UPDATE '.$this->_tb.' SET sort_ndx=sort_ndx-1 WHERE '
  //     .'t_id='.intval($this->iTypeID).' AND id<>\''.$this->ID.'\' '
  //     .'AND sort_ndx>='.intval($this->sortNdx);
  //   $db->query($sql);
  // 
  //   $sql = 'DELETE FROM '.$this->tbpdep.' WHERE id=\''.$this->iTypeID.'\' '
  //     .'AND (s_id=\''.$this->ID.'\' OR m_id=\''.$this->ID.'\')';
  //   $db->query($sql);
  //   $db->optimizeTable( $this->tbpdep );
  //   parent::delete( array($this->tbpval=>'p_id', $this->tbp2i=>'p_id') );
  // }


  /***************************************************/
  //                    PRIVATE                      //
  /***************************************************/



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
   * Возвращает значение свойства в виде строки или массива в зав-ти от требуемого типа свойства
   * используется при редактировании объекта
   *
   * @param int $type
   * @return misc
   */
  // function _getValueByType($type)
  // {
  //   if ( EL_IS_PROP_LIST <= $type )
  //   {
  //     return $this->values;
  //   }
  //   $cur = current($this->values);
  //   return isset($cur[0]) ? $cur[0] : '';
  // }

	/**
	 * Проверка формы редактирования полей объекта
	 * если его тип - список, то поле - варинты значений не должно быть пустым
	 *
	 * @return bool
	 */
	function _validForm() {
		$data = $this->_form->getValue(); //echo $data['type'];
		$src = $data['values'.$data['type']];
		return EL_IS_PROP_LIST <= $data['type'] && empty($src)
			? $this->_form->pushError('values'.$data['type'], 'Could not be empty')
			: true;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function _postSave() {
		$db = $this->_db();
		$indexes = $db->queryToArray('SELECT id, sort_ndx FROM '.$this->_tb.' WHERE t_id="'.$this->iTypeID.'" ORDER BY sort_ndx', 'id', 'sort_ndx');
		$i = 1;
		$s = sizeof($indexes);
		foreach ($indexes as $id=>$ndx) {
			if ($id != $this->ID) {
				if ($i == $this->sortNdx) {
					$i = $i == $s ? $s-1 : $i++;
				}
				if ($ndx != $i) {
					$db->query(sprintf('UPDATE %s SET sort_ndx=%d WHERE id=%d LIMIT 1', $this->_tb, $i, $id));
				}
			}
			$i++;
		}
		
		$data = $this->_form->getValue();
	    $src  = $data['values'.$this->type];
		$ins  = 'INSERT INTO %s (p_id, value, is_default) VALUES (%d, "%s", "%d")';
		$upd  = 'UPDATE %s SET value="%s", is_default="%d" WHERE id=%d';
		
		if ($this->isText()) {
			if (!empty($this->values)) {
				$db->query(sprintf('DELETE FROM %s WHERE p_id=%d AND id<>%d', $this->tbpval, $this->ID, key($this->values)));
			}
			$sql = empty($this->values) 
				? sprintf($ins, $this->tbpval, $this->ID, mysql_real_escape_string($src), 1) 
				: sprintf($upd, $this->tbpval, mysql_real_escape_string($src), 1, $this->ID);
			$db->query($sql);
		} else {
			$rm = array_diff(array_keys($this->values), array_keys($src));
			if (!empty($rm)) {
				$db->query(sprintf('DELETE FROM %s WHERE id IN (%s)', $this->tbpval, implode(',', $rm)));
			}
			// foreach ($src as )
			
		}
		$db->optimizeTable($this->tbpval);
		return true;
	}


	/*********************************************************/
	/***                     PRIVATE                       ***/
	/*********************************************************/
	


	/**
	 * fetch values by id and concat in string
	 *
	 * @param  array  $ids  list of values ids
	 * @return string
	 **/
	function _toString($ids) {
		$ret = array();
		foreach ($ids as $id) {
			if (isset($this->_opts[$id])) {
				$ret[] = $this->_opts[$id];
			}
		}
		return implode(', ', $ret);
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
