<?php

/**
 * Display form to search on pages of ishop module
 *
 * @package plugins
 **/
class elPluginIShopFinder extends elPlugin {
	

	var $_posNfo = array(
		EL_POS_LEFT   => array('PLUGIN_SPECIAL_OFFER_LEFT',   'default.html'),
		EL_POS_RIGHT  => array('PLUGIN_SPECIAL_OFFER_RIGHT',  'default.html'),
		EL_POS_TOP    => array('PLUGIN_SPECIAL_OFFER_TOP',    'default.html'),
		EL_POS_BOTTOM => array('PLUGIN_SPECIAL_OFFER_BOTTOM', 'default.html')
	);
	
	/**
	 * render form
	 *
	 * @return void
	 **/
	function onUnload() {
		if (empty($this->_params)) {
			return;
		}
		$srcs = array_keys($this->_params);
		if (empty($srcs) || !elSingleton::incLib('./modules/IShop/elIShopFactory.class.php', true)) {
			return;
		}
		$rnd = & elSingleton::getObj('elTE');
		elPrintr($srcs);
		foreach ($srcs as $src) {
			// check currect page
			$pages = $this->_param($src, 'pages', array());
			if (!in_array('1', $pages) && !in_array($this->pageID, $pages)) {
				continue;
			}
			// get plugin position on the page
			$pos  = $this->_param($src, 'pos', EL_POS_LEFT); // position on main module page
			$nav  = &elSingleton::getObj('elNavigator');
			$args = $nav->getRequestArgs();
			
			if (!empty($args) && isset($args[0])) {
				if (in_array($args[0], array('item', 'read'))) { // on item page
					$pos = $this->_param($src, 'pos3', 0);
				}
				else { // somewhere in category or elsewhere
					$pos = $this->_param($src, 'pos2', 0);
				}
			}

			list($pos, $tplVar, $tpl) = $this->_getPosInfo($pos);

			if (!$pos) {
				continue;
			}
			$rnd->setFile($tplVar, $tpl);
			echo 'here';
			
		}
		
		
	}
	
	/**
	 * configure plugin
	 *
	 * @return void
	 **/
	function conf() {
		$srcs = $this->findSources('IShop');
		if (!$srcs) {
			elThrow(E_USER_ERROR, 'There are no one data source of required type was found!', null, EL_URL);
		}
		
		$this->_makeConfForm($srcs);
		if (!$this->form->isSubmitAndValid()) {
			$rnd = & elSingleton::getObj('elTE');
			$rnd->assignVars('PAGE', $this->form->toHtml());
		} else {
			$data = $this->form->getValue();
			$params = array();
			foreach ($srcs as $src)
			{
				if (!empty($data['src_'.$src]))
				{
					$params[$src] = array();
					$params[$src]['title'] = $data['title_'.$src];
					$params[$src]['pos']   = $data['pos_'.$src];
					$params[$src]['pos1']  = $data['pos1_'.$src];
					$params[$src]['pos2']  = $data['pos2_'.$src];
					$params[$src]['pos3']  = $data['pos3_'.$src];
					$params[$src]['pages'] = $data['pages_'.$src];
				}
			}
			$conf = & elSingleton::getObj('elXmlConf');
			$conf->dropGroup('plugin'.$this->name);
			$conf->makeGroup('plugin'.$this->name, $params);
			$conf->save();
			elMsgBox::put(m('Data saved'));
			elLocation(EL_URL);
		}
	}
	
	/**
	 * Create configure form
	 *
	 * @param  array $srcs  array of IShop pages ID
	 * @return void
	 **/
	function _makeConfForm($srcs) {
		$nav      = & elSingleton::getObj('elNavigator');
		$pages    = elGetNavTree('+');
		$pages[1] = m('Whole site');
		$swLabel  = m('Use this data source');
		$sort     = array(m('Random'), m('Last added'));

		$this->form = &elSingleton::getObj('elForm');
		$this->form->setRenderer(elSingleton::getObj('elTplFormRenderer'));
		$this->form->setLabel(m('Configure plugin'));
		$this->form->add(new elCData('c_', m('Please, select required data sources')));
		foreach ($srcs as $src)
		{
			$pageName = $nav->getPageName($src);
			$box = & new elExpandBox('src_'.$src, $pageName, array('swLabel'=>$swLabel));
			if ($this->_param($src))
			{
				$box->setAttr('checked', 'on');
			}
			$box->add(new elText( 'title_'.$src, m('Title'),                                $this->_param($src, 'title',              $pageName)));
			$box->add(new elSelect('pos_'.$src,  m('Default position'),                     $this->_param($src, 'pos',   EL_POS_TOP), $GLOBALS['posNLRTB']));
			$box->add(new elSelect('pos2_'.$src, m('Position on catalogs categories etc.'), $this->_param($src, 'pos2',  EL_POS_TOP), $GLOBALS['posNLRTB']));
			$box->add(new elSelect('pos3_'.$src, m('Position on catalogs items'),           $this->_param($src, 'pos3',  EL_POS_TOP), $GLOBALS['posNLRTB']));
			$ms = & new elMultiSelectList('pages_'.$src, m('Site pages'),                   $this->_param($src, 'pages', array(1)),   $pages);
			$ms->setSwitchValue(1);
			$box->add($ms);
			$this->form->add($box);
		}
	}
	
	
	/**
	 * Overloaded parents method
	 * Return $this->_params[$src][$param] if exists or default value
	 * If $param is not set - return array $this->_params[$src]
	 *
	 * @param  string  $src
	 * @param  string  $param
	 * @param  mixed   $defVal
	 * @return mixed
	 */
	function _param($src, $param = null, $defVal = null) {
		if (null == $param) {
			return isset($this->_params[$src]) ? $this->_params[$src] : null;
		}
		return isset($this->_params[$src][$param]) ? $this->_params[$src][$param] : $defVal;
	}
	
} // END class 

?>