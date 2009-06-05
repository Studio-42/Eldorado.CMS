<?php


// TODO: check curl_version

// TODO: find self 'profileId' by 'title' in El OR better 

// TODO: check input data in functions

$GData = array();
$reportType = array('visits', 'country', 'source', 'keyword', 'pagePath');

include_once('analytics.class.php');
include_once('XMLParseIntoStruct.php');

$api = new Google_Analytics();

if ($_GET['list'] == 'yes') {
	$url = 'https://www.google.com/analytics/feeds/accounts/default?prettyprint=true';
	$xml = $api->getFeed($url);
	
	ParseDataFeed($xml);
	foreach ($GData as $key => $value) {
		print '<b>' . $value['TITLE'] . '</b> ';
		foreach ($reportType as $v) {
			print '<a href="' . $_SERVER["PHP_SELF"]
				. '?profileId=' . $value['ga:profileId']
				. '&reportType=' . $v
				. '">'
				. $v . '</a> ' . PHP_EOL;
		}
		print '<br />';
	}
} elseif (preg_match('/\d+/', $_GET['profileId'])) {

	$profileId = $_GET['profileId'];
	$url = 'https://www.google.com/analytics/feeds/data'
		. '?ids=ga:' . $profileId
		. '&start-date=' . date('Y-m-d', strtotime('-1 month'))
		. '&end-date=' . date('Y-m-d', strtotime('yesterday'))
		. '&prettyprint=true';
	// TODO: add sort for some reports
	// TODO: add date ranges
	switch ($_GET['reportType']) {
		default:
		case 'visits':
			$dim = 'ga:date';
			$met = array('ga:pageviews', 'ga:visits', 'ga:visitors');
			break;
		case 'country':
			$dim = 'ga:country';
			$met = array('ga:pageviews', 'ga:visits');
			break;
		case 'source':
			$dim = 'ga:source';
			$met = array('ga:visits');
			break;
		case 'keyword':
			$dim = 'ga:keyword';
			$met = array('ga:visits');
			break;
		case 'pagePath':
			$dim = 'ga:pagePath';
			$met = array('ga:pageviews');
			break;
		
	}
	
	$url .= '&dimensions=' . $dim;
	$url .= '&metrics=' . implode(',', $met);
	$xml = $api->GetFeed($url);
	ParseDataFeed($xml);
	DrawHTMLTable($GData, $dim, $met);
	DrawXMLChart($GData, $dim, $met);
	print file_get_contents('charts/sample.html');
} else {	// default action
	header('Location: http://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?list=yes');
}

function DrawXMLChart($data, $key, $values)
{
	$i = 0;
	$f = 6;
	$o = '<chart><chart_data><row><null/>';
	foreach ($data as $v) {
		if (($i % $f) == 0)
			$o .= '<string>' . substr($v[$key], 4, 2)
				. '/' . substr($v[$key], 6, 2) . '</string>' . PHP_EOL;
		else
			$o .= '<string></string>';
		$i++;
	}
	$o .= '</row>' . PHP_EOL;
	
	foreach ($values as $k) {
		$o .= '<row><string>' . $k . '</string>' . PHP_EOL;
		foreach ($data as $v) {
			$o .= '<number>' . $v[$k] . '</number>' . PHP_EOL;
		}
		$o .= '</row>' . PHP_EOL;
	}
	
	$o .= '</chart_data><chart_type>Line</chart_type></chart>';
	// file_put_contents('sample.xml', $o);		// PHP5 only
	$fp = fopen('charts/sample.xml', 'w');
	fwrite($fp, $o);
	fclose($fp);
}

function DrawHTMLTable($data, $key, $values)
{
	$list = array();
	$list = $values;
	array_unshift($list, $key);
	print_r($list);
	print '<table border="1">';
	print '<tr>';
	foreach ($list as $k) {
		print '<th>' . $k . '</th>';
	}
	print '</tr>' . PHP_EOL;
	foreach ($data as $v) {
		print '<tr>';
		foreach ($list as $k) {
			print '<td>' . $v[$k] . '</td>';
		}
		print '</tr>' . PHP_EOL;
	}
	print '</table>' . PHP_EOL;
}

function ParseDataFeed($xml)
{
	global $GData;
	// TODO check not null
	$parser = new XMLParseIntoStruct($xml);
	$parser->parse();
	$struct = $parser->getResult();
	ParseStruct($struct);
}

function ParseStruct($struct, $parent = null) {
	global $GData;
	$arr = array();
	foreach ($struct as $node) {
		if (
			array_key_exists('name', $node)
			&& array_key_exists('child', $node)
		) {
			// print 'START__:' . $node['name'] . PHP_EOL;
			$data = ParseStruct($node['child'], $node['name']);
			if (count($data) > 0)
				array_push($GData, $data);
			// print 'END____:' . $node['name'] . PHP_EOL;			
		} else {
			if ($parent == 'ENTRY') {
				if (preg_match('/^DXP\:/', $node['name'])) {
					if (array_key_exists('attributes', $node)) {
						if (
							array_key_exists('NAME', $node['attributes'])
							&& array_key_exists('VALUE', $node['attributes'])
						) {
							// print $node['attributes']['NAME'] . ' => '
							// 	. $node['attributes']['VALUE'] . PHP_EOL;
							$arr[$node['attributes']['NAME']] = $node['attributes']['VALUE'];
						}
					}
					if (array_key_exists('content', $node)) {
						// print $node['name'] . ' => '
						// 	. $node['content'] . PHP_EOL;
						$arr[$node['name']] = $node['content'];
					}
				} elseif ($node['name'] == 'TITLE') {
					// print $node['name'] . ' => '
					// 	. $node['content'] . PHP_EOL;
					$arr[$node['name']] = $node['content'];
				}
			}
		}
	}
	return $arr;
}

print '<br /><br />DEBUG:::<br />' . PHP_EOL;
print_r($GData);

