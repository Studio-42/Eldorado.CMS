<?php

/**
 * @class elCronYandexMarket
 *
 * Generate yandex market yml file everyday
 **/
class elCronYandexMarket
{
	function run()
	{
		if (
			!elSingleton::incLib('./modules/IShop/elIShopFactory.class.php', true) ||
			!elSingleton::incLib('./modules/IShop/elModuleIShop.class.php')
		)
		{
			return;
		}

		$nav = & elSingleton::getObj('elNavigator');
		// $nav->_load();
		foreach ($nav->findByModule('IShop') as $id)
		{
			$ishop = & elSingleton::getObj('elModuleIShop');
			$ishop->init($id, array(), 'IShop');
			$ishop->yandexMarket();
		}

		return true;
	}
}

// include_once dirname(__FILE__).'/../console.php';
// $o = new elCronYandexMarket;
// $o->run();

