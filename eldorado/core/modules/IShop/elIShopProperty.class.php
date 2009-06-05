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

class elIShopProperty extends elMemberAttribute
{
  var $tb            = '';
  var $tbpval        = '';
  var $tbp2i         = '';
  var $ID            = 0;
  var $iTypeID       = 0;
  var $type          = EL_IS_PROP_STR;
  var $name          = '';
  var $displayPos    = 'middle';
  var $displayName   = 1;
  var $isHidden      = 0;
  var $isAnnounced   = 0;  
  var $isSearched    = 0;
  var $isCompared    = 0;
  var $sortNdx       = 1;
  var $values        = array();
  var $dependID      = 0;
  var $depend        = array();
  var $_dependLoad   = 0;


  /**
   * Извлекает поля объекта из БД
   * таблицы: поля объекта из el_ishop_{pageID}_prop,
   * варианты значений  из el_ishop_{pageID}_prop_value
   */
  function fetch()
  {
    if ( !parent::fetch() )
    {
      return false;
    }
    $db = &elSingleton::getObj('elDb');
    $db->query( 'SELECT id, value, is_default FROM '.$this->tbpval.' WHERE p_id='.$this->ID );
    while ($r = $db->nextRecord())
    {
      $this->values[$r['id']] = array($r['value'], $r['is_default']);
    }

    return true;
  }

  /**
   * Возвращает массив объектов своего класса
   */
  function getCollection($field=null, $orderBy=null, $offset=0, $limit=0, $where=null )
  {
    $coll = parent::getCollection($field, $orderBy, $offset, $limit, $where);
    if ( !empty($coll) )
    {
      $db     = &elSingleton::getObj('elDb');
      $IDsStr = implode(',', array_keys($coll));
      $db->query( 'SELECT id, p_id, is_default, value FROM '.$this->tbpval.' WHERE p_id IN ('.$IDsStr.') ORDER BY id' );
      while ($r = $db->nextRecord())
      {
        $coll[$r['p_id']]->values[$r['id']] = array($r['value'], $r['is_default']);
      }
    }
    return $coll;
  }

  /**
   * Возвращает поля объекта в виде массива
   *
   * @return array
   */
  function toArray()
  {
    $ret = array(
                array('l'=>m('Name'), 'v'=>$this->name),
                array('l'=>m('Property type'), 'v'=>$GLOBALS['elIShopPropTypes'][$this->type][0]),
                );
    if ( EL_IS_PROP_LIST <= $this->type )
    {
      $ret[] = array('l'=>m('Value variants'), 'v'=>$this->valuesToString());
    }
    $ret[] = array('l'=>m('Default value'), 'v'=>$this->valuesToString(true));
    if ( EL_IS_PROP_MLIST == $this->type && $this->isHidden )
    {
      $ret[] = array('l'=>m('Display only in order form'), 'v'=>m('Yes'));
    }
    else
    {
      $ret[] = array('l'=>m('Display position in item card'), 'v'=>m($this->displayPos) );
      $ret[] = array('l'=>m('Display property name in item card'), 'v'=>$GLOBALS['yn'][$this->displayName]);
      $ret[] = array('l'=>m('Announce propery in items list'), 'v'=>$GLOBALS['yn'][$this->isAnnounced]);
    }
    return $ret;
  }

  function isDependAvailable()
  {
    if ( EL_IS_PROP_MLIST == $this->type )
    {
      $db = & elSingleton::getObj('elDb');
      $sql = 'SELECT id FROM '.$this->tb.' WHERE t_id=\''.$this->iTypeID.'\' AND type=\''.$this->type.'\' AND id<>\''.$this->ID.'\'';
      $db->query( $sql );
      return $db->numRows();
    }
    return false;
  }


  function inDepend()
  {
    if ( !$this->isDependAvailable() )
    {
      return false;
    }
    if ( $this->dependID )
    {
      return true;
    }
    $db = & elSingleton::getObj('elDb');
    $db->query('SELECT id FROM '.$this->tbpdep.' WHERE s_id='.$this->ID.' OR m_id='.$this->ID);
    return $db->numRows();
  }

  function editDependance( $iTypeName, $master )
  {
    parent::makeForm();
    $rnd = & elSingleton::getObj('elTplGridFormRenderer');
    $rnd->addButton( new elSubmit('submit', '', m('Submit')) );
    $rnd->addButton( new elReset('reset', '', m('Drop')) );
    $this->form->setRenderer( $rnd );
    $l = sprintf(m('Item type: %s. Edit properties dependance'), $iTypeName);
    $this->form->setLabel($l);
    $this->form->add( new elCData('myname', $this->name), array('class'=>'formSubheader') );
    $this->form->add( new elCData('two', $master->name), array('class'=>'formSubheader') );

    $mVals = array();
    foreach ( $master->values as $ID=>$v)
    {
      $mVals[$ID] = elIShopParsePropValue($v[0]);
    }
    $dep = $this->getDependance(); //elPrintR( $dep );
    foreach ( $this->values as $ID=>$v)
    {
      $this->form->add( new elCData('s_'.$ID, elIShopParsePropValue($v[0])) );
      //$vals = !empty($dep[$ID]) ? $dep[$ID] : null;
      $this->form->add( new elMultiSelectList('mvals['.$ID.']', '', !empty($dep[$ID]) ? $dep[$ID] : null, $mVals, null, false, true, 'span') );
    }

    if ( $this->form->isSubmitAndValid() )
    {
      $data = $this->form->getValue(); //elPrintR($data);
      $db   = & elSingleton::getObj('elDb');
      $db->query( 'DELETE FROM '.$this->tbpdep.' WHERE s_id='.$this->ID );
      $db->optimizeTable( $this->tbpdep );
      $db->prepare('INSERT INTO '.$this->tbpdep.' (s_id, s_value, m_id, m_value) VALUES ', '(%d, %d, %d, %d)');
      foreach ( $data['mvals'] as $sVal=>$mData )
      {
        if ( !empty($mData) )
        {
          $exec = 1;
          foreach ( $mData as $mVal )
          {
            $db->prepareData( array($this->ID, $sVal, $master->ID, $mVal) ) ;
          }
        }
      }
      if ( !empty( $exec) )
      {
        $db->execute();
      }
      return true;
    }

    return false;
  }

  function getDependance()
  {
    if ( !$this->_dependLoad )
    {
      $this->_dependLoad = 1;
      if ( EL_IS_PROP_MLIST == $this->type || $this->isDependAvailable() )
      {
        $db = & elSingleton::getObj('elDb');
        $db->query( 'SELECT s_value, m_value FROM '.$this->tbpdep.' WHERE s_id='.$this->ID.' AND m_id='.$this->dependID );
        while ( $r = $db->nextRecord() )
        {
//          if ( empty($this->depend[$r['s_value']]) )
//          {
//            $this->depend[$r['s_value']] = array();
//          }
          $this->depend[$r['s_value']][] = $r['m_value'];
        }
      }
    }
    return $this->depend;
  }



  function getValuesByIDs( $IDs, $h=false )
  {
    $ret = array();
    foreach ( $IDs as $ID )
    {
      if ( isset($this->values[$ID]) )
      {
        $ret[$ID] = (EL_IS_PROP_MLIST == $this->type && $h)
          ? elIShopParsePropValue($this->values[$ID][0])
          : $this->values[$ID][0];
      }
    }
    return $ret;
  }


  /**
   * Создает объект-форму для редактирования своих полей
   *
   */
  function makeForm( $params )
  {
    parent::makeForm();

    if ($this->ID)
    {
      $lable      = 'Edit propery for type "%s"';
      $maxSortNdx = $params['maxSortNdx'];
    }
    else
    {
      $lable         = 'Create new propery for type "%s"';
      $maxSortNdx    = $params['maxSortNdx'] + 1;
      $this->sortNdx = $maxSortNdx;
    }

    $this->form->setLabel( sprintf( m($lable), $params['itName'] ) );
    $typesList = & new elSelect('type', m('Property type'), $this->type, null, array('onChange'=>'checkISPropFormAdmin()'));
    $this->form->add( $typesList );
    $this->form->add( new elText('name', m('Name'), $this->name) );


    $attrs = array('rowAttrs'=>'style="display:none"');
    $opts = array();
    foreach ( $GLOBALS['elIShopPropTypes'] as $type=>$v )
    {
      $opts[$type] = $v[0];
      $class       = $v[1];
      $this->form->add( new $class('values'.$type, m('Deault value'), $this->_getValueByType($type), $v[2]), $attrs );
    }
    $typesList->add( $opts );

    $this->form->add( new elSelect('sort_ndx',     m('Order position'), $this->sortNdx, range(1, $maxSortNdx), null, false, false) );
    $this->form->add( new elSelect('display_pos',  m('Display position in item card'), $this->displayPos, $GLOBALS['elIShopPropPos']) );
    $this->form->add( new elSelect('display_name', m('Display property name in item card'), $this->displayName, $GLOBALS['yn'] ) );
    $this->form->add( new elSelect('is_hidden',    m('Display only in order form'),         $this->isHidden, $GLOBALS['yn'] ) );
    $this->form->add( new elSelect('is_announced', m('Announce propery in items list'), $this->isAnnounced, $GLOBALS['yn'] ) );

    if (EL_IS_PROP_MLIST == $this->type)
    {
      $db = & elSingleton::getObj('elDb');
      $sql = $sql = 'SELECT id, name FROM '.$this->tb
        .' WHERE t_id=\''.$this->iTypeID.'\' AND type=\''.$this->type.'\' AND id<>\''.$this->ID.'\' ORDER BY sort_ndx';
      $dList = array(0=>'none') + $db->queryToArray($sql, 'id', 'name');
      $this->form->add( new elSelect('depend_id', m('Depend on'), $this->dependID, $dList) );
    }

    $this->form->setRequired('name');

    elAddJs('checkISPropFormAdmin()', EL_JS_SRC_ONLOAD);

  }

  /**
   * Возвращает значения или значения по умолчанию в виде строки
   * для типа multi list заменяет конструкции range(begin end step) на human readable
   *
   * @param  bool $onlyDef
   * @return string
   */
  function valuesToString( $onlyDef=false )
  {
    if ( $this->hasTextType() )
    {
      $val = current($this->values);
      return $onlyDef && isset($val[0]) ? $val[0] : m('No');
    }
    else
    {
      $ret = array();
      foreach ( $this->values as $v )
      {
        if ( !$onlyDef || ($onlyDef && !empty($v[1])))
        {
          $ret[] = EL_IS_PROP_LIST == $this->type ? $v[0] : elIShopParsePropValue($v[0]);
        }
      }
      return !empty($ret) ? implode(', ', $ret) : m('No');
    }
  }

  /**
   * Возвращает объект-элемент формы в зав-ти от собственного типа
   * Используется в объекте elIShopItem
   *
   * @param array $itemValues
   * @return object
   */
  function getFormElement( $itemValues=null )
  {
    //echo $this->type.'<br>'; elPrintR($itemValues);
    if ( $this->hasTextType() )
    {
      if ( isset($itemValues[0]) )
      {
        $value = $itemValues[0];
      }
      else
      {
        $cur   = current($this->values);
        $value = isset($cur[0]) ? $cur[0] : '';
      }
      $class = EL_IS_PROP_STR == $this->type ? 'elText' : 'elTextArea';
      return new $class('props['.$this->ID.']', $this->name, $value);
    }
    else
    {
      $opts = $default = array();
      foreach ( $this->values as $ID=>$value )
      {
        $opts[$ID] = elIShopParsePropValue($value[0]);
        if ( !empty($value[1]) )
        {
          $default[] = $ID;
        }
      }
      if ( EL_IS_PROP_LIST == $this->type )
      {
        $values = !empty($itemValues) ? current($itemValues) : current($default);
        $class = 'elSelect';
      }
      else
      {
        $values = !empty($itemValues) ? $itemValues : $default; //elPrintR($values);
        $class  = 'elMultiSelectList';
      }

      return new $class('props['.$this->ID.']', $this->name, $values, $opts, null, false, true, 'span');
    }
  }


  /**
   * Удаляет поля объекта из всех таблиц в БД
   */
  function delete()
  {
    $db  = &elSingleton::getObj('elDb');
    $sql = 'UPDATE '.$this->tb.' SET sort_ndx=sort_ndx-1 WHERE '
      .'t_id='.intval($this->iTypeID).' AND id<>\''.$this->ID.'\' '
      .'AND sort_ndx>='.intval($this->sortNdx);
    $db->query($sql);

    $sql = 'DELETE FROM '.$this->tbpdep.' WHERE id=\''.$this->iTypeID.'\' '
      .'AND (s_id=\''.$this->ID.'\' OR m_id=\''.$this->ID.'\')';
    $db->query($sql);
    $db->optimizeTable( $this->tbpdep );
    parent::delete( array($this->tbpval=>'p_id', $this->tbp2i=>'p_id') );
  }

  /**
   * проверка типа объекта- текстовый тип или нет
   *
   * @return bool
   */
  function hasTextType()
  {
    return EL_IS_PROP_LIST > $this->type;
  }

  /***************************************************/
  //                    PRIVATE                      //
  /***************************************************/

  function _initMapping()
  {
    $map = array(
      'id'           => 'ID',
      't_id'         => 'iTypeID',
      'type'         => 'type',
      'depend_id'    => 'dependID',
      'name'         => 'name',
      'display_pos'  => 'displayPos',
      'display_name' => 'displayName',
      'is_hidden' => 'isHidden',
      'is_announced' => 'isAnnounced',
      'is_searched'  => 'isSearched',
      'is_compared'  => 'isCompared',
      'sort_ndx'     => 'sortNdx'
      );
    return $map;
  }

  /**
   * Очистка полей объекта
   */
  function cleanAttrs()
	{
		$mapping = $this->memberMapping();
		foreach ( $mapping as $attr=>$member )
		{
		  if ('type' != $attr && 'display_pos' != $attr)
		  {
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
  function _getValueByType($type)
  {
    if ( EL_IS_PROP_LIST <= $type )
    {
      return $this->values;
    }
    $cur = current($this->values);
    return isset($cur[0]) ? $cur[0] : '';
  }

	/**
	 * Проверка формы редактирования полей объекта
	 * если его тип - список, то поле - варинты значений не должно быть пустым
	 *
	 * @return bool
	 */
  function _validForm()
  {
    $data = $this->form->getValue(); //echo $data['type'];
    $src = $data['values'.$data['type']];
    return EL_IS_PROP_LIST <= $data['type'] && empty($src)
      ? $this->form->pushError('values'.$data['type'], 'Could not be empty')
      : true;
  }

  /**
   * После сохранения основных полей
   * обновляет значения индексов сортировки для других  принадлежащих тому же elIShopItemType
   * сохраняет варианты значний
   *
   * @return bool
   */
  function _postSave()
  {
    $db  = &elSingleton::getObj('elDb');
    $sql = 'UPDATE '.$this->tb.' SET sort_ndx=sort_ndx+1 WHERE '
      .'t_id='.intval($this->iTypeID).' AND id<>\''.$this->ID.'\' '
      .'AND sort_ndx>='.intval($this->sortNdx);
    $db->query($sql);

    $data   = $this->form->getValue();
    $src    = $data['values'.$this->type];
    $tplIns = 'INSERT INTO '.$this->tbpval.' (p_id, value, is_default) VALUES (\''.$this->ID.'\', \'%s\', \'%d\')';
    $tplUpd = 'UPDATE '.$this->tbpval.' SET value=\'%s\', is_default=\'%d\' WHERE id=\'%d\'';

    if ( $this->hasTextType() )
    {
      if (empty($this->values))
      {
        $sql = sprintf( $tplIns, mysql_real_escape_string($src), 1); //echo $sql.'<br>';
      }
      else
      {
        $ID  = current( array_keys($this->values) );
        $sql = sprintf($tplUpd, mysql_real_escape_string($src), 1, $ID); //echo $sql.'<br>';
        $db->query('DELETE FROM '.$this->tbpval.' WHERE p_id=\''.$this->ID.'\' AND id<>\''.$ID.'\''); //echo $sql.'<br>';
        $db->optimizeTable($this->tbpval);
      }
      $db->query($sql);
    }
    else
    {
      $rm = array_diff( array_keys($this->values), array_keys($src) );
      if ( !empty($rm) )
      {
        $db->query('DELETE FROM '.$this->tbpval.' WHERE id IN ('.implode(',', $rm).')');
        $db->optimizeTable($this->tbpval);
      }
      foreach ($src as $ID=>$v)
      {
        $sql = empty($this->values[$ID])
          ? sprintf($tplIns, mysql_real_escape_string($v[0]), intval(!empty($v[1])))
          : sprintf($tplUpd, mysql_real_escape_string($v[0]), intval(!empty($v[1])), $ID);
        $db->query($sql);
      }
    }
    return true;
  }

}
?>