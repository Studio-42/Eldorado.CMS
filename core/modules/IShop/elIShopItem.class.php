<?php

// include_once 'elCatalogItem.class.php';

class elIShopItem extends elDataMapping {
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
	var $special    = 0;
	var $ym         = 1;
	var $img        = '';
	var $gallery;
	var $crtime     = 0;
	var $mtime      = 0;
	var $propVals   = array();
	var $_type      = null;
	var $_factory   = null;
	var $_objName = 'Product';
  
	/**
	 * get item by id
	 *
	 * @return bool
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
	 * return item type
	 *
	 * @return elIShopItemType
	 **/
	function getType() {
		if (!isset($this->_type)) {
			$this->_type = $this->_factory->getFromRegistry(EL_IS_ITYPE, $this->typeID);
		}
		return $this->_type;
	}

	/**
	 * return categories in which item is found
	 *
	 * @return array
	 **/
	function getCats() {
		$a = array();
		$db = $this->_db();
		if ($db->query(sprintf('SELECT c_id FROM %s WHERE i_id=%d', $this->tbi2c, $this->ID))) {
			while ($r = $db->nextRecord()) {
				array_push($a, $r['c_id']);
			}
		}
		return $a;
	}

	/**
	 * return item manufacturer
	 *
	 * @return elIShopManufacturer
	 **/
	function getMnf() {
		return $this->_factory->getFromRegistry(EL_IS_MNF, $this->mnfID);
	}

	/**
	 * return item trademark
	 *
	 * @return elIShopTm
	 **/
	function getTm() {
		return $this->_factory->getFromRegistry(EL_IS_TM, $this->tmID);
	}
	
	/**
	 * return properties marked for annouce in items list
	 *
	 * @return array
	 **/
	function getAnnouncedProperties() {
		$ret   = array();
		$type  = $this->getType();
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
			if (!($ml || $p->isHidden)) {
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
	 * return images list - gallery
	 *
	 * @return array
	 **/
	function getGallery() {
		if ($this->ID && !isset($this->gallery)) {
			$db = $this->_db();
			$this->gallery = $db->queryToArray(sprintf('SELECT id, img FROM %s WHERE i_id=%d ORDER BY id', $this->tbgal, $this->ID), 'id', 'img');
		}
		return $this->gallery;
	}

	/**
	 * return first image thumbnail url 
	 *
	 * @return string
	 **/
	function getDefaultTmb($type = 'l') {
		$gallery = $this->getGallery();
		if ($gallery) {
			return $this->getTmbURL(key($gallery), $type);
		}
	}
	
	/**
	 * return thumbnail url by image id and tmb type
	 *
	 * @param  int    $id    image id
	 * @param  string $type  tmb type/size
	 * @return string
	 **/
	function getTmbURL($id, $type = 'l') {
		$gallery = $this->getGallery();
		if (isset($gallery[$id])) {
			list($tmbl, $tmbc) = $this->_getTmbNames($gallery[$id]);
			return EL_BASE_URL.('l' == $type ? $tmbl : $tmbc);
		}
	}
	
	/**
	 * return thumbnail path by image id and tmb type
	 *
	 * @param  int    $id    image id
	 * @param  string $type  tmb type/size
	 * @return string
	 **/
	function getTmbPath($id, $type = 'l') {
		return '.'.str_replace(EL_BASE_URL, '', $this->getTmbURL($id, $type));
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
	 * Create form for edit/create object 
	 *
	 * @param  array  $params 
	 * @return void
	 **/
	function _makeForm($params=null) {
		parent::_makeForm();
		if ($this->ID) {
			$cats = $this->getCats();
		} else {
			$this->typeID = $params['typeID'];
			$this->mnfID  = $params['mnfID'];
			$cats         = array($params['catID']);
		}
		$type = $this->_factory->getFromRegistry(EL_IS_ITYPE, $this->typeID);
		$this->_form->setLabel(sprintf($this->ID ? m('Edit object "%s"') : m('Create object "%s"'), $type->name));
		
		$this->_form->add(new elText('code',  m('Code/Articul'), $this->code) );
		$this->_form->add(new elText('name',  m('Name'),         $this->name,  array('style' => 'width:100%')));
		$this->_form->add(new elText('price', m('Price'),        $this->price) );
		
		$cat = $this->_factory->create(EL_IS_CAT);
		$this->_form->add(new elMultiSelectList('cat_id', m('Parent category'), $cats, $cat->getTreeToArray(0, true)));
		
		$mnfs  = array();
		$_mnfs = $this->_factory->getAllFromRegistry(EL_IS_MNF);
		foreach ($_mnfs as $m) {
			$mnfs[$m->ID] = $m->name;
		}
		if ($mnfs) {
			$this->_form->add(new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $mnfs));
			$tms  = array();
			$_tms = $this->_factory->getAllFromRegistry(EL_IS_TM);
			foreach ($_tms as $id=>$tm) {
				$tms[$id] = $tm->name;
			}
			if ($tms) {
				$tms = array(m('Undefined')) + $tms;
				$this->_form->add(new elSelect('tm_id', m('Trade mark/model'), $this->tmID, $tms));
			}
		}
		
		$this->_form->add(new elEditor('announce', m('Announce'), $this->announce, array('height' => 250)));
	    $this->_form->add(new elEditor('content',  m('Content'),  $this->content));
		// elPrintR($this->propVals);
		foreach ($type->getProperties() as $p) {
			$this->_form->add($p->toFormElement(isset($this->propVals[$p->ID]) ? $this->propVals[$p->ID] : null, true));
		}
	
		$this->_form->add(new elSelect('ym', m('Upload into Yandex market'), $this->ym, $GLOBALS['yn']));
	
		$this->_form->setRequired('cat_id[]');
	    $this->_form->setRequired('code');
	    $this->_form->setRequired('name');
	}

	/**
	 * save categories and properties values for item
	 *
	 * @return bool
	 **/
	function _postSave($isNew, $params=null) {
		$db = $this->_db();
		
		// set categories
		$catIDs = $this->_form->getElementValue('cat_id[]');
		$rm = array();
		$add = array();
		if ($isNew) {
			$add = $catIDs;
		} else {
			$old = $this->getCats();
			$add = array_diff($catIDs, $old);
			$rm  = array_diff($old, $catIDs);
		}
		
		if ($rm) {
			$db->query(sprintf('DELETE FROM %s WHERE i_id=%d AND c_id IN (%s)', $this->tbi2c, $this->ID, implode(',', $rm)));
			$db->optimizeTable($this->tbi2c);
		}
		if ($add) {
			$db->prepare('INSERT INTO '.$this->tbi2c.' (c_id, i_id) VALUES ', '(%d, %d)');
			foreach ($add as $catID) {
				$db->prepareData(array($catID, $this->ID));
			}
			$db->execute();
		}
		
		// save properties values
		$db->query(sprintf('DELETE FROM %s WHERE i_id=%d', $this->tbp2i, $this->ID));
		$db->optimizeTable($this->tbp2i);
		$data  = $this->_form->getValue();
		$type  = $this->getType();
		$props = $type->getProperties();
		foreach ($data as $k=>$v) {
			if (preg_match('/^prop_\d+/i', $k)) {
				$k = (int)str_replace('prop_', '', $k);
				if (isset($props[$k])) {
					if (is_array($v)) {
						foreach ($v as $_v) {
							$db->query(sprintf('INSERT INTO %s (i_id, p_id, value) VALUES (%d, %d, "%s")', $this->tbp2i, $this->ID, $k, mysql_real_escape_string($_v)));
						}
					} else {
						$db->query(sprintf('INSERT INTO %s (i_id, p_id, value) VALUES (%d, %d, "%s")', $this->tbp2i, $this->ID, $k, mysql_real_escape_string($v)));
					}
				}
			}
		}
		return true;
	}


  /**
   * Удаляет данные объекта из таблиц товаров, значений свойств и привязки к категориям
   * tb, tbp2i tbi2c
   *
   */
  function delete($ref = null)
  {
    parent::delete( array($this->tbp2i=>'i_id', $this->tbi2c=>'i_id') );
  }

  function removeItems($catID, $sortID=0)
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
	 * return small and middle tmb names
	 *
	 * @param  string  $imgPath  image path
	 * @return array
	 **/
	function _getTmbNames($imgPath) {
		$imgName = baseName($imgPath);
		$imgDir  = dirname($imgPath).DIRECTORY_SEPARATOR;
		return array($imgDir.'tmbl-'.$imgName, $imgDir.'tmbc-'.$imgName);
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
			'special'  => 'special',
			'ym'       => 'ym',
			'crtime'   => 'crtime',
			'mtime'    => 'mtime'
			);
		return $map;
	}

}

?>
