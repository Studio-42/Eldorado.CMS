<?php

error_reporting(E_ALL);

define('EL_CACHE_LIFETIME', 21600);

if (empty($_GET['type']))
{
	exit();
}

if (($_GET['type'] == 'css' || $_GET['type'] == 'js') && !empty($_GET['f']) && is_array($_GET['f']))
{
	$files = getFiles($_GET['f'], trim($_GET['type']));
	if (empty($files))
	{
		exit();
	}

	$key = crc32(implode('|', $files));
	$content = getCache($key);
	
	if (!$content)
	{
		$content = '';
		foreach ($files as $f)
		{
			$content .= "\n".file_get_contents($f)."\n";
		}
		setCache($key, $content);
	}
	if ($_GET['type'] == 'css')
	{
		header('Content-type: text/css; charset=utf-8');
	}
	else
	{
		header('Content-type: text/javascript; charset=utf-8');
	}
	
	ob_start("ob_gzhandler");
	echo $content;
	exit();
}


function getCache($key)
{
	$f = 'cache'.DIRECTORY_SEPARATOR.$key;
	if (file_exists($f) && is_readable($f) && time() - filemtime($f) < EL_CACHE_LIFETIME)
	{
		return @file_get_contents($f);
	}
}

function setCache($key, $str)
{
	if (!is_dir('cache') && !@mkdir('cache'))
	{
		return false;
	}
	$f = 'cache'.DIRECTORY_SEPARATOR.$key;
	if ($fp = @fopen($f, 'w'))
	{
		@fwrite($fp, $str);
		@fclose($fp);
		return true;
	}
}


function getFiles($files, $ext)
{
	$ret = array();
	$l   = strlen($ext)+1;
	$dir = $ext == 'css' ? 'style'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR : 'core'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR;
	foreach ($files as $f)
	{
		$f = trim($f);
		if (!strstr($f, './') && substr($f, -$l) == '.'.$ext && file_exists($dir.$f) && is_readable($dir.$f))
		{
			$ret[] = $dir.$f;
		}
	}
	return $ret;
}


?>