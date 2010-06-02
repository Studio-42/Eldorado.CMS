<?php

if (!function_exists('scandir')) {
	function scandir($dir) {
		$files = array();
		$dh  = opendir($dir);
		while (false !== ($filename = readdir($dh))) {
		    $files[] = $filename;
		}

		sort($files);
		return $files;
	}
}


function elRStripSlashes($data)
{
	if (!is_array($data))
	{
		return stripslashes($data);
	}
	return array_map('elRStripSlashes', $data);
}


function elLoadMessages( $group )
{
	global $elMsg;
	static $loaded = array();
	static $path   = '';

	if ( !defined('EL_LOCALE') )
	{
		$conf   = & elSingleton::getObj('elXmlConf');

		if ( null != ($locale = $conf->get('locale', 'common')) &&
		     null == ($path = elSingleton::incLib('locale/'.$locale.'/elLcConst.php', true)) )
		{
			$locale = 'en_US.UTF-8';
			if ( null == ($path = elSingleton::incLib('locale/'.$locale.'/elLcConst.php', true)))
			{
				define ('EL_DATE_FORMAT',           'd.m.Y');
				define ('EL_TIME_FORMAT',           'H:i');
				define ('EL_DATETIME_FORMAT',       'd.m.Y H:i');
				define ('EL_MYSQL_DATE_FORMAT',     '%d.%m.%Y');
				define ('EL_MYSQL_TIME_FORMAT',     '%H:%i');
				define ('EL_MYSQL_DATETIME_FORMAT', '%d.%m.%Y %H:%i');
				define ('EL_MYSQL_CHARSET',         'utf8');
				$GLOBALS['EL_CURRENCY_LIST'] = array(
				'USD'=>array('$',    'доллары США', '.', ','),
				'EUR'=>array('€', 'евро', ',', '.'),
				'RUR'=>array('руб.', 'российские рубли', ',', '.'),
				'UAH'=>array('грн.', 'украинские гривны', ',', '.'));
			}
		}

		list($lang,) = explode('.', $locale);
		if ( 0 < ($p=strpos($lang, '_')) )
		{
			$lang = substr($lang, 0, $p);
		}
		define ('EL_LOCALE',  $locale );
		define ('EL_LANG',    $lang);
		elAddJs('el_lang = "'.EL_LANG.'"');
		elDebug('Set locale path to "'.$path.'"');
	}

	if ( !isset($loaded[$group]) )
	{
		$loaded[$group] = 1;
		$f = $path.'/el'.$group.'.php';
		if ( is_readable($f) && include_once $f )
		{
			elDebug('Messages from group "'.$group.'" was loaded');
		}
	}
}

function m($key)
{
	return !empty($GLOBALS['elMsg'][$key]) ? $GLOBALS['elMsg'][$key] : $key;
}

function elCleanCache()
{
	system("find ".EL_DIR_CACHE." -type f -exec rm {} \;");
}

function elPrintR($var)
{
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}

function shutdown()
{
	$msgBox = & elSingleton::getObj('elMsgBox');
	$msgBox->save();
}

function elErrorHandler($errno, $msg, $file, $line)
{
	if ( $errno & EL_ERROR_DISPLAY )
	{
		elMsgBox::put("$msg", EL_WARNQ);
	}
	else
	{
		// disable deprecation warnings
		$deprecated = array(
			'Assigning the return value of new by reference is deprecated',
			'Only variables should be assigned by reference',
			'Only variables should be passed by reference'
		);
		if (!(in_array($msg, $deprecated)))
			elMsgBox::put("$msg [$file; $line]", EL_DEBUGQ);
	}

	$logMsg = date('[d-m-y H:i:s]') .' ' .$msg . ' [file: '.$file . ' line: '.$line."]\n";
	if ( $errno & EL_ERROR_LOG  )
	{
		error_log( $logMsg, 3, EL_DIR_LOG.$_SERVER['HTTP_HOST'].'.error-log');
	}

	if ( $errno & EL_ERROR_MAIL && EL_ADMIN_EMAIL )
	{
		$lockFile =EL_DIR_LOG . md5($errno.$msg.$file.$line);
		if ( !file_exists($lockFile) || filemtime($lockFile) < time() - EL_ERROR_MAIL_TIMEOUT )
		{
			error_log( $logMsg, 1, EL_ADMIN_EMAIL );
			touch($lockFile);
		}
	}
}

function elThrow($errno, $msg, $param=null, $location=null, $halt=false, $file=null, $line=null)
{
	if (!$halt)
	{
		elLoadMessages('Errors');
	}
	if ( is_array($param) )
	{
		$param = array_map('m', $param);
	}
	elseif ( !is_null($param) )
	{
		$param = m($param);
	}
	$msg = is_null($param) ? m($msg) : vsprintf( m($msg), $param );
	trigger_error($msg, $errno);

	if ($halt)
	{
		elHalt();
	}
	elseif ( $location )
	{
		elLocation($location);
	}
}

function elLocation( $uri )
{ 
	if ( false === strpos($uri, 'http://') && false === strpos($uri, 'https://') )
	{
		$uri = EL_BASE_URL . ('/' != $uri{0} ? '/' : '') . $uri;
	}

	if ( '/' != substr($uri, -1) && !strpos($uri, '?') && !strpos($uri, '#') ) 
	{
		$uri .= '/';
	}
	exit( header('Location:'.$uri ) );
}

function elDebug($msg) {

	if (is_string($msg)) {
		elMsgBox::put( htmlspecialchars($msg), EL_DEBUGQ );
	} elseif (is_array($msg)) {
		foreach ($msg as $k=>$v) {
			elMsgBox::put( htmlspecialchars($k.': '.$v), EL_DEBUGQ );
		}
	}
}

function elHalt()
{
	$html = '<html><body><h1 style="color:red;text-align:center">SYSTEM ERROR!</h1>
		<img src="{URL}/style/images/logo.gif" style="margin:1em auto" />
		<p>Eldorado CMS Core version: {VER} {NAME}</p>
		<hr />{DEBUG}</body></html>';

	if ( !empty($_GET['debug']) ) 
	{
		$msg  = & elSingleton::getObj('elMsgBox');
		$repl[1][3] .= "<b>Messages:</b> <br />".nl2br($msg->fetchToString(EL_MSGQ))."  <br />";
		$repl[1][3] .= "<b>Warnings:</b> <br />".nl2br($msg->fetchToString(EL_WARNQ))." <br />";
		$repl[1][3] .= "<b>Debug:</b>    <br />".nl2br($msg->fetchToString(EL_DEBUGQ))."<br />";
		$msg->save();
	}
	
	exit(str_replace($repl[0], $repl[1], $html));
}

function utime ()
{
	$time = explode( " ", microtime());
	$usec = (double)$time[0];
	$sec  = (double)$time[1];
	return $sec + $usec;
}

function elAddJs($js, $type=EL_JS_CSS_SRC)
{
	if ($type == EL_JS_CSS_FILE)
	{
		$GLOBALS['_js_'][EL_JS_CSS_FILE][] = $js;
	}
	else
	{
		$key = crc32($js);
		if ( !isset($GLOBALS['_js_'][$type][$key]) )
		{
			$GLOBALS['_js_'][$type][$key] = $js;
		}
	}
}

function elLoadJQueryUI()
{
	static $loaded = false;
	if (!$loaded)
	{ 
		$conf = & elSingleton::getObj('elXmlConf');
		$theme = $conf->get('jQUITheme', 'layout');
		if (!$theme || !is_dir('./style/css/ui-themes/'.$theme))
		{
			$theme = 'Cupertino';
		}
		$GLOBALS['_css_']['ui-theme'] = 'ui-themes/'.$theme.'/ui.all.css';
		if (sizeof($GLOBALS['_js_'][EL_JS_CSS_FILE]) > 2)
		{
			array_shift($GLOBALS['_js_'][EL_JS_CSS_FILE]);
			array_shift($GLOBALS['_js_'][EL_JS_CSS_FILE]);
			array_unshift($GLOBALS['_js_'][EL_JS_CSS_FILE], 'common.min.js');
			array_unshift($GLOBALS['_js_'][EL_JS_CSS_FILE], 'jquery-ui.js');
			array_unshift($GLOBALS['_js_'][EL_JS_CSS_FILE], 'jquery.js');
		}
		else
		{
			$GLOBALS['_js_'][EL_JS_CSS_FILE][] = 'jquery-ui.js';
		}
		$loaded = true;
	}
}

function elAddCss($file, $type=EL_JS_CSS_FILE)
{
	if (EL_JS_CSS_FILE == $type)
	{
		$GLOBALS['_css_'][EL_JS_CSS_FILE][] = $file;
	}
	else
	{
		$key = crc32($file);
		if (!isset($GLOBALS['_css_'][EL_JS_CSS_SRC][$key]))
		{
			$GLOBALS['_css_'][EL_JS_CSS_SRC][$key] = $file;
		}
	}
}

function elAppendToPagePath( $el, $clean=false )
{
	if ( $clean )
	{
		$GLOBALS['pagePath'] = array();
	}
	if ( 0 !== strpos($el['url'], EL_URL)  )
	{
		$el['url'] = EL_URL.$el['url'];
	}
	if ( '/' != substr($el['url'], -1) )
	{
		$el['url'] .= '/';
	}
	if ( !isset($GLOBALS['pagePath'][$el['url']]) )
	{
		$GLOBALS['pagePath'][$el['url']] = $el;
	}
}

function elAppendToPageTitle($str, $replace=0)
{
	if ( !$replace )
	{
		$GLOBALS['appendPageTitle'][] = $str;
	}
	else
	{
		$GLOBALS['appendPageTitle'] = array($str);
	}
}

function elClosePopupWindow()
{
	$_SESSION['msgNoDisplay'] = true;
    elAddJs('window.opener.location.reload(); window.close();', EL_JS_SRC_ONLOAD);
}

function getPluginsManageList()
{
	$pID = getPluginsCtlPageID();
	if (!allowPluginsCtlPage())
	{
		return false;
	}
	$nav = & elSingleton::getObj('elNavigator');
	$URL = $nav->getPageURL($pID); //echo $URL;

	$pls = array( 
		array('url'=>$URL, 'label'=>m('Plugins control'), 'ico'=>'icoPluginsConf'),
		array('url'=>'#', 'label'=>m('Create info block'), 'ico'=>'icoInfoBlockNew', 'onClick'=>"return popUp( '{POPUP_URL}__pl__/InfoBlock/edit/', 700, 500);")
		);

	$db = & elSingleton::getObj('elDb');
	$db->query('SELECT name, label, status FROM el_plugin WHERE status<>"disabled" ORDER BY name');
	while ($r = $db->nextRecord())
	{ 
		$status = 'on' == $r['status'] ? m('On') : m('Off');
		$pls[] = array(
			'url'  => $URL.'pl_conf/'.$r['name'].'/',
			'label'=> $r['label'].' ('.$status.')',
			'ico'  => 'icoPlugin'.$r['name']
			);
	}
	return $pls;
}

function getPluginsCtlPageID()
{
	static $pID = null;
	if (!isset($pID))
	{
		$nav = &elSingleton::getObj('elNavigator');
		$page = $nav->pageByModule('PluginsControl');
		$pID = $page['id'];
	}
	return $pID;
}

function allowPluginsCtlPage()
{
	static $access = null;
	if (!isset($access))
	{
		$pID = getPluginsCtlPageID();
		$ats = & elSingleton::getObj('elATS');
		$access = $ats->allow(EL_WRITE, $pID);
	}
	return $access;
}

function elWriteLog($str, $log='')
{
  $logFile = './log/'.($log ? $log : 'common').'.log';
  if ( false != ( $fp = fopen($logFile, 'a') ) )
  {
    fwrite($fp, date(EL_DATETIME_FORMAT).' ['.$_SERVER['HTTP_HOST'].'] : '.$str."\n");
    fclose($fp);
  }

}

function elRange($start, $stop, $step, $exclude=null)
{
  $range = array();
  for ($i=$start; $i<=$stop; $i+=$step)
  {
    if ( !is_array($exclude) || !in_array($i, $exclude) )
    {
      $range[] = $i;
    }
  }

  return $range;
}


function elDisplayCaptcha($str)
{
	header("Content-type: image/png");
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
	header('Cache-Control: no-store, no-cache, must-revalidate'); 
	header('Cache-Control: post-check=0, pre-check=0', FALSE); 
	header('Pragma: no-cache');
	$img          = is_file('./style/forms/captcha.png') ? './style/forms/captcha.png' : './core/styles/default/forms/captcha.png';
	$font         = is_file('./style/forms/captcha.gdf') ? './style/forms/captcha.gdf' : './core/styles/default/forms/captcha.gdf';
	$captchaImage = imagecreatefrompng($img) or die("Cannot Initialize new GD image stream");
	$captchaFont  = imageloadfont($font); 
	$captchaColor = imagecolorallocate($captchaImage,0,0,0);
	imagestring($captchaImage,$captchaFont,15,5,$str,$captchaColor);
	imagepng($captchaImage);
	imagedestroy($captchaImage);
}

?>
