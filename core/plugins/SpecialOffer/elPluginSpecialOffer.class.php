<?php

class elPluginSpecialOffer extends elPlugin
{
	var $_posNfo = array();
	var $_variants = array(
		'so' => 'Special Offer',
		'bs' => 'Best Sellers',
		'na' => 'New Arrivals'
	);

	function onUnload()
	{
		if (empty($this->_params)) {
			return;
		}
		$srcs = array_keys($this->_params);
		if (empty($srcs)) {
			return;
		}

		if (!elSingleton::incLib('./modules/IShop/elIShopFactory.class.php', true)
		|| 	!elSingleton::incLib('./modules/IShop/elModuleIShop.class.php')) {
			return;
		}

		elAddCss('elslider.css');
		elAddCss('modules/IShop.css');
		elAddJs('jquery.elslider.js', EL_JS_CSS_FILE);

		$rnd = & elSingleton::getObj('elTE');
		foreach ($srcs as $src)
		{
			list($sid, $variant) = explode('_', $src, 2);

			// define templates
			$this->_posNfo = array(
				EL_POS_LEFT   => array('PLUGIN_SPECIAL_OFFER_LEFT',   $variant.'-left-right.html'),
				EL_POS_RIGHT  => array('PLUGIN_SPECIAL_OFFER_RIGHT',  $variant.'-left-right.html'),
				EL_POS_TOP    => array('PLUGIN_SPECIAL_OFFER_TOP',    $variant.'-top-bottom.html'),
				EL_POS_BOTTOM => array('PLUGIN_SPECIAL_OFFER_BOTTOM', $variant.'-top-bottom.html')
			);

			// check currect page
			$pages = $this->_param($src, 'pages', array());
			if (!in_array('1', $pages) && !in_array($this->pageID, $pages)) {
				continue;
			}

			// get plugin position on the page
			$nav = &elSingleton::getObj('elNavigator');
			$args = $nav->getRequestArgs();
			$mypos = $this->_param($src, 'pos', EL_POS_LEFT); // position on main module page
			if (!empty($args) && isset($args[0]))
			{
				if (in_array($args[0], array('item', 'read'))) // on item page
				{
					$mypos = $this->_param($src, 'pos3', 0);
				}
				else // somewhere in category or elsewhere
				{
					$mypos = $this->_param($src, 'pos2', 0);
				}
			}

			if ($mypos == '0') { // if position is 'off'
				continue;
			}

			list($pos, $tplVar, $tpl) = $this->_getPosInfo($mypos);

			if (!$pos) {
				continue;
			}

			$rnd->setFile($tplVar, $tpl);
			switch ($pos) {
				case EL_POS_TOP:   $cssClass = 'pl-so-top'; break;
				case EL_POS_BOTTOM:$cssClass = 'pl-so-bottom'; break;
				case EL_POS_RIGHT: $cssClass = 'pl-so-right'; break;
				default:           $cssClass = 'pl-so-left';
			}
			$rnd->assignVars('plSoCssClass', $cssClass);
			// set title
			if (false != ($title = $this->_param($src, 'title', false))) {
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
			$shop = & elSingleton::getObj('elModuleIShop');
			$shop->init($sid, array(), 'IShop');
			$items = array();
			switch ($variant)
			{
				case 'so':
					$items = $shop->_factory->ic->create('special', 1, 0, $this->_param($src, 'num', 1), $sort);
					break;

				case 'na':
					$item  = $shop->_factory->create(EL_IS_ITEM);
					$items = $item->collection(true, true, null, 'crtime DESC', 0, $this->_param($src, 'num', 1));
					unset($item);
					break;

				case 'bs':
					if (!elSingleton::incLib('./modules/OrderHistory/elOrderItem.class.php'))
					{
						return;
					}
					$oi = & elSingleton::getObj('elOrderItem');
					$ids = $oi->bestSellers($sid, $this->_param($src, 'num', 1), $this->_param($src, 'period', 30));
					$items_unsorted = $shop->_factory->ic->create('search', $ids, 0, 0, 0);
					foreach ($ids as $id)
					{
						if (array_key_exists($id, $items_unsorted))
						{
							$items[$id] = $items_unsorted[$id];
						}
					}
					unset($oi);
					unset($items_unsorted);
					break;
			}

			if (empty($items))
			{
				continue;
			}

			foreach ($items as $id => $i)
			{
				$mnf  = $i->getMnf();
				$tm   = $i->getTm();
				$item = array(
					'name'  => $i->name,
					'code'  => $i->code,
					'price'	=> $currency->convert($i->price, $currency_opts),
					'mnf'   => $mnf->name,
					'tm'    => $tm->name,
					'link'  => $shop->getItemUrl($i->ID)
				);

				$rnd->assignBlockVars('PL_SO.PL_SO_ITEM', $item, 1);

				$g = $i->getGallery();
				if ($g) {
					$img = $i->getTmbPath(key($g));
					if (file_exists($img) && false != ($s = @getimagesize($img))) {
						$data = array(
							'img' => $i->getTmbURL(key($g)),
							'w'   => $s[0],
							'h'   => $s[1],
							'alt' => htmlspecialchars($i->name)
							);
						$rnd->assignBlockVars('PL_SO.PL_SO_ITEM.PL_SO_ITEM_IMG', $data, 2);
					}
				}
				
				foreach ($i->getAnnouncedProperties() as $p) {
					$rnd->assignBlockVars('PL_SO.PL_SO_ITEM.PROPS.PROP', $p, 3);
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
					$params[$src]['title']  = $data['title_' .$src];
					$params[$src]['num']    = $data['num_'   .$src];
					$params[$src]['sort']   = $data['sort_'  .$src];
					$params[$src]['period'] = $data['period_'.$src];
					$params[$src]['pos']    = $data['pos_'   .$src];
					$params[$src]['pos2']   = $data['pos2_'  .$src];
					$params[$src]['pos3']   = $data['pos3_'  .$src];
					$params[$src]['pages']  = $data['pages_' .$src];
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
		$period   = array(
			7  => m('Week'),
			30 => m('Month'),
			90 => m('3 month'),
			0  => m('All time')
		);
		$this->form = &elSingleton::getObj('elForm');
		$this->form->setRenderer(elSingleton::getObj('elTplFormRenderer'));
		$this->form->setLabel(m('Configure plugin'));
		$this->form->add(new elCData('c_', m('Please, select required data sources')));

		foreach ($srcs as $src)
		{
			list($sid, $variant) = explode('_', $src, 2);
			$pageName = $nav->getPageName($sid).': '.m($this->_variants[$variant]);
			$box = & new elExpandBox('src_'.$src, $pageName, array('swLabel'=>$swLabel));
			if ($this->_param($src))
			{
				$box->setAttr('checked', 'on');
			}
			$box->add(new elText( 'title_'.$src, m('Title'),                       $this->_param($src, 'title', $pageName)));
			$box->add(new elSelect( 'num_'.$src, m('How many offers to show'),     $this->_param($src, 'num',   1), $nums));
			if ($variant == 'so')
			{
				$box->add(new elSelect('sort_'.$src, m('How to sort offers'),      $this->_param($src, 'sort',  0), $sort));
			}
			elseif ($variant == 'bs')
			{
				$box->add(new elSelect('period_'.$src, m('Best for period'),       $this->_param($src, 'period',  0), $period));
			}
			$box->add(new elSelect( 'pos_'.$src, m('Position on module main page'),$this->_param($src, 'pos',   EL_POS_TOP), $GLOBALS['posNLRTB']));
			$box->add(new elSelect('pos2_'.$src, m('Position on module category'), $this->_param($src, 'pos2',  EL_POS_TOP), $GLOBALS['posNLRTB']));
			$box->add(new elSelect('pos3_'.$src, m('Position on module item'),     $this->_param($src, 'pos3',  EL_POS_TOP), $GLOBALS['posNLRTB']));
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

	/**
	 * Find sources but use multi source in each IShop
	 *
	 * @param  string  $module
	 * @return array   of multi sources
	 **/
	function findSources($module)
	{
		$srcs = array();
		foreach (parent::findSources($module) as $src)
		{
			foreach ($this->_variants as $k => $v)
			{
				array_push($srcs, $src.'_'.$k);
			}
		}
		return $srcs;
	}
}
