<?php

require_once './core/vendor/XMLParseIntoStruct.class.php';

class elGAParser
{
	var $_gdata = array();
	
	function parseDataFeed($xml)
	{
		if (!empty($xml))
		{
			$parser = new XMLParseIntoStruct;
			if (!$parser->parse($xml))
			{
				return false;
			}
			$this->parseStruct($parser->getResult());
			
			// flush _gdata
			$gdata = $this->_gdata;
			$this->_gdata = array();
			
			return $gdata;
		}
		return false;
	}
	
	function parseStruct($struct, $parent = null) 
	{
		$arr = array();
		foreach ($struct as $node) 
		{
			if (array_key_exists('name', $node)	&& array_key_exists('child', $node)	) 
			{
				$data = array();
				$data = $this->parseStruct($node['child'], $node['name']);
				if (count($data) > 0)
					array_push($this->_gdata, $data);
			} 
			else 
			{
				if ($parent == 'ENTRY') 
				{
					if (preg_match('/^DXP\:/', $node['name'])) 
					{
						if (array_key_exists('attributes', $node)) 
						{
							if (array_key_exists('NAME', $node['attributes']) && array_key_exists('VALUE', $node['attributes'])	) 
							{
								$arr[$node['attributes']['NAME']] = $node['attributes']['VALUE'];
							}
						}
						if (array_key_exists('content', $node)) 
						{
							$arr[$node['name']] = $node['content'];
						}
					} 
					elseif ($node['name'] == 'TITLE') 
					{
						$arr[$node['name']] = $node['content'];
					}
				}
			}
		}
		return $arr;
	}
}

?>
