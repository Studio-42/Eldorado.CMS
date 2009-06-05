<?php

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
				'RUR'=>array('руб.', 'российские рубли', ',', '.'));
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

function elLoadIconsConf()
{
	global $elIcons, $elIconsURL;
	if ( empty($elIcons) )
	{
		$elIconsURL = EL_BASE_URL.'/style/icons/';
		include_once(EL_DIR_STYLES.'icons/icons.conf.php');
	}
}

function elGetIconURL($label)
{
	if (empty($GLOBALS['elIcons']))
	{
		elLoadIconsConf();
	}
	$ico = !empty($GLOBALS['elIcons'][$label]) ? $GLOBALS['elIcons'][$label] : $GLOBALS['elIcons']['Default'];
	return $GLOBALS['elIconsURL'].$ico;
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

function elDebug($msg)
{
	static $debug;

	if ( !isset($debug) )
	{
		$conf  = &elSingleton::getObj('elXmlConf');
		$debug = (int)$conf->get('debug', 'common');
		if ( $debug )
		{
			$msgBox = & elSingleton::getObj('elMsgBox');
			$msgBox->createQueue(EL_DEBUGQ);
		}
	}
	if ( $debug )
	{
	  if (is_string($msg))
	  {
		  elMsgBox::put( htmlspecialchars($msg), EL_DEBUGQ );
	  }
	  elseif (is_array($msg))
	  {
	    foreach ($msg as $k=>$v)
	    {
	      elMsgBox::put( htmlspecialchars($k.': '.$v), EL_DEBUGQ );
	    }
	  }
	}
}

function elHalt()
{
	$html = file_get_contents('./style/halt.html');
	$repl = array(array('{URL}', '{VER}', '{NAME}', '{DEBUG}'), array(EL_BASE_URL, EL_VER, EL_NAME, '')) ;
	if (empty($html))
	{
		$html = '<html><body><h1 style="color:red;" align="center">SYSTEM ERROR!</h1><center><img src="{URL}/core/logo.gif" /><br />Eldorado CMS Core version: {VER} {NAME}</center><hr />{DEBUG}</body></html>';
	}

	if ( !empty($_GET['debug']) ) //$conf->get('debug', 'common') )
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
	$key = crc32($js);
	if ( !isset($GLOBALS['jsSrcipts'][$type][$key]) )
	{
		$GLOBALS['jsScripts'][$type][$key] = $js;
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
		elAddCss('ui-themes/'.$theme.'/ui.all.css', EL_JS_CSS_FILE);
		elAddJs('jquery.js', EL_JS_CSS_FILE);
		elAddJs('jquery-ui.js', EL_JS_CSS_FILE);
		$loaded = true;
	}
}

function elAddCss($file, $type=EL_JS_CSS_FILE)
{//echo $file.'<br>';
	$key = crc32($file);
	if ( !isset($GLOBALS['css'][$key]) )
	{
		if (EL_JS_CSS_SRC == $type || file_exists(EL_DIR_STYLES.'css/'.$file))
		{
			$GLOBALS['css'][$key] = array('src'=>$file, 't'=>$type);
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

function elGetCurrencyInfo()
{
	static $currInfo = array();
	if ( empty($currInfo))
	{
		$conf     = & elSingleton::getObj('elXmlConf');
		$currency = $conf->get('currency', 'currency');
		if ( $currency && !empty($GLOBALS['EL_CURRENCY_LIST'][$currency]))
		{
			$currInfo = array('currency'     => $currency,
												'currencySign' => $GLOBALS['EL_CURRENCY_LIST'][$currency][0],
												'currencyName' => $GLOBALS['EL_CURRENCY_LIST'][$currency][1],
												'decPoint'     => $GLOBALS['EL_CURRENCY_LIST'][$currency][2],
												'thousandsSep' => $GLOBALS['EL_CURRENCY_LIST'][$currency][3]
												);
		}
		else
		{
			$currInfo = array('currency'     => 'USD',
												'currencySign' => '$',
												'currencyName' => 'US dollars',
												'decPoint'     => '.',
												'thousandsSep' => ','
												);

		}

	}
	return $currInfo;
}

function elGetStatConf()
{
	$conf = &elSingleton::getObj('elXmlConf');

	$sconf = array('sql_type' => 'mysql');
	$sconf['sql_db']       = $conf->get('db', 'db');
	$sconf['sql_username'] = $conf->get('user', 'db');
	$sconf['sql_password'] = $conf->get('pass', 'db');
	$sock = $conf->get('sock', 'db');
	$host = $conf->get('host', 'db').(!empty($sock) ? ':'.$sock : '');
	$sconf['sql_serverip'] = $host;
	return $sconf;
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
			'url'=>$URL.'pl_conf/'.$r['name'].'/',
			'label'=>$r['label'].' ('.$status.')',
			'ico' => 'icoPlugin'.$r['name']
			);
	}
	return $pls;
}

function getPluginsCtlPageID()
{
	static $pID = null;
	if (!isset($pID))
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$pID = $conf->findGroup('module', 'PluginsControl');
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

function elMimeContentType($file)
{
  return elGetMimeContentType($file);
/**
  static $mimeFunc  = '';
  static $mimeFuncs = array('php');//, 'sysLinux', 'sysBSD');
  if (!$mimeFunc)
  {
    foreach ($mimeFuncs as $f)
    {
      if (elTestMime($f))
      {
        $ok = $f;
        break;
      }
    } echo $ok;
    $mimeFunc = isset($ok) ? $ok : 'el';
  }
echo $file.'<br>';
  switch ($mimeFunc)
  {
    case 'php':
      return mime_content_type($file);
      break;
    case 'sysLinux':
      return exec('file -ib '.escapeshellarg($file));
      break;

    case 'sysBSD':
      return exec('file -Ib '.escapeshellarg($file));
      break;

    default:
      return elGetMimeContentType($file);
  }
**/
}

function elGetMimeContentType($file)
{
  static $mimeList = array(
    'txt'  => 'plain/text',
    'php'  => 'text/php',
    'html' => 'text/html',
    'rtf'  => 'text/rtf',
    'xml'  => 'text/xml',
    'gz'   => 'application/x-gzip',
    'tgz'  => 'application/x-gzip',
    'bz2'  => 'application/x-bzip2',
    'bz'   => 'application/x-bzip2',
    'tbz'  => 'application/x-bzip2',
    'zip'  => 'application/x-zip',
    'rar'  => 'application/x-rar',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'png'  => 'image/png',
    'tif'  => 'image/tif',
    'psd'  => 'image/psd',
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'xls'  => 'application/msexel',
	'exe'  => 'application/octet-stream'
    );

    if ( false !== ($p = strrpos($file, '.') ) )
    {
      $ext = substr($file, $p+1);
      return !empty($mimeList[$ext]) ? $mimeList[$ext] : $mimeList['txt'];
    }
    return $mimeList['exe'];
}

function elTestMime($type)
{
  $file = './core/logo.jpg';
  $mime = 'image/jpeg';
  $test = ''; 
  if ('php' == $type)
  {
    if ( !function_exists('mime_content_type'))
    {
      return false;
    }
    $test = mime_content_type($file);
  }
  elseif ('sysLinux' == $type)
  {
    $test = @exec('file -ib '.escapeshellarg($file));
  }
  elseif ('sysBSD' == $type)
  {
    $test = @exec('file -Ib '.escapeshellarg($file));
  }
  return $mime == $test;
}

function elCheckAltTitleField($tb)
{
	$db =  &elSingleton::getObj('elDb');
	if ( !$db->isFieldExists($tb, 'altername') )
	{
		$db->query("ALTER TABLE  `".$tb."` ADD  `altername` varchar(256) ");	
	}
}

function elSetAlterTitle($alt)
{
	$nav = & elSingleton::getObj('elNavigator');
	$nav->setReplaceAltTitle($alt);
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
