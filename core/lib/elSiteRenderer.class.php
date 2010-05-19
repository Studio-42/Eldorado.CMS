<?php

define('EL_NAV_TYPE_MAIN',       1);
define('EL_NAV_TYPE_COMBI',      2);
define('EL_NAV_TYPE_JS',         3);
define('EL_NAV_TYPE_USER',       4);

define('EL_NAV_PATH_FULL',       0);
define('EL_NAV_PATH_FIRST_PAGE', 1);
define('EL_NAV_PATH_LAST_PAGE',  2);
define('EL_NAV_PATH_NO',         3);


class elSiteRenderer
{
	var $_conf   = null;
	var $_nav    = null;
	var $_te     = null; //template engine
	var $_tpls   = array(
		EL_WM_NORMAL => 'normal.html',
		EL_WM_POPUP  => 'popup.html'
		);

	var $_columns = array(
		EL_POS_LEFT   => 'LEFT_COLUMN',
		EL_POS_RIGHT  => 'RIGHT_COLUMN',
		EL_POS_TOP    => 'TOP_COLUMN',
		EL_POS_BOTTOM => 'BOTTOM_COLUMN'
		);

	var $_menuPos = array(
		'main' => array(
			EL_POS_TOP   => array(
				'var' => 'MENU_TOP',
				'tpl' => 'top.html'),
			EL_POS_LEFT  => array(
				'var' => 'MENU_LEFT',
				'tpl' => 'left.html'),
			EL_POS_RIGHT => array(
				'var' => 'MENU_RIGHT',
				'tpl' => 'right.html')
			),
		'sub'  => array(
			EL_POS_TOP   => array(
				'var' => 'MENU_TOP',
				'tpl' => 'sub-top.html'),
			EL_POS_LEFT  => array(
				'var' => 'MENU_LEFT',
				'tpl' => 'left.html'),
			EL_POS_RIGHT => array(
				'var' => 'MENU_RIGHT',
				'tpl' => 'right.html')
			),
		'side'  => array(
			EL_POS_LEFT  => array(
				'var' => 'SIDE_MENU_LEFT',
				'tpl' => 'left.html'),
			EL_POS_RIGHT => array(
				'var' => 'SIDE_MENU_RIGHT',
				'tpl' => 'right.html')
			),
		'cat'  => array(
			EL_POS_TOP   => array(
				'var' => 'CAT_MENU_TOP',
				'tpl' => 'select.html'),
			EL_POS_LEFT  => array(
				'var' => 'CAT_MENU_LEFT',
				'tpl' => 'left.html'),
			EL_POS_RIGHT => array(
				'var' => 'CAT_MENU_RIGHT',
				'tpl' => 'right.html'),
			EL_POS_BOTTOM => array(
				'var' => 'CAT_MENU_BOTTOM',
				'tpl' => 'select.html')
			)
			
		);

	var $_msgTpls = array(
		EL_MSGQ   => 'messages.html',
		EL_WARNQ  => 'warning.html',
		EL_DEBUGQ => 'debug.html'
		);

	var $_miscTpl = array(
		'search' => array(
			EL_POS_LEFT  => array('SEARCH_FORM_LEFT',  'common/search/left.html', 1),
			EL_POS_RIGHT => array('SEARCH_FORM_RIGHT', 'common/search/right.html', 1),
			EL_POS_TOP   => array('SEARCH_FORM_TOP',   'common/search/top.html',  0)
			),
		'icart' => array(
			EL_POS_LEFT  => array('ICART_LEFT',  'common/ICart/left.html', 1),
			EL_POS_RIGHT => array('ICART_RIGHT', 'common/ICart/left.html', 1),
			EL_POS_TOP   => array('ICART_TOP',   'common/ICart/top.html',  0)
			),
		'uAuth' => array(
			EL_POS_LEFT  => array('AUTH_FORM_LEFT',  'common/auth/auth-left.html', 1),
			EL_POS_RIGHT => array('AUTH_FORM_RIGHT', 'common/auth/auth-left.html', 1),
			EL_POS_TOP   => array('AUTH_FORM_TOP',   'common/auth/auth-top.html',  0)
		),
		'uInfo' => array(
			EL_POS_LEFT  => array('USER_INFO_LEFT',  'common/auth/user-left.html', 1),
			EL_POS_RIGHT => array('USER_INFO_RIGHT', 'common/auth/user-left.html', 1),
			EL_POS_TOP   => array('USER_INFO_TOP',   'common/auth/user-top.html',  0)
		)
	);

	var $_catsTbTpl = array(
		'DocsCatalog'    => 'el_dcat_%d_cat',
		'GoodsCatalog'   => 'el_gcat_%d_cat',
		'IShop'          => 'el_ishop_%d_cat',
		'FileArchive'    => 'el_fa_%d_cat',
		'TechShop'       => 'el_techshop_%d_cat',
		'VacancyCatalog' => 'el_vaccat_%d_cat'
		);

	var $_catsPosNfo = array(
		EL_POS_LEFT   => array('sideMenuLeft.html',  'CAT_MENU_LEFT',   'LEFT_COLUMN'),
		EL_POS_RIGHT  => array('sideMenuRight.html', 'CAT_MENU_RIGHT',  'RIGHT_COLUMN'),
		EL_POS_TOP    => array('sideMenuTop.html',   'CAT_MENU_TOP',    'TOP_COLUMN'),
		EL_POS_BOTTOM => array('sideMenuTop.html',   'CAT_MENU_BOTTOM', 'BOTTOM_COLUMN')
		);

	var $_tsMNavPosNfo = array(
	  	EL_POS_LEFT   => array('sideShopMNavLeft.html',  'TS_MMENU_LEFT',   'LEFT_COLUMN'),
		EL_POS_RIGHT  => array('sideShopMNavRight.html', 'TS_MMENU_RIGHT',  'RIGHT_COLUMN'),
		EL_POS_TOP    => array('sideShopMNavTop.html',   'TS_MMENU_TOP',    'TOP_COLUMN'),
		EL_POS_BOTTOM => array('sideShopMNavTop.html',   'TS_MMENU_BOTTOM', 'BOTTOM_COLUMN')
	 );

	var $_curPageID  = 0;
	var $indexTpl    = true;
	var $_userName    = '';
	var $_regAllow  = false;
	var $_adminMode   = false;
	var $_jsCacheTime = 0;

	function elSiteRenderer()
	{
		$this->_te        = &elSingleton::getObj('elTE');
		$this->_conf      = &elSingleton::getObj('elXmlConf');
		$ats              = & elSingleton::getObj('elATS');
		$this->_adminMode = $ats->allow(EL_WRITE);
		$this->_userName  = $ats->user->getFullName();
		$this->_regAllow  = $ats->isRegistrationAllowed();
		$this->_nav       = &elSingleton::getObj('elNavigator');
		$this->_curPageID = $this->_nav->getCurrentPageID();
		$this->_jsCacheTime = (int)$this->_conf->get('jsCacheTime', 'common')*60*60;
	}

	function prepare( $argsStr, $rndModule=true )
	{
		
        $tpl = '';
		if ( EL_WM == EL_WM_NORMAL )
		{
            $tpl = $rndModule ? $this->_nav->getAltTpl() : $this->_conf->get('servicesTpl', 'layout');
            if ( !empty($tpl) && !is_file(EL_DIR.'style/alternative/'.$tpl) )
            {
                $tpl = '';
            }
		}
		
        if ( !$tpl || !$this->_te->setFile('main', 'alternative/'.$tpl) )
        {
            $this->_te->setFile('main', $this->_tpls[EL_WM]);    
        }
	}

	function renderFromCache( $str )
	{
		$this->_te->tplData['main'] = $str;
	}

	function setPageContent( $str, $append=true)
	{
		$this->_te->assignVars('PAGE', $str, $append);
	}

	function getContent()
	{
		$this->_te->parseWithNested('main', null, false, true);
		return $this->_te->getVar('main', 1);
	}

	function display( $timer)
	{
		$this->_renderMessages();
		if ( EL_WM == EL_WM_NORMAL )
		{
			// parts are not cached
			$this->_renderUserInfo( );
			$this->_renderShoppingCart();
			$this->_renderColumns();
		}
		if ( $timer )
		{
			$this->_te->assignVars('work_time', sprintf( m('Working time: %s sec'), round($timer, 4)));
		}
		$this->_te->parseWithNested('main', null, false, true);
		$this->_te->fprint('main');
	}

	function render()
	{
		$this->_te->assignVars('owner',    $this->_conf->get('owner',    'common') );
		$this->_te->assignVars('contacts', $this->_conf->get('contacts', 'common') );
		$this->_te->assignVars('phones',   $this->_conf->get('phones',   'common') );
		$this->_te->assignVars('siteName', $this->_conf->get('siteName', 'common') );
		$this->_te->assignVars('elVer',    EL_VER );
		$this->_te->assignVars('elName',   EL_NAME );
		$this->_te->assignVars('YEAR',     date('Y'));
		$this->_te->assignVars('DATE',     date(EL_DATE_FORMAT));
        //$currencyNfo = elGetCurrencyInfo();
        //$this->_te->assignVars('currencySign', $currencyNfo['currencySign']);
        
		$mt = &elSingleton::getObj('elMetaTagsCollection');
        list($title, $meta) = $mt->get();
		
		if ( EL_WM == EL_WM_NORMAL )
		{
			$this->_renderMenu();
			$this->_renderPaths($title);
			$this->_renderSearchForm();

            $this->_te->assignBlockFromArray('META', $meta );
			$cntFile = EL_DIR_CONF.'counters.html';
			if ( !$this->_adminMode && is_readable($cntFile) )
			{
				$this->_te->assignBlockIfExists('COUNTERS', array('COUNTERS'=>file_get_contents($cntFile)));
			}

			if (false != ($stID = $this->_conf->findGroup('module', 'GAStat')) )
			{
				$code = $this->_conf->get('webPropertyId', $stID);
				if ($code)
				{
					$this->_te->assignBlockVars('GASTAT_COUNTER', array('webID'=>$code));
				}
				
			}
		}
		$this->_renderJs();
		$this->_renderCss();
	}

	function _renderColumns()
	{
		foreach ( $this->_columns as $pos=>$blockName )
		{
			if (!empty($GLOBALS['parseColumns'][$pos]))
			{
				$this->_te->assignBlockOnce($blockName, null, 1);
			}
		}
		$class = empty($GLOBALS['parseColumns'][EL_POS_LEFT]) && empty($GLOBALS['parseColumns'][EL_POS_RIGHT]) 
			? 'rm-both' 
			: (empty($GLOBALS['parseColumns'][EL_POS_LEFT]) ? 'rm-left' : (empty($GLOBALS['parseColumns'][EL_POS_RIGHT]) ? 'rm-right' : '') );
		$this->_te->assignVars('bodyCssClass', $class);
	}

	/**
  * render search form in position according to config
  **/
	function _renderSearchForm()
	{
		if ( 'n'   != ( $pos = $this->_conf->get('searchFormPosition', 'layout')) )
		{
			$tpl = $this->_processMiscTpl('search', $pos);
			$this->_te->setFile($tpl[0], $tpl[1]);
			$this->_te->parse($tpl[0]);
		}
	}

	function _renderJs()
	{
	  	
		$GLOBALS['_js_'][EL_JS_CSS_FILE] = array_unique($GLOBALS['_js_'][EL_JS_CSS_FILE]);
		// elPrintR($GLOBALS['_js_']);
		if (false !== ($dnum = array_search('eldialogform.js', $GLOBALS['_js_'][EL_JS_CSS_FILE])))
		{
			if (in_array('elfinder.min.js', $GLOBALS['_js_'][EL_JS_CSS_FILE]) || in_array('elrtefinder.full.js', $GLOBALS['_js_'][EL_JS_CSS_FILE]))
			{
				unset($GLOBALS['_js_'][EL_JS_CSS_FILE][$dmun]);
			}
		}
		// elPrintR($GLOBALS['_js_'][EL_JS_CSS_FILE]);
		
		$cache = '';
		if (!$this->_adminMode || !$this->_conf->get('debug', 'common'))
		{
			$file = crc32(implode('|', $GLOBALS['_js_'][EL_JS_CSS_FILE])).'.js';
			$path = EL_DIR_CACHE.$file;
			if (file_exists($path) && time() - filemtime($path) < $this->_jsCacheTime)
			{
				// echo 'js cache';
				$cache = $file;
			}
			else
			{
				$content = '';
				foreach($GLOBALS['_js_'][EL_JS_CSS_FILE] as $jsfile)
				{
					// echo $jsfile.' '.(filesize(EL_DIR_CORE.'js'.DIRECTORY_SEPARATOR.$jsfile)/1024).'<br>';
					$content .= trim(file_get_contents(EL_DIR_CORE.'js'.DIRECTORY_SEPARATOR.$jsfile))."\n";
				}
				if (false != ($fp = fopen(EL_DIR_CACHE.$file, 'w')))
				{
					fwrite($fp, $content);
					fclose($fp);
					$cache = $file;
				}
			}
		}
		
		if ($cache)
		{
			$this->_te->assignBlockVars('JS_LINK', array('js' => EL_BASE_URL.'/cache/'.$cache) );
		}
		else
		{
			foreach ($GLOBALS['_js_'][EL_JS_CSS_FILE] as $js)
			{
				$this->_te->assignBlockVars('JS_LINK', array('js' => EL_BASE_URL.'/core/js/'.$js) );
			}
		}
		
		if ( file_exists(EL_DIR_STYLES.'style.js') )
	  	{
		// elAddJs(EL_BASE_URL.'/style/style.js', EL_JS_CSS_FILE);
	    	$this->_te->assignBlockVars('JS_LINK', array('js' => EL_BASE_URL.'/style/style.js') );
	  	}
		
		
		if (!empty($GLOBALS['_js_'][EL_JS_SRC_ONLOAD]))
		{
			$js = '<script type="text/javascript"> window.onload = function() {'.implode(";\n", $GLOBALS['_js_'][EL_JS_SRC_ONLOAD]).' };</script>';
			$this->_te->assignVars('PAGE', $js, 1);
		}
		if (!empty($GLOBALS['_js_'][EL_JS_SRC_ONREADY]))
		{
			$this->_te->assignBlockVars('ONREADY', array('js'=>implode("\n", $GLOBALS['_js_'][EL_JS_SRC_ONREADY])));
		}
		
		if (!empty($GLOBALS['_js_'][EL_JS_CSS_SRC]))
		{
			$this->_te->assignVars('global_js', implode("\n", $GLOBALS['_js_'][EL_JS_CSS_SRC]));
		}
	}


	function _renderCss()
	{
		if (!empty($GLOBALS['_css_']['ui-theme']))
		{
			$this->_te->assignBlockVars('CSS_LINK', array('css'=>EL_BASE_URL.'/style/css/'.$GLOBALS['_css_']['ui-theme']));
		}
		
		$GLOBALS['_css_'][EL_JS_CSS_FILE]   = array_unique($GLOBALS['_css_'][EL_JS_CSS_FILE]); 
		$GLOBALS['_css_'][EL_JS_CSS_FILE][] = 'print.css';
		// elPrintR($GLOBALS['_css_'][EL_JS_CSS_FILE]);
		$cache = '';
		if (!$this->_adminMode || !$this->_conf->get('debug', 'common'))
		{
			$file = crc32(implode('|', $GLOBALS['_css_'][EL_JS_CSS_FILE])).'.css';
			// $dir  = EL_DIR_STYLES.'css-cache';
			$path = EL_DIR_CACHE.$file;
			if (file_exists($path) && time() - filemtime($path) < $this->_jsCacheTime)
			{
				// echo 'cache css';
				$cache = $file;
			}
			else
			{
				$content = '';
				foreach ($GLOBALS['_css_'][EL_JS_CSS_FILE] as $cssfile)
				{
					$content .= trim(str_replace("\t", '', str_replace("\n", "", file_get_contents(EL_DIR_STYLES.'css'.DIRECTORY_SEPARATOR.$cssfile))))."\n";
				}
				if (false != ($fp = fopen($path, 'w')))
				{
					fwrite($fp, str_replace(array('../../images', '../images'), EL_BASE_URL.'/style/images', $content));
					fclose($fp);
					$cache = $file;
				}
			}
		}
		
		if ($cache)
		{
			$this->_te->assignBlockVars('CSS_LINK', array('css' => EL_BASE_URL.'/cache/'.$cache));
		}
		else
		{
			foreach ($GLOBALS['_css_'][EL_JS_CSS_FILE] as $file)
			{
				if (file_exists(EL_DIR_STYLES.'css'.DIRECTORY_SEPARATOR.$file))
				{
					$this->_te->assignBlockVars('CSS_LINK', array('css'=>EL_BASE_URL.'/style/css/'.$file));
				}
			}
		}
	}

	function _renderMessages()
	{
		if ( EL_WM == EL_WM_POPUP && !empty($_SESSION['msgNoDisplay']) )
		{
			return;
		}
		elseif ( isset($_SESSION['msgNoDisplay']) )
		{
			unset($_SESSION['msgNoDisplay']);
		}

		$msgBox = & elSingleton::getObj('elMsgBox');
		$msgs   = $msgBox->listQueues();

		if ( !empty($msgs) )
		{
			$this->_te->setFile('SYS_MESSAGES', 'common/messages.html');
			$ats = & elSingleton::getObj('elATS');
			foreach ( $msgs as $queue )
			{
				if ( $msg = $msgBox->fetchToString($queue) )
				{
					if ( EL_DEBUGQ != $queue )
					{
						$b = EL_MSGQ == $queue ? 'SYS_MESSAGES.SYS_MSG' : 'SYS_MESSAGES.SYS_WARN';
						$this->_te->assignBlockVars( $b, array('msg'=>nl2br($msg)) );
					}
					elseif ( $ats->allow(EL_WRITE) && $this->_te->isBlockExists('SYS_DEBUG') )
					{
						$this->_te->assignBlockVars('SYS_DEBUG', array('msg'=>nl2br($msg)) );
					}
				}
			}
		}
	}

	/**
   * Render navigation menu depends on site configuration
   */

	function _renderMenu()
	{
        $navType = $this->_conf->get('navType', 'layout');
		if ($navType == EL_NAV_TYPE_USER) {
			if (!file_exists('./style/lib/elNavRnd.lib.php') || !include_once('./style/lib/elNavRnd.lib.php') || !function_exists('elusernavrnd')) {
				$this->_conf->set('navType', EL_NAV_TYPE_COMBI, 'layout');
		        $this->_conf->save();
		        elLocation(EL_URL);
			}
		}

		switch ($navType)
		{
			case EL_NAV_TYPE_MAIN:
				$pos   = $this->_conf->get('mainMenuPos', 'layout'); 
				$pos   = empty($this->_menuPos['main'][$pos]) ? EL_POS_LEFT : $pos;
				$param = $this->_menuPos['main'][$pos];
				$ico   = (int)$this->_conf->get('mainMenuUseIcons', 'layout');
				if (EL_POS_TOP == $pos) {
					$this->_rndMenuHoriz($this->_nav->getPages(0, 1, false), $param['var'], $param['tpl'], $ico);
				} else {
					$this->_rndMenuVert($this->_nav->getPages(0, 1, true), $param['var'], $param['tpl'], $ico, $pos);
				}
				break;

			case EL_NAV_TYPE_COMBI:
				$pages = $this->_nav->getPages(0, 1, false);
				$pos   = $this->_conf->get('mainMenuPos', 'layout'); 
				$param = $this->_menuPos['main'][$pos];
				$ico   = (int)$this->_conf->get('mainMenuUseIcons', 'layout');
				if (EL_POS_TOP == $pos) {
					$this->_rndMenuHoriz($pages,  $param['var'], $param['tpl'], $ico);
				} else {
					$this->_rndMenuVert($pages, $param['var'], $param['tpl'], $ico, $pos);
				}
				
				foreach ($pages as $page) {
					if ($this->_nav->isInCurPath($page['id']) && $page['has_childs']) {
						$subPages = $this->_nav->getPages($page['id'], 1, true, true); 
						$pos      = $this->_conf->get('subMenuPos', 'layout');
						$pos      = empty($this->_menuPos['sub'][$pos]) ? EL_POS_LEFT : $pos; 
						$param    = $this->_menuPos['sub'][$pos];
						$ico      = (int)$this->_conf->get('subMenuUseIcons', 'layout');
						$parent   = $this->_conf->get('subMenuDisplParent', 'layout') ? $this->_nav->getPageName($page['id']) : '';
						if ($pos == EL_POS_TOP) {
							$this->_rndMenuHoriz($subPages, $param['var'], $param['tpl'], $ico);
						} else {
							$this->_rndMenuVert($subPages, $param['var'], $param['tpl'], $ico, $pos, $parent);
						}
						break;
					}
				}
				break;

			case EL_NAV_TYPE_USER:
				elUserNavRnd($this->_te, $this->_nav, $this->_conf->get('navMainUseIcons', 'layout') );
				break;

			case EL_NAV_TYPE_JS:
				$this->_rndMenuJs();
				break;

			default:
				$this->_conf->set('navType', EL_NAV_TYPE_COMBI, 'layout');
				$this->_conf->save();
				elLocation(EL_URL);
		}

	

		$aMenus = $this->_nav->getAdditionalMenus(); 
		if ( !empty($aMenus[EL_ADD_MENU_TOP]) ) {
			$this->_rndAddMenu( $aMenus[EL_ADD_MENU_TOP], EL_ADD_MENU_TOP, $this->_conf->get('addMenuTop', 'layout'));
		}
		if ( !empty($aMenus[EL_ADD_MENU_BOT]) ) {
			$this->_rndAddMenu( $aMenus[EL_ADD_MENU_BOT], EL_ADD_MENU_BOT, $this->_conf->get('addMenuBottom', 'layout'));
		}
		
		if ( !empty($aMenus[EL_ADD_MENU_SIDE]) ) {
			foreach ( $aMenus[EL_ADD_MENU_SIDE] as $menu ) {
				if (!empty($menu['pages'])) {
					$pos   = $menu['pos'] == EL_POS_RIGHT ? EL_POS_RIGHT : EL_POS_LEFT;
					$param = $this->_menuPos['side'][$pos];
					$this->_rndMenuVert($menu['pages'], $param['var'], $param['tpl'], false, $pos, $menu['name'], '-sidemenu');
				}
			}
		}
		
		// render catalogs fast nav
		$groups = $this->_conf->getGroup('catalogsNavs'); 

		if ( !empty($groups) )
		{
			$cat = & elSingleton::getObj('elCatalogCategory');
			foreach ( $groups as $ID=>$g )
			{
				if ( in_array(1, $g['pIDs']) || in_array($this->_curPageID, $g['pIDs']) )
				{
					$src = $this->_nav->getPage($ID); 
					if (!empty($this->_catsTbTpl[$src['module']]))
					{
						$cat->tb(sprintf($this->_catsTbTpl[$src['module']], $ID));
						$tree  = $cat->getTreeToArray((int)$g['deep'], false, false, false); 
						$pos   = isset($this->_menuPos['cat'][$g['pos']]) ? $g['pos'] : EL_POS_LEFT;
						$param = $this->_menuPos['cat'][$pos];
						if (EL_POS_TOP == $pos) {
							
							$this->_te->setFile($param['var'], 'menus/'.$param['tpl']);
							if ($g['title']) {
								$this->_te->assignBlockVars('MENU_TITLE', array('title' => $g['title']));
							}
							$categoryID  = !empty($GLOBALS['categoryID']) ? (int)$GLOBALS['categoryID'] : 0;
							foreach ($tree as $id=>$p) {
								$this->_te->assignBlockVars('MENU_PAGE', array(
									'id'       => $id, 
									'name'     => str_repeat('+', $p['level']).$p['name'],
									'selected' => $id == $categoryID ? ' selected="on"' : ''
									));
							}
							$this->_te->parse($param['var'], null, true, false, true);
						} else {
							foreach($tree as $k=>$v) {
								$tree[$k]['url'] = $src['url'].$v['id'].'/';
							}
							$this->_rndMenuVert($tree, $param['var'], $param['tpl'], false, $pos, isset($g['title']) ? $g['title'] : '', '-catmenu');
						}
					}
					else
					{ // invalid module
						$this->_conf->drop($ID, 'catalogsNavs');
						$this->_conf->save();
					}
				}
			}
		}

		
		// меню производителей из тех-каталогов
		$groups = $this->_conf->getGroup('techShopsMNavs'); 
		
		// return;
		if (!empty($groups)) {
			foreach ($groups as $ID=>$g) {
				$page = $this->_nav->getPage($ID); 
				
				if ($page['module'] == 'TechShop') {
					if (in_array(1, $g['pids']) || in_array($this->_curPageID, $g['pids'])) {
						$pos = isset($this->_menuPos['cat'][$g['pos']]) ? $g['pos'] : EL_POS_LEFT;
						$param = $this->_menuPos['cat'][$pos];
						$tb = 'el_techshop_'.$ID.'_manufact'; 
					    $db = &elSingleton::getObj('elDb');
						if (EL_POS_TOP == $pos) {
							$sql = 'SELECT id, name FROM '.$tb.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name';
						} else {
							$sql = 'SELECT CONCAT("'.$page['url'].'mnf/", id) AS url, name FROM '.$tb.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name';
							$mnfs = $db->queryToArray($sql);
							$this->_rndMenuVert($mnfs, $param['var'], $param['tpl'], false, $pos, isset($g['title']) ? $g['title'] : '', '-sidemenu');
						}
					}
				} else {
					$this->_conf->drop($ID, 'techShopsMNavs');
					$this->_conf->save();
				}
			}
		}
	}


	/**
	 * Рисует горизонтальное верхнее меню
	 *
	 * @param  array   $pages    массив страниц
	 * @param  string  $block    имя в главном шаблоне, куда должно попасть меню
	 * @param  string  $varName  имя переменной для меню в шаблоне
	 * @param  string  $file     файл шаблона меню
	 * @param  bool    $icons    рисовать иконки в меню
	 * @return void
	 **/
	function _rndMenuHoriz($pages, $var, $tpl, $icons)
	{
		$this->_te->setFile($var, 'menus/'.$tpl); //echo elPrintR($pages);
		$size = sizeof($pages);
        $cellWidth = floor(100/$size);
		$size--;
		$i = 0;
		foreach ( $pages as $one )
		{
			$cssClass  = $this->_nav->isInCurPath($one['id']) ? 'navtop-item-selected' : 'navtop-item';
			$cssClass .= $i==0 ? '-first' : ($i==$size ? '-last' : '');
			
			$page = array(
				'url'       => $one['url'], 
				'name'      => $one['name'], 
				'descrip'   => !empty($one['page_descrip']) ? htmlspecialchars($one['page_descrip']) : '', 
				'ico'       => !empty($one['ico_main']) ? $one['ico_main'] : '',
				'cssClass'  => $cssClass,
				'odd'       => (int)($i++%2),
				'num'       => $i,
				'width'     => $cellWidth
				);
			
			$this->_te->assignBlockVars('MENU.PAGE', $page, 1);
			if ( $icons )
			{
				$this->_te->assignBlockVars('MENU.PAGE.ICO', $page, 2);
			}
		}
		$this->_te->parse($var, null, true, false, true);
	}

	function _rndMenuVert($pages, $var, $tpl, $icons, $pos, $parentName=null, $suffix='')
	{
    	if (!$pages)
    	{
      		return;
    	}
    	$GLOBALS['parseColumns'][$pos] = true;

    	$this->_te->setFile($var, 'menus/'.$tpl);
		$this->_te->assignVars('navSuffix', $suffix);
    	$curPageID = $this->_nav->getCurrentPageID();

    	if ($parentName)
    	{
      		$this->_te->assignBlockVars('MENU_PARENT', array('parentName'=>$parentName));
    	}

    	foreach ( $pages as $one )
		{
			$level = isset($one['level']) ? $one['level'] : 0;
			$page = array(
				'url'      => $one['url'],
				'name'     => $one['name'],
				'descrip'  => !empty($one['page_descrip']) ? htmlspecialchars($one['page_descrip']) : '',
				'level'    => $level,
				'ico'      => !empty($one['ico_main']) ? $one['ico_main'] : '',
				'cssClass' => 'level'.min($level, 3).' item'.($one['id'] == $curPageID ? '-selected' : '')
				);
			$this->_te->assignBlockVars('MENU.PAGE', $page, 1);
			if ($icons) {
				$this->_te->assignBlockVars('MENU.PAGE.ICO', $page, 2);
			}
		}
		$this->_te->parse($var, null, true, false, true);
	}

	/**
	 * Render vertical/horizontal menu on JS
	 *
	 * @param bool $vert
	 */
	function _rndMenuJS()
	{
		  	$pos = $this->_conf->get('mainMenuPos', 'layout');
			$pos   = empty($this->_menuPos['main'][$pos]) ? EL_POS_LEFT : $pos;
			$param = $this->_menuPos['main'][$pos];
		  	$vert      = EL_POS_TOP != $pos;
			$pages     = $this->_nav->getPages(0, 0, true, true);
			$showIcons = $this->_conf->get('mainMenuUseIcons', 'layout');
			$icoURL    = $this->_conf->get('mainMenuUseIcons', 'layout')
				? EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/pageIcons/' 
				: '';

			$curPageID = $this->_nav->getCurrentPageID();
			$html = "";
			$level = 1;
			$cnt = 0;
			foreach($pages as $p) {
				if ($p['level'] == 1) {
					$cnt++;
				}
			}

			$width = floor(100/$cnt);
			foreach ( $pages as $p )
			{
				if ( $p['level']>$level )
				{
					$html .= "\n<ul>\n";
				}
				elseif ( $p['level']<$level )
				{
					$html .= str_repeat("\n</ul></li>", $level-$p['level']);
				}

				$cssClass = '';
				if (!$vert && 1==$p['level'])
				{
					$cssClass = 'toplevel';
					$cssClass .= $curPageID == $p['id'] ? ' nav-top-current ' : '';
					$cssClass = 'class="'.$cssClass.'" style="width:'.$width.'%"';
				} 

				$ico = '';
				if ($icoURL)
				{
					$data = $this->_nav->getPage($p['id']);
					$ico = '<img src="'.$icoURL.$data['ico_main'].'" width="16" height="16" style="margin-right:5px" />';
				}

				$html .= "<li ".$cssClass.">";
				$html .= '<a href="'.$p['url'].'">'.$ico.$p['name']."</a>";
				if ( !$p['has_childs'] )
				{
					$html .= "</li>";
				}

				$level = $p['level'];
			}
			$cssClass = $vert ? 'el-menu-vert' : 'el-menu-horiz';
			$html = "\n<ul class=\"".$cssClass."\">\n".$html."</ul>\n";


	    	if (EL_POS_TOP != $pos)
	    	{
	      		$GLOBALS['parseColumns'][$param[0]] = true;
	    	}

			$this->_te->assignVars('MENU_TOP', $html);

			elAddJs('jquery.elmenu.min.js', EL_JS_CSS_FILE);
			$js = $vert ? '$(".el-menu-vert").elmenu({orientation : "vertical"}); ' : '$(".el-menu-horiz").elmenu({orientation : "horizontal", deltaY : 3, deltaX : 12}); ';
			elAddJs($js, EL_JS_SRC_ONREADY);
			elAddCss('elmenu.css');
	}


	function _getUl($pages, $icoURL='', $currID)
	{
		$html = "";
		$level = 1;
		foreach ( $pages as $p )
		{
			
			if ( $p['level']>$level )
			{
				$html .= "\n<ul>\n";
			}
			elseif ( $p['level']<$level )
			{
				$html .= str_repeat("\n</ul></li>\n", $level-$p['level']);
			}
			
			$cssClass = $currID == $p['id'] ? ' class="nav-top-current"' : '';
			$html .= "<li".$cssClass.">";
			$html .= '<a href="'.$p['url'].'">'.($icoURL ? '<img src="'.$icoURL.$p['ico_main'].'" />' : '').$p['name']."</a>";
			if ( !$p['has_childs'] )
			{
				$html .= "</li>\n";
			}
			
			$level = $p['level'];
		}
		
		$html = "<ul>\n".$html."</ul>\n";
		return $html;
	}

	function _rndAddMenu( $menu, $mType=EL_ADD_MENU_TOP, $displ=EL_ADD_MENU_TEXT )
	{
		if ( EL_ADD_MENU_TOP == $mType ) {
			$var   = 'ADD_MENU_TOP';
			$file  = 'add-top.html';
		} else {
			$var   = 'ADD_MENU_BOTTOM';
			$file  = 'add-bottom.html';
		}

		$this->_te->setFile($var, 'menus/'.$file);
		$s = sizeof($menu); 
		// $width = floor(100/$s);
		for ( $i=0; $i<$s; $i++ ) {
			$this->_te->assignBlockVars('MENU_PAGE', array('cssClass' => $i==0 ? 'first' : ($i==$s-1 ? 'last' : '')));
			if ($displ & EL_ADD_MENU_TEXT ) {
				$this->_te->assignBlockVars('MENU_PAGE.TEXT', $menu[$i], 1);
			}
			if ($displ & EL_ADD_MENU_ICO ) {
				$this->_te->assignBlockVars('MENU_PAGE.ICO', $menu[$i], 1);
			}
		}
		$this->_te->parse($var, null, true, false, true);
	}


	function _renderPaths($altTitle='')
	{
		// render path in window and page  title
		$sPath = $this->_nav->getNavPath();
		$pPath = $GLOBALS['pagePath'];

		$navPathInSTitle = (NULL !== $this->_conf->get('navPathInSTitle', $this->_nav->curPageID) )
		? (int)$this->_conf->get('navPathInSTitle', $this->_nav->curPageID)
		: (int)$this->_conf->get('navPathInSTitle', 'layout');
		$pagePathInSTitle = (NULL !== $this->_conf->get('pagePathInSTitle', $this->_nav->curPageID) )
		? (int)$this->_conf->get('pagePathInSTitle', $this->_nav->curPageID)
		: (int)$this->_conf->get('pagePathInSTitle', 'layout');
		$navPathInPTitle = (NULL !== $this->_conf->get('navPathInPTitle', $this->_nav->curPageID) )
		? (int)$this->_conf->get('navPathInPTitle', $this->_nav->curPageID)
		: (int)$this->_conf->get('navPathInPTitle', 'layout'); 
		$pagePathInPTitle = (NULL !== $this->_conf->get('pagePathInPTitle', $this->_nav->curPageID) )
		? (int)$this->_conf->get('pagePathInPTitle', $this->_nav->curPageID)
		: (int)$this->_conf->get('pagePathInPTitle', 'layout');

		// ------- render window title - TITLE-tag --------
		if ( $altTitle )
		{
			$siteTitle = $altTitle;
		}
		else
		{
            $siteTitle = $this->_conf->get('siteName', 'common').' ';
			switch ($navPathInSTitle)
			{
				case EL_NAV_PATH_FIRST_PAGE:
					$siteTitle .= ' / '.$sPath[0]['name'];
					break;
				case EL_NAV_PATH_LAST_PAGE:
					$siteTitle .= ' / '.$sPath[sizeof($sPath)-1]['name'];
					break;
				case EL_NAV_PATH_FULL:
					foreach ($sPath as $page )
					{
						$siteTitle .= ' / '.$page['name'];
					}
			}
            
            //$pPath = $GLOBALS['pagePath']; 
            switch ($pagePathInSTitle)
            {
                case EL_NAV_PATH_FIRST_PAGE:
                    $page       = array_shift($pPath);
                    $siteTitle .= ' / '.$page['name'];
                    break;
                case EL_NAV_PATH_LAST_PAGE:
                    $page       = array_pop($pPath);
                    $siteTitle .= ' / '.$page['name'];
                    break;
                case EL_NAV_PATH_FULL:
                    foreach ($pPath as $page )
                    {
                        $siteTitle .= ' / '.$page['name'];
                    }
            }
            if ( !empty($GLOBALS['appendPageTitle']) )
            {
                $siteTitle .= ' / '.implode(' / ', $GLOBALS['appendPageTitle']);
            }
		}
		
		$this->_te->assignVars('siteTitle', $siteTitle );

		// ---------- render page title --------------------

		if ( !$this->_te->isBlockExists('PATH') )
		{
			return;
		}

		$bTitle = 'PATH.EL';
		$bDelim = 'PATH.DELIM';
		$pPath  = $GLOBALS['pagePath'];

		switch ($navPathInPTitle)
		{
			case EL_NAV_PATH_FIRST_PAGE:
				$this->_te->assignBlockVars($bTitle, array_shift($sPath), 0);
				break;
			case EL_NAV_PATH_LAST_PAGE:
				$this->_te->assignBlockVars($bTitle, array_pop($sPath), 0);
				break;
			case EL_NAV_PATH_FULL: 
				$p1 = array_shift($sPath);
				$this->_te->assignBlockVars($bTitle, $p1, 0);
				$this->_te->assignBlockFromArray( array($bDelim, $bTitle), $sPath, 0 );
		}

		$pPath = $GLOBALS['pagePath'];
		if ( empty($pPath) )
		{
			return;
		}
		if ( EL_NAV_PATH_NO != $navPathInPTitle && !empty($pPath) )
		{
			$this->_te->assignBlockVars($bDelim, null, 0);
		}

		switch ($pagePathInPTitle)
		{
			case EL_NAV_PATH_FIRST_PAGE:
				$this->_te->assignBlockVars($bTitle, array_shift($pPath), 0);
				break;
			case EL_NAV_PATH_LAST_PAGE:
				$this->_te->assignBlockVars($bTitle, array_pop($pPath), 0);
				break;
			case EL_NAV_PATH_FULL: 
			$this->_te->assignBlockVars($bTitle, array_shift($pPath), 0);
			$this->_te->assignBlockFromArray( array($bDelim, $bTitle), $pPath, 0 );
		}
		// ----------------------------------------------------------------
	}

	/**
    * render auth form for nonauthorized user and user name for authed user
    */
	function _renderUserInfo( $prepare=false )
	{
		if ( $this->_userName )
		{
			$tpl = $this->_processMiscTpl('uInfo', $this->_conf->get('userInfoPosition', 'layout'));
			$this->_te->assignVars( 'userName', $this->_userName );
			$this->_te->setFile($tpl[0], $tpl[1]);
			$this->_te->parse($tpl[0]);
		}
		elseif ( 'n' != ($pos = $this->_conf->get('authFormPosition', 'layout')) )
		{
			elLoadMessages('Auth');
			$tpl = $this->_processMiscTpl('uAuth', $pos);
			$this->_te->setFile($tpl[0], $tpl[1]);
			if ($this->_regAllow && $this->_te->isBlockExists('USER_REG') )
			{
				$this->_te->assignBlockVars('USER_REG');
			}
			$this->_te->parse($tpl[0]);
		}
	}

    /**
     * Рисует корзину заказа для IShop и TechShop
     *
    **/
	function _renderShoppingCart($prepare=false)
	{
		
        $pos = $this->_conf->get('ICartPosition', 'layout');  
        if ( (!$pos || 'n' == $pos)
        || (  !$this->_conf->findGroup('module', 'IShop') && !$this->_conf->findGroup('module', 'TechShop'))
		)
        {
            return;
        }

        $ICart = & elSingleton::getObj('elICart');
		if ( !$ICart->qnt && !$this->_conf->get('iCartDisplayEmpty', 'layout') )
		{
			return;
		}
		$tpl = $this->_processMiscTpl('icart', $pos); 
		$this->_te->setFile($tpl[0], $tpl[1]);
		if ( !$ICart->qnt )
		{
			$this->_te->assignBlockVars('ICART_EMPTY');
		}
		else
		{
			$currency = &elSingleton::getObj('elCurrency');
			$data     = array(
				'iCartQnt'     => $ICart->qnt, 
				'iCartAmount'  => $ICart->amountFormated.' '.$currency->getSymbol()
			);
			$this->_te->assignBlockVars('ICART_SUMMARY',  $data);
		}
		
		$this->_te->parse($tpl[0]);
	}

	function _processMiscTpl($name, $pos) {
		if ( empty($this->_miscTpl[$name][$pos])) {
			$pos = EL_POS_LEFT;
		}
		$tpl = $this->_miscTpl[$name][$pos];
		if ( !empty($tpl[2]) ) {
			$GLOBALS['parseColumns'][$pos] = 1;
		}
		return $tpl;
	}

}

?>