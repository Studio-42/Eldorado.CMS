<?php

include_once 'XMLParseIntoStruct.php';

print '<pre>';

$file = 'Invoice_аа00000006.xml';
$xml = file_get_contents($file);

//print_r($xml);

$parser = new XMLParseIntoStruct($xml);
$parser->parse();
$struct = $parser->getResult();

// var_dump($struct);

$p = xml_parser_create('UTF-8');
xml_parse_into_struct($p, $xml, $vals);
xml_parser_free($p);

//print_r($vals);

$category = array();
$product = array();

foreach ($vals as $v)
{
	if (($v['tag'] == 'Группа') || ($v['tag'] == 'Category'))
		pCategory($v);
	elseif (($v['tag'] == 'Товар') || ($v['tag'] == 'Product'))
		pProduct($v);	
}

function pCategory($v)
{
	$v['Идентификатор'];
	print_r($v);
}

function pProduct($v)
{
	print_r($v);
}