<?php

define ('EL_IS_PROP_STR',   1);
define ('EL_IS_PROP_TXT',   2);
define ('EL_IS_PROP_LIST',  3);
define ('EL_IS_PROP_MLIST', 4);

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopItem.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopManufacturer.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopItemType.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopProperty.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopTm.class.php';

/**
 * IShop factory and regisry for item types
 *
 * @package IShop
 **/
class elIShopFactory {
	/**
	 * current page ID
	 *
	 * @var int
	 **/
	var $pageID = 0;
	/**
	 * madule config
	 *
	 * @var array
	 **/
	var $_conf = array();
	/**
	 * database
	 *
	 * @var elDb
	 **/
	var $_db    = null;
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	var $_registry = array();
	/**
	 * tables list
	 *
	 * @var array
	 **/
	var $_tb = array(
		'tbc'       => 'el_ishop_%d_cat',
		'tbi'       => 'el_ishop_%d_item',
		'tbmnf'     => 'el_ishop_%d_mnf',
		'tbtm'      => 'el_ishop_%d_tm',
		'tbi2c'     => 'el_ishop_%d_i2c',
		'tbt'       => 'el_ishop_%d_itype', // item types
		'tbp'       => 'el_ishop_%d_prop', //attrs
		'tbpval'    => 'el_ishop_%d_prop_value',
		'tbpdep'    => 'el_ishop_%d_prop_depend',
		'tbp2i'     => 'el_ishop_%d_p2i',	// item`s attrs
		'tbs'       => 'el_ishop_%d_search',
		'tbse'      => 'el_ishop_%d_se',
		'tbst'      => 'el_ishop_%d_st',
		'tbsp'      => 'el_ishop_%d_sp',
		'tbgal'     => 'el_ishop_%d_gallery'
		);
	/**
	 * classes list
	 *
	 * @var array
	 **/
	var $_classes = array(
		EL_IS_CAT => array(
			'name'=> 'elCatalogCategory',
			'tbs' => array('tbc', 'tbi2c')),
		EL_IS_ITEM => array(
			'name'=> 'elIShopItem',
			'tbs' => array('tbi', 'tbc', 'tbi2c', 'tbmnf', 'tbp2i', 'tbp', 'tbpdep', 'tbmnf', 'tbtm', 'tbgal')),
		EL_IS_MNF => array(
			'name'=> 'elIShopManufacturer',
			'tbs' => array('tbmnf', 'tbi')),
		EL_IS_ITYPE => array(
			'name'=> 'elIShopItemType',
			'tbs' => array('tbt', 'tbp')),
		EL_IS_PROP => array(
			'name'=> 'elIShopProperty',
			'tbs' => array('tbp', 'tbpval', 'tbp2i', 'tbpdep')),
		EL_IS_TM => array(
			'name' => 'elIShopTm',
			'tbs'  => array('tbtm')),
		);
    
	/**
	 * initilize factory
	 *
	 * @param  int    $pageID  current page ID
	 * @param  array  conf     module config
	 * @return void
	 **/
	function init($pageID, $conf) {
		$this->pageID = $pageID;
		foreach ($this->_tb as $k=>$tb) {
			$this->_tb[$k] = sprintf($tb, $this->pageID);
		}
		$this->_db    = & elSingleton::getObj('elDb');
		$this->_conf  = $conf;
	}

	/**
	 * create and return object of required type
	 *
	 * @param  int   $hndl  obj type
	 * @param  int   $ID    obj ID
	 * @return object|null
	 **/
	function create($hndl, $ID=0) {
		
		if (empty($this->_classes[$hndl])) {
			return null;
		}

		$c = $this->_classes[$hndl]['name'];
		$obj = new $c();
		$tbs = $this->_classes[$hndl]['tbs'];
		$i = count($tbs);
		while ($i--) {
			$member = ($i==0) ? '_tb' : $tbs[$i];
			$obj->{$member} = $this->_tb[$tbs[$i]];
		}

		$obj->idAttr((int)$ID);
		$obj->fetch();
		return $obj;
	}

	/**
	 * return object from regisry
	 *
	 * @param  int  $type  object type
	 * @param  int  $ID    object id
	 * @return object
	 **/
	function getFromRegistry($type, $ID) {
		if ($type != EL_IS_MNF && $type != EL_IS_TM && $type != EL_IS_ITYPE) {
			$type = EL_IS_ITYPE;
		}
		!isset($this->_registry[$type]) && $this->_loadRegistry($type);
		return isset($this->_registry[$type][$ID]) 
			? $this->_registry[$type][$ID]
			: ($type != EL_IS_ITYPE ? $this->create($type) : $this->_registry[$type][array_pop(array_keys($this->_registry[$type]))]);
	}

	/**
	 * return all object of required type from regisry
	 *
	 * @param  int  $type  object type
	 * @return array
	 **/
	function getAllFromRegistry($type=EL_IS_ITYPE) {
		if ($type != EL_IS_MNF && $type != EL_IS_TM && $type != EL_IS_ITYPE) {
			$type = EL_IS_ITYPE;
		}
		!isset($this->_registry[$type]) && $this->_loadRegistry($type);
		return $this->_registry[$type];
	}

	/**
	 * return types id/name list
	 *
	 * @return array
	 **/
	function getTypesList() {
		$types = $this->getAllFromRegistry(EL_IS_ITYPE);
		$ret = array();
		foreach ($types as  $id=>$t) {
			$ret[$id] = $t->name;
		}
		return $ret;
	}

	/**
	 * return trademarks for required manufacturer
	 *
	 * @param  int  $ID
	 * @return array
	 **/
	function getTmsByMnf($ID) {
		$all = $this->getAllFromRegistry(EL_IS_TM);
		$ret = array();
		foreach ($all as $id => $tm) {
			if ($tm->mnfID == $ID) {
				$ret[$id] = $tm;
			}
		}
		return $ret;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function countMnfItems($ID) {
		if (!isset($this->_mnfItemsCnt)) {
			$this->_mnfItemsCnt = $this->_db->queryToArray(sprintf('SELECT mnf_id, COUNT(id) AS num FROM %s GROUP BY mnf_id', $this->_tb['tbi']), 'mnf_id', 'num');
		}
		return isset($this->_mnfItemsCnt[$ID]) ? $this->_mnfItemsCnt[$ID] : 0;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function countTmItems($ID) {
		if (!isset($this->_tmItemsCnt)) {
			$this->_tmItemsCnt = $this->_db->queryToArray(sprintf('SELECT tm_id, COUNT(id) AS num FROM %s GROUP BY tm_id', $this->_tb['tbi']), 'tm_id', 'num');
		}
		return isset($this->_tmItemsCnt[$ID]) ? $this->_tmItemsCnt[$ID] : 0;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function getItemsSort() {
		return $this->_conf['itemsSortID'];
	}


      function getProperty($ID)
      {
        return $this->create(EL_IS_PROP, $ID);
      }

      function getProperties()
      {
        $prop = $this->create(EL_IS_PROP);
        return $prop->collection(true, true, null, 'sort_ndx');
      }

      function getItem($ID, $typeID=0)
      {
        $item = $this->create(EL_IS_ITEM, $ID);
        $item->mnfNfo = $this->_conf['mnfNfo'];  
        if ( empty($item->type) && !empty($typeID) )
        {
          $item->setType( $this->getItemType($typeID) );
        }
        return $item;
      }




	/**
	 * return items from category
	 *
	 * @param  int  $catID
	 * @param  int  $offset
	 * @param  int  $step
	 * @return array
	 **/
	function getItems($catID, $offset, $step) {
		$item = $this->create(EL_IS_ITEM);
		return $item->getByCategory($catID, $this->_conf['itemsSortID'], $offset, $step);
	}

      function countItemsByType( $typeID )
      {
        $this->_db->query('SELECT id FROM '.$this->_tb['tbi'].' WHERE type_id='.intval($typeID));
        return $this->_db->numRows();
      }





      
      function getSearchManager()
      {
        include_once 'elIShopSearch.lib.php'; 
        return new elIShopSearchManager($this->pageID, $this->_tb, (int)$this->_conf['searchColumnsNum'], $this->_conf['searchTypesLabel']);
      }
      
      function getSearchAdmin()
      {
        $sm = $this->getSearchManager();
        $sa = & elSingleton::getObj('elIShopSearchAdmin');
//        $sa = & new elIShopSearchAdmin();
        $sa->init($sm);
        return $sa;
      }
      

	/**
	 * return tables list
	 *
	 * @return array
	 **/
	function getTbs() {
		return $this->_tb;
	}

	/**
	 * return table by abbr name
	 *
	 * @param  string  $name  key of elIShopFactory::_tb
	 * @return string
	 **/
	function tb($name) {
		return isset($this->_tb[$name]) ? $this->_tb[$name] : null;
	}
	
	/*********************************************************/
	//                     PRIVATE                           //
	/*********************************************************/
	


	/**
	 * load part of regisry
	 *
	 * @param  int   item type to load
	 * @return void
	 **/
	function _loadRegistry($type) {
		$obj = $this->create($type);
		$this->_registry[$type] = $obj->collection(true, true);
		if ($type == EL_IS_ITYPE && empty($this->_registry[$type])) {
			$obj->attr('name', m('Default product'));
			$obj->save();
			$this->_registry[$type] = $obj->collection(true, true);
		}
	}


} // END class 

?>
