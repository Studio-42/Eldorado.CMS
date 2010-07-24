<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'constants.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopItem.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopManufacturer.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopItemType.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopProperty.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopTm.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopItemsCollection.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopFinder.class.php';

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
	 * default sort
	 *
	 * @var int
	 **/
	var $itemsSortID = 0;
	/**
	 * ItemsCollection for use from created objects as $this->_factory->ic
	 *
	 * @var elIShopItemsCollection
	 **/
	var $ic = null;
	/**
	 * database
	 *
	 * @var elDb
	 **/
	var $_db = null;
	/**
	 * internal registry
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
		'tbgal'     => 'el_ishop_%d_gallery'
	);
	/**
	 * classes list
	 *
	 * @var array
	 **/
	var $_classes = array(
		EL_IS_CAT => array(
			'name' => 'elCatalogCategory',
			'tbs'  => array('tbc', 'tbi2c')
		),
		EL_IS_ITEM => array(
			'name' => 'elIShopItem',
			'tbs'  => array('tbi', 'tbc', 'tbi2c', 'tbmnf', 'tbp2i', 'tbp', 'tbpdep', 'tbmnf', 'tbtm', 'tbgal')
		),
		EL_IS_MNF => array(
			'name' => 'elIShopManufacturer',
			'tbs'  => array('tbmnf')
		),
		EL_IS_ITYPE => array(
			'name' => 'elIShopItemType',
			'tbs'  => array('tbt', 'tbp')
		),
		EL_IS_PROP => array(
			'name' => 'elIShopProperty',
			'tbs'  => array('tbp', 'tbpval', 'tbp2i', 'tbpdep')
		),
		EL_IS_TM => array(
			'name' => 'elIShopTm',
			'tbs'  => array('tbtm')
		),
		EL_IS_ITEMSCOL => array(
			'name' => 'elIShopItemsCollection',
			'tbs'  => array('tbi', 'tbi2c', 'tbmnf', 'tbtm', 'tbp2i')
		)
	);

	/**
	 * initilize factory
	 *
	 * @param  int    $pageID        current page ID
	 * @return void
	 **/
	function elIShopFactory($pageID) {
		$this->pageID = $pageID;
		$this->_db    = & elSingleton::getObj('elDb');
		foreach ($this->_tb as $k=>$tb) {
			$this->_tb[$k] = sprintf($tb, $this->pageID);
		}
		$this->ic = & $this->create(EL_IS_ITEMSCOL);
	}

	/**
	 * create and return object of required type
	 *
	 * @param  int   $hndl  obj type
	 * @param  int   $ID    obj ID
	 * @return object|null
	 **/
	function create($hndl, $ID = 0) {
		elDebug('Factory create '.$this->_classes[$hndl]['name']."($ID)");
		if (empty($this->_classes[$hndl])) {
			return null;
		}

		$ID  = (int)$ID;
		$c   = $this->_classes[$hndl]['name'];
		$tbs = $this->_classes[$hndl]['tbs'];
		$i   = count($tbs);
		$obj = new $c();
		while ($i--) {
			$member = ($i == 0) ? '_tb' : $tbs[$i];
			$obj->{$member} = $this->_tb[$tbs[$i]];
		}
		$obj->_factory = & $this;

		if ($hndl == EL_IS_ITEMSCOL) // item collection is a little bit special, handle with care :)
		{
			$obj->_item = $this->create(EL_IS_ITEM);
			$obj->_sortID = isset($obj->_sort[$this->itemsSortID]) ? $this->itemsSortID : EL_IS_SORT_NAME;
		}
		else
		{
			if ($ID > 0)
			{
				$obj->idAttr($ID);
				$obj->fetch();
			}
		}
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
		!isset($this->_registry[$type]) && $this->_loadRegistry($type);
		return $this->_registry[$type];
	}

	/**
	 * return types (id/name) list
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
	 * return tables list
	 *
	 * @return array
	 **/
	function tbs() {
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

		$this->_registry[$type] = $obj->collection(true, true, null, 'name, id');
		if ($type == EL_IS_ITYPE && empty($this->_registry[$type])) {
			$obj->attr('name', m('Default product'));
			$obj->save();
			$this->_registry[$type] = $obj->collection(true, true, null, 'name, id');
		}
		
	}

} // END class 

?>
