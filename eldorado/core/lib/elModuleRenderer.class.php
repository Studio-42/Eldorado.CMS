<?php
/**
 * Base class for module's renderers classes
 */

class elModuleRenderer
{
  var $_mName     = '';
  var $_dir       = '.';
  var $_defTpl    = 'default.html';
  var $_tpls      = array();
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
	// $this->_rndCompliteNormal( $mMap );
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
	$tpl = isset($this->_tpls[$h]) ? $this->_tpls[$h] : $this->_defTpl;
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

		if ( $isPopUp ) 
		{
			return;
			unset($acts['Plugins']);
		}
		// elPrintR($acts);
		// elLoadJQueryUI();
		// elAddCss('el-menu-float.css',     EL_JS_CSS_FILE);
		// elAddJs('jquery.cookie.js',       EL_JS_CSS_FILE);
		// elAddJs('ellib/jquery.elmenu.js', EL_JS_CSS_FILE);

		$html  = "<ul class='el-menu-float'>\n"; 
		$html .= '<li class="toplevel drag-helper"><a href="#" onclick="return false" class="drag-helper">&nbsp;</a></li>';
		foreach ( $acts as $name=>$group )
		{
			if ( sizeof($group) ==1 )
			{
				$onclick = !empty($act['onClick']) ? ' onclick="'.$act['onClick'].'";return false;' : '';
				$html .= '<li class="toplevel"><a href="'.$group[0]['url'].'"'.$onclick.' class="'.$group[0]['ico'].'" title="'.m($group[0]['label']).'">&nbsp;</a></li>';
			}
			elseif ( sizeof($group) > 1 )
			{
				$html .= '<li class="toplevel"><a href="#" class="'.str_replace(' ', '_', $name).'"  onclick="return false;">&nbsp;</a>';

				$html .= '<ul>';
				foreach ($group as $act) 
				{
					// elPrintR($act);
					$onclick = !empty($act['onClick']) ? 'onclick="'.$act['onClick'].'; return false"' : '';
					$html .= '<li><a href="'.$act['url'].'" class="'.$act['ico'].'" '.$onclick.'>'.m($act['label']).'</a></li>';
				}
				$html .= '</ul>';
				$html .= '</li>';
			}
		}
		$html .= '</ul>';
		$this->_te->assignBlockVars('A_MENU', array('amenu'=>$html));
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