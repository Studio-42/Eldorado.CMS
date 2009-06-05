<?php

class elNavigator
{
	/**
   * массив страниц сайта
   */
	var $menus = array();

	var $aMenus = array(  );

	/**
   * ID текущей страницы
   */
	var $curPageID = 0;

	/**
   * ID страницы по умолчанию (запись с наименьшим _left & level=1)
   */
	var $defaultPageID = 0;
	/**
   * массив аргументов передаваемый странице в URL
   */
	var $args = array();

	/**
   * соответствие путей к страницам (URL) к ID страниц в массиве menus
   */
	var $_pathToPageMap = array();

	var $_maxRightNdx = 2;

	var $_srvMap = array();

	var $_replaceAltTitle = '';

	function init($srvMap)
	{
		$this->_load();
		$this->_location($srvMap);
	}

	/**
   * возвращает абсолютный URL текущей страницы
   */
	function getURL()
	{
		return EL_BASE_URL.'/'.( $this->curPageID == $this->defaultPageID ? '' : $this->menus[$this->curPageID]['path'] .'/');
	}

	/**
   * возвращает массив аргументов страницы
   */
	function getRequestArgs()
	{
		return $this->args;
	}

	function isDefaultPage()
	{
		return $this->curPageID == $this->defaultPageID;
	}
	/**
   * определяет, находится ли страница в ветке текущей страницы
   */
	function isInCurPath($ID)
	{
		if ( isset($this->menus[$ID]) &&
			 ($this->menus[$ID]['_left']<=$this->menus[$this->curPageID]['_left'] &&
				$this->menus[$ID]['_right']>=$this->menus[$this->curPageID]['_right']   ) )
		{
			return true;
		}
		return false;
	}

	/**
   * возвращает массив страниц от родителя parentID, на глубину deep, с областью видимости displ доступные для текущего пользователя
   * incPath - включает в результат один уровень страниц находящихся в ветке текущей страницы
   * incMenu - включает в результат один уровень страниц, если одна из страниц результата помечена как subMenu
   * user - 0 - текущий пользователь, 1 - гость
   */
	function getPages( $parentID=0, $deep=1, $incPath=true, $incMenu=false,  $displ=EL_PAGE_DISPL_ALL, $user=0 )
	{
		if ( !$parentID || !isset($this->menus[$parentID]) )
		{
			$topParent = array('id'=>1, 'level'=>0, 'parent_id'=>0, '_left'=>1, '_right'=>$this->_maxRightNdx );
		}
		else
		{
			$topParent = $this->menus[$parentID];
		}

		$parents = array($topParent);
		$pages = array();
		$ats = &elSingleton::getObj('elATS');
		$displLimit = $ats->isUserAuthed() ? EL_PAGE_DISPL_LIMIT_A : EL_PAGE_DISPL_LIMIT_NA;
		$curPage = $this->getCurrentPage();

		foreach ( $this->menus as $pID=>$p )
		{
			if ( (!$ats->allow(EL_READ, $pID) || $p['visible'] < $displ ||
			($p['visible_limit'] && $displLimit != $p['visible_limit'] ) ||
			!( $p['_left']>$topParent['_left'] && $p['_right']<$topParent['_right'])) || ( (!$ats->allowGuest($pID) || $p['visible_limit'] == 2) && ($user) ) )
			{ 
				continue;
			}

			while ( ($parents[0]['_right'] < $p['_left']) && (sizeof($parents)>1) )
			{
				array_shift($parents);
			}

			if ( $p['has_childs'] && $pID != $parents[0]['id'])
			{
				if ( !$deep || $p['level']-$topParent['level']<$deep )
				{
					array_unshift( $parents,  array('id'=>$p['id'], 'level'=>$p['level'], '_left'=>$p['_left'], '_right'=>$p['_right']) );

				}
				elseif ( $incPath && $this->isInCurPath($pID) )
				{
					array_unshift( $parents,  array('id'=>$p['id'], 'level'=>$p['level'], '_left'=>$p['_left'], '_right'=>$p['_right']) );
				}
				elseif ( $incMenu && $p['is_menu'] )
				{
					array_unshift( $parents,  array('id'=>$p['id'], 'level'=>$p['level'], '_left'=>$p['_left'], '_right'=>$p['_right']) );
				}
			}
			if ( $pID==$parents[0]['id'] || ($p['parent_id'] == $parents[0]['id'])  )
			{
				$p['level'] -= $topParent['level'];
				$pages[] = $p;
			}
		}
		return $pages;
	}

	function getNavPath()
	{
		if (!empty($this->_replaceNavPath))
		{
			return $this->_replaceNavPath;
		}

		$path = array();
		$pID = $this->curPageID;

		do
		{
			array_unshift( $path, array('url'=>$this->menus[$pID]['url'], 'name'=>$this->menus[$pID]['name']) );
			$pID = $this->menus[$pID]['parent_id'];
		}
		while( $pID>1 );
		return $path;
	}

	function replaceNavPath($path)
	{
		$this->_replaceNavPath = $path;
	}

	function getAltTpl()
	{
		return !empty($this->menus[$this->curPageID]['alt_tpl'])
			? $this->menus[$this->curPageID]['alt_tpl']
			: null;
	}
	function getIndexAltTpl()
	{
		reset($this->menus);
		$index = current($this->menus);// elPrintR($index);
		return !empty($index['alt_tpl']) ? $index['alt_tpl'] : null;
	}
	/**
   * возвращает массив - страницу по ID
   */
	function getPage($ID)
	{
		return isset($this->menus[$ID]) ? $this->menus[$ID] : null;
	}

	function getPageURL($ID)
	{
		return isset($this->menus[$ID]) ? $this->menus[$ID]['url'] : null;
	}

	function getPageName($ID)
	{
		return isset($this->menus[$ID]) ? $this->menus[$ID]['name'] : null;
	}

	/**
   * возвращает массив - текущую страницу
   */
	function getCurrentPage()
	{
		return $this->menus[$this->curPageID];
	}

	/**
   * возвращает ID текущей страницы
   */
	function getCurrentPageID()
	{
		return $this->curPageID;
	}

	function getCurPageModuleName()
	{
		return $this->menus[$this->curPageID]['module'];
	}

	function pageByModule( $module )
	{
		foreach ( $this->menus as $page )
		{
			if ( $module == $page['module'] )
			{
				return $page;
			}
		}
		return null;
	}

	function getPageID( $dir )
	{
		foreach ( $this->menus as $ID=>$page )
		{
			if ( $dir == $page['dir'] )
			{
				return $ID;
			}
		}
		return null;
	}

	/**
   * return an array of current page Meta tags
   */
	function getCurrentPageMeta()
	{
		$meta = array( ); //array('name'=>'AUTHOR', 'content'=>'dvl at weber dot ru , dima at levashov dot ru') );
		$db = & elSingleton::getObj('elDb');
		$sql = 'SELECT UCASE(name) AS name, content FROM el_meta WHERE '
					.'page_id=\''.$this->curPageID.'\' OR page_id=1 ORDER BY page_id, name';
		$db->query($sql);
		while ( $r = $db->nextRecord() )
		{
			if ( !isset($meta[$r['name']]) || !empty($r['content']) )
			{
				$meta[] = $r;
			}
		}
		return $meta;
	}

	/**
  * return additional menus (top, bottom and side)
  */
	function getAdditionalMenus()
	{
        if ( empty($this->aMenus) )
        {
            $ats = &elSingleton::getObj('elATS');
            foreach ($this->menus as $p)
            {
                if ( $p['in_add_menu_top'] && $ats->allow(EL_READ, $p['id']) )
                {
                    $this->aMenus[EL_ADD_MENU_TOP][] = array('id'=>$p['id'], 'name'=>$p['name'], 'url'=>$p['url'], 'ico'=>$p['ico_add_menu_top']);
                }
                if ($p['in_add_menu_bot'] && $ats->allow(EL_READ, $p['id']) )
                {
                    $this->aMenus[EL_ADD_MENU_BOT][] = array('id'=>$p['id'], 'name'=>$p['name'], 'url'=>$p['url'], 'ico'=>$p['ico_add_menu_bot']);
                }
            }
            
            // load  additional side menus 
            $this->aMenus[EL_ADD_MENU_SIDE] = array();
            $db = & elSingleton::getObj('elDb');
            $db->query('SELECT m.id, m.name, m.pos FROM el_amenu AS m, el_amenu_dest AS d WHERE d.p_id='.$this->curPageID.' AND m.id=d.m_id ORDER BY m.id');
            while ( $r = $db->nextRecord() )
            {
                $this->aMenus[EL_ADD_MENU_SIDE][$r['id']] = array('name'=>$r['name'], 'pos'=>$r['pos'], 'pages'=>array() );
            }
            
            if ( !empty($this->aMenus[EL_ADD_MENU_SIDE]) )
            {
                $sql = 'SELECT s.m_id, s.p_id FROM el_menu AS m, el_amenu_source AS s '
                .'WHERE s.m_id IN ('.implode(',', array_keys($this->aMenus[EL_ADD_MENU_SIDE])).') AND m.id=s.p_id ORDER BY  m._left';
                $db->query( $sql );
                while ($r = $db->nextRecord() )
                {
                    if ( !empty($this->aMenus[EL_ADD_MENU_SIDE][$r['m_id']]) && $ats->allow(EL_READ, $r['p_id']) )
                    {
                        $this->aMenus[EL_ADD_MENU_SIDE][$r['m_id']]['pages'][] = array('id'    => $r['p_id'],
                                                                                       'name'  => $this->menus[$r['p_id']]['name'],
                                                                                       'url'   => $this->menus[$r['p_id']]['url'],
                                                                                       'level' => 1//$this->menus[$r['p_id']]['level']
                                                                                       );
                    }
                }
            }
            
        }
        
		return $this->aMenus;
	}

	function getPagesByModules( $modules )
	{
	  $ret = array();
	  foreach ($this->menus as $ID=>$page)
	  {
	    if ( in_array($page['module'], $modules) )
	    {
	      $ret[$ID] = array('name'=>$page['name'], 'module'=>$page['module'], 'path'=>$page['path']);
	    }
	  }
	  return $ret;
	}

	/**
   * определяет текущую страницу и аргументы ей переданные
   */
	function _location( $srvMap )
	{
		$p = parse_url($_SERVER['REQUEST_URI']);
		$request = preg_replace(
			array( '|^'.(dirname($_SERVER['SCRIPT_NAME'])).'|', '|/{2,}|', '|^/?(.*[^/])/?$|', '|^/|'),
			array('', '/', "\\1", ''), $p['path']);

		$this->curPageID = $this->defaultPageID;
		if (empty($request) || '/' == $request)
		{
			return;
		}
		$r = explode('/', $request); //elPrintR($r);

		while (!empty($r))
		{
			$path = implode('/', $r);
			if ( !empty($this->_pathToPageMap[$path]) )
			{
				if (isset($srvMap[$r[sizeof($r)-1]]))
				{
					array_unshift($this->args, array_pop($r)); //elPrintR($this->args);
				}
				return $this->curPageID = $this->_pathToPageMap[$path];
			}
			array_unshift($this->args, array_pop($r));
		}
		
		
		
	}

	/**
   * загружает меню из БД
   */
	function _load()
	{
		$db = & elSingleton::getObj('elDb');

		$sql = 'SELECT ch.id, ch.dir, ch.name, ch.page_descrip, ch.level, ch._left, ch._right, ch.is_menu, '
		.'ch.module, ch.visible_limit, MIN(p.visible) AS visible, (ch._right-ch._left-1) AS has_childs, '
		.'ch.redirect_url, ch.in_add_menu_top, ch.in_add_menu_bot, ch.alt_tpl,  '
		.'IF(ch.ico_main<>"", ch.ico_main, "default.png") AS ico_main, '
		.'IF(ch.ico_add_menu_top<>"", ch.ico_add_menu_top, "default.png") AS ico_add_menu_top, '
		.'IF(ch.ico_add_menu_bot<>"", ch.ico_add_menu_bot, "default.png") AS ico_add_menu_bot, '
		.'p2.id AS parent_id  '
		.'FROM el_menu AS ch, el_menu AS p, el_menu AS p2 '
		.'WHERE ch._left BETWEEN p._left AND p._right  AND (ch._left BETWEEN p2._left AND p2._right AND ch.level=p2.level+1) '
		.'GROUP BY ch.id ORDER BY ch._left';
		$db->query($sql);
		if ( 1>=$db->numRows() )
		{
			elThrow(E_USER_WARNING, 'Site navigation has invalid cofiguration', null, null, true);
		}

		$parents = array();
		$stack = array();
		$path = array();
		while ( $p = $db->nextRecord() )
		{
			$p['page_descrip'] = htmlspecialchars($p['page_descrip']);
			while ( !empty($stack) && $p['parent_id'] != $stack[0]['id'] )
			{
				array_shift($stack);
				array_pop($path);
			}
			array_unshift($stack, $p);
			array_push($path, $p['dir']);

			$p['path'] = implode('/', $path);
			if ( empty($p['redirect_url']) )
			{
				$p['url'] = EL_BASE_URL.'/'.$p['path'].'/';
			}
			else
			{
				$p['url'] = $p['redirect_url'];
			}
			$this->menus[$p['id']] = $p;
			$this->_pathToPageMap[$p['path']] = $p['id'];
			if ( $p['_right'] >= $this->_maxRightNdx )
			{
				$this->_maxRightNdx = $p['_right'] + 1;
			}
		}

		$p                                         = current($this->menus);
		$this->defaultPageID                       = $p['id'];
		$this->menus[$this->defaultPageID]['path'] = '/';
		$this->menus[$this->defaultPageID]['url']  = EL_BASE_URL.'/';
	}

	function _loadASideMenu()
	{
		// load  additional side menus now because of we new current page ID
        $this->aMenus[EL_ADD_MENU_SIDE] = array();
		$db = & elSingleton::getObj('elDb');
		$db->query('SELECT m.id, m.name, m.pos FROM el_amenu AS m, el_amenu_dest AS d WHERE d.p_id='.$this->curPageID.' AND m.id=d.m_id ORDER BY d.sort, m.id');
		while ( $r = $db->nextRecord() )
		{
			$this->aMenus[EL_ADD_MENU_SIDE][$r['id']] = array('name'=>$r['name'], 'pos'=>$r['pos'], 'pages'=>array() );
		}
		$ats = &elSingleton::getObj('elATS');
		if ( !empty($this->aMenus[EL_ADD_MENU_SIDE]) )
		{
			$sql = 'SELECT s.m_id, s.p_id FROM el_amenu AS m, el_amenu_source AS s WHERE s.m_id IN ('.implode(',', array_keys($this->aMenus[EL_ADD_MENU_SIDE])).') ORDER BY s.sort, s.p_id';
			$db->query( $sql );
			while ($r = $db->nextRecord() )
			{
                echo $r['p_id'].' '.intval($ats->allow(EL_READ, $r['p_id'])).' <br>';
				if ( !empty($this->aMenus[EL_ADD_MENU_SIDE][$r['m_id']]) && $ats->allow(EL_READ, $r['p_id']) )
				{
					$this->aMenus[EL_ADD_MENU_SIDE][$r['m_id']]['pages'][] = array('id'    => $r['p_id'],
                                                                                   'name'  => $this->menus[$r['p_id']]['name'],
																				   'url'   => $this->menus[$r['p_id']]['url'],
																				   'level' => 1//$this->menus[$r['p_id']]['level']
                                                                                   );
				}
			}
		}
	}


}


?>