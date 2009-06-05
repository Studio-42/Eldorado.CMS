<?
 /*****************************************************************
 * Класс сбора информации WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/
 class userInfo extends sql {

	 var $_browser_info = array(  'ua' => '',
	 			'browser' => 'unknown',
	 			'version' => '',
	 			'javascript' => '0.0',
	 			'platform' => 'unknown',
	 			'os' => 'unknown',
	 			'ip' => 'unknown',
	 			'cookies' => 'unknown',
	 			'language' => 'en',
	 			'long_name' => 'unknown',
	 			'color' => 'unknown',
	 			'screen' => 'unknown',
	 			'country' => 'unknown',
	 			'city' => 'unknown',
	 			'region' => 'unknown',
	 			'organization' => 'unknown',
	 			'referer' => 'unknown',
	 			'search' => 'unknown',
	 			'search_word' => 'unknown',
	 			'catalog' => 'unknown',
	 			'forum' => 'unknown',
	 			'page' => 'unknown'
	 			);

	 var $_feature_set = array(	'css2' => 'false',
	 			'css1' => 'false',
	 			'iframes' => 'false',
	 			'xml' => 'false',
	 			'dom' => 'false',
	 			'avoid_popup' => 'false',
	 			'cache_forms' => 'false',
	 			'cache_ssl' => 'false'
	 			);

	 var $_browser_search_regex = '([a-z]+)([0-9]*)([0-9.]*)(up|dn|\+|\-)?';
	 var $_language_search_regex = '([a-z-]{2,})';
	 var $timeout = 900; //15 мин.
	 var $count = 0;

 function init() {

	 global $sources_array, $gbl_config;

	 if(!$this->connect($gbl_config['sql_serverip'], $gbl_config['sql_username'], $gbl_config['sql_password'], $gbl_config['sql_db'])) { die("<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка соединения с базой!"); }

	 $result=$this->exec("SELECT * FROM wc_browser ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_browsers[$data[1]] = $data[2];
	 }

	 $result=$this->exec("SELECT * FROM wc_browser_features ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_browser_features[$data[1]] = $data[2];
	 }

	 $result=$this->exec("SELECT * FROM wc_java ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_javascript_versions[$data[1]] = $data[2];
	 }

	 $result=$this->exec("SELECT * FROM wc_color ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_color[$data[1]] = $data[1];
	 }

	 $result=$this->exec("SELECT * FROM wc_screen ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_screen[$data[1]] = $data[1];
	 }

	 $result=$this->exec("SELECT * FROM wc_search ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_search[$data[1]] = $data[2];
	 }

	 $result=$this->exec("SELECT * FROM wc_catalog ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_catalog[$data[2]] = $data[2];
	 }
 
	 $result=$this->exec("SELECT * FROM wc_forum ORDER BY id;");
	 while($data=$result->fetch_row()) {
		$_forum[$data[2]] = $data[2];
	 }

	 $sources_array['_browsers'] = $_browsers;
	 $sources_array['_browser_features'] = $_browser_features;
	 $sources_array['_color'] = $_color;
	 $sources_array['_screen'] = $_screen;
	 $sources_array['_search'] = $_search;
	 $sources_array['_catalog'] = $_catalog;
	 $sources_array['_forum'] = $_forum;
	 $sources_array['_javascript_versions'] = $_javascript_versions;

	 if (empty($UA)) $UA=getenv('HTTP_USER_AGENT');
	 if (empty($UA)) {
		 $pv=explode(".", PHP_VERSION);
		 $UA=($pv[0] > 3 && $pv[1] > 0) ? $_SERVER['HTTP_USER_AGENT'] : $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
	 }
	 if (empty($UA)) return false;
	 $this->_set_browser('ua', $UA);
	 $this->_get_ip();
	 $this->_test_cookies();
	 $this->_get_browser_info();
	 $this->_get_languages();
	 $this->_get_os_info();
	 $this->_get_javascript();
	 $this->_get_features();
	 $this->_get_color();
	 $this->_get_screen();
	 $this->_get_geoip();
	 $this->_get_referer();
	 $this->_get_page();
 }

 function property($p = null) {
	 if ($p == null) {
		 return $this->_browser_info;
	 } else {
		 return $this->_browser_info[strtolower($p)];
	 }
 }

 function get_property($p) { return $this->property($p); }

 function is($s) {

	 if (preg_match('/l:' . $this->_language_search_regex . '/i', $s, $match)) {
		 if ($match) return $this->_perform_language_search($match);
	 }
		 elseif (preg_match('/b:' . $this->_browser_search_regex . '/i', $s, $match)) {
		 if ($match) return $this->_perform_browser_search($match);
	 }
	 return false;
 }

 function browser_is($s) {

	 preg_match('/' . $this->_browser_search_regex . '/i', $s, $match);
	 if ($match) return $this->_perform_browser_search($match);
 }

 function has_feature($s) { return $this->_feature_set[$s] ? 'false' : 'true'; }

 function _perform_browser_search($data) {

	 $search=array();
	 $search['phrase']=isset($data[0]) ? $data[0] : '';
	 $search['name']=isset($data[1]) ? strtolower($data[1]) : '';
	 $search['direction']=isset($data[4]) ? strtolower($data[4]) : '';
	 if ($search['name'] == 'aol' || $search['name'] == 'webtv') {
		 return stristr($this->_browser_info['ua'], $search['name']);
	 } elseif ($this->_browser_info['browser'] == $search['name']) {
		 $what_we_are=$majv . $minv;
		 if (($search['direction'] == 'up' || $search['direction'] == '+') && ($what_we_are >= $looking_for)) {
			 return true;
		 } elseif (($search['direction'] == 'dn' || $search['direction'] == '-') && ($what_we_are <= $looking_for)) {
			 return true;
		 } elseif ($what_we_are == $looking_for) {
			 return true;
		 }
	 }
	 return false;
 }

 function _perform_language_search($data) {

	 $this->_get_languages();
	 return stristr($this->_browser_info['language'], $data[1]);
 }

 function _get_languages() {

	 if ($languages=getenv('HTTP_ACCEPT_LANGUAGE')) {
		 $languages=preg_replace('/(;q=[0-9]+.[0-9]+)/i', '', $languages);
	 }
	 $this->_set_browser('language', $languages);
 }

 function _get_os_info() {

	 $regex_windows='/([^dar]win[dows]*)[\s]?([0-9a-z]*)[\w\s]?([a-z0-9.]*)/i';
	 $regex_mac='/(68[k0]{1,3})|(ppc mac os x)|([p\S]{1,5}pc)|(darwin)/i';
	 $regex_os2='/os\/2|ibm-webexplorer/i';
	 $regex_sunos='/(sun|i86)[os\s]*([0-9]*)/i';
	 $regex_irix='/(irix)[\s]*([0-9]*)/i';
	 $regex_hpux='/(hp-ux)[\s]*([0-9]*)/i';
	 $regex_aix='/aix([0-9]*)/i';
	 $regex_dec='/dec|osfl|alphaserver|ultrix|alphastation/i';
	 $regex_vms='/vax|openvms/i';
	 $regex_sco='/sco|unix_sv/i';
	 $regex_linux='/x11|inux/i';
	 $regex_bsd='/(free)?(bsd)/i';

	 if (preg_match_all($regex_windows, $this->_browser_info['ua'], $match)) {
		 $v=$match[2][count($match[0]) - 1];
		 $v2=$match[3][count($match[0]) - 1];
		 if (stristr($v, 'NT') && $v2 == 5.1) $v='xp';
		 elseif ($v == '2000')  $v='2k';
		 elseif (stristr($v, 'NT') && $v2 == 5.0)  $v='2k';
		 elseif (stristr($v, '9x') && $v2 == 4.9)  $v='98';
		 elseif ($v . $v2 == '16bit')  $v='31';
		 else  $v.=$v2;
		 if (empty($v)) $v='win';
		 $this->_set_browser('os', strtolower($v));
		 $this->_set_browser('platform', 'win');
	 }
		 elseif (preg_match($regex_os2, $this->_browser_info['ua'])) {
		 $this->_set_browser('os', 'os2');
		 $this->_set_browser('platform', 'os2');
	 }
		 elseif (preg_match($regex_mac, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', 'mac');
		 $os=!empty($match[1]) ? '68k' : '';
		 $os=!empty($match[2]) ? 'osx' : $os;
		 $os=!empty($match[3]) ? 'ppc' : $os;
		 $os=!empty($match[4]) ? 'osx' : $os;
		 $this->_set_browser('os', $os);
	 }
		 elseif (preg_match($regex_sunos, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 if (!stristr('sun', $match[1])) $match[1]='sun' . $match[1];
		 $this->_set_browser('os', $match[1] . $match[2]);
	 }
		 elseif (preg_match($regex_irix, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', $match[1] . $match[2]);
	 }
		 elseif (preg_match($regex_hpux, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $match[1]=str_replace('-', '', $match[1]);
		 $match[2]=(int)$match[2];
		 $this->_set_browser('os', $match[1] . $match[2]);
	 }
		 elseif (preg_match($regex_aix, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'aix' . $match[1]);
	 }
		 elseif (preg_match($regex_dec, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'dec');
	 }
		 elseif (preg_match($regex_vms, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'vms');
	 }
		 elseif (preg_match($regex_sco, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'sco');
	 }
		 elseif (stristr('unix_system_v', $this->_browser_info['ua'])) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'unixware');
	 }
		 elseif (stristr('ncr', $this->_browser_info['ua'])) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'mpras');
	 }
		 elseif (stristr('reliantunix', $this->_browser_info['ua'])) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'reliant');
	 }
		 elseif (stristr('sinix', $this->_browser_info['ua'])) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'sinix');
	 }
		 elseif (preg_match($regex_bsd, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', $match[1] . $match[2]);
	 }
		 elseif (preg_match($regex_linux, $this->_browser_info['ua'], $match)) {
		 $this->_set_browser('platform', '*nix');
		 $this->_set_browser('os', 'linux');
	 }
 }

 function _get_browser_info() {

	 $this->_build_regex();
	 if (preg_match_all($this->_browser_regex, $this->_browser_info['ua'], $results)) {
		 $count=count($results[0]) - 1;
		 $this->_set_browser('browser', $this->_get_short_name($results[1][$count]));
		 $this->_set_browser('long_name', $results[1][$count]);
		 $this->_set_browser('version', $results[2][$count]);
	 }
 }

 function _get_ip() {

	 if (getenv('HTTP_CLIENT_IP')) {
		 $this->ip=getenv('HTTP_CLIENT_IP');
	 } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
		 $this->ip=getenv('HTTP_X_FORWARDED_FOR');
	 } elseif (getenv('HTTP_X_FORWARDED')) {
		 $this->ip=getenv('HTTP_X_FORWARDED');
	 } elseif (getenv('HTTP_FORWARDED_FOR')) {
		 $this->ip=getenv('HTTP_FORWARDED_FOR');
	 } elseif (getenv('HTTP_FORWARDED')) {
		 $this->ip=getenv('HTTP_FORWARDED');
	 } else {
		 $this->ip=$_SERVER['REMOTE_ADDR'];
	 }
	 //$this->ip="195.39.206.10";

	 $tempip = strrpos($this->ip,",");
	 if ($tempip!=0) { $this->ip = trim(substr($this->ip, $tempip+1)); }

	 $this->_set_browser('ip', $this->ip);
 }

 function _get_color() {

 	 global $sources_array;

	 foreach ($sources_array['_color'] as $key => $value) {
		 if ($_GET['col'] == $value) {
			 $this->_set_browser('color', $_GET['col']);
			 break 1;
	 	}
	}
 }

 function _get_screen() {

 	 global $sources_array;

	 foreach ($sources_array['_screen'] as $key => $value) {
		 if ($_GET['scr'] == $value) {
			 $this->_set_browser('screen', $_GET['scr']);
			 break 1;
	 	}
	}
 }

 function _get_geoip() {

 	 global $sources_array;

	 include("./includes/geoip/geoipcity.inc");

	 if(file_exists("./includes/geoip/GeoIPOrg.dat")) {
		$giorg = geoip_open("./includes/geoip/GeoIPOrg.dat", GEOIP_STANDARD);
		$org = geoip_org_by_addr($giorg, $this->ip);
		if ($org) {
			$this->_set_browser('organization', $org);
		}
		geoip_close($giorg);
	 }

	 if(file_exists("./includes/geoip/GeoIPCity.dat")) {
		$gi = geoip_open("./includes/geoip/GeoIPCity.dat", GEOIP_STANDARD);
		$record = geoip_record_by_addr($gi, $this->ip);
		if ($record->country_name) {
			$this->_set_browser('country', $record->country_name);
		}
		if ($record->city) {
			$this->_set_browser('city', $record->city);
		}
		if ($FIPS[$record->country_code][$record->region]) {
			$this->_set_browser('region', $FIPS[$record->country_code][$record->region]);
		}
	 }
 }

 function _get_referer() {

 	global $sources_array;

	if (!$_GET['ref'] && $_SERVER['HTTP_REFERER']) {
		$_GET['ref'] = $_SERVER['HTTP_REFERER'];
	}

	if ($_GET['ref']) {
		 $this->_set_browser('referer', $_GET['ref']);
	}

	$url = urldecode($_GET['ref']);
	$host = $_SERVER['SERVER_NAME'];

	if (($url!="") and (!stristr($url, $host))) {
		
		$this->_set_browser('referer', $url);

		// Search
		foreach ($sources_array['_search'] as $key => $val) {

			if(stristr($url, $key))	{
				$sw = $val; $engine = $key;
				break;
			}
		}

		if (isset($engine)) {
			$url2=urldecode($url);
			$url2=stripslashes ($url2);
			$url2=strip_tags ($url2);

			// Яндекс
			if  (stristr($url, "yandpage")) {
				$url2=convert_cyr_string ($url2, k, w);
			}

			// Google
			if  (stristr($url, "google")) {
				$url2 = iconv("UTF-8", "Windows-1251", $url2);
				$this->_set_browser('referer', $_GET['ref']);
			}

			eregi ($sw."([^&]*)", $url2."&", $url2);
			$url2 = strip_tags ($url2[1]);
			$this->_set_browser('search', $engine);
			$this->_set_browser('search_word', $url2);
		}

		// Catalog
		foreach ($sources_array['_catalog'] as $key => $val) {

			if(stristr($url, $key))	{
				$this->_set_browser('catalog', $key);
				break;
			}
		}
		
		// Forun
		foreach ($sources_array['_forum'] as $key => $val) {

			if(stristr($url, $key))	{
				$this->_set_browser('forum', $key);
				break;
			}
		}
	}
}

 function _get_page() {

 	global $sources_array;

	if ($_GET['pg']) {
		 $this->_set_browser('page', $_GET['pg']);
	}
 }

 function _build_regex() {

	 global $sources_array;

	 $browsers='';
	 while (list($k, )=each($sources_array['_browsers'])) {
		 if (!empty($browsers)) $browsers.="|";
		 $browsers.=$k;
	 }
	 $version_string="[\/\sa-z(]*([0-9]+)([\.0-9a-z]+)?";
	 $this->_browser_regex="/($browsers)$version_string/i";
 }

 function _get_short_name($long_name) {

	global $sources_array;
 	
 	return $sources_array['_browsers'][strtolower($long_name)];
 }

 function _test_cookies() {

	 if (setcookie("cookies", "ok")) {
		$this->_set_browser('cookies', 'true');
	 } else {
	 	 $this->_set_browser('cookies', 'false');
	 }
 }

 function _get_javascript() {

	 global $sources_array;

	 $set=false;
	 while (list($version, $browser)=each($sources_array['_javascript_versions'])) {
		 $browser = explode(',', $browser);
		 while (list(, $search)=each($browser)) {
			 if ($this->is('b:' . $search)) {
				 $this->_set_browser('javascript', $version);
				 $set=true;
				 break;
			 }
		 }
		 if ($set) break;
	 }
 }

 function _get_features() {

 	 global $sources_array;

 	 while (list($feature, $browser)=each($sources_array['_browser_features'])) {
 		 $browser = explode(',', $browser); 
		 while (list(, $search)=each($browser)) {
			 if ($this->browser_is($search)) {
				 $this->_set_feature($feature); 
				 break;
			 }
		 }
	 }
 }

 function _set_browser($k, $v) {

	$k = $this->string_to($k);
 	$v = $this->string_to($v);

 	$this->_browser_info[$k] = $v;
 }

 function _set_feature($k) {

 	$this->_feature_set[strtolower($k)]=!$this->_feature_set[strtolower($k)];
 }

 function online_user() {

	 $this->exec("INSERT INTO wc_online (ses_id, timestamp, ip) VALUES ('".$this->num_session."', '".$this->timestamp."', '".$this->ip."');");
	 $this->exec("DELETE FROM wc_online WHERE timestamp < (".$this->timestamp." - ".$this->timeout.");");
 }

 function session_user() {

	$result=$this->exec("SELECT ses_id, ip FROM wc_online WHERE ip='".$this->ip."';");
	$row = $result->fetch_array();

	$this->num_session = $row['ses_id'] ? $row['ses_id'] : "0";
 }

 function string_to($str) {

	$t_str_up = "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$t_str_low = "абвгдеёжзийклмнопрстуфхцчшщъыьэюяabcdefghijklmnopqrstuvwxyz";

	$str = strtr($str, $t_str_up, $t_str_low);

	if(is_array($str)) { return implode("", $str); }

	return $str;
 }
 }
?>