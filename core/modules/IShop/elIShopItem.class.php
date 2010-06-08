<?php

include_once 'elCatalogItem.class.php';

class elIShopItem extends elCatalogItem {
	var $tbmnf      = '';
	var $tbp2i      = '';
	var $tbi2c      = '';
	var $tbgal      = '';
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
	var $gallery    = array();
	var $crtime     = 0;
	var $mtime      = 0;
	var $propVals   = null;
	var $_type       = null;

	var $_objName = 'Product';
  
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function fetch() {
		if (parent::fetch()) {
			
			$vals = $this->fetchPropsValues(array($this->ID));
			foreach ($vals as $v) {
				if (!isset($this->propVals[$v['p_id']])) {
					$this->propVals[$v['p_id']] = array();
				}
				$this->propVals[$v['p_id']][] = $v['value'];
			}
			return true;
		}
		return false;
	}


	/**
	 * return properties values by items ids
	 *
	 * @param  array  $ids
	 * @return array
	 **/
	function fetchPropsValues($ids) {
		$db = $this->_db();
		return $db->queryToArray(sprintf('SELECT i_id, p_id, value FROM %s WHERE i_id IN (%s)', $this->tbp2i, implode(',', $ids)));
	}

	/**
	 * return item manufacturer
	 *
	 * @return elIShopManufacturer
	 **/
	function getType() {
		if (!isset($this->_type)) {
			$f = & elSingleton::getObj('elIShopFactory');
			$this->_type = $f->getFromRegistry(EL_IS_ITYPE, $this->mnfID);
		}
		return $this->_type;
	}
	
	
	/**
	 * return item manufacturer
	 *
	 * @return elIShopManufacturer
	 **/
	function getMnf() {
		$f = & elSingleton::getObj('elIShopFactory');
		return $f->getFromRegistry(EL_IS_MNF, $this->mnfID);
	}

	/**
	 * return item trademark
	 *
	 * @return elIShopTm
	 **/
	function getTm() {
		$f = & elSingleton::getObj('elIShopFactory');
		return $f->getFromRegistry(EL_IS_TM, $this->tmID);
	}
	
	/**
	 * return properties marked for annouce in items list
	 *
	 * @return array
	 **/
	function getAnnouncedProperties() {
		$ret   = array();
		$type  = $this->getType();
		$props = $type->getAnnouncedProperties();
		foreach ($type->getAnnouncedProperties() as $p) {
			$ret[] = array(
				'name'  => $p->name,
				'value' => $p->valuesToString(isset($this->propVals[$p->ID]) ? $this->propVals[$p->ID] : array())
				);
		}
		return $ret;
	}

	/**
	 * return properties grouped by position
	 *
	 * @return array
	 **/
	function getProperties() {
		$ret   = array('top'=>array(), 'table'=>array(), 'bottom'=>array(), 'order'=>array());
		$type  = $this->getType();
		$props = $type->getProperties();

		foreach ($props as $p) {
			
			$ml = $p->isMultiList();
			if ($ml) {
				$ret['order'][] = array(
					'name'  => $p->name, 
					'value' => $p->valuesToString($value)
					);
			}
			if (!($ml && $p->isHidden)) {
				$value = isset($this->propVals[$p->ID]) ? $this->propVals[$p->ID] : array();
				$value = $p->valuesToString($value);
				if ($value) {
					$ret[$p->displayPos][] = array(
						'name'  => $p->name, 
						'value' => $value
						);
				}
			}
		}
		return $ret;
	}



  /**
   * Извлекает поля объкта из БД
   *
   * @return bool
   */
  function _fetch()
  {
    if ( !$this->ID )
    {
      return false;
    }
    // в зав-ти от настроек прозв/торг марка ($this->mnfNfo) извлекаем произв
    // - если только произв - по id из табл товара
    // иначе по id из табд торговых марок
    $sql = 'SELECT '.$this->attrsToString('i').', m.name AS mnf, m.country, t.name AS tm '
      .'FROM '.$this->_tb.' AS i LEFT JOIN '.$this->tbtm.' AS t ON i.tm_id=t.id '
      .'LEFT JOIN '.$this->tbmnf.' AS m ON IF( '.intval(EL_IS_USE_MNF==$this->mnfNfo).' OR i.tm_id=0, i.mnf_id=m.id, t.mnf_id=m.id) '
      .'WHERE i.id=\''.intval($this->ID).'\' ' ;
    $db = & elSingleton::getObj('elDb');
    $db->query($sql);
    if ( !$db->numRows() )
    {
        return false;
    }
    $r = $db->nextRecord(); 
    $this->attr( $r );
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
   * Возвращает массив объектов-итемов полученный в рез-те поиска
   *
   * @param string $tbr - временная таблица с ID итемов
   * @return array
  */
  function getBySearchResult( $tbr )
  {
    $items = array();
    $db    = & elSingleton::getObj('elDb');

    $sql = 'SELECT '.$this->attrsToString('i').' '
        .' FROM '.$tbr.' AS r, '.$this->_tb.' AS i '
        .' WHERE i.id=r.id  '
        .' ORDER BY mnf, name';

    $db->query( $sql );
    $factory = & elSingleton::getObj('elIShopFactory');
    while( $row = $db->nextRecord() )
    {
      $items[$row['id']]             = $this->copy($row);
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
  function _getProperties()
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





  function _findValue($pID, $val)
  {
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
  function _makeForm( $params )
  {
    $label = !$this->idAttr() ? 'Create object "%s"' : 'Edit object "%s"';
    $this->_form = & elSingleton::getObj( 'elForm', 'mf',  sprintf( m($label), m($this->_objName))  );
    $this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );

    $this->_form->add( new elCData2('t', m('Type'), $this->type->name) );

    if ( empty($params['parents']) )
    {
      $params['parents'] = array(1);
    }
    
    if ( false == ($pIDs = $this->_getParents()) )
    {
      $pIDs = array( $params['catID'] );
    }
    $this->_form->add( new elMultiSelectList('pids', m('Parent categories'), $pIDs, $params['parents']) );


    if ( EL_IS_USE_MNF == $this->mnfNfo )
    {
      $this->_form->add( new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $this->_mnfsList()) );
    }
    elseif ( 0 <> $this->mnfNfo )
    {
      $sel     = & new elExtSelect('tm_id', m('Manufacturers / Trade marks'), $this->tmID);
      $mnfsTms = $this->_mnfsTmsList();
      foreach ( $mnfsTms as $g )
      {
        $gid = $sel->addGroup( $g[0], $g[1] );
      }
      $this->_form->add( $sel );
    }
    $textAttrs = array('style'=>'width:100%;');
    $this->_form->add( new elText('code',  m('Code/Articul'),  $this->code,     $textAttrs) );
    $this->_form->add( new elText('name',  m('Name'),          $this->name,     $textAttrs) );
    $this->_form->add( new elText('price', m('Price'),         $this->price,    $textAttrs) );
    $this->_form->add( new elEditor('announce', m('Announce'), $this->announce, array('height' => 250)) );
    $this->_form->add( new elEditor('content',  m('Content'),  $this->content) );
    $this->_form->setRequired('pids[]');
    $this->_form->setRequired('code');
    $this->_form->setRequired('name');
    foreach ($this->type->props as $ID=>$p)
    {
      $this->_form->add( $p->getFormElement( $this->_getPropVal($p->ID) ) );//$this->_getPropValue($p->ID)) );
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
    $db = & elSingleton::getObj('elDb');
    $sql = 'SELECT id, CONCAT(code, " ", name) AS name  FROM '.$this->_tb.', '.$this->tbi2c
    	  .' WHERE c_id=\''.$catID.'\' AND id=i_id ORDER BY '.$this->_getOrderBy($sortID);
    $items = $db->queryToArray($sql, 'id', 'name');
    $this->_form = & elSingleton::getObj( 'elForm', 'mf',  m('Select documents to remove')  );
	$this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
	
    $this->_form->add( new elCheckBoxesGroup('items', '', null, $items) );

    if ( $this->_form->isSubmitAndValid() )
    {
      $data = $this->_form->getValue();
      if ( !empty($data['items']) )
      {
        $iIDs = '('.implode(',', $data['items']).')';
        $db->query('DELETE FROM '.$this->_tb.'    WHERE id IN '.$iIDs);
        $db->query('DELETE FROM '.$this->tbi2c.' WHERE i_id IN '.$iIDs);
        $db->query('DELETE FROM '.$this->tbp2i.' WHERE i_id IN '.$iIDs);
        $db->optimizeTable($this->_tb);
        $db->optimizeTable($this->tbi2c);
        $db->optimizeTable($this->tbp2i);
      }
      return true;
    }
    return false;
  }

	// Image manipulation

	// if $img_id is not set - get first (main) image, else image with $img_id
	function getImg($img_id = false)
	{
		if ((!$this->ID) || (!$img_id))
		{
			return false;
		}
		$sql = sprintf('SELECT id, img FROM %s WHERE id=%d AND i_id=%d LIMIT 1', $this->tbgal, (int)$img_id, $this->ID);
		$db = & elSingleton::getObj('elDb');
		$db->query($sql);
		if (!$db->numRows())
		{
			return false;
		}
		$f = $db->nextRecord();
		$this->img = $f['img'];
		return $f['img'];
	}

	function getGallery()
	{
		if (!$this->ID)
		{
			return false;
		}

		if (!empty($this->gallery) || ($this->gallery === false))
		{
			return $this->gallery;
		}

		$db = & elSingleton::getObj('elDb');
		$db->query(sprintf('SELECT id, img FROM %s WHERE i_id=%d ORDER BY id', $this->tbgal, $this->ID));
		if ($db->numRows() < 1)
		{
			$this->gallery = false;
			return false;
		}

		$gallery = array();
		while ($r = $db->nextRecord())
		{
			$gallery[$r['id']] = $r['img'];
		}
		$this->gallery = $gallery;
		return $gallery;
	}

	function rmImage($img_id = false)
	{
		if ((!$this->ID) || (!$img_id) || (($img = $this->getImg($img_id)) === false))
		{
			return false;
		}

		$db  = & elSingleton::getObj('elDb');
		$sql = sprintf('DELETE FROM %s WHERE id=%d AND i_id=%d', $this->tbgal, $img_id, $this->ID);
		$db->query($sql);
		list($tmbl, $tmbc) = $this->_getTmbNames($img);
		@unlink('.'.$tmbl);
		@unlink('.'.$tmbc);
		return true;
	}

	function changeImage($img_id, $lSize, $cSize)
	{
		if (empty($_POST['imgURL']))
		{
			return false;
		}

		$imgPath = urldecode(str_replace(EL_BASE_URL, '', $_POST['imgURL']));
		if (in_array($imgPath, $this->getGallery()))
		{
			return elThrow(E_USER_WARNING, 'This image is already in the gallery');
		}

		$_image = & elSingleton::getObj('elImage');
		list($tmbl, $tmbc) = $this->_getTmbNames($imgPath);

		// list image
		$lSize = $lSize < 30 ? 120 : $lSize;
		$info = $_image->imageInfo('.'.$imgPath);
		list($w, $h) = $_image->calcTmbSize($info['width'], $info['height'], $lSize);
		if (!$_image->tmb('.'.$imgPath, '.'.$tmbl, $w, $h))
		{
			return elThrow(E_USER_WARNING, $image->error);
		}

		// item card image
		$cSize = $cSize < 30 ? 120 : $cSize;
		$info = $_image->imageInfo('.'.$imgPath);
		list($w, $h) = $_image->calcTmbSize($info['width'], $info['height'], $cSize);
		if (!$_image->tmb('.'.$imgPath, '.'.$tmbc, $w, $h))
		{
			return elThrow(E_USER_WARNING, $image->error);
		}

		if ($img_id > 0)
		{
			if (($img = $this->getImg($img_id)) !== false)
			{
				if ($img != $imgPath) // if set to the same image as before do not delete just generated thumbs 
				{
					list($tmbl, $tmbc) = $this->_getTmbNames($this->img);
					@unlink('.'.$tmbl);
					@unlink('.'.$tmbc);
				}
			}
			$sql = sprintf('UPDATE %s SET img="%s" WHERE id=%d AND i_id=%d LIMIT 1', $this->tbgal, $imgPath, $img_id, $this->ID);			
		}
		else
		{
			$sql = sprintf('INSERT INTO %s (i_id, img) VALUES (%d, "%s")', $this->tbgal, $this->ID, $imgPath);
		}
		$db = & elSingleton::getObj('elDb');
		$db->query($sql);
		return true;
	}

	function getTmbURL($tmbType = 'l', $thisImg = false)
	{
		$img = false;
		if ($thisImg != false)
		{
			$img = $thisImg;
		}
		else
		{
			$img = array_shift($this->getGallery()) or false;
		}

		if ($img)
		{
			list($tmbl, $tmbc) = $this->_getTmbNames($img);
			return EL_BASE_URL.('c' == $tmbType ? $tmbc : $tmbl);
		}
	}

	function getTmbPath($tmbType = 'l', $thisImg = false) {
		$url = $this->getTmbURL($tmbType, $thisImg);
		if (!empty($url)) {
			return '.'.str_replace(EL_BASE_URL, '', $url);
		}
	}

	function _getTmbNames($imgPath)
	{
		$imgName = baseName($imgPath);
		$imgDir  = dirname($imgPath).DIRECTORY_SEPARATOR;
		return array($imgDir.'tmbl-'.$imgName, $imgDir.'tmbc-'.$imgName);
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
    return $db->queryToArray('SELECT DISTINCT d.s_value FROM '.$this->tbpdep.' AS d WHERE s_id='.$pID.' AND m_value='.$mVal, null, 's_value');
  }


	/*********************************************************/
	/***                     PRIVATE                       ***/
	/*********************************************************/	

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _loadProps() {
		if (!is_array($this->_propsVal)) {
			$sql = 'SELECT id, value FROM %s WHERE i_id=%d AND p_id ID (%s)';
			// $sql = sprintf($sql, $this->_tbp2i, $this->ID, implode(",", ))
		}
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
    $data = $this->_form->getValue();
    $code = mysql_real_escape_string($data['code']);
    $db   = &elSingleton::getObj('elDb');
    $db->query('SELECT id FROM '.$this->_tb.' WHERE code=\''.$code.'\''.($this->ID ? ' AND id<>'.$this->ID : ''));
    if ($db->numRows())
    {
      return $this->_form->pushError('code', m('Item code must be unique'));
    }
    return true;
  }

	/**
	 * update timestamps before save
	 *
	 * @return array
	 **/
	function _attrsForSave() {
		$attrs = parent::_attrsForSave();
		$attrs['mtime'] = time();
		if (!$this->ID) {
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
	    $db->query('UPDATE '.$this->_tb.' SET mnf_id=(SELECT mnf_id FROM '.$this->tbtm.' WHERE id='.intval($this->tmID).' LIMIT 0,1) WHERE id='.$this->ID);
	  }

	  $db->query('DELETE FROM '.$this->tbp2i.' WHERE i_id='.$this->ID);
	  $db->optimizeTable($this->tbp2i);

    $data = $this->_form->getValue(); //elPrintR($data); exit;

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
	 * init attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		$map = array(
			'id'       => 'ID',
			'type_id'  => 'typeID',
			'mnf_id'   => 'mnfID',
			'tm_id'    => 'tmID',
			'code'     => 'code',
			'name'     => 'name',
			'announce' => 'announce',
			'content'  => 'content',
			'price'    => 'price',
			'crtime'   => 'crtime',
			'mtime'    => 'mtime'
			);
		return $map;
	}

}

?>
