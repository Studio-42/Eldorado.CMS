<?php

elLoadMessages('CommonAdmin');
/**
 * Manage plugin menus
 *
 * @package plugins
 **/
class elPluginManagerIShopNav {
	/**
	 * list of defined ishop menus
	 *
	 * @var array
	 **/
	var $_menus = array();
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
	 * db object
	 *
	 * @var elDb
	 **/
	var $_db;
	
	/**
	 * constructor
	 *
	 * @param  elPluginIShopNav  $master  plugin
	 * @return void
	 **/
	function elPluginManagerIShopNav(&$master) {
		$this->_master = & $master;
		$this->_types = array_map('m', $this->_types);
		$this->_db    = & elSingleton::getObj('elDb');
		$this->_menus = $this->_db->queryToArray('SELECT id, src, type, pos, name, deep, tpl FROM el_plugin_ishop_nav ORDER BY id', 'id');
		$pages        = $this->_db->queryToArray('SELECT r.id, r.page_id, IF(r.page_id = 1, "'.m('Whole site').'", m.name) AS name FROM el_plugin_ishop_nav2page AS r, el_menu AS m WHERE m.id=r.page_id');
		
		foreach ($this->_menus as $id=>$menu) {
			if (!isset($this->_master->src[$menu['src']])) {
				$this->rm($id);
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
	}
	
	/**
	 * execute method according to first argument
	 *
	 * @param  array  $args  arguments
	 * @return void
	 **/
	function run($args) {
		$action = isset($args[0]) ? $args[0] : '';
		switch ($action) {
			case 'edit':
				$this->_edit(isset($args[1]) ? (int)$args[1] : 0);
				break;
			case 'rm':
				if (empty($args[1]) || $args[1] < 1 || !isset($this->_menus[$args[1]])) {
					elThrow(E_USER_WARNING, 'There is no such menu', null, EL_URL.'pl_conf/IShopNav/');
				}
				$this->rm((int)$args[1]);
				elMsgBox::put(m('Menu was removed'));
				elLocation(EL_URL.'pl_conf/IShopNav/');
				break;
			default:
				$this->_displayAll();
		}
	}
	
	/**
	 * Create/edit menu
	 *
	 * @param  int   $id  menu id
	 * @return void
	 **/
	function _edit($id) {
		if (empty($this->_master->src)) {
			elThrow(E_USER_WARNING, 'There no one installed internet shop was found', null, EL_URL.'pl_conf/IShopNav/');
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
		$form = & elSingleton::getObj('elForm', 'ishop_nav_form',  m(!$menu['id'] ? 'Create internet shop menu' : 'Edit internet shop menu'));
		$form->setRenderer(elSingleton::getObj('elTplFormRenderer'));
		$form->add(new elSelect('src',  m('Internet shop'), $menu['src'], $this->_master->src));
		$form->add(new elText('name',   m('Name'), $menu['name']));
		$form->add(new elSelect('type', m('Menu type'), $menu['type'], $this->_types));
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
		
		$js = "
			$('#ishop_nav_form #type').change(function() {
				var v = $(this).val(),
					d = $('#ishop_nav_form #deep').parents('tr').eq(0),
					t = $('#ishop_nav_form #tm').parents('tr').eq(0);
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
		// elPrintr($data);
		// return;
		$src  = isset($this->_master->src[$data['src']]) ? $data['src'] : array_pop(array_keys($srcs));
		$type = isset($this->_types[$data['type']]) ? $data['type'] : 'cats';
		$pos  = isset($GLOBALS['posLRT'][$data['pos']]) ? $data['pos'] : 'l';
		$deep = $type == 'cats' ? $data['deep'] : ($type == 'mnfs' ? $data['tm'] : 0);
		$tpl  = isset($data['tpl']) ? $data['tpl'] : '';
		
		$sql = $menu['id']
			? sprintf('UPDATE el_plugin_ishop_nav SET src="%d", pos="%s", type="%s", deep="%d", name="%s", tpl="%s" WHERE id="%d"', $src, $pos, $type, $deep, mysql_real_escape_string($data['name']), mysql_real_escape_string($tpl), $id)
			: sprintf('INSERT INTO el_plugin_ishop_nav (src, pos, type, deep, name, tpl) VALUES (%d, "%s", "%s", "%d", "%s", "%s")', $src, $pos, $type, $deep, mysql_real_escape_string($data['name']), mysql_real_escape_string($tpl)) ;
		// echo $sql;
		if (!$this->_db->query($sql)) {
			elThrow(E_USER_WARNING, 'Unable to create/edit menu', null, EL_URL.'pl_conf/IShopNav/');
		}
		if (!$menu['id']) {
			$menu['id'] = $this->_db->insertID();
		}
		
		$this->_db->query('DELETE FROM el_plugin_ishop_nav2page WHERE id="'.$menu['id'].'"');
		$this->_db->optimizeTable('el_plugin_ishop_nav2page');
		if (is_array($data['page_id']) && !empty($data['page_id'])) {
			$this->_db->prepare('INSERT INTO el_plugin_ishop_nav2page (id, page_id) VALUES ', '(%d, %d)');
			foreach ($data['page_id'] as $pageID) {
				$this->_db->prepareData(array($menu['id'], $pageID));
			}
			$this->_db->execute();
		}
		elMsgBox::put(m('Data saved'));
		elLocation(EL_URL.'pl_conf/IShopNav/');
	}
	
	/**
	 * Remove menu from db
	 *
	 * @param  int   $id  menu id
	 * @return void
	 **/
	function rm($id) {
		$this->_db->query('DELETE FROM el_plugin_ishop_nav WHERE id='.intval($id));
		$this->_db->query('DELETE FROM el_plugin_ishop_nav2page WHERE id='.intval($id));
		$this->_db->optimizeTable('el_plugin_ishop_nav');
		$this->_db->optimizeTable('el_plugin_ishop_nav2page');
	}
	
	/**
	 * Display menuslist
	 *
	 * @return void
	 **/
	function _displayAll() {
		$rnd = & elSingleton::getObj('elTE');
		$rnd->setFile('list', 'plugins/IShopNav/list.html');
		$rnd->assignVars('pluginName', 'IShopNav');
		
		foreach ($this->_menus as $m) {
			$data = array(
				'id'    => $m['id'],
				'name'  => $m['name'],
				'src'   => $this->_master->src[$m['src']],
				'type'  => $this->_types[$m['type']],
				'pos'   => $GLOBALS['posLRT'][$m['pos']],
				'pages' => implode(',', $m['page_id'])
				);
			$rnd->assignBlockVars('ISHOP_MENU', $data);
		}
		
		$rnd->parse('list', 'PAGE', true);
	}
	
} // END class 
?>