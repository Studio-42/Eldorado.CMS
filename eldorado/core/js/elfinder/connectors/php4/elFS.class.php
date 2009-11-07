<?php

/**
 * Статический класс для работы с файловой системой.
 * Получение списков файлов и директорий,
 * рекурсивное корирование, перемещение и удаление директорий,
 * подробная инф о файлах
 *
 * @package    el.lib
 * @subpackage filesistem
 * @author     Dmitry Levashov dio@eldorado-cms.ru
 **/
define('EL_FS_ONLY_DIRS', 1);
define('EL_FS_ONLY_FILES', 2);
define('EL_FS_DIRMODE', 0777);

class elFS
{

	/**
	 * Возвращает список - содержимое директории
	 *
	 * @param  string  $dir            имя директории
	 * @param  int     $flag           если задан - возвращает или только файлы или только директории
	 * @param  bool    $ignoreDotFiles игнорировать файлы начинающиеся с точки
	 * @return array
	 **/
	function ls($dir, $flag=0, $ignoreDotFiles=true, $hashKey=false)
	{
		$_dir = realpath($dir);
		if (!$_dir || !is_dir($_dir))
		{
			trigger_error(sprintf('Directory %s does not exists', $dir));
			return false;
		}
		if (!is_readable($_dir))
		{
			trigger_error(sprintf('Directory %s is not readable', $dir));
			return false;
		}
		$list = array();
		if ( false == ($d = dir($_dir)))
		{
			trigger_error(sprintf('Unable to open directory %s', $dir), E_USER_WARNING);
			return false;
		}
		while( $entr = $d->read())
		{
			if ('.'!=$entr && '..'!=$entr && (!$ignoreDotFiles || '.'!=substr($entr, 0, 1)))
			{
				$_path = $d->path.DIRECTORY_SEPARATOR.$entr;
				if (!$flag 
				|| (EL_FS_ONLY_FILES == $flag && is_file($_path)) 
				|| (EL_FS_ONLY_DIRS  == $flag && is_dir($_path)))
				{
					if ($hashKey)
					{
						$list[crc32($_path)] = $entr;
					}
					else
					{
						$list[] = $entr;
					}
				}
			}
		}
		$d->close();
		return $list;
	}
	
	/**
	 * Возвращает массив директории и файлы в заданой директории с подробной информацией о них ( см FileInfo::info())
	 *
	 * @param  string  $dir            имя директории
	 * @param  bool    $ignoreDotFiles флаг - игнорировать файлы начинающиеся с точки
	 * @return array
	 **/
	function lsall($dir, $ignoreDotFiles=true)
	{
		if (false === ($list = elFS::ls($dir, 0, $ignoreDotFiles)))
		{
			return false;
		}
		if (!class_exists('elFileInfo'))
		{
			include_once('elFileInfo.class.php');
		}
		$dir  = realpath($dir);
		$dirs = $files = array();
		for ($i=0, $s=sizeof($list); $i < $s; $i++) 
		{ 
			$path = $dir.DIRECTORY_SEPARATOR.$list[$i];
			if ( is_dir($path) )
			{
				$dirs[$list[$i]] = elFileInfo::info($path);
			}
			else 
			{
				$files[$list[$i]] = elFileInfo::info($path);
			}
		}
		return $dirs+$files;
	}

	/**
	 * Возвращает многомерный массив  - дерево директории
	 *
	 * @param  string  $dir  имя директории
	 * @param  el\ACL  $acl  если передан - директория добавляется в дерево, только если доступ к ней разрешен в $acl
	 * @param  string  $role роль для которой проверяет доступ $acl
	 * @return array
	 **/
	function tree($path, $acl=null, $role='', $perms=array(), $default=false)
	{
		$path  = realpath($path); 
		if ($path && is_dir($path))
		{
			$tree = array(
				'path' => $path,
				'hash' => crc32($path),
				'dirs' => array()
				);
			if (false!=($list = elFS::ls($path, EL_FS_ONLY_DIRS)))
			{
				
				foreach (elFS::ls($path, EL_FS_ONLY_DIRS) as $dirName)
				{
					$dir = $path.DIRECTORY_SEPARATOR.$dirName;
					if ($acl && $role) 
					{
						if ($acl->isAllowed($role, $dir)) {
							$tree['dirs'][$dirName] = elFS::tree($dir, $acl, $role);
						}
					}
					elseif ($perms) 
					{
						if (isset($perms[$dir]['read']) && $perms[$dir]['read']) 
						{
							$tree['dirs'][$dirName] = elFS::tree($dir, null, '', $perms, $default);
						}
					}
					elseif ($default) 
					{
						$tree['dirs'][$dirName] = elFS::tree($dir, null, '', $perms, $default);
					}
				}
				
			}
			return $tree;
		}
	}

	/**
	 * Возвращает список всех всех вложенных директорий
	 *
	 * @param  string  $dir  имя директории
	 * @return array
	 **/
	function tree2list($dir)
	{
		$dir    = realpath($dir);
		if ($dir)
		{
			$result = array($dir);
			if (false!=($list = elFS::ls($dir, EL_FS_ONLY_DIRS)))
			{
				foreach ($list as $_dir)
				{
					$result = array_merge($result, elFS::tree2list($dir.DIRECTORY_SEPARATOR.$_dir));
				}
				
			}
			return $result;
		} 
	}

	/**
	 * последовательно создает дерево директории по указаному пути
	 *
	 * @param  string  $file  путь
	 * @param  int     $umask umask
	 * @return bool
	 **/
	function mkdir($dir, $mode=null)
	{
		if ( !is_dir($dir) )
		{
			$parts = explode(DIRECTORY_SEPARATOR, $dir); 
			$path  = $parts[0].DIRECTORY_SEPARATOR;
			$mode = $mode ? $mode : EL_FS_DIRMODE;
			for ($i=1, $s = sizeof($parts); $i < $s; $i++) 
			{ 
				if ( '' != $parts[$i] )
				{
					$path .= $parts[$i]; 
					if ( !is_dir($path) )
					{
						
						if (!@mkdir($path, $mode))
						{
							trigger_error(sprintf('Unable to create directory %s', $path));
							return false;
						}
						chmod($path, $mode);
					}
					$path .= DIRECTORY_SEPARATOR;
				}
			}
		}
		return true;
	}
	
	/**
	 * Удаление файла или рекурсивное удаление директории
	 *
	 * @param  string  $path  путь к файлу или директории
	 * @return bool
	 **/
	function rm($path)
	{
		$path = realpath($path);
		if (!file_exists($path))
		{
			return false;
		}
		return is_dir($path) ? elFS::rmdir($path) : @unlink($path);
	}
	
	/**
	 * рекурсивно удаляет директорию со всеми вложеными директориями и файлами
	 *
	 * @param  string  $file  имя директории
	 * @return bool
	 **/
	function rmdir($dir)
	{
		$dir = realpath($dir); 
		if ($dir && is_dir($dir))
		{
			if (false != ($list = elFS::ls($dir, 0, false)))
			{
				for ($i=0, $s=sizeof($list); $i < $s; $i++) 
				{ 
					$path = $dir.DIRECTORY_SEPARATOR.$list[$i]; 
					if (is_dir($path))
					{
						if (!elFS::rmdir($path))
						{
							return false;
						}
					}
					elseif (!@unlink($path))
					{
						return false;
					}
				}
			}
			return @rmdir($dir);
		}
	}
	
	/**
	 * копирует файл или рекурсивно копирует директорию
	 * При копировании файла - $target может быть именем файла-приемника или существующей директории
	 * При копировании директорий если $target не существует, она будет создана
	 *
	 * @param  string  $source  имя источника
	 * @param  string  $target  имя целевой директории или файла
	 * @param  int     $mode    mode создаваемых директорий
	 * @return bool
	 **/
	function copy($source, $target, $mode=null)
	{
		$_source = realpath($source);
		$mode = $mode ? $mode : EL_FS_DIRMODE;
		if (!$_source) 
		{
			return elFS::_error('File %s does not exists', $source);
		}
		
		if ( is_dir($_source) )
		{
			//  target может быть только директорией
			$_target = realpath($target); 
			if ( $_target && !is_dir($_target) )
			{
				trigger_error(sprintf('%s is not directory', $target), E_USER_WARNING);
				return false;
			}
			elseif (!$_target)
			{
				if (!elFS::mkdir($target, $mode))
				{
					trigger_error(sprintf('Unable to create directory %s', $target), E_USER_WARNING);
					return false;
				}
				$_target = realpath($target);
			}
			if (0 === strpos($_target, $_source))
			{
				trigger_error(sprintf('Unable to copy directory %s into heself or into nested directory', $source), E_USER_WARNING);
				return false;
			}
			$_target .= DIRECTORY_SEPARATOR.basename($_source); 
			if (!is_dir($_target) && !@mkdir($_target, $mode))
			{
				trigger_error(sprintf('Unable to create directory %s', $_target), E_USER_WARNING);
				return false;
			}
			$list = elFS::ls($_source);
			for ($i=0, $s=sizeof($list); $i < $s; $i++) 
			{ 
				if ( !elFS::copy($_source.DIRECTORY_SEPARATOR.$list[$i], $_target) )
				{
					trigger_error(sprintf('Unable to copy %s to %s', array($_source.DIRECTORY_SEPARATOR.$list[$i], $_target)), E_USER_WARNING);
					return false;
				}
			}
			return true;
		}
		else
		{
			//  target может быть директорией или именем файла
			$target = is_dir($target) ? realpath($target).DIRECTORY_SEPARATOR.basename($_source) : $target;
			if ( dirname($_source) == realpath(dirname($target)) && basename($_source) == basename($target))
			{
				trigger_error(sprintf('Unable to copy file %s into himself', $source), E_USER_WARNING);
				return false;
			}
			if (file_exists($target) && !is_writable($target))
			{
				trigger_error(sprintf('File %s has no write permissions', $target), E_USER_WARNING);
				return false;
			}
			return copy($source, $target);
		}
	}
	
	/**
	 * перемещает файл или директорию
	 *
	 * @param  string  $source  имя источника
	 * @param  string  $target  имя приемника
	 * @return bool
	 **/
	function move($source, $target, $mode=null)
	{
		return elFS::copy($source, $target, $mode) && elFS::rm($source);
	}
	
} // END class 
?>