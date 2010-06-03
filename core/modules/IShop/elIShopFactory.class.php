<?php






define ('EL_IS_PROP_STR',   1);
define ('EL_IS_PROP_TXT',   2);
define ('EL_IS_PROP_LIST',  3);
define ('EL_IS_PROP_MLIST', 4);

// if (!defined('EL_IS_USE_MNF'))
// {
// 	define('EL_IS_USE_MNF',    1);
// 	define('EL_IS_USE_TM',     2);
// 	define('EL_IS_USE_MNF_TM', 3);
// 
// 	define('EL_IS_SORT_NAME',  1);
// 	define('EL_IS_SORT_CODE',  2);
// 	define('EL_IS_SORT_PRICE', 3);
// 	define('EL_IS_SORT_TIME',  4);
// 	
// }

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopItem.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopManufacturer.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopItemType.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopProperty.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elIShopTm.class.php';

/**
 * IShop factory
 *
 * @package IShop
 **/
 
class elIShopFactory {
  var $pageID = 0;
  var $_types = array();
  var $_objs  = array();
  var $_db    = null;

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

  var $_classes = array(
      EL_IS_CAT   => array(
          'name'=> 'elCatalogCategory',
          'tbs' => array('tbc', 'tbi2c')),
      EL_IS_ITEM  => array(
          'name'=> 'elIShopItem',
          'tbs' => array('tbi', 'tbc', 'tbi2c', 'tbmnf', 'tbp2i', 'tbp', 'tbpdep', 'tbmnf', 'tbtm', 'tbgal')),
      EL_IS_MNF   => array(
          'name'=> 'elIShopManufacturer',
          'tbs' => array('tbmnf', 'tbi')),
      EL_IS_ITYPE   => array(
          'name'=> 'elIShopItemType',
          'tbs' => array('tbt', 'tbp')),
      EL_IS_PROP   => array(
          'name'=> 'elIShopProperty',
          'tbs' => array('tbp', 'tbpval', 'tbp2i', 'tbpdep')),
      EL_IS_TM => array(
          'name' => 'elIShopTm',
          'tbs'  => array('tbtm', 'tbmnf')),
      );


    var $_conf = array();
    
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
		$type         = $this->create(EL_IS_ITYPE);
		$this->_types = $type->collection(); 
		$this->_conf  = $conf;
	}

	/**
	 * Return category
	 *
	 * @param  int  $ID
	 * @return elCatalogCategory
	 **/
	function getCategory($ID=0) {
		return $this->create(EL_IS_CAT, $ID);
	}

      function &getItemType($ID)
      {
        if ( !empty($this->_types[$ID]) )
        {
          return $this->_types[$ID];
        }
        return $this->create(EL_IS_ITYPE, $ID);
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

      function getTypesList()
      {
        $ret = array();
        foreach ($this->_types as $ID=>$t)
        {
          $ret[$ID] = $t->name;
        }
        return $ret;
      }

      function getItemsTypes()
      {
        return $this->_types;
      }

      function getItems( $catID, $sortID, $offset, $step )
      {
        //$item = $this->_create(EL_IS_ITEM);
        $item = $this->getItem(0);
        return $item->getByCategory( $catID, $sortID, $offset, $step );
      }

      function countItemsByType( $typeID )
      {
        $this->_db->query('SELECT id FROM '.$this->_tb['tbi'].' WHERE type_id='.intval($typeID));
        return $this->_db->numRows();
      }

      function getMnf($ID)
      {
        return $this->create(EL_IS_MNF, $ID);
      }

      function getMnfs()
      {
        $mnf = $this->create(EL_IS_MNF);
        return $mnf->collection();
      }

      function getTm($ID)
      {
        return $this->create(EL_IS_TM, $ID);
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

} // END class 

?>
