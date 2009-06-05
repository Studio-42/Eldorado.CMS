<?php

class elRndNavigationControl extends elModuleRenderer
{
  var $_tpls    = array('meta'    => 'adminMetaList.html',
                        'menuAdd' => 'menuAdd.html',
                        'modules' => 'modules.html');

  var $_admTpls = array('meta'    => 'adminMetaList.html',
                        'menuAdd' => 'adminMenuAdd.html' );

  function render( $menu, $modList, $parentsID )
  {
	elAddJs('jquery.treeTable.min.js', EL_JS_CSS_FILE);
    $this->_setFile();
    $vsb = array(EL_PAGE_DISPL_ALL => m('Whole site'),
                 EL_PAGE_DISPL_MAP => m('On site map only'),
                 0                 => m('Invisible'));
    $vsbLimit = array( 0=>'',
                       EL_PAGE_DISPL_LIMIT_NA => m('Only for non authed users'),
                       EL_PAGE_DISPL_LIMIT_A  => m('Only for authed users') );

    $perms = array( 0=>m('No access'), EL_READ=>m('Read only'));

    $nav = & elSingleton::getObj('elNavigator');
    foreach ($menu as $p)
    {
      $page             = $p->getAttrs();
      $page['deep']     = str_repeat('-&nbsp;', $page['level']-1);
      $page['visible']  = $vsb[$page['visible']].'<br /> '.$vsbLimit[$page['visible_limit']];
      $page['perm']     = $perms[$page['perm']];
      $page['module']   = $modList[$page['module']];
      $page['ico_main'] = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/pageIcons/'.$page['ico_main'];
      $page['url']      = $nav->getPageURL($page['id']);
		$page['parent'] = $parentsID[$p->ID]>1 ? ' class="child-of-node-'.$parentsID[$p->ID].'"' : '';

      $this->_te->assignBlockVars('MPAGE', $page);

      if ( $page['redirect_url'] )
      {
        $URL = strlen($page['redirect_url']) <= 25
          ? $page['redirect_url']
          : substr($page['redirect_url'], 0, 15).'...'.substr($page['redirect_url'], -7, 7);
        $this->_te->assignBlockVars('MPAGE.OUTER_URL', array('redirect_url'=>$URL), 1);
      }
    }
  }


  function rndMenusAdd( $aMenus, $statTop, $statBot )
  { //elPrintR($aMenus);
    $this->_setFile('menuAdd');

    $data = array('mTopID'      => EL_ADD_MENU_TOP,
                  'mBotID'      => EL_ADD_MENU_BOT,
                  'menuTopStat' => $statTop,
                  'menuBotStat' => $statBot); 
    $this->_te->assignVars($data);

    if ( empty($aMenus[EL_ADD_MENU_TOP]) )
    {
      $this->_te->assignBlockVars('ADM_ADD_MENU_TOP_NOPAGE');
    }
    else
    {
      foreach ( $aMenus[EL_ADD_MENU_TOP] as $page )
      {
      	$page['ico'] = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/pageIcons/'.$page['ico'];
        $this->_te->assignBlockVars('ADM_ADD_MENU_TOP_PAGE', $page);
      }
    }

    if ( empty($aMenus[EL_ADD_MENU_BOT]) )
    {
      $this->_te->assignBlockVars('ADM_ADD_MENU_BOT_NOPAGE');
    }
    else
    {
      foreach ( $aMenus[EL_ADD_MENU_BOT] as $page )
      {
      	$page['ico'] = EL_BASE_URL.'/'.EL_DIR_STORAGE_NAME.'/pageIcons/'.$page['ico'];
        $this->_te->assignBlockVars('ADM_ADD_MENU_BOT_PAGE', $page);
      }
    }
    
    if ( empty($aMenus[EL_ADD_MENU_SIDE]) )
    {
      $this->_te->assignBlockVars('ADD_SIDE_NOMENUS');
    }
    else
    {
      foreach ($aMenus[EL_ADD_MENU_SIDE] as $menu )
      {
        $data = array('id'=>$menu->ID,
                      'name'=>$menu->name,
                      'pos'=>$GLOBALS['posLRT'][$menu->pos],
                      'dest'=>implode('<br />', $menu->dst),
                      'source'=>implode('<br />', $menu->src));
        $this->_te->assignBlockVars('ADD_SIDE_MENUS.ADD_SIDE_MENU', $data);
      }
    }

  }

  function rndMeta($tree)
  {
    $this->_setFile('meta');
    foreach($tree as $page)
    {
      $this->_te->assignBlockVars('NC_META', $page);
      $b = $page['has_childs'] ? 'NC_META.NC_META_CTRL' : 'NC_META.NC_META_CTRL_NO';
      $this->_te->assignBlockVars($b, $page, 1);

    }
  }
  
  function rndModules($modules, $locked)
  {
	elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
    $this->_setFile('modules');
    foreach ($modules as $one)
    {
      $one['usedMsg'] = $one['used'] ? m('Yes') : m('No');
      $this->_te->assignBlockVars('SYS_MODULE', $one);
      if ( $this->_admin && !in_array($one['module'], $locked) && !$one['used'] )
      {
        $this->_te->assignBlockVars('SYS_MODULE.MODRM', $one, 1);
      }
    }
    
  }

}

?>