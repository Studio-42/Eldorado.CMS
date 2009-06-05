<?php
//ver 2.0
include_once EL_DIR_CORE.'lib/elPlugin.class.php';

class elPluginNewsTopics extends elPlugin
{
	var $_posNfo = array(
		EL_POS_LEFT   => array('PLUGIN_NEWS_TOPICS_LEFT',   'left.html'),
		EL_POS_RIGHT  => array('PLUGIN_NEWS_TOPICS_RIGHT',  'right.html'),
		EL_POS_TOP    => array('PLUGIN_NEWS_TOPICS_TOP',    'top.html' ),
		EL_POS_BOTTOM => array('PLUGIN_NEWS_TOPICS_BOTTOM', 'top.html' )
		);

	/**
	 * Display news topics
	 *
	 */
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
			//elPrintR($this->_params);
			$pages = $this->_param($src, 'pages', array());
			//check currect page
			if ( !in_array('1', $pages) && !in_array($this->pageID, $pages))
			{
				continue;
			}

			$tb  = 'el_news_'.$src;
			// drop source from conf if table is not exists
			if (!$db->isTableExists($tb))
			{
				$this->_dropSrc($src);
				continue;
			}

			$ann = $this->_param($src, 'view', '0')
				? 'announce, '
				: '';
			$date = $this->_param($src, 'date', '')
				? 'DATE_FORMAT( FROM_UNIXTIME(published+10), "'.EL_MYSQL_DATE_FORMAT.'") AS pubDate, '
				: '';
			$num = (int)$this->_param($src, 'num', 1);
			$sql = 'SELECT '.$ann.$date.'id, title FROM '.$tb.' ORDER BY published DESC LIMIT 0, '.$num;

			if (!$db->query($sql))
			{
				$this->_dropSrc($src);
				continue;
			}

			list($pos, $tplVar, $tpl) = $this->_getPosInfo($this->_param($src, 'pos', EL_POS_LEFT), $this->_param($src, 'tpl'));
			if (!$pos)
			{
				continue;
			}
			$srcPage = $nav->getPage( $src );
			$URL     = $srcPage['url'].'read/1/';

			$rnd->setFile($tplVar, $tpl);

			if (false != ($title = $this->_param($src, 'title', false)))
			{
				$rnd->assignBlockVars('PL_NT_TITLE', array('title'=>$title));
			}

			while ($r = $db->nextRecord() )
			{
				$data = array('id'=>$r['id'], 'title'=>$r['title'], 'newsURL'=>$URL.$r['id'].'/');
				$rnd->assignBlockVars('PL_NT_NEWS', $data);
				if ($date)
				{
					$rnd->assignBlockVars('PL_NT_NEWS.PL_NT_DATE', array('pubDate'=>$r['pubDate']), 1 );
				}
				if ($ann)
				{
					$rnd->assignBlockVars('PL_NT_NEWS.PL_NT_ANN', array('announce'=>$r['announce']), 1 );
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
		$conf = & elSingleton::getObj('elXmlConf'); //echo 'here';
		$srcs = $conf->findGroup('module', 'News', true);
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
				{
					$params[$src] = array();
					$params[$src]['title'] = $data['title_'.$src];
					$params[$src]['num']   = $data['num_'.$src];
					$params[$src]['view']  = $data['view_'.$src];
					$params[$src]['date']  = $data['date_'.$src];
					$params[$src]['pos']   = $data['pos_'.$src];
					$params[$src]['pages'] = $data['pages_'.$src];
					$params[$src]['tpl']   = $data['tpl_'.$src];
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
		$views    = array( m('Only topics'), m('Topics and announces'));
		$pages    = elGetNavTree('+');
		$pages[1] = m('Whole site');
		$swLabel  = m('Use this data source');
		$tList    = $this->_getAltTpls();
		foreach ($srcs as $src)
		{
			$pageName = $nav->getPageName($src);
			$box = & new elExpandBox('src_'.$src, $pageName, array('swLabel'=>$swLabel));
			if ($this->_param($src))
			{
				$box->setAttr('checked', 'on');
			}
			$box->add( new elText('title_'.$src,   m('Title'),                 $this->_param($src, 'title', $pageName)) );
			$box->add( new elSelect('num_'.$src,   m('How many news display'), $this->_param($src, 'num', 1), $nums) );
			$box->add( new elSelect('view_'.$src,  m('How to display news'),   $this->_param($src, 'view', 0), $views ) );
			$box->add( new elSelect('date_'.$src,  m('Display news date'),     $this->_param($src, 'date', 0), $GLOBALS['yn'] ) );
			$box->add( new elSelect('pos_'.$src,   m('Position on page'),      $this->_param($src, 'pos', EL_POS_LEFT), $GLOBALS['posLRTB']) );

		  if ( !empty($tList))
		  {
		    $box->add( new elSelect('tpl_'.$src, m('Use alternative template'), $this->_param($src, 'tpl'), $tList) );
		  }
			$p =  &new elMultiSelectList('pages_'.$src, m('Pages'), $this->_param($src, 'pages', array(1)),  elGetNavTree('+', m('Whole site')) );
		  $p->setSwitchValue(1);
		  $box->add( $p );
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
			return !empty($this->_params[$src]) ? $this->_params[$src] : null;
		}
		return !empty($this->_params[$src][$param]) ? $this->_params[$src][$param] : $defVal;
	}

	function _dropSrc($src)
	{
		$conf = & elSingleton::getObj('elXmlConf');
		unset($this->_params[$src]);
		$conf->dropGroup('plugin'.$this->name);
		$conf->makeGroup('plugin'.$this->name, $this->_params);
		$conf->save();
	}

	function _getAltTpls()
	{
	  $tList = glob(EL_DIR.'style/plugins/NewsTopics/*.html');
	  $exclude = array( 'top.html', 'left.html', 'right.html');
	  $ret = array(''=>m('No'));
	  for ($i=0, $s=sizeof($tList); $i<$s; $i++)
	  {
	    $tpl = basename($tList[$i]);
	    if (!in_array( $tpl, $exclude ))
	    {
	      $ret[$tpl] = $tpl;
	    }
	  }
    return sizeof($ret) >1 ? $ret : null;
	}
}

?>