<?php
/**
 * Display/manage menus for allsite catalogs
 *
 * @package plugins
 **/
class elPluginCatsNav extends elPlugin {
	/**
	 * db object
	 *
	 * @var elDb
	 **/
	var $_db;
	/**
	 * available modules-sources list
	 *
	 * @var array
	 **/
	var $_src = array();
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
	 * menu types list
	 *
	 * @var array
	 **/
	var $_types = array(
		'cats'  => 'Categories',
		'mnfs'  => 'Manufacturers',
		'types' => 'Products types'
		);
	/**
	 * plugin redirect url
	 *
	 * @var string
	 **/
	var $_url = '';
	/**
	 * modules list
	 *
	 * @var array
	 **/
	var $_modules = array('DocsCatalog', 'FileArchive', 'GoodsCatalog', 'TechShop', 'IShop', 'VacancyCatalog');	
	/**
	 * tables names templates
	 *
	 * @var array
	 **/
	var $_tbs = array(
		'DocsCatalog'    => 'el_dcat_%d_cat',
		'GoodsCatalog'   => 'el_gcat_%d_cat',
		'FileArchive'    => 'el_fa_%d_cat',
		'IShop'          => 'el_ishop_%d_cat',
		'TechShop'       => 'el_techshop_%d_cat',
		'VacancyCatalog' => 'el_vaccat_%d_cat'
		);
	/**
	 * Current module args from elNavigator
	 *
	 * @var array
	 **/
	var $_args = array();
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elPluginCatsNav($name, $pageID, $params) {
		parent::elPlugin($name, $pageID, $params);
		$this->_nav  = & elSingleton::getObj('elNavigator');
		$this->_args = $this->_nav->getRequestArgs();
		foreach ($this->_modules as $m) {
			foreach($this->_nav->findByModule($m) as $id) {
				$this->_src[$id] = $this->_nav->getPageName($id);
			}
		}
		$this->_url = EL_URL.'pl_conf/CatsNav/';
	}
		
	/**
	 * render menus
	 *
	 * @return void
	 **/
	function onUnload() {
		$db = & elSingleton::getObj('elDb');
		$src = $db->queryToArray('SELECT m.id, m.src, m.type, m.pos, m.name, m.deep, m.tpl FROM el_plugin_cats_nav AS m, el_plugin_cats_nav2page AS p WHERE (p.page_id="'.$this->pageID.'" OR p.page_id=1) AND m.id=p.id ORDER BY id', 'id');
		if (empty($src)) {
			return;
		}

		$rnd = & elSingleton::getObj('elSiteRenderer');

		foreach ($src as $id => $s) {
			if (!isset($this->_src[$s['src']])) {
				$this->_rm($id);
				unset($src[$id]);
				continue;
			}
			list($pos, $tplVar, $tpl) = $this->_getPosInfo($s['pos'], $s['tpl']);
			$menu    = array();
			$url     = $this->_nav->getPageURL($s['src']);
			$p       = $this->_nav->getPage($s['src']);
			$module  = $p['module'];

			switch ($module) {
				case 'IShop':
					switch ($s['type']) {
						case 'types':
							$menu = $this->_typesMenu($s, $url);
							break;
						case 'mnfs':
							$menu = $this->_mnfsMenuIShop($s, $url);
							break;
						default:
							$menu = $this->_catsMenu($s, $module, $url);
					}
					break;
				case 'TechShop':
					$menu = $s['type'] == 'mnfs' ? $this->_mnfsMenuTechShop($s, $url) : $this->_catsMenu($s, $module, $url);
					break;
				default:
					$menu = $this->_catsMenu($s, $module, $url);
			}
			$rnd->rndDefaultMenu($menu, $pos, $tplVar, $tpl, false, $s['name'], '-catmenu');
		}
	}
	
	/**
	 * manage menus
	 *
	 * @param  array  $args  arguments from url
	 * @return void
	 **/
	function conf($args) {
		$this->_db    = & elSingleton::getObj('elDb');
		$this->_menus = $this->_db->queryToArray('SELECT id, src, type, pos, name, deep, tpl FROM el_plugin_cats_nav ORDER BY id', 'id');
		$pages        = $this->_db->queryToArray('SELECT r.id, r.page_id, IF(r.page_id = 1, "'.m('Whole site').'", m.name) AS name FROM el_plugin_cats_nav2page AS r, el_menu AS m WHERE m.id=r.page_id');
		
		foreach ($this->_menus as $id=>$menu) {
			if (!isset($this->_src[$menu['src']])) {
				$this->_rm($id);
				unset($this->_menus[$id]);
			} else {
				$this->_menus[$id]['page_id'] = array();
				foreach ($pages as $p) {
					if ($p['id'] == $id) {
						$this->_menus[$id]['page_id'][$p['page_id']] = $p['name'];
					}
				}
			}
		}

		$this->_args = $args;
		$action = isset($args[0]) ? $args[0] : '';
		switch ($this->_arg(0)) {
			case 'edit':
				$this->_edit($this->_arg(1));
				break;
			case 'rm':
				$id = $this->_arg(1);
				if ($id < 1 || !isset($this->_menus[$id])) {
					elThrow(E_USER_WARNING, 'There is no such menu', null, $this->_url);
				}
				$this->_rm((int)$id);
				elMsgBox::put(m('Menu was removed'));
				elLocation($this->_url);
				break;
			default:
				$this->_displayAll();
		}
	}
	
	/**
	 * output json with module name
	 *
	 * @param  array  $args  arguments from url
	 * @return void
	 **/
	function call($args) {
		include_once EL_DIR_CORE.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';
		$id = !empty($args[0]) ? (int)$args[0] : 0;
		if (isset($this->_src[$id])) {
			$page = $this->_nav->getPage($id);
			exit(elJSON::encode(array('module' => $page['module'])));
		}
		exit(elJSON::encode(array('error' => 'Invalid id')));
	}
	
	/**
	 * Return categories menu
	 *
	 * @return array
	 **/
	function _catsMenu($s, $module, $url) {
		include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elCatalogCategory.class.php';
		$menu = array();
		$cat  = & new elCatalogCategory(0, sprintf($this->_tbs[$module], $s['src']));
		foreach ($cat->getTreeToArray($s['pos'] == EL_POS_TOP ? 1 : $s['deep']) as $c) {
			$c['url'] = $url.($module == 'IShop' ? 'cats/' : '').$c['id'].'/';
			$menu[$c['id']] = $c;
		}
		
		if ($s['src'] == $this->pageID && isset($menu[$GLOBALS['categoryID']])) {
			if ($module == 'IShop')
			{
				$c       = &elSingleton::getObj('elXmlConf');
				$conf    = $c->getGroup($s['src']);
				// check that we actually looking category and not mnf or type
				if ((isset($this->_args[0]) && ($this->_args[0] == 'cats')) || ($conf['default_view'] == 1))
				{
					$menu[$GLOBALS['categoryID']]['selected'] = true;
				}
			}
			else
			{
				$menu[$GLOBALS['categoryID']]['selected'] = true;
			}
		}
		// elPrintr($menu);
		return $menu;
	}
	
	/**
	 * Return ishop manufacturers menu
	 *
	 * @return array
	 **/
	function _mnfsMenuIShop($s, $url) {
		include_once EL_DIR_CORE.'modules'.DIRECTORY_SEPARATOR.'IShop'.DIRECTORY_SEPARATOR.'elIShopFactory.class.php';
		$menu    = array();
		$c       = &elSingleton::getObj('elXmlConf');
		$conf    = $c->getGroup($s['src']);
		$factory = & elSingleton::getObj('elIShopFactory', $s['src']);
		$mnfs    = $factory->getAllFromRegistry(EL_IS_MNF);
		$tms     = $s['deep'] > 0 && $s['pos'] != EL_POS_TOP
					? $factory->getAllFromRegistry(EL_IS_TM)
					: array();

		foreach ($mnfs as $mnf) {
			if ($conf['displayEmptyMnf'] || $mnf->countItems()) {
				$menu[$mnf->ID.'-0'] = array(
					'id'    => $mnf->ID,
					'name'  => $mnf->name,
					'level' => 1,
					'url'   => $url.'mnfs/mnf/'.$mnf->ID.'/'
					);
			} else {
				continue;
			}
			foreach ($tms as $tm) {
				if ($tm->mnfID == $mnf->ID && ($conf['displayEmptyTm'] || $tm->countItems())) {
					$menu[$mnf->ID.'-'.$tm->ID] = array(
						'id'    => $tm->ID,
						'name'  => $tm->name,
						'level' => 2,
						'url'   => $url.'mnfs/tm/'.$mnf->ID.'/'.$tm->ID.'/'
						);
				}
			}
			if (($s['src'] == $this->pageID)
				&& (isset($this->_args[0]) && ($this->_args[0] == 'mnfs')))
			{
				if (isset($GLOBALS['ishopTmID']) && isset($menu[$GLOBALS['ishopParentID'].'-'.$GLOBALS['ishopTmID']])) {
					$menu[$GLOBALS['ishopParentID'].'-'.$GLOBALS['ishopTmID']]['selected'] = true;
				} elseif (isset($menu[$GLOBALS['ishopParentID'].'-0'])) {
					$menu[$GLOBALS['ishopParentID'].'-0']['selected'] = 1;
				}
				// echo $GLOBALS['ishopParentID'].' '.$GLOBALS['ishopTmID'];
			}
		}
		return $menu;
	}
	
	
	/**
	 * Return IShop types menu
	 *
	 * @return array
	 **/
	function _typesMenu($s, $url) {
		include_once EL_DIR_CORE.'modules'.DIRECTORY_SEPARATOR.'IShop'.DIRECTORY_SEPARATOR.'elIShopFactory.class.php';
		$menu    = array();
		$c       = &elSingleton::getObj('elXmlConf');
		$conf    = $c->getGroup($s['src']); 
		$factory = & elSingleton::getObj('elIShopFactory', $s['src']);
		foreach ($factory->getAllFromRegistry(EL_IS_ITYPE) as $t) {
			if ($conf['displayEmptyTypes'] || $t->countItems()) {
				$menu[$t->ID] = array(
					'id'    => $t->ID,
					'name'  => $t->name,
					'level' => 1,
					'url'   => $url.'types/type/'.$t->ID.'/'
					);
			}
		}
		if (
			(($s['src'] == $this->pageID) && isset($menu[$GLOBALS['ishopParentID']]))
			&& (
				(isset($this->_args[0]) && ($this->_args[0] == 'types'))
				|| ($conf['default_view'] == 3)
			)
		)
		{
			$menu[$GLOBALS['ishopParentID']]['selected'] = 1;
		}
		return $menu;
	}
	
	/**
	 * return menu with TechShop manufacturers
	 *
	 * @return array
	 **/
	function _mnfsMenuTechShop($s, $url) {
		$tb = sprintf('el_techshop_{pageID}_manufact', $s['src']);
		$db = & elSingleton::getObj('elDb');
		foreach ($db->queryToArray('SELECT id, name FROM '.$tb.' ORDER BY name', 'id', 'name') as $id=>$name) {
			$menu[$id] = array(
				'id'    => $id,
				'name'  => $name,
				'level' => 1,
				'url'   => $url.'mnf_items/'.$id.'/'
				);
		}
		return $menu;
	}
	
	/**
	 * Create/edit menu
	 *
	 * @param  int   $id  menu id
	 * @return void
	 **/
	function _edit($id) {
		if (empty($this->_src)) {
			elThrow(E_USER_WARNING, 'There no one installed catalog was found', null, $this->_url);
		}
		
		$deep = array(m('All'), 1, 2, 3);
		$tpls = scandir('./style/menus');
		$tpls = array_diff($tpls, array('.', '..', 'add-bottom.html', 'add-top.html', 'left.html', 'right.html', 'sub-top.html', 'top.html'));
		
		$menu = isset($this->_menus[$id])
			? $this->_menus[$id]
			: array(
				'id'     => 0,
				'src' => 0,
				'type'   => 'cats',
				'pos'    => 'l',
				'deep'   => 0,
				'name'   => '',
				'tpl'    => '',
				'page_id' => array(1 => m('Whole site'))
				);
		$form = & elSingleton::getObj('elForm', 'cats_nav_form',  m(!$menu['id'] ? 'Create catalog menu' : 'Edit catalog menu'));
		$form->setRenderer(elSingleton::getObj('elTplFormRenderer'));
		$form->add(new elSelect('src',  m('Catalog'), $menu['src'], $this->_src));
		$form->add(new elText('name',   m('Name'), $menu['name']));
		$form->add(new elSelect('type', m('Menu type'), $menu['type'], array_map('m', $this->_types)));
		$form->add(new elSelect('pos',  m('Position'), $menu['pos'], $GLOBALS['posLRT']));
		$form->add(new elSelect('deep', m('How many levels of categories display'), $menu['deep'], $deep));
		$form->add(new elSelect('tm',   m('Display trademarks/models'), $menu['deep'], $GLOBALS['yn']));
		$pages = new elMultiSelectList('page_id', m('Pages'), array_keys($menu['page_id']), elGetNavTree('+', m('Whole site')));
		$pages->setSwitchValue(1);
		$form->add($pages);
		if (!empty($tpls)) {
			$_tpls = array('' => m('No'));
			foreach ($tpls as $tpl) {
				$_tpls[$tpl] = $tpl;
			}
			$form->add(new elSelect('tpl', m('Use alternative template'), $menu['tpl'], $_tpls));
		}
		$url = EL_URL.'__pl__/CatsNav/';
		$js = "
			$('#cats_nav_form #src').change(function() {
				var v = $(this).val(),
					t = $('#cats_nav_form #type').eq(0);
				
				window.console.log(t)
				$.ajax({
					url : '".$url."'+v,
					type : 'get',
					dataType : 'json',
					error : function() { window.console && window.console.log && window.console.log('error'); },
					success : function(data) {
						if (data.error) {
							window.console && window.console.log && window.console.log(data.error);
						}
						switch (data.module) {
							case 'IShop':
								t.children('option').removeAttr('disabled');
								break;
							case 'TechShop':
								t.children('option').removeAttr('disabled').eq(2).attr('disabled', 'on');
								break;
							default:
								t.children('option').attr('disabled', 'on').eq(0).removeAttr('disabled')
						}
						t.val('cats').change();
					}
				});
			});
		
			$('#cats_nav_form #type').change(function() {
				var v = $(this).val(),
					d = $('#cats_nav_form #deep').parents('tr').eq(0),
					t = $('#cats_nav_form #tm').parents('tr').eq(0);
				if (v == 'mnfs') {
					t.show();
					d.hide();
				} else if (v == 'cats') {
					d.show();
					t.hide();
				} else {
					d.add(t).hide();
				}
			}).trigger('change');
		";
		elAddJs($js, EL_JS_SRC_ONREADY);
		
		if (!$form->isSubmitAndValid()) {
			$rnd = & elSingleton::getObj('elTE');
			return $rnd->assignVars('PAGE', $form->toHtml(), true);
		}
		
		$data = $form->getValue();
		$src  = isset($this->_src[$data['src']]) ? $data['src'] : array_pop(array_keys($this->_src));
		$type = isset($this->_types[$data['type']]) ? $data['type'] : 'cats';
		$pos  = isset($GLOBALS['posLRT'][$data['pos']]) ? $data['pos'] : 'l';
		$deep = $type == 'cats' ? $data['deep'] : ($type == 'mnfs' ? $data['tm'] : 0);
		$tpl  = isset($data['tpl']) ? $data['tpl'] : '';
		
		$sql = $menu['id']
			? sprintf('UPDATE el_plugin_cats_nav SET src="%d", pos="%s", type="%s", deep="%d", name="%s", tpl="%s" WHERE id="%d"', $src, $pos, $type, $deep, mysql_real_escape_string($data['name']), mysql_real_escape_string($tpl), $id)
			: sprintf('INSERT INTO el_plugin_cats_nav (src, pos, type, deep, name, tpl) VALUES (%d, "%s", "%s", "%d", "%s", "%s")', $src, $pos, $type, $deep, mysql_real_escape_string($data['name']), mysql_real_escape_string($tpl)) ;

		if (!$this->_db->query($sql)) {
			elThrow(E_USER_WARNING, 'Unable to create/edit menu', null, $this->_url);
		}
		if (!$menu['id']) {
			$menu['id'] = $this->_db->insertID();
		}
		
		$this->_db->query('DELETE FROM el_plugin_cats_nav2page WHERE id="'.$menu['id'].'"');
		$this->_db->optimizeTable('el_plugin_cats_nav2page');
		if (is_array($data['page_id']) && !empty($data['page_id'])) {
			$this->_db->prepare('INSERT INTO el_plugin_cats_nav2page (id, page_id) VALUES ', '(%d, %d)');
			foreach ($data['page_id'] as $pageID) {
				$this->_db->prepareData(array($menu['id'], $pageID));
			}
			$this->_db->execute();
		}
		elMsgBox::put(m('Data saved'));
		elLocation($this->_url);
	}
	
	/**
	 * Remove menu from db
	 *
	 * @param  int   $id  menu id
	 * @return void
	 **/
	function _rm($id) {
		$this->_db->query('DELETE FROM el_plugin_cats_nav WHERE id='.intval($id));
		$this->_db->query('DELETE FROM el_plugin_cats_nav2page WHERE id='.intval($id));
		$this->_db->optimizeTable('el_plugin_cats_nav');
		$this->_db->optimizeTable('el_plugin_cats_nav2page');
	}
	
	/**
	 * Display menuslist
	 *
	 * @return void
	 **/
	function _displayAll() {
		$this->_types = array_map('m', $this->_types);
		$rnd = & elSingleton::getObj('elTE');
		$rnd->setFile('list', 'plugins/CatsNav/list.html');
		$rnd->assignVars('pluginName', 'CatsNav');
		
		foreach ($this->_menus as $m) {
			$data = array(
				'id'    => $m['id'],
				'name'  => $m['name'],
				'src'   => $this->_src[$m['src']],
				'type'  => $this->_types[$m['type']],
				'pos'   => $GLOBALS['posLRT'][$m['pos']],
				'pages' => implode(',', $m['page_id'])
				);
			$rnd->assignBlockVars('ISHOP_MENU', $data);
		}
		$rnd->parse('list', 'PAGE', true);
	}
	
	/**
	 * Return info about menu position/template
	 *
	 * @param  string  $pos     position
	 * @param  string  $altTpl  alternative template
	 * @return array
	 **/
	function _getPosInfo($pos, $altTpl=null) {
		$pos = !empty($this->_posNfo[$pos]) ? $pos : EL_POS_LEFT;
		return array($pos, $this->_posNfo[$pos][0], (empty($altTpl) ? $this->_posNfo[$pos][1] : $altTpl));
	}

} // END class 
?>
