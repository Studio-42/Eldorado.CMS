<?php

include_once 'CommerceMLv2.class.php';


$file = '1cbitrix/offers.xml';
$xml = file_get_contents($file);

$cml = new CommerceMLv2;

$products = $cml->parse($xml);
unset($xml); // free memory

print '<pre>';
print_r($products);


