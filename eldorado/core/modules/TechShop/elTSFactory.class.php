<?php

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';

define ('EL_TS_CAT',   1);
define ('EL_TS_ITEM',  2);
define ('EL_TS_MODEL', 3);
define ('EL_TS_MNF',   4);
define ('EL_TS_FTG',   6);
define ('EL_TS_FT',    7);

class elTSFactory
{
	var $pageID = 0;
	var $conf = array();
	var $_objs  = array();
	var $_db    = null;

	var $_tb = array(
		'tbc'       => 'el_techshop_%d_cat',
		'tbi'       => 'el_techshop_%d_item',
		'tbm'       => 'el_techshop_%d_model',
		'tbmnf'     => 'el_techshop_%d_manufact',
		'tbftg'     => 'el_techshop_%d_ft_group',
		'tbft'      => 'el_techshop_%d_feature',
		'tbi2c'     => 'el_techshop_%d_i2c',
		'tbft2i'    => 'el_techshop_%d_ft2i',
		'tbft2m'    => 'el_techshop_%d_ft2m',
		'tbprice'   => 'el_techshop_%d_price'
	);

	var $_classes = array(
		EL_TS_CAT   => array(
			'name'=> 'elCatalogCategory',
			'tbs' => array('tbc', 'tbi2c')),
		EL_TS_ITEM  => array(
			'name'=> 'elTSItem',
			'tbs' => array('tbi', 'tbc', 'tbi2c', 'tbmnf', 'tbm', 'tbft2i', 'tbft2m', 'tbprice') ),
		EL_TS_MODEL => array(
			'name'=> 'elTSModel',
			'tbs' => array('tbm', 'tbft2m')),
		EL_TS_MNF   => array(
			'name'=> 'elTSManufacturer',
			'tbs' => array('tbmnf', 'tbi')),
		EL_TS_FTG   => array(
			'name'=> 'elTSFeaturesGroup',
			'tbs' => array('tbftg', 'tbft')),
		EL_TS_FT    => array(
			'name'=> 'elTSFeature',
			'tbs' => array('tbft', 'tbftg', 'tbft2i', 'tbft2m')),

		);

	var $_sortVars = array(
		EL_TS_ITEM => array(
			'IF(i2c.sort_ndx>0, LPAD(i2c.sort_ndx, 4, "0"), "9999"), i.name',
			'IF(i2c.sort_ndx>0, LPAD(i2c.sort_ndx, 4, "0"), "9999"), i.code, i.name',
			'IF(i2c.sort_ndx>0, LPAD(i2c.sort_ndx, 4, "0"), "9999"), i.crtime DESC, i.name'));

	function init($pageID, $conf)
	{
		$this->pageID = $pageID;
		$this->conf = $conf;
		foreach ( $this->_tb as $k=>$tb )
		{
			$this->_tb[$k] = sprintf($tb, $this->pageID);
		}
		$this->_db = & elSingleton::getObj('elDb');
	}

	/**
	 * Create object of required type
	 *
	 * @param  integer  $hndl  object type
	 * @param  integer  $ID    object ID
	 * @return object
	 **/
	function create( $hndl, $ID=0 )
	{
		if (empty($this->_classes[$hndl]))
		{
			return null;
		}
		$c = $this->_classes[$hndl]['name'];
		if (!class_exists($c)) {
			include_once dirname(__FILE__).DIRECTORY_SEPARATOR.$c.'.class.php';
		}
		
		$this->_objs[$hndl] = new $c();

		$tbs = $this->_classes[$hndl]['tbs'];
		for ($i=0, $s=sizeof($tbs); $i<$s; $i++)
		{
			$member = ($i==0) ? '_tb' : $tbs[$i];
			$this->_objs[$hndl]->{$member} = $this->_tb[$tbs[$i]];
		}
		$this->_objs[$hndl]->idAttr((int)$ID);
		
		if ($this->_objs[$hndl]->ID && !$this->_objs[$hndl]->fetch())
		{
			$this->_objs[$hndl]->clean();
		}
		return $this->_objs[$hndl];
	}

	/**
	 * Return array of items from required category
	 *
	 * @param  integer  $catID     category ID
	 * @param  integer  $sortID    sort index
	 * @param  integer  $offset    query offset
	 * @param  integer  $step      max number of items
	 * @param  boolen   $features  flag. load features?
	 * @return array
	 **/
	function getItemsFromCategory($catID, $sortID, $offset, $step, $features)
	{
		$items = array();
		$itemsWModels = $itemsWoModels = array();
		$item = $this->create(EL_TS_ITEM);
		$sql = 'SELECT '.$item->attrsToString('i').', m.name AS mnfName, m.country AS mnfCountry, md.id AS hasModels, p.id AS fakePrice FROM '
				.$this->_tb['tbi'].' AS i '
				.'LEFT JOIN '.$this->_tb['tbmnf'].' AS m ON m.id=i.mnf_id '
				.'LEFT JOIN '.$this->_tb['tbm'].' AS md ON md.i_id=i.id '
				.'LEFT JOIN '.$this->_tb['tbprice'].' AS p ON p.i_id=i.id, '
				.$this->_tb['tbi2c'].' AS i2c '
				.'WHERE c_id=\''.intval($catID).'\' AND i.id=i2c.i_id '
				.'GROUP BY i.id '
				.'ORDER BY '.$this->_getOrderBy(EL_TS_ITEM, $sortID).' '
				.'LIMIT '.$offset.', '.$step;
		$this->_db->query($sql);
		while ($r = $this->_db->nextRecord())
		{
			$items[$r['id']] = $item->copy($r);
			$items[$r['id']]->hasFakePrice = !empty($this->conf['fakePrice']) && $r['fakePrice'];
			if ($r['hasModels']) {
				$itemsWModels[] = $r['id'];	
				$items[$r['id']]->hasModels = true;
			} else {
				$itemsWoModels[] = $r['id'];	
				$items[$r['id']]->hasModels = false;
			}
		}

		return $this->_items($items, $itemsWModels, $itemsWoModels, $features);

	}


	/**
	 * Return with models/features
	 *
	 * @param  integer  $ID    item ID
	 * @return object
	 **/
	function getItem($ID) {
		$item = $this->create(EL_TS_ITEM, $ID);
		if ($item->ID) {
			$model = $this->create(EL_TS_MODEL);
			$sql = 'SELECT '.$model->attrsToString().' FROM '.$this->_tb['tbm'].' WHERE i_id=\''.intval($ID).'\' ORDER BY code, name';
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord()) {
				$item->models[$r['id']] = $model->copy($r);
			}
			
			if ($item->models) {
				// load models features and features and groups names 
				$sql = 'SELECT g.id AS gid, g.name AS gname, ft.id AS fid, ft.name, ft.unit, ftv.m_id, ftv.is_announced, ftv.is_split, ftv.value FROM '
					.$this->_tb['tbftg'].'  AS g, '
					.$this->_tb['tbft'].'   AS ft, '
					.$this->_tb['tbft2m'].' AS ftv '
					.'WHERE '
					.'ftv.m_id IN ('.implode(',', array_keys($item->models)).') AND ft.id=ftv.ft_id AND g.id=ft.gid '
					.'ORDER BY '
					.'IF(g.sort_ndx>0,  LPAD(g.sort_ndx,  4, "0"), "9999"), g.name, '
					.'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
				$this->_db->query($sql);
				while ($r = $this->_db->nextRecord()) {
					if (!empty($item->models[$r['m_id']])) {
						$item->models[$r['m_id']]->features[$r['fid']] = stripslashes($r['value']);
					}
					if (empty($item->features[$r['gid']])) {
						$item->features[$r['gid']] = array(
							'name'     => $r['gname'],
							'features' => array()
							);
					}
					if (empty($item->features[$r['gid']]['features'][$r['fid']])) {
						$item->features[$r['gid']]['features'][$r['fid']] = array(
							'name'         => $r['name'],
							'unit'         => $r['unit'],
							'is_announced' => $r['is_announced'],
							'is_split'     => $r['is_split']
							);
					}
				}
			} else {
				// load item own features
				$sql = 'SELECT g.id AS gid, g.name AS gname, ft.id AS fid, ft.name, ft.unit, ftv.is_announced, ftv.value FROM '
					.$this->_tb['tbft2i'].' AS ftv, '
					.$this->_tb['tbft'].' AS ft, '
					.$this->_tb['tbftg'].' AS g '
					.'WHERE '
					.'ftv.i_id="'.$item->ID.'" AND ft_id=ft.id AND g.id=ft.gid '
					.'ORDER BY '
					.'IF(g.sort_ndx>0,  LPAD(g.sort_ndx,  4, "0"), "9999"), g.name,'
					.'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
				$this->_db->query($sql);
				while ($r = $this->_db->nextRecord()) {
					if (empty($item->features[$r['gid']])) {
						$item->features[$r['gid']] = array(
							'name' => $r['gname'],
							'features' => array()
							);
					}
					$item->features[$r['gid']]['features'][$r['fid']] = array(
						'name' => $r['name'],
						'unit' => $r['unit'],
						'value' => $r['value'],
						'is_announced' => $r['is_announced']
						);
				}
			}

			if (!empty($this->conf['ishop']) && !empty($this->conf['fakePrice'])) {
				$item->fakePriceList = $this->_db->queryToArray('SELECT name, price FROM '.$this->_tb['tbprice'].' WHERE i_id="'.$item->ID.'" ORDER BY name');
				$item->hasFakePrice = (bool)count($item->fakePriceList);

			}
		}
		return $item;
	}

	/**
	 * Return all features groups
	 *
	 * @return object
	 **/
	function getFeaturesGroups() {
		$g = $this->create(EL_TS_FTG);
		$f = $this->create(EL_TS_FT);
		$groups = $g->collection(true, true, null, 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name');
		$features = $f->collection(true, false, null, 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name');
		foreach($features as $f) {
			if (isset($groups[$f->GID])) {
				$groups[$f->GID]->features[$f->ID] = $f;
			}
		}
		return $groups;
	}

	/**
	 * Return all items for required manufacturer
	 *
	 * @return array
	 **/
	function getManufacturerItems($mID, $features) {
		$items = array();
		$itemsWModels = $itemsWoModels = array();
		$item = $this->create(EL_TS_ITEM);
		$sql = 'SELECT '.$item->attrsToString('i').', m.name AS mnfName, m.country AS mnfCountry, md.id AS hasModels FROM '
				.$this->_tb['tbi'].' AS i '
				.'LEFT JOIN '.$this->_tb['tbmnf'].' AS m ON m.id=i.mnf_id '
				.'LEFT JOIN '.$this->_tb['tbm'].' AS md ON md.i_id=i.id '
				.'WHERE i.mnf_id=\''.$mID.'\' '
				.'GROUP BY i.id '
				.'ORDER BY i.code, i.name';
		$this->_db->query($sql);
		while ($r = $this->_db->nextRecord())
		{
			$items[$r['id']] = $item->copy($r);
			if ($r['hasModels']) {
				$itemsWModels[] = $r['id'];	
				$items[$r['id']]->hasModels = true;
			} else {
				$itemsWoModels[] = $r['id'];	
				$items[$r['id']]->hasModels = false;
			}
		}
		return $this->_items($items, $itemsWModels, $itemsWoModels, $features);
	}

	function getItemPrice($iID) {
		$ret = array();
		$sql = 'SELECT name, price FROM '.$this->_tb['tbprice'].' WHERE i_id="'.$iID.'" ORDER BY name';
		return $this->_db->queryToArray($sql);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	function getCompareTable($iIDs, $mIDs)
	{
		$tb = array(
			'items' => array(),
			'features' => array()
			);

		$items  = !empty($iIDs) ? $this->_db->queryToArray('SELECT id AS i_id, IF(code!="", CONCAT(code, " ", name), name) AS name FROM '.$this->_tb['tbi'].' WHERE id IN ('.implode(',', $iIDs).')', 'i_id') : array();
		$models = !empty($mIDs) ? $this->_db->queryToArray('SELECT id AS m_id, i_id, IF(code!="", CONCAT(code, " ", name), name) AS name FROM '.$this->_tb['tbm'].' WHERE id IN ('.implode(',', $mIDs).')', 'm_id') : array();
		$features = array();
		
		if ($models) {
			// load announced features for models
			$sql =  'SELECT m.id, ftv.ft_id, IF(ftv.is_split="0", ftv.value, ftv2.value) AS value FROM '
				.$this->_tb['tbm'].'    AS m, '
				.$this->_tb['tbm'].'    AS m2 '
				.'LEFT JOIN '.$this->_tb['tbft2m'].' AS ftv2 ON ftv2.m_id=m2.id AND ftv2.value!="", '
				.$this->_tb['tbft2m'].'   AS ftv '
				.'WHERE '
				.'m.id IN ('.implode(',', array_keys($models)).') '
				.'AND m2.i_id=m.i_id '
				.'AND ftv.m_id=m.id  '
				.'AND ftv2.ft_id=ftv.ft_id GROUP BY m.id, ftv.ft_id '
				;
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord()) {
				if (!isset($models[$r['id']]['features'])) {
					$models[$r['id']]['features'] = array();
				}
				$models[$r['id']]['features'][$r['ft_id']] = $r['value'];
				$features[$r['ft_id']] = 1;
			}
			// elPrintR($models);
		}
		
		if ($items) {
			$sql = 'SELECT ftv.i_id, ftv.ft_id,  ftv.value FROM '
				.$this->_tb['tbft2i'].' AS ftv '
				.'WHERE ftv.i_id IN ('.implode(',', array_keys($items)).') ';
				
			
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord()) {
				if (!isset($items[$r['i_id']]['features'])) {
					$items[$r['i_id']]['features'] = array();
				}
				$items[$r['i_id']]['features'][$r['ft_id']] = $r['value'];
				$features[$r['ft_id']] = 1;
			}
		}
		$tb['items'] = array_merge($models, $items);
		usort($tb['items'], create_function('$a, $b', 'return strcmp($a["name"], $b["name"]);'));
		// elPrintR($features);
		// elPrintR($tb['items']);
		if ($features) {
			$sql = 'SELECT g.id AS gid, g.name AS gname, ft.id AS fid, IF(ft.unit="", ft.name, CONCAT(ft.name, ", ", ft.unit)) AS name FROM '
				.$this->_tb['tbftg'].' AS g, '
				.$this->_tb['tbft'].'  AS ft '
				.'WHERE '
				.'ft.id IN ('.implode(',', array_keys($features)).') AND g.id=ft.gid '
				.'ORDER BY '
				.'IF(g.sort_ndx>0,  LPAD(g.sort_ndx,  4, "0"), "9999"), g.name,'
				.'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord()) {
				if (!isset($tb['features'][$r['gid']])) {
					$tb['features'][$r['gid']] = array('name' => $r['gname'], 'features' => array());
				}
				$tb['features'][$r['gid']]['features'][$r['fid']] = $r['name'];
			}
		}
		return $tb;
		elPrintR($tb['features']);
		// 

			
	}

	function sort($a, $b) {
		return cmp($a['name'], $b['name']);
	}

	function getCompare($iIDs=null, $mIDs=null)
	{
		$list = array();
		if ($iIDs)
		{
			$sql = 'SELECT i.id, i.code, i.name, i.price, c.c_id, i.mnf_id, m.name AS mnf, m.country AS country, \'1\' AS isItem FROM '
						.$this->_tb['tbi2c'].' AS c, '
						.$this->_tb['tbi'].' AS i LEFT JOIN '.$this->_tb['tbmnf'].' AS m '
						.'ON m.id=i.mnf_id '
						.'WHERE i.id IN ('.implode(',', $iIDs).') AND c.i_id=i.id '
						.'GROUP BY i.id ORDER BY i.code, i.name';
			$this->_db->query($sql);
			$list = array_merge($list, $this->_db->queryToArray($sql));
		}

		if ( $mIDs )
		{
			$sql = 'SELECT m.id, m.code, m.name, m.price, m.img, m.i_id, c.c_id, mnf.id AS mnf_id, mnf.name AS mnf, mnf.country, \'0\' AS isItem FROM '
						.$this->_tb['tbi2c'].' AS c, '
						.$this->_tb['tbm'].' AS m, '
						.$this->_tb['tbi'].' AS i LEFT JOIN '.$this->_tb['tbmnf'].' AS mnf '
						.'ON mnf.id=i.mnf_id WHERE m.id IN ('.implode(',', $mIDs).') AND i.id=m.i_id AND c.i_id=m.i_id';
			$list = array_merge($list, $this->_db->queryToArray($sql));

		}
		return array($list, $this->getCompareFtgs($iIDs, $mIDs));
	}

	function getCompareFtgs($iIDs=null, $mIDs=null)
	{
		$ret = $iFtIDs = $mFtIDs = array();
		$ftg = $this->create(EL_TS_FTG);
		$ft  = $this->create(EL_TS_FT);

		if (!empty($iIDs))
		{
		  $iFtIDs = $this->_db->queryToArray('SELECT DISTINCT ft_id FROM '.$this->_tb['tbft2i'].' WHERE i_id IN ('.implode(',', $iIDs).')', null, 'ft_id');
		}
    if (!empty($mIDs))
		{
		  $mFtIDs = $this->_db->queryToArray('SELECT DISTINCT ft_id FROM '.$this->_tb['tbft2m'].' WHERE m_id IN ('.implode(',', $mIDs).')', null, 'ft_id');
		}

    $ftIDs = array_unique( array_merge($iFtIDs, $mFtIDs)) ;
    if (empty($ftIDs))
		{
		   return $ret;
		}
    $sql = 'SELECT DISTINCT g.id, g.name FROM '.$this->_tb['tbftg'].' AS g,'.$this->_tb['tbft'].' AS f '
      .'WHERE f.id IN ('.implode(',', $ftIDs).') AND g.id=f.gid '
      .'ORDER BY IF(g.sort_ndx>0, LPAD(g.sort_ndx, 4, "0"), "9999"), g.name ';
    $this->_db->query($sql);
    while ( $r = $this->_db->nextRecord() )
    {
      $ret[$r['id']] = $ftg->copy( $r );
    }

    $sql = 'SELECT '.$ft->listAttrsToStr().' FROM '.$this->_tb['tbft'].' WHERE id IN ('.implode(',', $ftIDs).')  '
      .'ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name';
    $this->_db->query($sql);
    while ($r = $this->_db->nextRecord())
    {
      $ret[$r['gid']]->setFeature($ft->copy($r));
    }

    if ($iIDs)
		{
			$sql = 'SELECT f.id, f.gid, r.i_id, r.value FROM '.$this->_tb['tbft'].' AS f, '.$this->_tb['tbft2i'].' AS r '
				   .'WHERE r.i_id IN ('.implode(',', $iIDs).') AND r.ft_id=f.id';
			$this->_db->query($sql);
			while ( $r = $this->_db->nextRecord() )
			{
				$ret[$r['gid']]->setItemFeatureValue($r['id'], $r['i_id'], $r['value']);
			}
		}
    if ( $mIDs )
    {
      $sql = 'SELECT  f.gid, r1.ft_id, r1.m_id, IF(r1.is_split="0", r1.value, r2.value) as value '
        .'FROM '.$this->_tb['tbft2m'].' AS r1, '
        .$this->_tb['tbm'].' AS m, '
        .$this->_tb['tbm'].' AS m2 '
        .'LEFT JOIN '.$this->_tb['tbft2m'].' AS r2 ON r2.m_id=m2.id  AND r2.value!="", '
        .$this->_tb['tbft'].' AS f '
        .' WHERE r1.m_id IN ('.implode(',', $mIDs).') AND m.id=r1.m_id AND m2.i_id=m.i_id AND r2.ft_id=r1.ft_id
        AND f.id=r1.ft_id
        GROUP BY r1.m_id, r1.ft_id';
      ;
      $this->_db->query($sql);
			while ( $r = $this->_db->nextRecord() )
			{
				$ret[$r['gid']]->setModelFeatureValue($r['ft_id'], $r['m_id'], $r['value']);
			}

    }

		return $ret;
	}

	/**
	 * Return table name
	 *
	 * @param  string  $name  table name handler
	 * @return string
	 **/
	function tb($name)
	{
		return !empty($this->_tb[$name]) ? $this->_tb[$name] : null;
	}

	function getPrice()
	{
	  $tmp = $price = array();

    $sql = 'SELECT DISTINCT i.id, i.code, i.name, i.price, c.id AS cid, c._left FROM '
      .$this->_tb['tbi'].' AS i, '.$this->_tb['tbc'].' AS c, '.$this->_tb['tbi2c'].' AS i2c '
      .'WHERE i2c.i_id=i.id AND c.id=i2c.c_id ORDER BY c._left, i.code, i.name '; //echo $sql;
    $this->_db->query($sql);
    while ($r = $this->_db->nextRecord())
    {
      $tmp[$r['id']] = array('code'=>$r['code'], 'name'=>$r['name'], 'price'=>$r['price'], 'models'=>array(), 'cid'=>$r['cid']);
    }

    $sql = 'SELECT id, i_id, code, name, price FROM '.$this->_tb['tbm'].' ORDER BY code, name';
    $this->_db->query($sql);
    while ($r = $this->_db->nextRecord())
    {
      if (!empty($tmp[$r['i_id']]))
      {
        $tmp[$r['i_id']]['models'][$r['id']] = array('code'=>$r['code'], 'name'=>$r['name'], 'price'=>$r['price']);
      }
    }

    foreach ( $tmp as $iID=>$item)
    {
      if (empty($item['models']))
      {
        $price[] = array('cid'=>$item['cid'], 'iid'=>$iID, 'code'=>$item['code'], 'name'=>$item['name'], 'price'=>$item['price'] );
      }
      else
      {
        foreach ($item['models'] as $mID=>$model)
        {
          $price[] = array('cid'=>$item['cid'], 'iid'=>$iID, 'mid'=>$mID, 'code'=>$model['code'], 'name'=>$model['name'], 'price'=>$model['price'] );
        }
      }
    }
    return $price;
	}

	function getCodes()
	{
	  $items = $models = array();
	  $all   = $this->getPrice();
	  foreach ($all as $one)
	  {
	    if (empty($one['code']))
	    {
	      continue;
	    }
	    if (empty($one['mid']))
	    {
	      $items[$one['code']] = $one['iid'];
	    }
	    else
	    {
	      $models[$one['code']] = $one['mid'];
	    }
	  }
	  //echo sizeof($all).' '.sizeof($items).' '.sizeof($models);
	  return array($items, $models);
	}

	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	function _items($items, $itemsWModels, $itemsWoModels, $features) {

		if ($items && $features) {
			// items with models
			if ($itemsWModels) {
				// load models
				$model = $this->create(EL_TS_MODEL);
				$mids = array();
				
				$sql = 'SELECT '.$model->attrsToString().' FROM '.$this->_tb['tbm']
						.' WHERE i_id IN ('.implode(',', $itemsWModels).') ORDER BY code, name';
				$this->_db->query($sql);
				while ($r = $this->_db->nextRecord())
				{
					$items[$r['i_id']]->models[$r['id']] = $model->copy($r);
					$mids[] = $r['id'];
				}

				// load announced features for models
				$sql =  'SELECT m.id, m.i_id, ft.name, ft.unit, IF(ftv.is_split="0", ftv.value, ftv2.value) AS value FROM '
					.$this->_tb['tbm'].'    AS m, '
					.$this->_tb['tbm'].'    AS m2 '
					.'LEFT JOIN '.$this->_tb['tbft2m'].' AS ftv2 ON ftv2.m_id=m2.id AND ftv2.value!="", '
					.$this->_tb['tbft'].'   AS ft, '
					.$this->_tb['tbft2m'].' AS ftv, '
					.$this->_tb['tbftg'].'  AS g '
					.'WHERE '
					.'m.id IN ('.implode(',', $mids).') '
					.'AND m2.i_id=m.i_id '
					.'AND ftv.m_id=m.id AND ftv.is_announced=\'1\' AND ft.id=ftv.ft_id AND g.id=ft.gid '
					.'AND ftv2.ft_id=ftv.ft_id GROUP BY m.id, ft.id '
					.'ORDER BY  m.code, m.name, '
					.'IF(g.sort_ndx>0,  LPAD(g.sort_ndx,  4, "0"), "9999"), g.name,'
					.'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
				
				$this->_db->query($sql);
				while ($r = $this->_db->nextRecord()) {
					if (!empty($items[$r['i_id']]) && !empty($items[$r['i_id']]->models[$r['id']])) {
						$items[$r['i_id']]->models[$r['id']]->features[] = array('name' => $r['name'], 'unit'=>$r['unit'], 'value' => $r['value']);
					}
				}
			}
		
			// items without models
			if ($itemsWoModels) {
				// load items features
				$sql = 'SELECT i.id, ft.name, ft.unit, ftv.value, ftv.is_announced FROM '
					.$this->_tb['tbi'].' AS i, '
					.$this->_tb['tbft'].' AS ft, '
					.$this->_tb['tbft2i'].' AS ftv, '
					.$this->_tb['tbftg'].' AS g '
					.'WHERE i.id IN ('.implode(',', $itemsWoModels).') '
					.'AND ftv.i_id=i.id AND ft.id=ftv.ft_id AND g.id=ft.gid '
					.'ORDER BY i.id, '
					.'IF(g.sort_ndx>0, LPAD(g.sort_ndx, 4, "0"), "9999"), g.name,'
					.'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
				
				$this->_db->query($sql);
				while ($r = $this->_db->nextRecord())
				{
					$items[$r['id']]->hasFeatures = true; // need this to display or not compare checkbox
					if ($r['is_announced']) {
						$items[$r['id']]->features[] = array('name'=>$r['name'], 'unit'=>$r['unit'], 'value'=>$r['value']);
					}
					
				}
			}
		}
		return $items;
	}
	

	function _getOrderBy($hndl, $sortID)
	{
		if ( empty($this->_sortVars[$hndl]) )
		{
			return null;
		}
		$orderBy = !empty($this->_sortVars[$hndl][$sortID])
			? $this->_sortVars[$hndl][$sortID]
			: $this->_sortVars[$hndl][0];

  	return $orderBy;
	}


}


?>