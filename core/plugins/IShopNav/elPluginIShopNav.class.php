<?php
include_once EL_DIR_CORE.'lib/elPlugin.class.php';
/**
 * Display ishops menus
 *
 * @package plugins
 **/
class elPluginIShopNav extends elPlugin {
	/**
	 * 
	 *
	 * @var array
	 **/
	var $src = array();
	/**
	 * object to manage menus
	 *
	 * @var elPluginManagerIShopNav
	 **/
	var $_manager = null;
	/**
	 * menus positions data
	 *
	 * @var array
	 **/
	var $_posNfo = array(
		EL_POS_LEFT  => array('CAT_MENU_LEFT',  'left.html'),
		EL_POS_RIGHT => array('CAT_MENU_RIGHT', 'right.html'),
		EL_POS_TOP   => array('CAT_MENU_TOP',   'top.html')
		);
		
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elPluginIShopNav($name, $pageID, $params) {
		parent::elPlugin($name, $pageID, $params);
		$this->_nav   = & elSingleton::getObj('elNavigator');
		foreach($this->_nav->findByModule('IShop') as $id) {
			$this->src[$id] = $this->_nav->getPageName($id);
		}
	}
	
	/**
	 * render menus
	 *
	 * @return void
	 **/
	function onUnload() {
		$db = & elSingleton::getObj('elDb');
		$src = $db->queryToArray('SELECT m.id, m.src, m.type, m.pos, m.name, m.deep, m.tpl FROM el_plugin_ishop_nav AS m, el_plugin_ishop_nav2page AS p WHERE (p.page_id="'.$this->pageID.'" OR p.page_id=1) AND m.id=p.id ORDER BY id', 'id');
		if (empty($src)) {
			return;
		}
		include_once EL_DIR_CORE.'modules'.DIRECTORY_SEPARATOR.'IShop'.DIRECTORY_SEPARATOR.'elIShopFactory.class.php';

		$rnd = & elSingleton::getObj('elSiteRenderer');
		
		foreach ($src as $id=>$s) {
			if (!isset($this->src[$s['src']])) {
				$this->_loadManager();
				$this->_manager->rm($id);
				continue;
			}
			list($pos, $tplVar, $tpl) = $this->_getPosInfo($s['pos'], $s['tpl']);
			// echo "$pos, $tplVar, $tpl";
			$menu    = array();
			$url     = $this->_nav->getPageURL($s['src']);
			$factory = & elSingleton::getObj('elIShopFactory', $s['src']);
			switch ($s['type']) {
				case 'mnfs':
					$view = EL_IS_VIEW_MNFS;
					$mnfs = $factory->getAllFromRegistry(EL_IS_MNF);
					$tms = $s['deep'] > 0 && $s['pos'] != EL_POS_TOP
						? $factory->getAllFromRegistry(EL_IS_TM)
						: array();
					foreach ($mnfs as $m) {
						$menu[] = array(
							'id'    => $m->ID,
							'name'  => $m->name,
							'level' => 1,
							'url'   => $url.'mnfs/mnf/'.$m->ID.'/'
							);
						foreach ($tms as $tm) {
							if ($tm->mnfID == $m->ID) {
								$menu[] = array(
									'id'    => $tm->ID,
									'name'  => $tm->name,
									'level' => 2,
									'url'   => $url.'mnfs/tm/'.$m->ID.'/'.$tm->ID.'/'
									);
							}
						}
					}
					break;
				case 'types':
					$view = EL_IS_VIEW_TYPES;
					$types = $factory->getAllFromRegistry(EL_IS_ITYPE);
					foreach ($types as $t) {
						$menu[$t->ID] = array(
							'id'    => $t->ID,
							'name'  => $t->name,
							'level' => 1,
							'url'   => $url.'types/type/'.$t->ID.'/'
							);
					}
					break;
				default:
					$view = EL_IS_VIEW_CATS;
					$cat = $factory->create(EL_IS_CAT, 1);
					foreach ($cat->getTreeToArray($s['pos'] == EL_POS_TOP ? 1 : $s['deep']) as $c) {
						$menu[] = $c + array('url' => $url.'cats/'.$c['id'].'/');
					}

			}
			
			foreach ($menu as $id=>$page) {
				
				if ($s['src'] == $this->pageID && $GLOBALS['ishopView'] == $view ) {
					if ($view == EL_IS_VIEW_MNFS) {
						if (($page['level'] == 2 && !empty($GLOBALS['ishopTmID']) && $page['id'] == $GLOBALS['ishopTmID'])
						|| ($page['level'] == 1 && $GLOBALS['ishopParentID'] == $page['id'])) {
							$cssClass .= '-selected';
							$menu[$id]['selected'] = true;
						} 
					} elseif ($GLOBALS['ishopParentID'] == $page['id']) {
						$cssClass .= '-selected';
						$menu[$id]['selected'] = true;
					}
				}
			}
			$rnd->rndDefaultMenu($menu, $pos, $tplVar, $tpl, $ico, $s['name'], '-catmenu');
		}
	}
	
	/**
	 * manage menus
	 *
	 * @param  array  $args  arguments from url
	 * @return void
	 **/
	function conf($args) {
		// elPrintr($this);
		$this->_loadManager();
		$this->_manager->run($args);
	}
	
	function _getPosInfo($pos, $altTpl=null) {
		$pos = !empty($this->_posNfo[$pos]) ? $pos : EL_POS_LEFT;
		return array($pos, $this->_posNfo[$pos][0], (empty($altTpl) ? $this->_posNfo[$pos][1] : $altTpl));
	}
	
	/**
	 * load manager object
	 *
	 * @return void
	 **/
	function _loadManager() {
		include_once EL_DIR_CORE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'IShopNav'.DIRECTORY_SEPARATOR.'elPluginManagerIShopNav.class.php';
		$this->_manager = & new elPluginManagerIShopNav($this);
	}
	
} // END class 

?>