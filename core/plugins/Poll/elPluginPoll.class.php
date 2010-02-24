<?php
//ver 2.0
include_once EL_DIR_CORE.'lib/elPlugin.class.php';

class elPluginPoll extends elPlugin
{
	var $_posNfo = array(
		EL_POS_LEFT   => array('PLUGIN_POLL_TOPICS_LEFT',   'left.html'),
		EL_POS_RIGHT  => array('PLUGIN_POLL_TOPICS_RIGHT',  'right.html'),
		//EL_POS_TOP    => array('PLUGIN_POLL_TOPICS_TOP',    'top.html'),
		//EL_POS_BOTTOM => array('PLUGIN_POLL_TOPICS_BOTTOM', 'bottom.html')
		);

	/**
	 * Display news topics
	 *
	 */
	function onUnload()
	{
		if ( empty($this->_params) )
		{
			return;
		}
		$srcs = array_keys($this->_params); 
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
			// drop source from conf if table is not exists

			if (!$db->isTableExists('el_poll_'.$src))
			{
				//$this->_dropSrc($src); 
				continue;
			}
			if ( !elSingleton::incLib('modules/Poll/elPollsFactory.class.php', true) )
			{
				return;
			}
			
			$factory = & elSingleton::getObj('elPollsFactory', $src);
			$polls   = $factory->getActive(false, (int)$this->_param($src, 'num', 0));
			if ( empty($polls) )
			{
				continue;
			}
			
			list($pos, $tplVar, $tpl) = $this->_getPosInfo($this->_param($src, 'pos', EL_POS_LEFT)); 
			$rnd->setFile($tplVar, $tpl);
			$srcPage = $nav->getPage( $src );  
			if ( false != ($title = $this->_param($src, 'title') ) )
			{
				$rnd->assignBlockVars('PL_POLL_TITLE', array('title'=>$title, 'url'=>$srcPage['url']));
			}
			
			foreach ($polls as $poll )
			{
				
				$data = array('name'    => $poll->name,
							  'descrip' => $this->_param($src, 'descrip') ? nl2br($poll->descrip) : '',
							  ); 
				$rnd->assignBlockVars('PL_POLL', $data);
				if ( $poll->voted )
				{
					$block = 'PL_POLL.PL_VPOLL.PL_VPOLL_VAR';	
				}
				else
				{
					$block = 'PL_POLL.PL_APOLL.PL_APOLL_VAR';
					$rnd->assignBlockVars('PL_POLL.PL_APOLL', array('id'=>$poll->ID, 'url' => $srcPage['url']), 1);
				}
				
				foreach ( $poll->variants as $v )
				{
					$data = array();
					$rnd->assignBlockVars($block, $v, 2);
					if ( $poll->voted )// && $v['prc'] > 0 )
					{
						$rnd->assignBlockVars($block.'.PL_VPOLL_BAR', array('prc'=>$v['prc']), 3 );
					}
				}
			}
			$rnd->parse($tplVar, $tplVar, true, false, true);
			$GLOBALS['parseColumns'][$pos] = true;
		}


	}

	/**
	 * Configure plugin - set data sources (polls pages)
	 *
	 */
	function conf()
	{
		$srcs = $this->findSources('Poll');
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
					$params[$src]['title']   = $data['title_'.$src];
					$params[$src]['num']     = $data['num_'.$src];
					$params[$src]['view']    = $data['view_'.$src];
					$params[$src]['date']    = $data['date_'.$src];
					$params[$src]['pos']     = $data['pos_'.$src];
					$params[$src]['pages']   = $data['pages_'.$src];
					$params[$src]['descrip'] = $data['descrip_'.$src];
				}
			}
			$conf = & elSingleton::getObj('elXmlConf');
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

		$nav = & elSingleton::getObj('elNavigator');
		$nums = range(0, 20);
		unset($nums[0]);
		$views = array( m('Only topics'), m('Topics and announces'));
		$pages = elGetNavTree('+');
		$pages[1] = m('Whole site');
		$swLabel = m('Use this data source');
		foreach ($srcs as $src)
		{
			$pageName = $nav->getPageName($src);
			$box = & new elExpandBox('src_'.$src, $pageName, array('swLabel'=>$swLabel));
			if ($this->_param($src))
			{
				$box->setAttr('checked', 'on');
			}
			$box->add( new elText('title_'.$src, m('Title'), $this->_param($src, 'title', $pageName)) );
			$box->add( new elSelect('pos_'.$src,   m('Position on page'), $this->_param($src, 'pos', EL_POS_LEFT), $GLOBALS['posLR']) );
			$box->add( new elSelect('num_'.$src,   m('Number of polls to display'), $this->_param($src, 'num', 0), array(m('All'), 1=>1, 2=>2, 3=>3, 4=>4)) );
			$box->add( new elSelect('descrip_'.$src, m('Display polls descriptions'), $this->_param($src, 'descrip', 0), $GLOBALS['yn']) );
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

}

?>