<?php


/**
 * Статический класс для получения инф о файле (mimetype и пр).
 * При неправильных настройках php | apache получение mimetype файла оказывается затруднительно:
 * функция mime_content_type() доступна не всегда (MAMPRO на Mac :) ), 
 * не указан mime_magic.magicfile в php.ini или
 * finfo_open()  не находит magic_file и пр.
 * Поэтому, при первом обращении класс  пытается найти наилучший доступный метод получения content type 
 * (finfo, mime_content_type(), системная утилита file)
 * если они недоступны - кое-как определяет content type по расширению файла. :)
 * Замечание: для некоторых типов файлов finfo и mime_content_type отдают не такой mimetype, как системная утилита file
 * например для php - text/x-php и text/x-c++
 *
 * @package    el.lib
 * @subpackage filesistem
 * @author     Dmitry Levashov dio@eldorado-cms.ru
 **/
class elFileInfo
{

	
	/**
	 * возвращает mime type файла или unknown если не может определить
	 *
	 * @param  string  $file  имя файла
	 * @param  bool    $onlyMime  вернуть только  mime type
	 * @return string
	 **/
	function mimetype($file, $onlyMime=true)
	{
		/**
		 * массив расширений файлов/mime types для _method = 'internal' (когда недоступны все прочие методы)
		 *
		 * @var array
		 **/
		$_mimeTypes = array(
			'txt'  => 'plain/text',
		    'php'  => 'text/x-php',
		    'html' => 'text/html',
		 	'js'   => 'text/javascript',
			'css'  => 'text/css',
		    'rtf'  => 'text/rtf',
		    'xml'  => 'text/xml',
		    'gz'   => 'application/x-gzip',
		    'tgz'  => 'application/x-gzip',
		    'bz'   => 'application/x-bzip2',
			'bz2'  => 'application/x-bzip2',
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
			
		if (!defined('EL_FINFO_METHOD')) {
			elFileInfo::configure();
		}
		
		if ( !file_exists($file) )
		{
			trigger_error( sprintf('File %s does not exists', $file), E_USER_WARNING);
			return false;
		}
		if (is_dir($file))
		{
			return 'directory';
		}
		if (!is_readable($file))
		{
			trigger_error( sprintf('File %s is not readable', $file), E_USER_WARNING);
			return false;
		}
		switch (EL_FINFO_METHOD)
		{
			case 'php':   
			 	$type = mime_content_type($file);
				break;
			case 'linux':  
				$type = exec('file -ib '.escapeshellarg($file));
				break;
			case 'bsd':   
				$type = exec('file -Ib '.escapeshellarg($file));
				break;
			default:
				$ext  = false !== ($p = strrpos($file, '.')) ? substr($file, $p+1) : '';
				$type = isset($_mimeTypes[$ext]) ? $_mimeTypes[$ext] : 'unknown';
		}
		if ($onlyMime && false!=($p=strpos($type, ';')))
		{
			$type = substr($type, 0, $p);
		}
		return $type;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author dio
	 **/
	function isWebImage($file)
	{
		$file = realpath($file);
		if ($file && false != ($s = getimagesize($file)))
		{
			return $s['mime'] == 'image/jpeg' || $s['mime'] == 'image/gif' || $s['mime'] == 'image/png' ? $s : false;
		}
	}
	
	/**
	 * Возвращает инф о файле или директории
	 * 
	 * @param  string  $file  имя файла
	 * @return array
	 **/
	function info($file)
	{
		clearstatcache();
		if (!file_exists($file))
		{
			return false;
		}
		$path = realpath($file);
		$stat = stat($path);
		$info = array(
			'basename' => basename($file),
			'path'     => $path,
			'hash'     => crc32($path),
			'atime'    => $stat['atime'],
			'mtime'    => $stat['mtime'],
			'ctime'    => $stat['ctime'],
			'read'     => is_readable($file),
			'write'    => is_writable($file),
			'uid'      => $stat['uid'],
			'gid'      => $stat['gid'],
			'mode'     => $stat['mode'],
			'size'     => $stat['size'],
			'mimetype' => 'unknown',
			'hsize'    => '-'
			);
			
		if (is_dir($file))
		{
			$info['mimetype'] = 'directory';
			
		}
		elseif (is_file($file) && $info['read'])
		{
			$mimetype = elFileInfo::mimetype($file, false);
			$charset  = 'binary';
			if (false!=($p=strpos($mimetype, ';')))
			{
				if (preg_match('/charset=([^\s]+)/', $mimetype, $m))
				{
					$charset = $m[1];
				}
				$mimetype = substr($mimetype, 0, $p);
			}
			$info['mimetype'] = $mimetype;
			$info['charset']  = $charset;    
			$info['hsize']    = elFileInfo::formatSize($info['size']);
		}
		return $info;
	}
	
	/**
	 * Возвращает размер файла в читабельном виде (1 Mb)
	 *
	 * @param  int   $size  размер файла
	 * @return string
	 **/
	function formatSize($size)
	{
        $n    = 1;
		$unit = '';
		if ($size > 1073741824)
		{
			$n    = 1073741824;
			$unit = 'Gb';
		}
        elseif ( $size > 1048576 )
        {
            $n    = 1048576;
            $unit = 'Mb';
        }
        elseif ( $size > 1024 )
        {
            $n    = 1024;
            $unit = 'Kb';
        }
        return intval($size/$n).' '.$unit;
	}
	
	/**
	 * начальная настройка класса 
	 *
	 * @return void
	 **/
	function configure()
	{
		if (!defined('EL_FINFO_METHOD')) {

			if ( function_exists('mime_content_type') && mime_content_type(__FILE__) == 'text/x-php' )
			{
				return define('EL_FINFO_METHOD', 'php');
			}
			
			$type = exec('file -ib '.escapeshellarg(__FILE__)); 
			if (0 === strpos($type, 'text/x-php') || 0 === strpos($type, 'text/x-c++'))
			{
				return define('EL_FINFO_METHOD', 'linux');
			}
			
			$type = exec('file -Ib '.escapeshellarg(__FILE__));
			if (0 === strpos($type, 'text/x-php') || 0 === strpos($type, 'text/x-c++'))
			{
				return define('EL_FINFO_METHOD', 'bsd');
			}
			define('EL_FINFO_METHOD', 'internal');
		}
	}

} // END class

?>