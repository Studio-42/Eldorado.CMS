<?php

// TODO check for double+ price and return error

class CommerceMLv2
{
	var $_t = array(
		'CommerceInformation' => 'КоммерческаяИнформация',
		'Offer'               => 'Предложение',
		'Id'                  => 'Ид',
		'ProductCode'         => 'Артикул',
		'ItemPrice'           => 'ЦенаЗаЕдиницу',
		'ItemName'            => 'Наименование',
		'Quantity'            => 'Количество',
		'Unit'                => 'Единица',
		'SchemaVersion'       => 'ВерсияСхемы'
		);
	var $_checked   = false;
	var $_language  = null;
	var $_p         = array();
	var $_pSw       = false;
	var $_products  = array();
	
	function parse($xml)
	{
		if (empty($xml))
			return false;
			
		$this->_detectLanguage($xml);
		
		if ($this->_language == 'EN')
			$this->_setEnLanguage();
		
		$this->_parseCML($xml);
		
		return $this->_products;
	}
	
	function _parseCML($xml)
	{
		$parser = xml_parser_create('UTF-8');
		xml_parse_into_struct($parser, $xml, $struct);
		xml_parser_free($parser);
		
		foreach ($struct as $v)
		{
			if ($v['type'] == 'cdata')	// skip character data as we don't need it
				continue;
				
			if (($this->_pSw == true) && ($v['type'] == 'complete'))
			{
				$key = array_search($v['tag'], $this->_t);
				if (($key != false) && ($key != null))
					$this->_p[$key] = $v['value'];
				continue;
			}
			
			switch ($v['tag'])
			{
				case $this->_t['Offer']:
					$this->_pOffer($v);
					break;
				case $this->_t['CommerceInformation']:
					$this->_pCommerceInformation($v);
					break;
				default:
					break;
			}	
		}
	}

	function _pOffer($v)
	{
		if ($v['type'] == 'open') // offer open - start collecting data
		{
			$this->_p = array();
			$this->_pSw = true;
		}
		elseif ($v['type'] == 'close') // populate products
		{
			array_push($this->_products, $this->_p);
			$this->_pSw = false;
		}
	}

	function _pCommerceInformation($v)
	{
		$version = $v['attributes'][$this->_t['SchemaVersion']];
		if (($version >= 2) && ($version < 3))
		{
			// TODO print debug $version
			$this->_checked = true;
		}
	}
	
	function _detectLanguage($xml)
	{
		$begin = substr($xml, 0, 512);
		if (preg_match('/<КоммерческаяИнформация/', $begin))
			$this->_language = 'RU';
		elseif (preg_match('/<CommerceInformation/', $begin))
			$this->_language = 'EN';
		// TODO print debug $this->_language;
	}

	function _setEnLanguage()
	{
		foreach ($this->_t as $k => $v)
			$this->_t[$k] = $k;
	}

}


?>