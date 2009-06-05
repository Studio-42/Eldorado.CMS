<?php
// ver 2.0
include_once EL_DIR_CORE.'lib/elPlugin.class.php';

if ( !defined('EL_IG_DISPL_POPUP') )
{
	define('EL_IG_DISPL_POPUP', 0);
}
if (!defined('EL_IG_DISPL_LIGHTBOX') )
{
	define('EL_IG_DISPL_LIGHTBOX', 1);
}

class elPluginRandomImage extends elPlugin
{
	var $_posNfo = array(
		EL_POS_LEFT   => array('PLUGIN_RAND_IMG_LEFT',   'left.html'),
		EL_POS_RIGHT  => array('PLUGIN_RAND_IMG_RIGHT',  'right.html'),
		EL_POS_TOP    => array('PLUGIN_RAND_IMG_TOP',    'top.html'),
		EL_POS_BOTTOM => array('PLUGIN_RAND_IMG_BOTTOM', 'top.html')
		);



	function onUnload()
	{
		if (empty($this->_params))
		{
			return;
		}
		$srcs = array_keys($this->_params);
		if ( empty($srcs) )
		{
			return;
		}

		$db  = & elSingleton::getObj('elDb');
		$rnd = & elSingleton::getObj('elTE');
		$nav = & elSingleton::getObj('elNavigator');

		foreach ($srcs as $src)
		{
			$pages = $this->_param($src, 'pages', array());
			//check currect page
			if ( !in_array('1', $pages) && !in_array($this->pageID, $pages))
			{
				continue;
			}

			$tb  = 'el_ig_'.$src.'_image';
			// drop source from conf if table is not exists
			if (!$db->isTableExists($tb))
			{
				$this->_dropSrc($src);
				continue;
			}

			$name = $this->_param($src, 'name', 0);
			$sort = $this->_param($src, 'sort', 0);
			$num  = $this->_param($src, 'num', 1);
			$sql  = 'SELECT i_id, i_gal_id, i_file, i_name, i_width_0, i_height_0, i_width_tmb, i_height_tmb '
					.'FROM '.$tb.(0==$sort ? ' ORDER BY RAND() ' : ' ORDER BY i_crtime DESC ')
					.'LIMIT 0, '.$num;

			if (!$db->query($sql))
			{
				$this->_dropSrc($src);
				continue;
			}
			list($pos, $tplVar, $tpl) = $this->_getPosInfo($this->_param($src, 'pos', EL_POS_LEFT));
			if (!$pos)
			{
				continue;
			}
			$rnd->setFile($tplVar, $tpl);
			$rnd->assignVars('srcID', $src);
			$view    = $this->_param($src, 'view', EL_IG_DISPL_LIGHTBOX);
			$imgName = $this->_param($src, 'name', 0);



			if (false != ($title = $this->_param($src, 'title', false)))
			{
				$rnd->assignBlockVars('PL_RI_TITLE', array('title'=>$title));
			}
			if (false != ($link = $this->_param($src, 'url', false)))
			{
				$srcPage = $nav->getPage( $src );
				$rnd->assignBlockVars('PL_RI_LINK', array('url'=>$srcPage['url'], 'link'=>$link));
			}
			if ( EL_IG_DISPL_LIGHTBOX == $view )
			{
				elAddJs('jquery.js', EL_JS_CSS_FILE);
				elAddJs('jquery.metadata.js', EL_JS_CSS_FILE);
				elAddJs('jquery.fancybox.js', EL_JS_CSS_FILE);
				elAddCss('fancybox.css');
				if ( $rnd->isBlockExists('PL_RI_SCRIPT') )
				{
					$rnd->assignBlockVars('PL_RI_SCRIPT');
				}
			}
			while ($r = $db->nextRecord() )
			{
				if ( EL_IG_DISPL_LIGHTBOX == $view )
				{
					$block  = 'PL_RI.PL_RI_LB';
					$nBlock = 'PL_RI.PL_RI_LB.PL_RI_LB_NAME';
				}
				else
				{
					$block  = 'PL_RI.PL_RI_POPUP';
					$nBlock = 'PL_RI.PL_RI_POPUP.PL_RI_POPUP_NAME';
				}

				$rnd->assignBlockVars($block, $r, 1);
				if ($imgName && !empty($r['i_name']))
				{
					$rnd->assignBlockVars($nBlock, array('i_name'=>$r['i_name']), 2);
				}
			}

			$rnd->parse($tplVar, $tplVar, true, false, true);
			$GLOBALS['parseColumns'][$pos] = true;
		}
	}

	/**
	 * Configure plugin - set data sources (news pages)
	 *
	 */
	function conf()
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$srcs = $conf->findGroup('module', 'ImageGalleries', true);
		if (!$srcs)
		{
			elThrow(E_USER_ERROR, 'There are no one data source of required type was found!', null, EL_URL);
		}
		$this->_makeConfForm($srcs);

		if (!$this->form->isSubmitAndValid())
		{
			$rnd = & elSingleton::getObj('elTE');
			$rnd->assignVars('PAGE', $this->form->toHtml());
		}
		else
		{
			$data = $this->form->getValue();
			$params = array();
			foreach ($srcs as $src)
			{
				if (!empty($data['src_'.$src]))
				{//elPrintR($data); exit;
					$params[$src] = array();
					$params[$src]['title'] = $data['title_'.$src];
					$params[$src]['url']   = $data['url_'.$src];
					$params[$src]['num']   = $data['num_'.$src];
					$params[$src]['sort']  = $data['sort_'.$src];
					$params[$src]['name']  = $data['name_'.$src];
					$params[$src]['view']  = (int)$data['view_'.$src];
					$params[$src]['pos']   = $data['pos_'.$src];
					$params[$src]['pages'] = $data['pages_'.$src];
					//elPrintR($params);
				}
			}
			$conf->dropGroup('plugin'.$this->name);
			$conf->makeGroup('plugin'.$this->name, $params);
			$conf->save();
			elMsgBox::put( m('Data saved') );
			elLocation( EL_URL );
		}
	}

	/**
	 * Create configure form
	 *
	 * @param array $srcs  array of news pages ID
	 */
	function _makeConfForm($srcs)
	{
		$this->form = &elSingleton::getObj('elForm');
		$this->form->setRenderer( elSingleton::getObj('elTplFormRenderer'));
		$this->form->setLabel( m('Configure plugin') );
		$this->form->add( new elCData('c_', m('Please, select required data sources') ) );

		$nav      = & elSingleton::getObj('elNavigator');
		$nums     = range(0, 20);
		unset($nums[0]);
		$pages    = elGetNavTree('+');
		$pages[1] = m('Whole site');
		$swLabel  = m('Use this data source');
		$sort     = array( m('Random images'), m('Last added images') );
		$view     = array(
			EL_IG_DISPL_POPUP    => m('Popup window'),
			EL_IG_DISPL_LIGHTBOX => m('LightBox'));

		foreach ($srcs as $src)
		{
			$pageName = $nav->getPageName($src);
			$box = & new elExpandBox('src_'.$src, $pageName, array('swLabel'=>$swLabel));
			if ($this->_param($src))
			{
				$box->setAttr('checked', 'on');
			}
			$box->add( new elText('title_'.$src, m('Title'), $this->_param($src, 'title', $pageName)) );
			$box->add( new elText('url_'.$src, m('Gallery page URL text'), $this->_param($src, 'url', '')) );
			$box->add( new elSelect('num_'.$src,   m('How many images display'), $this->_param($src, 'num', 1), $nums) );
			$box->add( new elSelect('sort_'.$src,   m('Which images display'), $this->_param($src, 'sort', 0), $sort) );
			$box->add( new elSelect('name_'.$src,  m('Display images name'),   $this->_param($src, 'name', 0), $GLOBALS['yn'] ) );
			$box->add( new elSelect('view_'.$src,  m('Display full sized images using'),   $this->_param($src, 'view', EL_IG_DISPL_LIGHTBOX), $view ) );
			$box->add( new elSelect('pos_'.$src,   m('Position on page'), $this->_param($src, 'pos', EL_POS_LEFT), $GLOBALS['posLRTB']) );
			$box->add( new elMultiSelectList('pages_'.$src, m('Site pages'), $this->_param($src, 'pages', array(1)), $pages) );

			$this->form->add( $box );
		}

	}

	/**
	 * Overloaded parents method
	 * Return $this->_params[$src][$param] if exists or default value
	 * If $param is not set - return array $this->_params[$src]
	 *
	 * @param string $src
	 * @param string $param
	 * @param mixed $defVal
	 * @return mixed
	 */
	function _param($src, $param=null, $defVal=null)
	{
		if ( null == $param)
		{
			return isset($this->_params[$src]) ? $this->_params[$src] : null;
		}
		return isset($this->_params[$src][$param]) ? $this->_params[$src][$param] : $defVal;
	}

	function _dropSrc($src)
	{
		$conf = & elSingleton::getObj('elXmlConf');
		unset($this->_params[$src]);
		$conf->dropGroup('plugin'.$this->name);
		$conf->makeGroup('plugin'.$this->name, $this->_params);
		$conf->save();
	}

}

?>