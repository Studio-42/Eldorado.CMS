<?php

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elTSItem.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elTSModel.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elTSManufacturer.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elTSFeaturesGroup.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elTSFeature.class.php';

define ('EL_TS_CAT',   1);
define ('EL_TS_ITEM',  2);
define ('EL_TS_MODEL', 3);
define ('EL_TS_MNF',   4);
define ('EL_TS_FTG',   6);
define ('EL_TS_FT',    7);

class elTSFactory
{
	var $pageID = 0;
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
	);

	var $_classes = array(
		EL_TS_CAT   => array(
			'name'=> 'elCatalogCategory',
			'tbs' => array('tbc', 'tbi2c')),
		EL_TS_ITEM  => array(
			'name'=> 'elTSItem',
			'tbs' => array('tbi', 'tbc', 'tbi2c', 'tbmnf', 'tbm', 'tbft2i', 'tbft2m') ),
		EL_TS_MODEL => array(
			'name'=> 'elTSModel',
			'tbs' => array('tbm')),
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

	function init($pageID)
	{
		$this->pageID = $pageID;
		foreach ( $this->_tb as $k=>$tb )
		{
			$this->_tb[$k] = sprintf($tb, $this->pageID);
		}
		$this->_db = & elSingleton::getObj('elDb');
	}

	function create( $hndl, $ID=0 )
	{
		if (empty($this->_classes[$hndl]))
		{
			return null;
		}
		$c = $this->_classes[$hndl]['name'];
		$this->_objs[$hndl] = new $c();

		$tbs = $this->_classes[$hndl]['tbs'];
		for ($i=0, $s=sizeof($tbs); $i<$s; $i++)
		{
			$member = ($i==0) ? 'tb' : $tbs[$i];
			$this->_objs[$hndl]->{$member} = $this->_tb[$tbs[$i]];
		}

		$this->_objs[$hndl]->setUniqAttr((int)$ID);
		if ($this->_objs[$hndl]->ID && !$this->_objs[$hndl]->fetch())
		{
			$this->_objs[$hndl]->cleanAttrs();
		}
		return $this->_objs[$hndl];
	}

	function getItemsFromCategory($catID, $sortID, $offset, $step)
	{
		$items = array();
		$item = $this->create(EL_TS_ITEM);
		$sql = 'SELECT '.$item->listAttrsToStr('i').', m.name AS mnfName, m.country AS mnfCountry '
				.'FROM '.$this->_tb['tbi'].' AS i LEFT JOIN '.$this->_tb['tbmnf'].' AS m '
				.'ON m.id=i.mnf_id, '.$this->_tb['tbi2c'].' AS i2c '
				.'WHERE c_id=\''.intval($catID).'\' AND i.id=i2c.i_id '
				.'ORDER BY '.$this->_getOrderBy(EL_TS_ITEM, $sortID).' '
				.'LIMIT '.$offset.', '.$step;
				;
		$this->_db->query($sql);
		if ($this->_db->numRows())
		{
			while ($r = $this->_db->nextRecord())
			{
				$items[$r['id']] = $item->copy($r);
			}
		}
		if ( !empty($items))
		{
		   $iWOModels = array_flip(array_keys($items));
		   $iWModels  = array();
			$model = $this->create(EL_TS_MODEL);
			$sql = 'SELECT '.$model->listAttrsToStr().' FROM '.$this->_tb['tbm']
					.' WHERE i_id IN ('.implode(',', array_keys($items)).') ORDER BY code, name';
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord())
			{
				$items[$r['i_id']]->models[$r['id']] = $model->copy($r);
				if ( !isset($iWModels[$r['i_id']]) || isset($iWOModels[$r['i_id']]) )
				{
				   $iWModels[$r['i_id']] = 1; //echo $r['i_id'].'<br>';
				   unset($iWOModels[$r['i_id']]);
				}
			}
			//echo 'W/O models'; elPrintR($iWOModels); echo 'W models'; elPrintR($iWModels);

			if ( !empty($iWOModels))
			{
			   $sql = 'SELECT i.id, ft.name, ft.unit, ftv.value FROM '
			         .$this->_tb['tbi'].' AS i, '
			         .$this->_tb['tbft'].' AS ft, '
			         .$this->_tb['tbft2i'].' AS ftv, '
			         .$this->_tb['tbftg'].' AS g '
			         .'WHERE i.id IN ('.implode(',', array_keys($iWOModels)).') '
			         .'AND ftv.i_id=i.id AND ft.id=ftv.ft_id AND ftv.is_announced=\'1\' AND g.id=ft.gid '
			         .'ORDER BY i.id, '
					   .'IF(g.sort_ndx>0, LPAD(g.sort_ndx, 4, "0"), "9999"), g.name,'
					   .'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
			   $this->_db->query($sql); //echo $sql;
			   while ($r = $this->_db->nextRecord())
			   {
			      $items[$r['id']]->ft[] = array('name'=>$r['name'], 'unit'=>$r['unit'], 'value'=>$r['value']);
			   }
			}

			if (!empty($iWModels))
			{
				  $sql = 'SELECT i.id, m.id AS mid, ft.name, ft.unit, IF(ftv.is_split="0", ftv.value, ftv2.value) AS value FROM '
			         .$this->_tb['tbi'].'    AS i, '
			         .$this->_tb['tbm'].'    AS m, '
			         .$this->_tb['tbm'].'    AS m2 '
			         .'LEFT JOIN '.$this->_tb['tbft2m'].' AS ftv2 ON ftv2.m_id=m2.id AND ftv2.value!="", '
			         .$this->_tb['tbft'].'   AS ft, '
			         .$this->_tb['tbft2m'].' AS ftv, '
			         .$this->_tb['tbftg'].'  AS g '
			         .'WHERE i.id IN ('.implode(',', array_keys($iWModels)).') '
			         .'AND m.i_id=i.id '
			         .'AND m2.i_id=i.id '
			         .'AND ftv.m_id=m.id AND ftv.is_announced=\'1\' AND ft.id=ftv.ft_id AND g.id=ft.gid '
			         .'AND ftv2.ft_id=ftv.ft_id GROUP BY m.id, m.i_id, ft.id '
			         .'ORDER BY i.id, m.name, '
					   .'IF(g.sort_ndx>0,  LPAD(g.sort_ndx,  4, "0"), "9999"), g.name,'
					   .'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
					   //echo $sql;
			   $this->_db->query($sql);
			   while ($r = $this->_db->nextRecord())
			   {
			      if (!isset($items[$r['id']]->ft[$r['mid']]))
			      {
			         $items[$r['id']]->ft[$r['mid']] = array();
			      }
			      $items[$r['id']]->ft[$r['mid']][] = array('name'=>$r['name'], 'unit'=>$r['unit'], 'value'=>$r['value']);
			   }

			}
		}
		return $items;
	}

	function getItemModels($iID)
	{
		$models = array();
		$model = $this->create(EL_TS_MODEL);
		$sql = 'SELECT '.$model->listAttrsToStr('m').' FROM '.$this->_tb['tbm'].' AS m '
				  .'WHERE m.i_id=\''.intval($iID).'\' ORDER BY m.name'; //echo $sql;
		$this->_db->query($sql);
		if ($this->_db->numRows())
		{
			while ($r = $this->_db->nextRecord())
			{
				$models[$r['id']] = $model->copy($r);
			}
		}
		return $models;
	}

	function getItemFt($ID)
	{
		$ret = array();
		$ftg = $this->create(EL_TS_FTG);
		$ft  = $this->create(EL_TS_FT);
		$sql = 'SELECT g.name AS grName, '.$ft->listAttrsToStr('f').', rel.value, rel.is_announced '
					.'FROM '.$this->_tb['tbftg'].' AS g, '
					.$this->_tb['tbft'].' AS f, '
					.$this->_tb['tbft2i'].' AS rel WHERE '
					.'rel.i_id=\''.$ID.'\' AND f.id=rel.ft_id AND g.id=f.gid '
					.'ORDER BY '
					.'IF(g.sort_ndx>0, LPAD(g.sort_ndx, 4, "0"), "9999"), g.name,'
					.'IF(f.sort_ndx>0, LPAD(f.sort_ndx, 4, "0"), "9999"), f.name';
		$this->_db->query($sql);
		while ($r = $this->_db->nextRecord() )
		{
			if (empty($ret[$r['gid']]))
			{
				$ret[$r['gid']] = $ftg->copy( array('id'=>$r['gid'], 'name'=>$r['grName']));
			}
			$ret[$r['gid']]->setFeature($ft->copy($r), $r['is_announced']);
			$ret[$r['gid']]->setItemFeatureValue($r['id'], $ID, $r['value']);
		}
		return $ret;
	}

	function getMnfItems($mnf)
	{
	   $items = array();
		$item  = $this->create(EL_TS_ITEM);
		$sql = 'SELECT '.$item->listAttrsToStr('i').', '
		      .' "'.$mnf->name.'" AS mnfName, "'.$mnf->country.'" AS mnfCountry '
				.'FROM '.$this->_tb['tbi'].' AS i '
				.'WHERE i.mnf_id=\''.$mnf->ID.'\' '
				.'ORDER BY i.code, i.name';
		$this->_db->query($sql);
		if ($this->_db->numRows())
		{
			while ($r = $this->_db->nextRecord())
			{
				$items[$r['id']] = $item->copy($r);
			}
		}
		if (!empty($items))
		{
			$model = $this->create(EL_TS_MODEL);
			$sql = 'SELECT '.$model->listAttrsToStr().' FROM '.$this->_tb['tbm']
					.' WHERE i_id IN ('.implode(',', array_keys($items)).') ORDER BY code, name';
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord())
			{
				$items[$r['i_id']]->models[$r['id']] = $model->copy($r);
				$items[$r['i_id']]->mnfName    = $mnf->name;
		    $items[$r['i_id']]->mnfCountry = $mnf->country;
			}

			$sql = 'SELECT i.id, m.id AS mid, ft.name, ft.unit, ftv.value FROM '
      			.$this->_tb['tbi'].'    AS i, '
      			.$this->_tb['tbm'].'    AS m, '
      			.$this->_tb['tbft'].'   AS ft, '
      			.$this->_tb['tbft2m'].' AS ftv, '
      			.$this->_tb['tbftg'].'  AS g '
      			.'WHERE i.id IN ('.implode(',', array_keys($items)).') '
      			.'AND m.i_id=i.id '
      			.'AND ftv.m_id=m.id AND ftv.is_announced=\'1\' AND ft.id=ftv.ft_id AND g.id=ft.gid '
      			.'ORDER BY i.id, mid, '
      			.'IF(g.sort_ndx>0, LPAD(g.sort_ndx, 4, "0"), "9999"), g.name,'
      			.'IF(ft.sort_ndx>0, LPAD(ft.sort_ndx, 4, "0"), "9999"), ft.name';
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord())
			{
			  if (!isset($items[$r['id']]->ft[$r['mid']]))
			  {
			    $items[$r['id']]->ft[$r['mid']] = array();
			  }
			  $items[$r['id']]->ft[$r['mid']][] = array('name'=>$r['name'], 'unit'=>$r['unit'], 'value'=>$r['value']);
			}

		}
		return $items;
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

	function getModelsFt( $mIDs, $ann=false )
	{
		$ret = array();
		$ftg = $this->create(EL_TS_FTG);
		$ft  = $this->create(EL_TS_FT);
		$sql = 'SELECT g.name AS grName, '.$ft->listAttrsToStr('f').', rel.is_split, rel.m_id, rel.value, rel.is_announced '
					.'FROM '.$this->_tb['tbftg'].' AS g, '
					.$this->_tb['tbft'].' AS f, '
					.$this->_tb['tbft2m'].' AS rel WHERE '
					.'rel.m_id IN('.implode(',', $mIDs).')'.($ann ? ' AND rel.is_announced="1" ' : '').' AND f.id=rel.ft_id AND g.id=f.gid '
					.'ORDER BY '
					.'IF(g.sort_ndx>0, LPAD(g.sort_ndx, 4, "0"), "9999"), g.name,'
					.'IF(f.sort_ndx>0, LPAD(f.sort_ndx, 4, "0"), "9999"), f.name';

					//echo $sql;
		$this->_db->query($sql);
		while ($r = $this->_db->nextRecord() )
		{
			//elPrintR($r);
			if (empty($ret[$r['gid']]))
			{
				$ret[$r['gid']] = $ftg->copy( array('id'=>$r['gid'], 'name'=>$r['grName']));
			}
			//if (empty($ret[$r['gid']]->fetaures[$r['id']]))
			$ret[$r['gid']]->setFeature($ft->copy($r), $r['is_announced'], $r['is_split']); //echo $r['is_split'];
			$ret[$r['gid']]->setModelFeatureValue($r['id'], $r['m_id'], $r['value']);
		}
		return $ret;
	}

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