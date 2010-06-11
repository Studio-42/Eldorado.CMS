<?php

class elPluginSpecialOffer extends elPlugin
{
	var $_posNfo = array(
		EL_POS_LEFT   => array('PLUGIN_SPECIAL_OFFER_LEFT',   'left-right.html'),
		EL_POS_RIGHT  => array('PLUGIN_SPECIAL_OFFER_RIGHT',  'left-right.html'),
		EL_POS_TOP    => array('PLUGIN_SPECIAL_OFFER_TOP',    'top-bottom.html'),
		EL_POS_BOTTOM => array('PLUGIN_SPECIAL_OFFER_BOTTOM', 'top-bottom.html')
	);

	function onUnload()
	{
		if (empty($this->_params))
		{
			return;
		}
		$srcs = array_keys($this->_params);
		if (empty($srcs))
		{
			return;
		}

		if (
			!elSingleton::incLib('./modules/IShop/elIShopFactory.class.php', true) ||
			!elSingleton::incLib('./modules/IShop/elModuleIShop.class.php')
		)
		{
			return;
		}

		$rnd = & elSingleton::getObj('elTE');
		foreach ($srcs as $src)
		{
			// check currect page
			$pages = $this->_param($src, 'pages', array());
			if (!in_array('1', $pages) && !in_array($this->pageID, $pages))
			{
				continue;
			}

			// check renderer
			$mypos = $this->_param($src, 'pos', EL_POS_LEFT);
			if ($this->pageID == $src) // HACK to change position in IShop item view
			{
				if (strstr($_SERVER['REQUEST_URI'], '/item/')) // not 100% true
				{
					$mypos = $this->_param($src, 'pos2', 0);
					if ($mypos === '0')
					{
						continue; // next if set to none
					}
				}
			}
			list($pos, $tplVar, $tpl) = $this->_getPosInfo($mypos);
			if (!$pos)
			{
				continue;
			}
			$rnd->setFile($tplVar, $tpl);

			// set title
			if (false != ($title = $this->_param($src, 'title', false)))
			{
				$rnd->assignBlockVars('PL_SO_TITLE', array('title'=>$title));
			}

			// load currency settings
			$conf = & elSingleton::getObj('elXmlConf');
			$ishop_conf = $conf->getGroup($src);

			$currency = &elSingleton::getObj('elCurrency');
			$currency_opts = array(
				'precision'   => $ishop_conf['pricePrec'],
				'currency'    => $ishop_conf['currency'],
				'exchangeSrc' => $ishop_conf['exchangeSrc'],
				'commision'   => $ishop_conf['commision'],
				'rate'        => $ishop_conf['rate'],
				'format'      => true,
				'symbol'      => 1
			);

			// get special offers from IShop
			$sort = ($this->_param($src, 'sort', 0) == '0' ? EL_IS_SORT_RAND : EL_IS_SORT_TIME);
			$factory = & elSingleton::getObj('elIShopFactory');
			$factory->init($src, 0);
			$ic = & elSingleton::getObj('elIShopItemsCollection');
			$items = $ic->create('special', 1, 0, $this->_param($src, 'num', 1), $sort);
			foreach ($items as $id => $i)
			{
				$mnf  = $i->getMnf();
				$tm   = $i->getTm();
				$props = '';
				foreach ($i->getAnnouncedProperties() as $p)
				{
					$props .= '<br/>'.$p['name'].': '.$p['value']; // TODO move this to renderer
				}

				$item = array(
					'name'  => $i->name,
					'code'  => $i->code,
					'price'	=> $currency->convert($i->price, $currency_opts),
					'mnf'   => $mnf->name,
					'tm'    => $tm->name,
					'img'   => $i->getDefaultTmb(),
					'props' => $props
				);

				$b = 'PL_SO.PL_SO_ITEM';
				$rnd->assignBlockVars($b, $item, 1);

				if (!empty($item['img']))
				{
					$b = 'PL_SO.PL_SO_ITEM.PL_SO_ITEM_IMG';
					$rnd->assignBlockVars($b, $item, 2);
				}
			}
			$rnd->parse($tplVar, $tplVar, true, false, true);
			$GLOBALS['parseColumns'][$pos] = true;
		}
	}

	/**
	 * Configuration - set data source and choose where to display
	 **/
	function conf()
	{
		$srcs = $this->findSources('IShop');
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
					$params[$src]['sort']  = $data['sort_'.$src];
					$params[$src]['pos']   = $data['pos_'.$src];
					$params[$src]['pos2']  = $data['pos2_'.$src];
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
	 * @param array $srcs  array of IShop pages ID
	 */
	function _makeConfForm($srcs)
	{
		$nav      = & elSingleton::getObj('elNavigator');
		$nums     = range(0, 20);
		unset($nums[0]);
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
			$box->add(new elText( 'title_'.$src, m('Title'),                       $this->_param($src, 'title', $pageName)));
			$box->add(new elSelect( 'num_'.$src, m('How many offers to show'),     $this->_param($src, 'num',   1), $nums));
			$box->add(new elSelect('sort_'.$src, m('How to sort offers'),          $this->_param($src, 'sort',  0), $sort));
			$box->add(new elSelect( 'pos_'.$src, m('Position on page'),            $this->_param($src, 'pos',   EL_POS_TOP), $GLOBALS['posLRTB']));
			$box->add(new elSelect('pos2_'.$src, m('Position on IShop item page'), $this->_param($src, 'pos2',  EL_POS_TOP), $GLOBALS['posNLRTB']));
			$ms = & new elMultiSelectList('pages_'.$src, m('Site pages'),          $this->_param($src, 'pages', array(1)), $pages);
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
	function _param($src, $param = null, $defVal = null)
	{
		if (null == $param)
		{
			return isset($this->_params[$src]) ? $this->_params[$src] : null;
		}
		return isset($this->_params[$src][$param]) ? $this->_params[$src][$param] : $defVal;
	}

}
