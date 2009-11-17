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

  var $_mainMenuPos = array(
    EL_POS_TOP   => array('MAIN_MENU_POS_TOP', 'MAIN_MENU_TOP', 'mainMenuTop.html', '_rndMenuHoriz'),
    EL_POS_LEFT  => array(EL_POS_LEFT,         'MENU_LEFT',     'menuLeft.html',    '_rndMenuVert'),
    EL_POS_RIGHT => array(EL_POS_RIGHT,        'MENU_RIGHT',    'menuRight.html',   '_rndMenuVert')
  );

  var $_subMenuPos = array(
    EL_POS_TOP   => array('SUB_MENU_POS_TOP', 'SUB_MENU_TOP', 'subMenuTop.html', '_rndMenuHoriz'),
    EL_POS_LEFT  => array(EL_POS_LEFT,        'MENU_LEFT',    'menuLeft.html',   '_rndMenuVert'),
    EL_POS_RIGHT => array(EL_POS_RIGHT,       'MENU_RIGHT',   'menuRight.html',  '_rndMenuVert')
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
			EL_POS_LEFT  => array('AUTH_FORM_LEFT',  'common/authFormsAndInfo/authFormLeft.html', 1),
			EL_POS_RIGHT => array('AUTH_FORM_RIGHT', 'common/authFormsAndInfo/authFormLeft.html', 1),
			EL_POS_TOP   => array('AUTH_FORM_TOP',   'common/authFormsAndInfo/authFormTop.html',  0)
		),
		'uInfo' => array(
			EL_POS_LEFT  => array('USER_INFO_LEFT',  'common/authFormsAndInfo/userInfoLeft.html', 1),
			EL_POS_RIGHT => array('USER_INFO_RIGHT', 'common/authFormsAndInfo/userInfoLeft.html', 1),
			EL_POS_TOP   => array('USER_INFO_TOP',   'common/authFormsAndInfo/userInfoTop.html',  0)
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
		EL_POS_LEFT   => array('catMenuLeft.html',  'CAT_MENU_LEFT',   'LEFT_COLUMN'),
		EL_POS_RIGHT  => array('catMenuRight.html', 'CAT_MENU_RIGHT',  'RIGHT_COLUMN'),
		EL_POS_TOP    => array('catMenuTop.html',   'CAT_MENU_TOP',    'TOP_COLUMN'),
		EL_POS_BOTTOM => array('catMenuTop.html',   'CAT_MENU_BOTTOM', 'BOTTOM_COLUMN')
		);

	var $_tsMNavPosNfo = array(
	  	EL_POS_LEFT   => array('techShopMNavLeft.html',  'TS_MMENU_LEFT',   'LEFT_COLUMN'),
		EL_POS_RIGHT  => array('techShopMNavRight.html', 'TS_MMENU_RIGHT',  'RIGHT_COLUMN'),
		EL_POS_TOP    => array('techShopMNavTop.html',   'TS_MMENU_TOP',    'TOP_COLUMN'),
		EL_POS_BOTTOM => array('techShopMNavTop.html',   'TS_MMENU_BOTTOM', 'BOTTOM_COLUMN')
	 );

	var $_curPageID  = 0;
	var $indexTpl    = true;
	var $userName    = '';
	var $isRegAllow  = false;
	var $adminMode   = false;
	var $_isAdmin    = false;
	var $_jsCacheTime = 0;

	function elSiteRenderer()
	{
		$this->_te        = &elSingleton::getObj('elTE');
		$this->_conf      = &elSingleton::getObj('elXmlConf');
		$ats              = & elSingleton::getObj('elATS');
		$this->_isAdmin   = $ats->allow(EL_WRITE);
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

	function display( $timer)//, $userName, $userAuthed, $regAuth )
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
        $currencyNfo = elGetCurrencyInfo();
        $this->_te->assignVars('currencySign', $currencyNfo['currencySign']);
        $mt = &elSingleton::getObj('elMetaTagsCollection');
        list($title, $meta) = $mt->get();
		
		if ( EL_WM == EL_WM_NORMAL )
		{
			$this->_renderMenu();
			$this->_renderPaths($title);
			$this->_renderSearchForm();

            $this->_te->assignBlockFromArray('META', $meta );
			$cntFile = EL_DIR_CONF.'counters.html';
			if ( !$this->adminMode && is_readable($cntFile) )
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
	  	if ( file_exists(EL_DIR_STYLES.'style.js') )
	  	{
	    	$this->_te->assignBlockVars('JS_LINK', array('js' => EL_BASE_URL.'/style/style.js') );
	  	}
		$GLOBALS['_js_'][EL_JS_CSS_FILE] = array_unique($GLOBALS['_js_'][EL_JS_CSS_FILE]);
		
		if (false !== ($dnum = array_search('eldialogform.js', $GLOBALS['_js_'][EL_JS_CSS_FILE])))
		{
			if (in_array('elfinder.min.js', $GLOBALS['_js_'][EL_JS_CSS_FILE]) || in_array('elrtefinder.full.js', $GLOBALS['_js_'][EL_JS_CSS_FILE]))
			{
				unset($GLOBALS['_js_'][EL_JS_CSS_FILE][$dmun]);
			}
		}
		// elPrintR($GLOBALS['_js_'][EL_JS_CSS_FILE]);
		
		$cache = '';
		if (!$this->_isAdmin || !$this->_conf->get('debug', 'common'))
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
		if (!$this->_isAdmin || !$this->_conf->get('debug', 'common'))
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

	function _renderCatalogNav($tree, $pos, $ID, $URL, $title)
	{
		$posNfo = !empty($this->_catsPosNfo[$pos]) ? $this->_catsPosNfo[$pos] : $this->_catsPosNfo[EL_POS_LEFT];
		$this->_te->setFile($posNfo[1], 'menus/'.$posNfo[0]);
		$this->_te->assignVars('catID', $ID);
		$this->_te->assignVars('catURL', $URL);
		if (!empty($title))
		{
			$this->_te->assignBlockVars('CAT_NAV_TITLE', array('catNavTitle' => $title));
		}
		$categoryID  = !empty($GLOBALS['categoryID']) ? (int)$GLOBALS['categoryID'] : 0;
		$appendLevel = EL_POS_TOP == $pos || EL_POS_BOTTOM == $pos;
		if ( EL_POS_TOP == $pos || EL_POS_BOTTOM == $pos )
		{
		  $appendLevel = true;
		}
		else
		{
		  $appendLevel = false;
		  array_shift($tree);
		}
		foreach ( $tree as $node )
		{
			$node['url'] = $URL.$node['id'].'/';
			if ( $node['id'] == $categoryID )
			{
				$block            = 'CAT_NAV.CAT_NAV_ITEM_CUR';
				$node['cssClass'] = $node['level'] <= 3 ? 'modNavCur'.$node['level'] : 'modNavCur3';
			}
			else
			{
				$block            = 'CAT_NAV.CAT_NAV_ITEM';
				$node['cssClass'] = $node['level'] <= 3 ? 'modNav'.$node['level'] : 'modNav3';
			}
			if ($appendLevel)
			{
			   $node['name'] = 1 < $node['level']
			      ? str_repeat(' + ', $node['level']-1).' '.$node['name']
			      : strtoupper($node['name']);
			}
			$this->_te->assignBlockVars($block, $node, 0);
		}
		$this->_te->parse($posNfo[1], $posNfo[1], true, false, true);
		$GLOBALS['parseColumns'][$pos] = true;
	}

	/**
   * Render navigation menu depends on site configuration
   */

	function _renderMenu()
	{
        $navType = $this->_conf->get('navType', 'layout');
        if ( (file_exists('./style/lib/elNavRnd.lib.php') && include_once('./style/lib/elNavRnd.lib.php') ) && function_exists('elusernavrnd') )
        {
            $navType = EL_NAV_TYPE_USER;
        }

	  switch ($navType)
	  {
	    case EL_NAV_TYPE_MAIN:
	      $pos = $this->_conf->get('mainMenuPos', 'layout');
	      if (empty($this->_mainMenuPos[$pos]))
	      {
	        $pos = EL_POS_LEFT;
	      }
	      $pages = EL_POS_TOP == $pos
	       ? $this->_nav->getPages(0, 1, false)
	       : $this->_nav->getPages(0, 1, true, true);
	      $m = $this->_mainMenuPos[$pos][3];
	      $this->{$m}($pages, $this->_mainMenuPos[$pos][0], $this->_mainMenuPos[$pos][1],
	                 $this->_mainMenuPos[$pos][2], (int)$this->_conf->get('mainMenuUseIcons', 'layout'));
	      break;

	    case EL_NAV_TYPE_COMBI:

	      $pages = $this->_nav->getPages(0, 1, false);
	      $param = $this->_mainMenuPos[EL_POS_TOP];
	      $this->_rndMenuHoriz($pages, $param[0], $param[1], $param[2], (int)$this->_conf->get('mainMenuUseIcons', 'layout'));
	      foreach ($pages as $page)
	      {
	        if ($this->_nav->isInCurPath($page['id']) && $page['has_childs'])
	        {

	          $subPages = $this->_nav->getPages($page['id'], 1, true, true); //elPrintR($subPages);
	          $pos      = $this->_conf->get('subMenuPos', 'layout');
	          if (empty($this->_subMenuPos[$pos]))
	          {
	            $pos = EL_POS_LEFT;
	          }
	          $param      = $this->_mainMenuPos[$pos]; 
	          $parentName = $this->_conf->get('subMenuDisplParent', 'layout') ? $this->_nav->getPageName($page['id']) : '';
	          $this->{$param[3]}($subPages, $param[0], $param[1], $param[2], (int)$this->_conf->get('subMenuUseIcons', 'layout'), $parentName);
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

		$aMenus = $this->_nav->getAdditionalMenus(); //elPrintR($aMenus);
		if ( !empty($aMenus[EL_ADD_MENU_TOP]) )
		{
			$this->_rndAddMenu( $aMenus[EL_ADD_MENU_TOP], EL_ADD_MENU_TOP, $this->_conf->get('addMenuTop', 'layout'));
		}
		if ( !empty($aMenus[EL_ADD_MENU_BOT]) )
		{
			$this->_rndAddMenu( $aMenus[EL_ADD_MENU_BOT], EL_ADD_MENU_BOT, $this->_conf->get('addMenuBottom', 'layout'));
		}
	//	echo EL_ADD_MENU_SIDE;
		if ( !empty($aMenus[EL_ADD_MENU_SIDE]) )
		{
			foreach ( $aMenus[EL_ADD_MENU_SIDE] as $menu )
			{
				// elPrintR($menu);
				if (! empty($menu['pages']) )
				{
					
					$param = $this->_mainMenuPos[$menu['pos']]; //elPrintR($param);
					$this->{$param[3]}($menu['pages'], $param[0], $param[1], $param[2], (int)$this->_conf->get('subMenuUseIcons', 'layout'), $menu['name']);
				}
				
			}
		}

		// render catalogs fast nav
		$groups = $this->_conf->getGroup('catalogsNavs'); //elPrintR($groups);

		if ( !empty($groups) )
		{
			$cat = & elSingleton::getObj('elCatalogCategory');
			foreach ( $groups as $ID=>$g )
			{
				if ( $g['all'] || (is_array($g['pIDs']) && in_array($this->_curPageID, $g['pIDs'])) )
				{
					$module = $this->_conf->get('module', $ID); 
					if (!empty($this->_catsTbTpl[$module]))
					{
						$cat->tb = sprintf($this->_catsTbTpl[$module], $ID);
						$tree = $cat->getTreeToArray((int)$g['deep'], false, false, true); //elPrintR($tree);
						$this->_renderCatalogNav($tree, $g['pos'], $ID, $this->_nav->getPageURL($ID), $g['title'] );
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
		if (!empty($groups))
		{
		  foreach ($groups as $ID=>$g)
		  {
		    //invalid module
		    if ('TechShop' != $this->_conf->get('module', $ID) )
		    {
		      $this->_conf->drop($ID, 'techShopsMNavs');
					$this->_conf->save();
		    }
		    if ( in_array(1, $g['pids']) || in_array($this->_curPageID, $g['pids']))
		    {
          $this->_rndTechShopMNav($ID, $g, $this->_nav->getPageURL($ID));
		    }
		  }
		}
	}

	function _rndTechShopMNav($ID, $data, $baseURL)
	{
	  $posNfo = !empty($this->_tsMNavPosNfo[$data['pos']])
	   ? $this->_tsMNavPosNfo[$data['pos']]
	   : $this->_tsMNavPosNfo[EL_POS_LEFT];
		$this->_te->setFile($posNfo[1], 'menus/'.$posNfo[0]);
    $this->_te->assignVars('tsMNavURL', $baseURL);
		if (!empty($data['title']))
		{
		  $this->_te->assignBlockVars('TS_MNAV_TITLE', array('tsMNavTitle'=>$data['title']));
		}
    if ($data['view'])
    {
	     $tb = 'el_techshop_'.$ID.'_manufact'; 
	     $db = &elSingleton::getObj('elDb');
	     $sql = 'SELECT id, name FROM '.$tb.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name';
	     $mnfs = $db->queryToArray($sql, 'id', 'name');
	     foreach ( $mnfs as $mID=>$mnf)
	     {
          $vars = array('name'=>$mnf, 'mnfID'=>$mID);
          $this->_te->assignBlockVars('TS_MNAV_MNFS.TS_MNAV_MNF', $vars, 1);
	     }
    }
	  $this->_te->parse($posNfo[1], $posNfo[1], true, false, true);
		$GLOBALS['parseColumns'][$data['pos']] = true;
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
	function _rndMenuHoriz($pages, $block, $varName, $file, $icons)
	{
		$this->_te->setFile($varName, 'menus/'.$file);
		//$curPageBlock = $this->_te->isBlockExists('HM_CPAGE'); 
		$size = sizeof($pages);
        $cellWidth = ceil(100/$size);
		$size--;
		$i = 0;
		foreach ( $pages as $one )
		{
			// совместимость со старой версткой
			// if ( $curPageBlock && $this->_nav->isInCurPath($one['id']) )
			// {
			// 	$b = 'HMENU.HM_PAGES.HM_CPAGE';
			// 	$bIco = $b.'.HMCP_ICO';
			// }
			// else
			// {
			// 	$b = 'HMENU.HM_PAGES.HM_PAGE';
			// 	$bIco = $b.'.HMP_ICO';
			// }
			$b         = 'HMENU.HM_PAGES.HM_PAGE';
			$bIco      = $b.'.HMP_ICO';
			$cssClass  = $this->_nav->isInCurPath($one['id']) ? 'nav-top-current' : 'nav-top-page';
			$cssClass .= $i==0 ? ' first' : ($i==$size ? ' last' : '');
			
			$page = array(
				'url'          => $one['url'], 
				'name'         => $one['name'], 
				'page_descrip' => $one['page_descrip'], 
				'ico_main'     => $one['ico_main'],
				'cssClass'     => $cssClass,
				'odd'          => (int)($i++%2),
				'num'          => $i,
				'cellWidth'    => $cellWidth
				);
			
			$this->_te->assignBlockVars($b, $page, 1);
			if ( $icons )
			{
				$this->_te->assignBlockVars($bIco, $page, 3);
			}
		}
		//$this->_te->assignBlockVars('HMENU', array('elementsNum'=> sizeof($pages)), 1);
		$this->_te->parse($varName);
		$this->_te->assignBlockVars($block);
	}

	function _rndMenuVert($pages, $pos, $varName, $file, $icons, $parentName=null)
	{
		// elPrintR($pages); echo $varName;
    	if (!$pages)
    	{
      		return;
    	}
    	$GLOBALS['parseColumns'][$pos] = true;

    	$this->_te->setFile($varName, 'menus/'.$file);
    	$curPageID = $this->_nav->getCurrentPageID();

    	if ($parentName)
    	{
      		$this->_te->assignBlockVars('VMENU.VM_PARENT', array('parentName'=>$parentName), 1);
    	}
		$i = 0;
    	foreach ( $pages as $one )
		{
			if ($curPageID != $one['id'])
			{
				$block           = 'VMENU.VM_PAGES.VM_PAGE';
				$blockIco        = $block.'.VMP_ICO';
				$one['cssClass'] = $one['level'] <= 3 ? 'nav'.$one['level'] : 'nav';
			}
			else
			{
				$block           = 'VMENU.VM_PAGES.VM_CPAGE';
				$blockIco        = $block.'.VMCP_ICO';
				$one['cssClass'] = $one['level'] <= 3 ? 'navCur'.$one['level'] : 'navCur3';
			}
			if ( $i++ == 0)
			{
				$one['cssClass'] .= ' first';
			}
			elseif ( $i == sizeof($pages) )
			{
				$one['cssClass'] .= ' last';
			}
			$this->_te->assignBlockVars($block, $one, 1);
			if ( $icons )
			{
				// elPrintR($one);
				$this->_te->assignBlockVars($blockIco, $one, 3);
			}
		}
		$this->_te->parse($varName, null, true, false, true);
	}

	/**
	 * Render vertical/horizontal menu on JS
	 *
	 * @param bool $vert
	 */
	function _rndMenuJS()
	{
	  	$pos       = $this->_conf->get('mainMenuPos', 'layout');
	  	if (empty($this->_mainMenuPos[$pos]))
	  	{
	    	$pos = EL_POS_LEFT;
	  	}
	  	$param     = $this->_mainMenuPos[$pos]; 
	  	$vert      = EL_POS_TOP != $pos;
		$pages     = $this->_nav->getPages(0, 0, true, true);
		$showIcons = $this->_conf->get('mainMenuUseIcons', 'layout');
		$icoURL    = $this->_conf->get('mainMenuUseIcons', 'layout')
			? EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/pageIcons/' 
			: '';

		$curPageID = $this->_nav->getCurrentPageID();
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
			
			$cssClass = '';
			if (!$vert && 1==$p['level'])
			{
				$cssClass = 'toplevel';
				$cssClass .= $curPageID == $p['id'] ? ' nav-top-current ' : '';
				$cssClass = 'class="'.$cssClass.'"';
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
				$html .= "</li>\n";
			}
			
			$level = $p['level'];
		}
		$cssClass = $vert ? 'el-menu-vert' : 'el-menu-horiz';
		$html = "\n<ul class=\"".$cssClass."\">\n".$html."</ul>\n";

    	if (EL_POS_TOP == $pos)
    	{
			$this->_te->assignBlockVars($param[0]);
    	}
    	else
    	{
      		$GLOBALS['parseColumns'][$param[0]] = true;
    	}

    	$this->_te->assignVars($param[1], $html);

		
		elAddJs('ellib/jquery.elmenu.js', EL_JS_CSS_FILE);
		$js = $vert ? '$(".el-menu-vert").elmenu({orientation : "vertical"}); ' : '$(".el-menu-horiz").elmenu({orientation : "horizontal", deltaY : 12}); ';
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
		
		if ( EL_ADD_MENU_TOP == $mType )
		{
			$var       = 'ADD_MENU_TOP';
			$pos       = 'ADD_MENU_TOP_POS';
			$file      = 'addMenuTop.html';
			$block     = 'ADD_MENU_TOP_PAGE';
			$blockIco  = '.AMTP_ICO';
			$blockText = '.AMTP_TEXT';
		}
		else
		{
			$var       = 'ADD_MENU_BOTTOM';
			$pos       = 'ADD_MENU_BOTTOM_POS';
			$file      = 'addMenuBottom.html';
			$block     = 'ADD_MENU_BOTTOM_PAGE';
			$blockIco  = '.AMBP_ICO';
			$blockText = '.AMBP_TEXT';
		}

		$this->_te->setFile($var, 'menus/'.$file);
		$s = sizeof($menu); 
		$width = ceil(100/$s);
		for ( $i=0; $i<$s; $i++ )
		{
			$cssClass = $i==0 ? 'first' : ( $i==$s-1 ? 'last' : '');
			$cssClass .= ($this->_nav->isInCurPath($menu[$i]['id']) ? ' nav-add-current' : '' );
			$this->_te->assignBlockVars($block, array('cssClass'=>$cssClass, 'num'=>$i+1, 'cellWidth'=>$width));
			if ($displ & EL_ADD_MENU_TEXT )
			{
				$this->_te->assignBlockVars($block.$blockText, $menu[$i], 1);
			}
			if ($displ & EL_ADD_MENU_ICO )
			{
				$this->_te->assignBlockVars($block.$blockIco, $menu[$i], 1 );
			}
		}
		$this->_te->parse($var);
		$this->_te->assignBlockVars($pos);
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
		if ( $this->userName )
		{
			$tpl = $this->_processMiscTpl('uInfo', $this->_conf->get('userInfoPosition', 'layout'));
			if (!$prepare)
			{
				$this->_te->assignVars( 'userName', $this->userName );
				$this->_te->setFile($tpl[0], $tpl[1]);
				$this->_te->parse($tpl[0]);
			}
		}
		elseif ( 'n' != ($pos = $this->_conf->get('authFormPosition', 'layout')) )
		{
			elLoadMessages('Auth');
			$tpl = $this->_processMiscTpl('uAuth', $pos);
			if (!$prepare)
			{
				$this->_te->setFile($tpl[0], $tpl[1]);
				if ($this->isRegAllow && $this->_te->isBlockExists('USER_REG') )
				{
					$this->_te->assignBlockVars('USER_REG');
				}
				
				$this->_te->parse($tpl[0]);
			}
			
		}
	}

    /**
     * Рисует корзину заказа для IShop и TechShop
     *
    **/
	function _renderShoppingCart($prepare=false)
	{
		if ($prepare)
        {
            return;
        }
        $pos = $this->_conf->get('ICartPosition', 'layout');  
        if ( (!$pos || 'n' == $pos)
        || (  !$this->_conf->findGroup('module', 'IShop') && !$this->_conf->findGroup('module', 'TechShop'))
		)
        {
            return;
        }

        $ICart = & elSingleton::getObj('elICart');
		$qnt   = $ICart->getTotalQnt();
		if ( !$qnt && !$this->_conf->get('iCartDisplayEmpty', 'layout') )
		{
			return;
		}
		$tpl = $this->_processMiscTpl('icart', $pos); 
		$this->_te->setFile($tpl[0], $tpl[1]);
		if ( !$qnt )
		{
			$this->_te->assignBlockVars('ICART_EMPTY');
		}
		else
		{
			$currInfo  = elGetCurrencyInfo();
			$pricePrec = $this->_conf->get('priceIsInt', 'iCart') ? 0 : 2;
			$amount    = number_format(round($ICart->getAmount(), $pricePrec), $pricePrec, $currInfo['decPoint'], $currInfo['thousandsSep']);
			$data      = array('iCartQnt'     => $qnt, 'iCartAmount'  => $amount );
			$this->_te->assignBlockVars('ICART_SUMMARY',  $data);
		}
		
		$this->_te->parse($tpl[0]);
	}

	function _processMiscTpl($name, $pos)
	{
		if ( empty($this->_miscTpl[$name][$pos]))
		{
			$pos = EL_POS_LEFT;
		}
		$tpl = $this->_miscTpl[$name][$pos];
		if ( !empty($tpl[2]) )
		{
			$GLOBALS['parseColumns'][$pos] = 1;
		}
		else
		{
			$this->_te->assignBlockOnce($tpl[0]);
		}
		return $tpl;
	}

}

?>