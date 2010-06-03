<?php

class elCurrency {
	
	var $info = array(
		'USD' => array(
			'intCode'   => 'USD',
			'name'      => 'USA dollar',
			'symbol'    => '$',
			'point'     => '.',
			'separator' => ',',
			'cbrCode'   => 'R01235'
			),
		'EUR' => array(
			'intCode'   => 'EUR',
			'name'      => 'Euro',
			'symbol'    => '€',
			'point'     => ',',
			'separator' => '.',
			'cbrCode'   => 'R01239'
			),
		'RUR' => array(
			'intCode'   => 'RUR',
			'name'      => 'Russian Ruble',
			'symbol'    => 'руб.',
			'point'     => ',',
			'separator' => ' ',
			'cbrCode'   => ''
			),
		'UAH' => array(
			'intCode'   => 'UAH',
			'name'      => 'Ukrainian Hryvnia',
			'symbol'    => 'грн.',
			'point'     => ',',
			'separator' => '.',
			'cbrCode'   => 'R01720'
			),
		'BYR' => array(
			'intCode'   => 'BYR',
			'name'      => 'Belarussian Ruble',
			'symbol'    => 'руб.',
			'point'     => ',',
			'separator' => '.',
			'cbrCode'   => 'R01090'
			),	
		'LVL' => array(
			'intCode'   => 'LVL',
			'name'      => 'Latvian Lat',
			'symbol'    => 'Ls',
			'point'     => ',',
			'separator' => '.',
			'cbrCode'   => 'R01405'
			),
		'LTL' => array(
			'intCode'   => 'LTL',
			'name'      => 'Lithuanian Lita',
			'symbol'    => 'Lt',
			'point'     => ',',
			'separator' => '.',
			'cbrCode'   => 'R01435'
			)
		);
	
	var $current = array();
	
	var $_rates = null;
	
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	var $_updateType = 'auto';
	
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	var $_updateTime = 0;
	
	function elCurrency() {
		$conf     = & elSingleton::getObj('elXmlConf');
		$currency = $conf->get('currency', 'currency');
		$this->current = isset($this->info[$currency]) 
			? $this->info[$currency]
			: $this->info['USD'];
		$this->_load();
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 **/
	function getInfo()
	{
		return $this->current;
	}

	function getSymbol() {
		return $this->current['symbol'];
	}
	
	function convert($price, $opts=array()) {

		if (isset($opts['currency']) && $opts['currency'] != $this->current['intCode'] && !empty($opts['exchangeSrc'])) {
			
			if ($opts['exchangeSrc'] == 'manual' 
			&& !empty($opts['rate']) 
			&& $opts['rate'] > 0) {
				$rate = $opts['rate'];
				$price *= $rate;
			} else {
				// echo 'auto';
				if ($this->current['intCode'] == 'RUR') {
					$rate = $this->_rate($opts['currency']);
				} else {
					$rate = $this->_rate($opts['currency'])/$this->_rate($this->current['intCode']);
				}
				$price *= $rate;
				if (!empty($opts['commision']) && $opts['commision'] > 0) {
					$price += $price*$opts['commision']/100;
				}
				
			}
			// $price *= $rate;
			// echo $rate;
		}
		$p = isset($opts['precision']) ? $opts['precision'] : 0;
		return !empty($opts['format']) ? $this->format($price, $opts) : round($price, $p);
	}
	
	function format($price, $opts) { 
		$p     = isset($opts['precision']) ? $opts['precision'] : 0;
		$price = number_format(round($price, $p), $p, $this->current['point'], $this->current['separator']);
		if (!empty($opts['symbol'])) {
			$price .= ' '.$this->current['symbol'];
		}
		return $price;
	}
	
	function getList() {
		$ls = array();
		foreach ($this->info as $k=>$v) {
			$ls[$k] = m($v['name']);
		}
		asort($ls);
		return $ls;
	}
	
	function updateConf() {
		$type  = 'manual';
		$conf  = & elSingleton::getObj('elXmlConf');
		$nav   = & elSingleton::getObj('elNavigator');
		$pages = array_merge($nav->findByModule('TechShop'), $nav->findByModule('IShop'), $nav->findByModule('GoodsCatalog'));
		foreach ($pages as $id) {
			// $c = $conf->get('currency',     $id);
			$t = $conf->get('exchangeSrc', $id);
			if ($t == 'auto') {
				$type = $t;
				break;
			}
		}
		
		$conf->set('type', $type, 'currencyUpdate');
		$conf->set('time', 0,     'currencyUpdate');
		$conf->save();
		// $this->_load();
	}
	
	function _rate($from) {
		
		if (is_null($this->_rates)) {
			$this->_load();
		}
		return isset($this->_rates[$from]) ? $this->_rates[$from] : 1;
		return 1;
	}
	
	function _load() {
		$conf = & elSingleton::getObj('elXmlConf');
		$this->_updateType = $conf->get('type', 'currencyUpdate');
		$this->_updateTime = (int)$conf->get('time', 'currencyUpdate');

		if ($this->_updateType == 'auto') {
			if (!$this->_updateTime 
			|| date('d', $this->_updateTime) < date('d') 
			|| date('m', $this->_updateTime) < date('m')) {
				elDebug('update currency');
				// elDebug(date('d', $this->_updateTime).' : '.date('d'));
				// elDebug(date('m', $this->_updateTime).' : '.date('m'));
				$url  = 'http://www.cbr.ru/scripts/XML_dynamic.asp';
				$date = date('d/m/Y');
				foreach ($this->info as $k=>$v) {
					if ($k != 'RUR') {
						$data = file($url.'?date_req1='.$date.'&date_req2='.$date.'&VAL_NM_RQ='.$v['cbrCode']);
						if (is_array($data)) {
							$p = xml_parser_create();
							xml_parse_into_struct($p, implode('', $data), $vals, $index);
							xml_parser_free($p);
							if (isset($index['VALUE'][0])) {
								$conf->set($k, str_replace(',', '.', $vals[$index['VALUE'][0]]['value']), 'currencyRates');
							}
						}
					}
				}
				$this->_updateTime = time();
				$conf->set('type', 'auto', 'currencyUpdate');
				$conf->set('time', $this->_updateTime, 'currencyUpdate');
				$conf->save();
			}
		}
		$this->_rates = $conf->getGroup('currencyRates');
	}
	
	
}

?>
