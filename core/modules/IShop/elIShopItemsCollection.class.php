<?php

class elIShopItemsCollection
{
	var $_tb    = '';
	var $tbi2c  = '';
	var $tbmnf  = '';
	var $tbtm   = '';
	var $tbp2i  = '';
	var $_db     = null;
	var $_item   = null;
	var $_count  = array();
	var $_sortID = EL_IS_SORT_NAME;
	var $_sort   = array(
		EL_IS_SORT_NAME  => 'name',
		EL_IS_SORT_CODE  => 'code, name',
		EL_IS_SORT_PRICE => 'price DESC, name',
		EL_IS_SORT_TIME  => 'crtime DESC, name',
		EL_IS_SORT_RAND  => 'RAND()'
	);

	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elIShopItemsCollection() {
		$this->_db     = & elSingleton::getObj('elDb');
	}
		
	/**
	 * return number of items belongs to parent object with $type and $ID
	 *
	 * @param  int  $type  type of parent object (category/manufacturer/trademark)
	 * @param  int  $ID    parent ID
	 * @return int
	 **/
	function count($type, $ID) {
		if (!isset($this->_count[$type])) {
			$this->_count($type);
		}
		return isset($this->_count[$type][$ID]) ? $this->_count[$type][$ID] : 0;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function countAll() {
		$r = $this->_db->queryToArray('SELECT COUNT(id) AS num FROM '.$this->_tb, null, 'num');
		return $r[0];
	}

	/**
	 * return items collection by required parent type/id
	 *
	 * @param  int  $type    type of parent object (category/manufacturer/trademark)
	 * @param  int  $ID      parent ID
	 * @param  int  $offset  collection offset
	 * @param  int  $step    collection size
	 * @param  int  $sortID  sort items id
	 * @return array
	 **/
	function create($type, $ID, $offset=0, $step=0, $sortID=null) {
		$coll = array();
		$sort = isset($this->_sort[$sortID]) ? $this->_sort[$sortID] : $this->_sort[$this->_sortID];
		switch ($type) {
			case EL_IS_MNF:
				$coll = $this->_item->collection(true, true, 'mnf_id='.intval($ID), $sort);
				break;
				
			case EL_IS_TM:
				$coll = $this->_item->collection(true, true, 'tm_id='.intval($ID), $sort);
				break;
				
			case 'special':
				$coll = $this->_item->collection(true, true, 'special="'.intval($ID).'"', $sort, $offset, $step);
				break;
				
			case 'search':
				if (is_array($ID) && !empty($ID)) {
					$coll = $this->_item->collection(true, true, 'id IN ('.implode(',', $ID).')', $sort);
				}
				break;
				
			default:
				$sql = 'SELECT %s FROM %s AS i2c, %s AS i WHERE i2c.c_id=%d AND i.id=i2c.i_id ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), %s';
				if ($step > 0)
				{
					$sql .= ' LIMIT %d, %d ';
				}
				$sql = sprintf($sql, $this->_item->attrsToString('i'), $this->tbi2c, $this->_tb, $ID, $sort, $offset, $step);
				$this->_db->query($sql);
				while ($r = $this->_db->nextRecord()) {
					$coll[$r['id']] = $this->_item->copy($r);
			    }
		}

		if ($coll) {
			$vals = $this->_item->fetchPropsValues(array_keys($coll));
			foreach ($vals as $v) {
				if (!isset($coll[$v['i_id']]->propVals[$v['p_id']])) {
					$coll[$v['i_id']]->propVals[$v['p_id']] = array();
				}
				$coll[$v['i_id']]->propVals[$v['p_id']][] = $v['value'];
			}
		}

		return $coll;
	}

	/**
	 * load items counts for required parent type
	 *
	 * @param  int  $type  type of parent object (category/manufacturer/trademark)
	 * @return void
	 **/
	function _count($type = EL_IS_CAT) {
		$cnt = array();
		switch ($type) {
			case EL_IS_MNF:
				$sql = sprintf('SELECT mnf_id, COUNT(id) AS num FROM %s GROUP BY mnf_id', $this->_tb);
				$cnt = $this->_db->queryToArray($sql, 'mnf_id', 'num');
				break;
				
			case EL_IS_TM:
				$sql = sprintf('SELECT tm_id, COUNT(id) AS num FROM %s GROUP BY tm_id', $this->_tb);
				$cnt = $this->_db->queryToArray($sql, 'tm_id', 'num');
				break;
				
			default:
				$sql = 'SELECT i2c.c_id, COUNT(i2c.i_id) AS num FROM %s AS i2c, %s AS i WHERE i2c.i_id=i.id GROUP BY (i2c.c_id)';
				$sql = sprintf($sql, $this->tbi2c, $this->_tb);
				$cnt = $this->_db->queryToArray($sql, 'c_id', 'num');
		}
		
		$this->_count[$type] = $cnt;
	}

}

