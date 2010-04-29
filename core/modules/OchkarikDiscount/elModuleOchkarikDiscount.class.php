<?php

/**
 * Ochkarik discount module, displays discount for card holder
 *
 * @package OchkarikDiscount
 * @version 1.0
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/

require_once './core/vendor/OchkarikDisount.class.php';

class elModuleOchkarikDiscount extends elModule
{
	var $_mMap = array('query' => array('m' => 'queryDiscount'));
	var $_mMapConf  = array();

	// allow 5 requests in 5 min
	var $flood_limit = 5;
	var $flood_time  = 300;

	function defaultMethod()
	{
		$this->_initRenderer();
		$this->_rnd->rndDefault();
	}

	function queryDiscount()
	{
		//sleep(1);
		$discount     = 0;
		$spent_amount = 0;

		if ($this->_arg(0))
		{
			$card_number = $this->_arg(0);
			$card_number = str_replace(' ', '', $card_number);
			$card_number = str_replace('-', '', $card_number);
			$card_number = str_replace('%20', '', $card_number);

			// check cache
			$elod = elSingleton::getObj('elOchkarikDiscount');
			$elod->idAttr($card_number);
			$cache = array();
			$where = 'update_time>"'.(time() - 86400).'" AND card_num="'.$card_number.'"';
			$cache = $elod->collection(false, false, $where, null, 0, 1);
			if (!empty($cache))
			{
				//echo 'cache hit! <br/>';
				$cache = array_shift($cache);
				//print_r($cache);
				$discount     = $cache['discount'];
				$spent_amount = $cache['spent_amount'];
				// update cache
				$elod->updateCache($card_number);
			}
			else
			{
				//echo 'not found in cache <br/>';

				// check flood
				$where = 'update_time>"'.(time() - $this->flood_time).'" AND query_ip="'.$_SERVER['REMOTE_ADDR'].'"';
				$cache = $elod->collection(false, false, $where);
				if (count($cache) >= $this->flood_limit)
				{
					echo $this->_errorText('Слишком много запросов с вашего IP-адреса, попробуйте позже');
					exit();
				}
				
				$od = new OchkarikDiscount;
				if ($od->check($card_number))
				{		
					$discount     = $od->discount;
					$spent_amount = $od->spent_amount;

					// update cache
					$elod->updateCache($card_number, $discount, $spent_amount);
				}
				else
				{
					echo $this->_errorText($od->error);
					exit();
				}
			}
		}
		else
		{
			echo $this->_errorText('Ошибка, попробуйте позже');
			exit();
		}
		
		// everythink is ok
		$this->_initRenderer();
		echo $this->_rnd->rndDiscount(array('discount' => $discount, 'spent_amount' => $spent_amount));
		exit();
	}
	
	function _errorText($text)
	{
		return '<span style="color: red;">'.m($text).'</span>';
	}
}

