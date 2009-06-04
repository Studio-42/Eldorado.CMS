<?php

// TODO check input
// TODO free globals and move to class
// TODO check version and input file
// make 2 classes, one for EL usage, one for parsing

print '<pre>';

$t = array(
	'CommerceInformation' => 'КоммерческаяИнформация',
	'Offer'               => 'Предложение',
	'Id'                  => 'Ид',
	'ProductCode'         => 'Артикул',
	'ItemPrice'           => 'ЦенаЗаЕдиницу',
	// 'View'                => 'Представление',
	'ItemName'            => 'Наименование',
	'Quantity'            => 'Количество',
	'Unit'                => 'Единица',
	'SchemaVersion'       => 'ВерсияСхемы'
	);
$tags = array();
$category = array();
$product = array();

$file = '1cbitrix/offers.xml';
$xml = file_get_contents($file);

$parser = xml_parser_create('UTF-8');
xml_parse_into_struct($parser, $xml, $struct);
xml_parser_free($parser);

$sw = false;
$p = array();
$products = array();

foreach ($struct as $v)
{
	if ($v['type'] == 'cdata')	// skip character data as we don't need it
		continue;
	
	if ($v['tag'] == $t['Offer'])
		pOffer($v);
		
	if ($v['tag'] == $t['CommerceInformation'])
		pCommerceInformation($v);
		
	if (($sw == true) && ($v['type'] == 'complete')) {
		$key = array_search($v['tag'], $t);
		if (($key != false) && ($key != null))
			$p[$key] = $v['value'];
		// print_r($v);
	}
	$tags[$v['tag']] += 1;
}
print_r($tags);

function pCommerceInformation($v)
{
	global $t;
	$version = $v['attributes'][$t['SchemaVersion']];
	if (($version >= 2) && ($version < 3))
	{
		print 'OK';
	}

}

function pOffer($v)
{
	global $sw, $p, $products;
//	print '------------------------'. "\n";
	
	if ($v['type'] == 'open')
	{
		$p = array();
		$sw = true;
	}
	elseif ($v['type'] == 'close')
	{
		// print_r($p);
		array_push($products, $p);
		$sw = false;
	}
}
print count($products) . "\n";
print_r($products);
