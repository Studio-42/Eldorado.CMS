<?php
/**
 * Default renderer for modules / parent class
 *
 * @package core
 **/
class elModuleRenderer {
	/**
	 * templates directory
	 *
	 * @var string
	 **/
	var $_dir       = '.';
	/**
	 * default template file name
	 *
	 * @var string
	 **/
	var $_defTpl    = 'default.html';
	/**
	 * other templates handle=>file name list
	 *
	 * @var array
	 **/
	var $_tpls      = array();
	/**
	 * panel (under page title) content
	 *
	 * @var array
	 **/
	var $_paneCont  = array();
	/**
	 * elTE object
	 *
	 * @var elTE
	 **/
	var $_te        = null;
	/**
	 * is admin mode?
	 *
	 * @var bool
	 **/
	var $_admin     = false;
	/**
	 * module configuration
	 *
	 * @var array
	 **/
	var $_conf      = array();
	/**
	 * tabs list (multiModule only)
	 *
	 * @var array
	 **/
	var $_tabs      = null;
	/**
	 * current tab (multiModule only)
	 *
	 * @var string
	 **/
	var $_curTab    = null;
	/**
	 * submodule part of URL - set by setTabs-method of module
	 * multiModule only
	 *
	 * @var string
	 **/
	var $_path      = null; //submodule part of URL - set by setTabs-method of module
	/**
	 * part of URL inside submodule
	 * multiModule only
	 *
	 * @var string
	 **/
	var $_innerPath = null; 

	/**
	 * initilize module
	 *
	 * @param  string       directory under style/modules name
	 * @param  array        module configuration
	 * @param  bool         is in admin mode
	 * @param  array|null   tabs for multimodule
	 * @param  string|null  current tab
	 * @return void
	 **/
	function init($dirname, $conf, $admin=false, $tabs=null, $curTab=null) {
		$this->_conf   = $conf;
		$this->_dir    = 'modules'.DIRECTORY_SEPARATOR.$dirname.DIRECTORY_SEPARATOR;
		$this->_admin  = $admin;
		$this->_tabs   = $tabs;
		$this->_curTab = $curTab;
		$this->_te     = & elSingleton::getObj('elTE');
		if (!empty($tabs)) {
			$this->_path = $tabs[$curTab]['path'];
		}
	}

	/**
	 * set templates directory
	 *
	 * @param  string $dir  dir name
	 * @return void
	 **/
	function setDir($dir) {
		$this->_dir = $dir;
	}

	/**
	 * set default template
	 *
	 * @param  string $fileName   template file
	 * @return void
	 **/
	function setDefTpl($fileName) {
		$this->_defTpl = $fileName;
	}

	/**
	 * render template
	 *
	 * @param  array  $vars  data for template
	 * @param  string $h     template handler/name
	 * @param  string $block block to iterate
	 * @param  int    $l     block iteration level   
	 * @return void
	 **/
	function render($vars, $h=null, $block=null, $l=0) {
		$this->_setFile($h);
		if (is_array($vars)) {
			if ($block) {
				$this->_te->assignBlockFromArray($block, !empty($vars) ? $vars : array(' '), $l);
			} else {
				$this->_te->assignVars($vars);
			}
		}
	}

	/**
	 * add content on panel under title
	 *
	 * @param  string  $cont  content
	 * @param  bool    $new   on new panel?
	 * @param  string  $attr  panel attr
	 * @return void
	 **/
	function addOnPane($cont, $new=false, $attr=null) {
		$this->_paneCont[] = array('content'=>$cont, 'attr'=>$attr, 'isNew'=>$new);
	}

	/**
	 * render page title and actions menu 
	 *
	 * @param  array  $mMap  mapping for actions menu
	 * @return void
	 **/
	function renderComplite( $mMap ) {

		if (EL_WM == EL_WM_NORMAL) {
			if ( !$this->_tabs ) {
				$this->_te->setFile('PAGE_TITLE', 'common/pageTitle.html');
			} else {
				$this->_te->setFile('PAGE_TITLE', 'common/pageTitleTabs.html');
				$this->_te->assignVars(array(
					'SUBMOD_PATH' => $this->_path,
					'SUBMOD_URL'  => EL_URL.$this->_path,
					'POPUP_URL'   => EL_URL.EL_URL_POPUP.'/'.$this->_path
					));

				foreach($this->_tabs as $tab) {
					$tab['label'] = m($tab['label']);
					$this->_te->assignBlockVars($this->_path==$tab['path'] ? 'TABS.A_TAB' : 'TABS.TAB', $tab, 0);
				}
			}

			if (!empty($mMap)) {
				$this->_rndActionsMenu($mMap); 
			}
			if (!empty($this->_paneCont)) {
				foreach ($this->_paneCont as $one) {
					$this->_te->assignBlockVars('PANE.PANE_CONTENT', $one, $one['isNew'] ? 2 : 1);
				}
			}
		}

		// check because some module renderers dont use tpl files (forms, etc)
		if ($this->_te->isTplLoaded('PAGE')) {
			$this->_te->parseWithNested('PAGE', 'PAGE', true);
		}
	}

	/**
	 * add string to page content
	 *
	 * @param  string  $str
	 * @param  bool    $repl  replace existed content
	 * @return void
	 **/
	function addToContent($str, $repl=false) {
		if ($repl) {
			$str = str_replace( $this->_te->vars['name'], $this->_te->vars['value'], $str);
		}
		$this->_te->assignVars('PAGE', $str, true);
	}

	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//

	/**
	 * set template
	 *
	 * @param  string  $h  template handler
	 * @param  string  $t  target variable name
	 * @param  bool    $whiteSpace store whitespaces in template
	 * @return void
	 **/
	function _setFile($h='', $t='', $whiteSpace=false) {
		$tpl = !empty($this->_tpls[$h]) ? $this->_tpls[$h] : $this->_defTpl;
		$this->_te->setFile($t ? $t : 'PAGE', $this->_dir.$tpl, $whiteSpace );
	}

	/**
	 * render actions menu
	 *
	 * @param  array  $acts  actions data
	 * @return void
	 **/
	function _rndActionsMenu($acts) { 
		$html  = "<ul class='el-menu-float'>\n"; 
		$html .= '<li class="toplevel drag-helper"><a href="#" onclick="return false" class="drag-helper">&nbsp;</a></li>';
		foreach ($acts as $name=>$group) {
			if (sizeof($group) ==1) {
				$onclick = !empty($act['onClick']) ? ' onclick="'.$act['onClick'].'";return false;' : '';
				$html .= '<li class="toplevel"><a href="'.$group[0]['url'].'"'.$onclick.' class="'.$group[0]['ico'].'" title="'.m($group[0]['label']).'">&nbsp;</a></li>';
			} elseif (sizeof($group) > 1) {
				$html .= '<li class="toplevel"><a href="#" class="'.str_replace(' ', '_', $name).'"  onclick="return false;">&nbsp;</a>';

				$html .= '<ul>';
				foreach ($group as $act) {
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
		
	/**
	 * return conf param by name
	 *
	 * @param  string  $param
	 * @return mixed
	 **/
	function _conf($param) {
		return isset($this->_conf[$param]) ? $this->_conf[$param] : null;
	}

} // END class

?>