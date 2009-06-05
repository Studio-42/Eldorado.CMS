<?php
/**
 * Base class for module's renderers classes
 */

class elModuleRenderer
{
  var $_mName     = '';
  var $_dir       = '.';
  var $_defTpl    = 'default.html';
  var $_defAdmTpl = 'adminDefault.html';
  var $_tpls      = array();
  var $_admTpls   = array();
  var $_paneCont  = array();
  var $_te        = null;

  var $_admin     = false;
  var $_prnt      = false; //depricated
  var $_icoPopup  = false;
  var $_panePopup = false;
  var $_conf      = array();

  var $_tabs      = null;
  var $_curTab    = null;
  var $_path      = null; //submodule part of URL - set by setTabs-method of module
  var $_innerPath = null; //part of URL inside submodule

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  function init( $moduleName, $conf, $prnt=false, $admin=false, $tabs=null, $curTab=null )
  {
    $this->_mName     = $moduleName;
    $this->_conf      = $conf;
    $this->_dir       = 'modules/'.$moduleName.'/';
    $this->_prnt      = $prnt;
    $this->_admin     = $admin;
    $this->_tabs      = $tabs;
    $this->_curTab    = $curTab;

    $this->_te        = &elSingleton::getObj('elTE');
    if ( !empty($tabs) )
    {
      $this->_path = $tabs[$curTab]['path'];
    }
  }

  function setDir($dir)
  {
  	$this->_dir = $dir;
  }

  function setDefTpl($fileName)
  {
  	$this->_defTpl = $fileName;
  }

  function render( $vars, $fh=null, $block=null, $l=0 )
  {
    $this->_setFile($fh);
    if (  !is_array($vars) )
    {
      return;
    }

    if ( !$block )
    {
      $this->_te->assignVars( $vars );
    }
    else
    {
    	if (empty($vars))
    	{
    		$vars = array(' ');
    	}
      $this->_te->assignBlockFromArray($block, $vars, $l);
    }
  }

  function addOnPane( $cont, $new=false, $attr=null )
  {
    $this->_paneCont[] = array('content'=>$cont, 'attr'=>$attr, 'isNew'=>$new);
  }

  function renderComplite( $mMap )
  {
    if ( EL_WM == EL_WM_NORMAL )
    {
      $this->_rndCompliteNormal( $mMap );
    }
    else
    {
      $this->_rndComplitePopup( $mMap );
    }

    // check because some module renderers dont use tpl files (forms, etc)
    if ( $this->_te->isTplLoaded('PAGE') )
    {
      $this->_te->parseWithNested('PAGE', 'PAGE', true);
    }
  }

  function addToContent($str, $repl=false)
  {
  	if ($repl)
  	{
  		$str = str_replace( $this->_te->vars['name'], $this->_te->vars['value'], $str);
  	}
    $this->_te->assignVars('PAGE', $str, true);
  }

 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  function _setFile($h='', $t='', $whiteSpace=false)
  {
    if ( !$this->_admin )
    {
      $tpl = $h && isset($this->_tpls[$h]) ? $this->_tpls[$h] : $this->_defTpl;
    }
    elseif ( $h && isset($this->_admTpls[$h]) )
    {
      $tpl = $this->_admTpls[$h];
    }
    elseif ($h && isset($this->_tpls[$h]) )
    {
      $tpl = $this->_tpls[$h];
    }
    else
    {
      $tpl = $this->_defAdmTpl && file_exists($this->_te->dir.'/'.$this->_dir.$this->_defAdmTpl)
        ? $this->_defAdmTpl
        : $this->_defTpl;
    }
    $this->_te->setFile($t ? $t : 'PAGE', $this->_dir.$tpl, $whiteSpace );
  }

  function _rndComplitePopup( $acts )
  {
  	if ( !$this->_panePopup && !$this->_icoPopup )
  	{
  		return;
  	}
    $this->_te->setFile('PAGE_TITLE', 'common/popUpTitle.html');

    if ( $this->_icoPopup && !empty($acts))
    {
    	$this->_rndActionsMenu($acts, true);
    }
    if ( $this->_panePopup )
    {
      	$this->_rndPane();
    }
  }

  function _rndCompliteNormal( $acts )
  {
    if ( !$this->_tabs )
    {
      $this->_te->setFile('PAGE_TITLE', 'common/pageTitle.html');
    }
    else
    {
      $this->_te->setFile('PAGE_TITLE',     'common/pageTitleTabs.html');
      $this->_te->assignVars('SUBMOD_PATH', $this->_path);
      $this->_te->assignVars('SUBMOD_URL',  EL_URL.$this->_path);
      $this->_te->assignVars('POPUP_URL',   EL_URL.EL_URL_POPUP.'/'.$this->_path);
      $this->_te->assignVars('tabWidth',    ' width="'.intval(100/sizeof($this->_tabs)).'%"');
	  $this->_te->assignVars('tabsNum', sizeof($this->_tabs));
      foreach( $this->_tabs as $tab )
      {
        $tab['label'] = m($tab['label']);
        $block = $this->_path==$tab['path'] ? 'TABS.A_TAB' : 'TABS.TAB';
        $this->_te->assignBlockVars($block, $tab, 0);
      }
    }

    if ( !empty($acts) )
    {
  		$this->_rndActionsMenu($acts); 
    }
    $this->_rndPane();
  }

	function _rndActionsMenu($acts, $isPopUp=false)
	{ 
		if (!$this->_te->isBlockExists('A_MENU'))
		{
			return $this->_rndActionsMenuOLD($acts, $isPopUp);
		}
		if ( $isPopUp && !empty($acts['Plugins']) ) 
		{
			unset($acts['Plugins']);
		}
		elAddCss('admin-menu.css',  EL_JS_CSS_FILE);
		elAddJs('jquery-ui.js',     EL_JS_CSS_FILE);
		elAddJs('jquery.elmenu.js', EL_JS_CSS_FILE);

		$html  = '<ul class="rounded-5 a-menu no-print'.($isPopUp ? 'a-menu-popup' : '').'">'; 
		$html .= '<li class="drag-handle"><img src="{icoDrag}" width="16" /> <img src="{STYLE_URL}icons/admin/delim.gif" width="2" /></li>';
		foreach ( $acts as $name=>$group )
		{
			if ( sizeof($group) ==1 )
			{
				$text    = m($group[0]['label']);
				$onclick = !empty($act['onClick']) ? 'onclick="'.$act['onClick'].'"' : '';
				if ( empty($group[0]['ico']) )
				{
					$css = ' class="a-menu-txt"';
				}
				else
				{
					$css = '';
					$text = '<img src="{'.$group[0]['ico'].'}" width="16" title="'.$text.'" />';
				}
				$html .= '<li'.$css.'><a href="'.$group[0]['url'].'"'.$onclick.'>'.$text.'</a></li>';
			}
			elseif ( sizeof($group) > 1 )
			{
				$text = m($name); 
				
				$html .= !empty($GLOBALS['elIcons']['Group'.$name])
					? '<li><img src="{icoGroup'.$name.'}" width="16" title="'.$text.'" />' 
					: '<li class="a-menu-txt">'.$text.'';
				$html .= '<ul>';
				foreach ( $group as $act )
				{ //elPrintR($act);
					$html .= '<li>'; 
					$html .= '<a href="'.$act['url'].'"'.(!empty($act['onClick']) ? 'onclick="'.$act['onClick'].'"' : '').'>';
					$html .= !empty($act['ico']) ? '<img src="{'.$act['ico'].'}" /> ' : '';
					$html .= m($act['label']);
					$html .= '</a></li>';
				}
				$html .= '</li></ul>';
			}
			
			
		}
		$html .= '</ul>';
		$this->_te->assignBlockVars('A_MENU', array('amenu'=>$html));
	}
		

  function _rndActionsMenuOLD($acts, $isPopUp=false)
  {
  	elAddJs('gMenu.js', EL_JS_CSS_FILE);
  	elAddCss('gMenu.css');
  	$js = '';
  	$this->_te->setFile('ACTIONS_MENU', 'common/actionsMenu.html');
  	foreach ($acts as $gName=>$g)
  	{
        $actName = 'act'.str_replace(' ', '_', $gName);
  		$js .= "var ".$actName." = [";
  		foreach ($g as $a)
  		{
  			if ($isPopUp)
  			{

  				$a['url'] = str_replace(EL_URL, EL_URL.EL_URL_POPUP.'/', $a['url']);
  			}
  			$js .= '["'.(m($a['label']).'", "'.$a['url']).'", null,'.(!empty($a['onClick'])?'"'.$a['onClick'].'"':'null').'], ';
  		}
  		$js = substr($js, 0, -2);
  		$js .= "];";
  		$vars = array('groupID'=>$gName, 'arName'=>$actName, 'groupName'=>m($gName)); //elPrintR($vars);
  		$this->_te->assignBlockVars('AGROUP', $vars);
  	}
  	elAddJs($js);
  }

  function _rndPane()
  {
    if ( !empty($this->_paneCont) )
    {
      foreach ( $this->_paneCont as $one )
      {
        $this->_te->assignBlockVars('PANE.PANE_CONTENT', $one, $one['isNew'] ? 2 : 1);
      }
    }
  }

	/**
   * Retiurn config paramerter if exists
   */
	function _conf( $param )
	{
		return isset($this->_conf[$param]) ? $this->_conf[$param] : null;
	}

}

?>