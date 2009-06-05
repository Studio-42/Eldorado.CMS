<?php

include_once 'elCatalogItem.class.php';

class elIShopItem extends elCatalogItem
{
  var $mnfNfo     = EL_IS_USE_MNF;
  var $tbmnf      = '';
  var $tbp2i      = '';
  var $tbi2c      = '';
  var $tb         = '';
  var $ID         = 0;
  var $typeID     = 0;
  var $mnfID      = 0;
  var $tmID       = 0;
  var $code       = '';
  var $name       = '';
  var $announce   = '';
  var $content    = '';
  var $price      = 0;
  var $img        = '';
  var $crtime     = 0;
  var $mtime      = 0;
  var $propVals   = array();
  var $type       = null;
  var $mnf        = '';
  var $mnfCountry = '';
  var $tm         = '';
  var $_sortVars  = array(
    EL_IS_SORT_NAME  => 'name',
    EL_IS_SORT_CODE  => 'code, name',
    EL_IS_SORT_PRICE => 'price DESC, name',
    EL_IS_SORT_TIME  => 'crtime DESC, name'
    );
  var $_objName = 'Good';
  
  /**
   * Извлекает поля объкта из БД
   *
   * @return bool
   */
  function fetch()
  {
    if ( !$this->ID )
    {
      return false;
    }
    // в зав-ти от настроек прозв/торг марка ($this->mnfNfo) извлекаем произв
    // - если только произв - по id из табл товара
    // иначе по id из табд торговых марок
    $sql = 'SELECT '.$this->listAttrsToStr('i').', m.name AS mnf, m.country, t.name AS tm '
      .'FROM '.$this->tb.' AS i LEFT JOIN '.$this->tbtm.' AS t ON i.tm_id=t.id '
      .'LEFT JOIN '.$this->tbmnf.' AS m ON IF( '.intval(EL_IS_USE_MNF==$this->mnfNfo).' OR i.tm_id=0, i.mnf_id=m.id, t.mnf_id=m.id) '
      .'WHERE i.id=\''.intval($this->ID).'\' ' ;
    $db = & elSingleton::getObj('elDb');
    $db->query($sql);
    if ( !$db->numRows() )
    {
        return false;
    }
    $r = $db->nextRecord(); 
    $this->setAttrs( $r );
    $this->mnf        = $r['mnf'];
    $this->mnfCountry = $r['country'];
    $this->tm         = $r['tm'];

    if ( !empty($this->typeID) )
    {
      $factory = & elSingleton::getObj('elIShopFactory');
      $this->setType( $factory->getItemType($this->typeID) );
    }

    $sql = 'SELECT ip.p_id, IF( p.type<3, ip.value, ip.pv_id) AS value '
      .'FROM '.$this->tbp2i.' AS ip, '.$this->tbp.' AS p '
      .'WHERE ip.i_id='.$this->ID.' AND p.id=ip.p_id';

    $db->query($sql);
    while ( $r = $db->nextRecord() )
    {
      $this->propVals[$r['p_id']][] = $r['value'];
    }
    //elPrintR($this);
    return true;
  }

  /**
   * Возвращает массив объектов-итемов в данной категории
   *
   * @param int $catID
   * @param int $sortID
   * @param int $offset
   * @param int $step
   * @return array
   */
  function getByCategory($catID, $sortID, $offset, $step)
  {
    $items = array();
    $db    = & elSingleton::getObj('elDb');

    $sql = 'SELECT '.$this->listAttrsToStr('i').', m.name AS mnf, m.country, t.name AS tm '
        .' FROM '.$this->tbi2c.' AS i2c, '.$this->tb.' AS i '
        .' LEFT JOIN '.$this->tbtm.' AS t ON i.tm_id=t.id '
        .' LEFT JOIN '.$this->tbmnf.' AS m ON IF('.intval(EL_IS_USE_MNF==$this->mnfNfo).' OR i.tm_id=0, i.mnf_id=m.id, t.mnf_id=m.id) '
        .' WHERE i2c.c_id=\''.$catID.'\' AND i.id=i2c.i_id '
        .' ORDER BY '.$this->_getOrderBy($sortID).' LIMIT '.$offset.', '.$step;

    $db->query( $sql );
    $factory = & elSingleton::getObj('elIShopFactory');
    while( $row = $db->nextRecord() )
    {
      $items[$row['id']]             = $this->copy($row);
      $items[$row['id']]->mnf        = $row['mnf'];
      $items[$row['id']]->mnfCountry = $row['country'];
      $items[$row['id']]->tm         = $row['tm'];
      $items[$row['id']]->setType( $factory->getItemType( $row['type_id'] ) );
    }

    if ( !empty($items) )
    {
      $sql = 'SELECT ip.i_id, ip.p_id, IF( p.type<3, ip.value, ip.pv_id) AS value '
          .'FROM '.$this->tbp2i.' AS ip, '.$this->tbp.' AS p '
          .'WHERE ip.i_id IN('.implode(',', array_keys($items)).') AND p.id=ip.p_id';
      $db->query( $sql );
      while ($r = $db->nextRecord() )
      {
        $items[$r['i_id']]->propVals[$r['p_id']][] = $r['value'];
      }
    }
    return $items;
  }

  
  /**
   * Возвращает массив объектов-итемов полученный в рез-те поиска
   *
   * @param string $tbr - временная таблица с ID итемов
   * @return array
  */
  function getBySearchResult( $tbr )
  {
    $items = array();
    $db    = & elSingleton::getObj('elDb');

    $sql = 'SELECT '.$this->listAttrsToStr('i').', m.name AS mnf, m.country, t.name AS tm '
        .' FROM '.$tbr.' AS r, '.$this->tb.' AS i '
        .' LEFT JOIN '.$this->tbtm.' AS t ON i.tm_id=t.id '
        .' LEFT JOIN '.$this->tbmnf.' AS m ON IF('.intval(EL_IS_USE_MNF==$this->mnfNfo).' OR i.tm_id=0, i.mnf_id=m.id, t.mnf_id=m.id) '
        .' WHERE i.id=r.id  '
        .' ORDER BY mnf, name';

    $db->query( $sql );
    $factory = & elSingleton::getObj('elIShopFactory');
    while( $row = $db->nextRecord() )
    {
      $items[$row['id']]             = $this->copy($row);
      $items[$row['id']]->mnf        = $row['mnf'];
      $items[$row['id']]->mnfCountry = $row['country'];
      $items[$row['id']]->tm         = $row['tm'];
      $items[$row['id']]->setType( $factory->getItemType( $row['type_id'] ) );
    }

    if ( !empty($items) )
    {
      $sql = 'SELECT ip.i_id, ip.p_id, IF( p.type<3, ip.value, ip.pv_id) AS value '
          .'FROM '.$this->tbp2i.' AS ip, '.$this->tbp.' AS p '
          .'WHERE ip.i_id IN('.implode(',', array_keys($items)).') AND p.id=ip.p_id';
      $db->query( $sql );

      while ($r = $db->nextRecord() )
      {
        $items[$r['i_id']]->propVals[$r['p_id']][] = $r['value'];
      }
    }
    return $items;
  }

  /**
   * Возвращает массив свойств сгруппированных по позиции в карточке товара
   * набор свойств зависит от типа товара
   *
   * @return array
   */
  function getProperties()
  {
    $ret   = array('top'=>array(), 'middle'=>array(), 'table'=>array(), 'bottom'=>array());
    $order = array();
    foreach ($this->type->props as $p)
    {
      if (!empty($this->propVals[$p->ID]))
      {
        $name = $p->displayName || 'table'==$p->displayPos ? $p->name : '';
        if ( EL_IS_PROP_MLIST == $p->type )
        {

          $order[] = array(
            'id'     => $p->ID,
            'name'   => $p->name,
            'value'  => $this->_propertyToArray($p->ID),
            'depend' => $p->inDepend()
            );
        }
		if ( EL_IS_PROP_MLIST != $p->type  || !$p->isHidden )
		{
		  $ret[$p->displayPos][$p->ID] = array('name'=>$name, 'value'=>$this->_propertyToString($p->ID));
		}
      }
    }
    return array($ret, $order);
  }

  function getPropName($pID)
  {
	return isset($this->type->props[$pID]) ? $this->type->props[$pID]->name : '';
  }

  /**
   * Возвращает массив свойств отмеченных для показа в списке товаров (аннонс)
   * набор свойств зависит от типа товара
   *
   * @return array
   */
  function getAnnProperties()
  {
    $ret = array();
    foreach ($this->type->props as $p)
    {
      if (!$p->isHidden && $p->isAnnounced && !empty($this->propVals[$p->ID]))
      {
        $ret[] = array('name'=>$p->name, 'value'=>$this->_propertyToString($p->ID));
      }
    }
    return $ret;
  }

  function _findValue($pID, $val)
  {
    //elPrintR($this->type->props[$pID]->values);
    $reg = '/range\(([0-9\-\.]+)\,?\s*([0-9\-\.]+)\,?\s*([0-9\-\.]+)\s*\)\s*(exclude\((.+)\))?.*/si';
    foreach ( $this->type->props[$pID]->values as $vID=>$v )
    {
      if ( !preg_match($reg, $v[0], $m) )
      {
        if ( $val == $v[0])
        {
          return $vID;
        }
      }
      else
      {
        $excl = !empty($m[5]) ? array_map('trim', explode(',', $m[5])) : null;
        $range = elRange($m[1], $m[2], $m[3], $excl);
        if ( in_array($val, $range) )
        {
          return $vID;
        }
      }
    }
    return 0;
  }

  function getDependValues($propID, $propVal)
  {
    $vID = $this->_findValue($propID, $propVal); //echo 'ID='.$vID.'<br>';
    $myVals = array();


    $ret = array(); $tmp = array(); //echo $propID.' '.$propVal;
    $db = &elSingleton::getObj('elDb');
    $sql = 'SELECT m_id, m_value FROM '.$this->tbpdep.' WHERE s_id='.$propID.' AND s_value='.$vID;
    $db->query($sql);
    while ($r = $db->nextRecord())
    {
      $tmp[$r['m_id']][] = $r['m_value'];
    }
    $tmp1 = $db->queryToArray($sql, 'm_id', 'm_value');
    $sql = 'SELECT s_id, s_value FROM '.$this->tbpdep.' WHERE m_id='.$propID.' AND m_value='.$vID; //echo $sql;
    $db->query($sql);

    while ($r = $db->nextRecord())
    {
      $tmp[$r['s_id']][] = $r['s_value'];
    }

    $reg = '/range\(([0-9\-\.]+)\,?\s*([0-9\-\.]+)\,?\s*([0-9\-\.]+)\s*\)\s*(exclude\((.+)\))?.*/si';
    foreach ($tmp as $pID=>$vIDs)
    {
      $vals = $this->type->props[$pID]->getValuesByIDs($vIDs); //elPrintR($vals);
      foreach ( $vals as $v )
      {
        if ( !preg_match($reg, $v, $m) )
        {
          $ret[$pID][] = $v;
        }
        else
        {
          $excl = !empty($m[5]) ? array_map('trim', explode(',', $m[5])) : null;
          $range = elRange($m[1], $m[2], $m[3], $excl);
          $ret[$pID] = array_merge_recursive($ret[$pID], $range);
        }
      }
    }
    $xml = '';
    $xml .= "<response>\n";
		$xml .= "<method>updateProps</method>\n";
		$xml .= "<result>\n";
    foreach ( $ret as $pID=>$pVals )
    {
      $xml .= "<property>\n";
      $xml .= "<id>".$pID."</id>";
      $xml .= "<value>".implode("</value>\n<value>", $pVals)."</value>\n";
      $xml .= "</property>\n";
    }
    $xml .= "</result>\n";
		$xml .= "</response>\n";
    //$sql = 'SELECT DISTINCT s_id, m_id, s_value, m_value FROM '.$this->tbpdep.' WHERE s_id='.$propID.' OR m_id='.$propID;
    //elPrintR( $ret);
    return $xml;
  }

   /**
   * устанавливает аттрибут - объект тип товара
   *
   * @param object $type
   */
  function setType(&$type)
  {
    $this->type   = &$type;
    $this->typeID = $type->ID;
  }

  /**
   * Создает объект-форму для редактирования товара
   *
   * @param array $parents
   */
  function makeForm( $params )
  {
    $label      = !$this->getUniqAttr() ? 'Create object "%s"' : 'Edit object "%s"';
    $this->form = & elSingleton::getObj( 'elForm', 'mf',  sprintf( m($label), m($this->_objName))  );
    $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );

    $this->form->add( new elCData2('t', m('Type'), $this->type->name) );

    if ( empty($params['parents']) )
    {
      $params['parents'] = array(1);
    }
    
    if ( false == ($pIDs = $this->_getParents()) )
    {
      $pIDs = array( $params['catID'] );
    }
    $this->form->add( new elMultiSelectList('pids', m('Parent categories'), $pIDs, $params['parents']) );


    if ( EL_IS_USE_MNF == $this->mnfNfo )
    {
      $this->form->add( new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $this->_mnfsList()) );
    }
    elseif ( 0 <> $this->mnfNfo )
    {
      $sel     = & new elExtSelect('tm_id', m('Manufacturers / Trade marks'), $this->tmID);
      $mnfsTms = $this->_mnfsTmsList();
      foreach ( $mnfsTms as $g )
      {
        $gid = $sel->addGroup( $g[0], $g[1] );
      }
      $this->form->add( $sel );
    }
    $textAttrs = array('style'=>'width:100%;');
    $this->form->add( new elText('code',  m('Code/Articul'),  $this->code,     $textAttrs) );
    $this->form->add( new elText('name',  m('Name'),          $this->name,     $textAttrs) );
    $this->form->add( new elText('price', m('Price'),         $this->price,    $textAttrs) );
    $this->form->add( new elEditor('announce', m('Announce'), $this->announce, array('rows'=>'35')) );
    $this->form->add( new elEditor('content',  m('Content'),  $this->content) );
    $this->form->setRequired('pids[]');
    $this->form->setRequired('code');
    $this->form->setRequired('name');
    foreach ($this->type->props as $ID=>$p)
    {
      $this->form->add( $p->getFormElement( $this->_getPropVal($p->ID) ) );//$this->_getPropValue($p->ID)) );
    }
  }


  function _mnfsTmsList()
  {
    $ret = array();
    $db = & elSingleton::getObj('elDb');
    $sql = 'SELECT  m.name AS mnf, t.id, t.mnf_id, t.name '
      .'FROM '.$this->tbmnf.' AS m, '.$this->tbtm.' AS t '
      .'WHERE t.mnf_id=m.id ORDER BY m.name, t.name';
    $db->query( $sql );
    while ( $r = $db->nextRecord() )
    {
      if ( empty($ret[$r['mnf_id']]))
      {
        $ret[$r['mnf_id']] = array($r['mnf'], array());
      }
      $ret[$r['mnf_id']][1][$r['id']] = $r['name'];
    }
    return $ret;
  }

  /**
   * Удаляет данные объекта из таблиц товаров, значений свойств и привязки к категориям
   * tb, tbp2i tbi2c
   *
   */
  function delete()
  {
    parent::delete( array($this->tbp2i=>'i_id', $this->tbi2c=>'i_id') );
  }

  function removeItems( $catID )
  {
    $db = & $this->_getDb();
    $sql = 'SELECT id, CONCAT(code, " ", name) AS name  FROM '.$this->tb.', '.$this->tbi2c
    	  .' WHERE c_id=\''.$catID.'\' AND id=i_id ORDER BY '.$this->_getOrderBy($sortID);
    $items = $db->queryToArray($sql, 'id', 'name');
    $this->form = & elSingleton::getObj( 'elForm', 'mf',  m('Select documents to remove')  );
	$this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
	
    $this->form->add( new elCheckBoxesGroup('items', '', null, $items) );

    if ( $this->form->isSubmitAndValid() )
    {
      $data = $this->form->getValue();
      if ( !empty($data['items']) )
      {
        $iIDs = '('.implode(',', $data['items']).')';
        $db->query('DELETE FROM '.$this->tb.'    WHERE id IN '.$iIDs);
        $db->query('DELETE FROM '.$this->tbi2c.' WHERE i_id IN '.$iIDs);
//        echo 'DELETE FROM '.$this->tbp2i.' WHERE i_id IN '.$iIDs;
        $db->query('DELETE FROM '.$this->tbp2i.' WHERE i_id IN '.$iIDs);
        $db->optimizeTable($this->tb);
        $db->optimizeTable($this->tbi2c);
        $db->optimizeTable($this->tbp2i);
      }
      return true;
    }
    return false;
  }

  function changeImage($lSize, $iSize)
  {
    $this->_makeImageForm();
    if ( !$this->form->isSubmitAndValid() )
    {
      return false;
    }

    $sqlTpl = 'UPDATE '.$this->tb.' SET img="%s" WHERE id="'.$this->ID.'"';
    $data   = $this->form->getValue(); //elPrintR($data);
    if ( empty($data['imgURL']) )
    {
      $imgPath = '';
      if ( !empty($data['rm']) && $this->img )
      {
        list($tmbl, $tmbc) = $this->_getTmbNames($this->img);
        @unlink('.'.$tmbl);
        @unlink('.'.$tmbc);
      }
    }
    else
    {
      if (30 > $lSize)
      {
        $lSize = 120;
      }
      if (30 > $iSize)
      {
        $lSize = 250;
      }
      $imgPath = str_replace(EL_BASE_URL, '', $data['imgURL']);
      $imager  = & elSingleton::getObj('elImager');
      list($tmbl, $tmbc) = $this->_getTmbNames($imgPath);

	    if (!$imager->copyResized('.'.$imgPath, '.'.$tmbl, $lSize, $iSize))
	    {
	     elThrow(E_USER_WARNING, $imager->getError());
	    }
	    if (!$imager->copyResized('.'.$imgPath, '.'.$tmbc, $iSize, $iSize))
	    {
	     elThrow(E_USER_WARNING, $imager->getError());
	    }

    }
    $db = & elSingleton::getObj('elDb');
    $db->query( sprintf($sqlTpl, $imgPath) );
    return true;
  }

  function getTmbURL($tmbType='l')
  {
    if ( $this->img )
    {
      list($tmbl, $tmbc) = $this->_getTmbNames($this->img);

      return EL_BASE_URL.('c' == $tmbType ? $tmbc : $tmbl);
    }
  }

  function _getTmbNames($imgPath)
  {
    $imgName = baseName($imgPath);
    $imgDir  = dirname($imgPath).'/';
    return array($imgDir.'tmbl-'.$imgName, $imgDir.'tmbc-'.$imgName);
  }

  function _makeImageForm()
  {
    	$this->form = & elSingleton::getObj( 'elForm', 'mf',  sprintf( m('Image for "%s"'), $this->name )  );
		$this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
    	$attrs = array('onClick'=>'return popUp(\''.EL_BASE_URL.'/'.EL_URL_POPUP.'/__fm__/'.'\', 500, 400)', 'class'=>'form-submit');

	  $this->form->add( new elSubmit('s', '', m('Select or upload image file'), $attrs), array('cellAttrs'=>'colspan="2"') );
		//$this->form->add( new elCData('c1', m('Select or upload image file')), array('cellAttrs'=>'colspan="2"'));
	  if ( !$this->img )
	  {
	    $imgURL = '';
	    $imgHtml = m('No image');
	  }
	  else
	  {
	    $imgURL  = EL_BASE_URL.$this->img; //echo $imgURL;
	    $imgHtml = '<img src="'.$imgURL.'" />';
	    $this->form->add( new elCheckBox('rm', m('Delete image'), 1, array('onClick'=>'elISUpdatePreview(this.checked?1:0);')));
	  }
	  $this->form->add( new elHidden('imgURL',     '', $imgURL) );
	  $this->form->add( new elHidden('imgURLSave', '', $imgURL) );
	  $this->form->add( new elCData('i', '<b>'.m('Preview').'</b><br /><div id="imgPrew" align="center">'.$imgHtml.'</div>'));

    $js = "
      function SetUrl(URL){  elISUpdatePreview(URL); }

      function elISUpdatePreview(URL)
      {
        var h    = document.getElementById('imgURL');
        var hs   = document.getElementById('imgURLSave');
        var prev = document.getElementById('imgPrew');
        if (URL == 0)
        {
          h.value = hs.value;
          var i = document.createElement('img');
          i.src=h.value;
        }
        else if (URL == 1)
        {
          h.value = '';
          i = document.createTextNode('".m('No image')."');
        }
        else
        {
          h.value = hs.value = URL;
          var i = document.createElement('img');
          i.src=URL;
        }
        prev.replaceChild(i, prev.firstChild);
      }
    ";
    elAddJs($js);
  }

  /***********************************************************/
  //                      PRIVATE                            //
  /***********************************************************/

  /**
   * возвращает значение свойства товара в виде строки
   *
   * @param int $pID
   * @return array
   */
  function _propertyToString($pID)
  {
    $clue = ', ';
    return ( $this->type->props[$pID]->hasTextType() )
      ? $this->propVals[$pID][0]
      : implode($clue, $this->type->props[$pID]->getValuesByIDs($this->propVals[$pID], true));
  }

  /**
   * возвращает значение свойства товара с типом multi-list в виде массива
   * заменяя конструкции вида range(begin end step) exclude(val1 val2..)
   * в соответствующие диапазоны значений
   * Используется для выбора параметров товара при заказе
   *
   * @param int $pID
   * @return array
   */
  function _propertyToArray($pID)
  {
    $raw = $this->type->props[$pID]->getValuesByIDs($this->propVals[$pID]);
     //elPrintR($this->propVals[$pID]);
     //echo 'raw='; elPrintR($raw);
    $enabled = $this->propVals[$pID];
    if ( $this->type->props[$pID]->dependID )
    {
      $mID = $this->type->props[$pID]->dependID;
      //echo $pID.' depend on '.$mID;
      $mVal = $this->propVals[$mID][0]; //echo 'mval='.$mVal.' ';
      //echo $dependOnVal;
      //elPrintR($this->propVals[$this->type->props[$pID]->dependID]);
      $enabled = $this->getDependOnValue( $pID, $mVal );
    }
    //echo 'enable=';elPrintR($enabled);
    $enabled = array_flip($enabled ); //elPrintR($enabled);
    $ret = array();
    $reg = '/range\(([0-9\-\.]+)\,?\s*([0-9\-\.]+)\,?\s*([0-9\-\.]+)\s*\)\s*(exclude\((.+)\))?.*/si';
    foreach ( $raw as $ID=>$v )
    {
      $en = intval(isset($enabled[$ID])); //echo $ID.' ';
      if ( !preg_match($reg, $v, $m) )
      {
        $ret[] = array($v, $en);
      }
      else
      {
        $excl = !empty($m[5]) ? array_map('trim', explode(',', $m[5])) : null;
        $range = elRange($m[1], $m[2], $m[3], $excl);
        //$ret = array_merge($ret, elRange($m[1], $m[2], $m[3], $excl));
        for ($i=0, $s=sizeof($range); $i<$s; $i++)
        {
          $ret[] = array($range[$i], $en);
        }
      }
    }
    //elPrintR($ret);
    return $ret;
  }

  function getDependOnValue( $pID, $mVal )
  {
    $db  = & elSingleton::getObj('elDb');
    $sql = 'SELECT DISTINCT d.s_value FROM '.$this->tbpdep.' AS d WHERE s_id='.$pID.' AND m_value='.$mVal;
    return $db->queryToArray( $sql, null, 's_value');
  }


  function _getPropVal( $pID )
  {
    return isset( $this->propVals[$pID] ) ? $this->propVals[$pID] : null;
  }

  function _mnfsList()
  {
    $db = &elSingleton::getObj('elDb');
    return $db->queryToArray('SELECT id, name FROM '.$this->tbmnf.' ORDER BY name', 'id', 'name');
  }


  /**
   * Проверяет данные формы на предмет дубликатов артикулов
   *
   * @return bool
   */
  function _validForm()
  {
    $data = $this->form->getValue();
    $code = mysql_real_escape_string($data['code']);
    $db   = &elSingleton::getObj('elDb');
    $sql  = 'SELECT id FROM '.$this->tb.' WHERE code=\''.$code.'\''.($this->ID ? ' AND id<>'.$this->ID : '');
    $db->query($sql);
    if ($db->numRows())
    {
      return $this->form->pushError('code', m('Item code must be unique'));
    }
    return true;
  }

  function _attrsForSave()
  {
    $attrs = parent::_attrsForSave();
    $attrs['mtime'] = time();
    if ( !$this->ID )
    {
      $attrs['crtime'] = time();
    }
    return $attrs;
  }


  /**
   * сохраняет привязку товара к категориям и значения свойств-товара
   *
   * @return bool
   */
  function _postSave()
	{
	  $db = &elSingleton::getObj('elDb');

	  if ( EL_IS_USE_TM == $this->mnfNfo || EL_IS_USE_MNF_TM == $this->mnfNfo ) //!empty($this->tmID) )
	  {
	    $sql = 'UPDATE '.$this->tb.' '
	     .'SET mnf_id=(SELECT mnf_id FROM '.$this->tbtm.' WHERE id='.intval($this->tmID).' LIMIT 0,1) '
	     .'WHERE id='.$this->ID;
	    $db->query($sql);
	  }

	  $db->query('DELETE FROM '.$this->tbp2i.' WHERE i_id='.$this->ID);
	  $db->optimizeTable($this->tbp2i);

    $data = $this->form->getValue(); //elPrintR($data); exit;

    foreach ($data['props'] as $pID=>$value)
    {
      if ( !empty($value) )
      {
      $db->prepare('INSERT INTO '.$this->tbp2i.' (i_id, p_id, value, pv_id) VALUES', '(%d, %d, \'%s\', \'%d\')');
      if ( !is_array($value))
      {
        $pvID = EL_IS_PROP_LIST == $this->type->props[$pID]->type ? $value : 0;
        $db->prepareData( array($this->ID, $pID, $value, $pvID));
      }
      else
      {
        foreach ($value as $pvID)
        {
          $db->prepareData( array($this->ID, $pID, '', $pvID));
        }
      }
      $db->execute();
      }
    }
    return parent::_postSave();
	}

	/**
	 * возвращает фрагмент sql-кода - правило сортировки коллекциии товаров
	 *
	 * @param int $sortID
	 * @return string
	 */
  function _getOrderBy($sortID)
  {
  	$orderBy = !empty($this->_sortVars[$sortID]) ? $this->_sortVars[$sortID] : $this->_sortVars[1];
  	return 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), '.$orderBy;
  }

  function _initMapping()
  {
    $map = array(
      'id'=>'ID',
      'type_id'  => 'typeID',
      'mnf_id'   => 'mnfID',
      'tm_id'    => 'tmID',
      'code'     => 'code',
      'name'     => 'name',
      'announce' => 'announce',
      'content'  => 'content',
      'price'    => 'price',
      'img'      => 'img',
      'crtime'   => 'crtime',
      'mtime'    => 'mtime'
      );
    return $map;
  }

}

?>