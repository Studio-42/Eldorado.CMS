<?php

define('EL_ARCTYPE_GZIP', 1);
define('EL_ARCTYPE_BZIP', 2);
define('EL_ARCTYPE_ZIP',  3);
define('EL_ARCTYPE_RAR',  4);

class elArk
{
	/**
   * mime type to file extension and class methods for create and extract relations
   * indexes: 0 - file extension, 1 - archive creator method, 2 - files extractor method
   */
	var $_handles = array(
  	EL_ARCTYPE_GZIP => array('tar.gz',  '_createGzip', '_extractGzip'),
  	EL_ARCTYPE_BZIP => array('tar.bz2', '_createBzip', '_extractBzip'),
  	EL_ARCTYPE_ZIP  => array('zip',     '_createZip',  '_extractZip'),
  	EL_ARCTYPE_RAR  => array('rar',     '_createRar',  '_extractUnRar'),
  	);

  var $_mimeTypes = array(
    'application/x-gzip'  => EL_ARCTYPE_GZIP,
  	'application/x-bzip2' => EL_ARCTYPE_BZIP,
  	'application/x-zip'   => EL_ARCTYPE_ZIP,
  	'application/zip'     => EL_ARCTYPE_ZIP,
  	'application/x-rar'   => EL_ARCTYPE_RAR,
  	'application/rar'     => EL_ARCTYPE_RAR
  );

	//system call output
	var $_pOutput = array();
	//system call exit code
	var $_exitCode = 0;
	//creating archive file name
	var $_aName = '';
	//creating archive mime type
	var $_aType = '';
	//files added to new archive
	var $_files = array();

	var $_sDir = './';

	var $_overwrite = false;

	/**
	 * Конструктор
	 * Проверяет наличие системных архиваторов
	 *
	 */
  function elArk()
	{
		if ( false == ($test = exec('which bzip2')) || '/' <> $test[0] )
		{
		  unset($this->_handles[EL_ARCTYPE_BZIP][1]);
		  unset($this->_handles[EL_ARCTYPE_BZIP][2]);
		}
		if ( false == ( $test = exec('which zip')) || '/' <> $test[0])
		{
		  unset($this->_handles[EL_ARCTYPE_ZIP][1]);
		}
		if ( false == ( $test = exec('which unzip')) || '/' <> $test[0])
		{
		  unset($this->_handles[EL_ARCTYPE_ZIP][2]);
		}
		if ( false == ( $test = exec('which rar')) || '/' <> $test[0])
		{
		  unset($this->_handles[EL_ARCTYPE_RAR][1]);
		  unset($this->_handles[EL_ARCTYPE_RAR][2]);
		}
		if ( empty($this->_handles[EL_ARCTYPE_RAR][2])
		&&   false != ( $test = exec('which unrar')) && '/' == $test[0])
		{
			$this->_handles[EL_ARCTYPE_RAR][2] = '_extractUnRar';
		}
	}

  /**
   * Устанавливает флаг перезаписи существуещего архива
   *
   * @param bool $flag
   */
	function setOverwrite($flag)
	{
	  $this->_overwrite = (bool)$flag;
	}

	/**
	 * Возвращает флаг перезаписи существуещего архива
	 *
	 * @return bool
	 */
	function getOverwrite()
	{
	  return $this->_overwrite;
	}

  /**
   * Очищает поля объекта
   *
   */
	function reset()
	{
	  $this->_exitCode = 0;
	  $this->_aType    = 0;
		$this->_aName    = '';
		$this->_files    = array();
		$this->_pOutput  = array();
	}


	function getMimesExtract()
	{
	  $mimes = array_flip($this->_mimeTypes);
	  $ret = array();
	  foreach ( $this->_handles as $type=>$v )
	  {
	    if (!empty($v[2]))
	    {
	      $ret[$mimes[$type]] = $v[0];
	    }
	  }
	  return $ret;
	}

	function getTypesCreate()
	{
	  $ret = array();
	  foreach ( $this->_handles as $type=>$v )
	  {
	    if (!empty($v[1]))
	    {
	      $ret[$type] = $v[0];
	    }
	  }
	  return $ret;
	}

	function getMimesCreate()
	{
	  $mimes = array_flip($this->_mimeTypes);
	  $ret   = array();
	  foreach ( $this->_handles as $type=>$v )
	  {
	    if (!empty($v[1]))
	    {
	      $ret[$mimes[$type]] = $v[0];
	    }
	  }
	  return $ret;
	}

	/**
	 * Получает тип или  mime-тип архива
	 * Возвращает имя метода распаковки архива или false
	 *
	 * @param  string $type
	 * @param  bool   $byMime
	 * @return string
	 */
	function canExtract( $type, $byMime=false )
	{
	  if ( $byMime )
	  {
	    $type = !empty($this->_mimeTypes[$type]) ? $this->_mimeTypes[$type] : 0;
	  }
	  return !empty($this->_handles[$type][2]) ? $this->_handles[$type][2] : null;
	}

	/**
	 * Получает тип или  mime-тип архива
	 * Возвращает имя метода создания архива или false
	 *
	 * @param  string $type
	 * @param  bool   $byMime
	 * @return string
	 */
	function canCreate( $type, $byMime=false )
	{
	  if ( $byMime )
	  {
	    $type = !empty($this->_mimeTypes[$type]) ? $this->_mimeTypes[$type] : 0;
	  }
	  return !empty($this->_handles[$type][1]) ? $this->_handles[$type][1] : null;
	}

	/**
	 * Возврашает содержимое $this->_pOutput в виде строки если последняя операция не удалась
	 *
	 * @return string
	 */
	function getError()
	{
	  return $this->_exitCode ? implode(' ', $this->_pOutput) : '';
	}

	/**
	 * Устанавливает имя будущего архива, его тип и дир, относительно которой будут добавляться файлы в архив
	 * Возвращает false если:
	 * - не может быть создан архив этого типа
	 * - имя архива содержит некорретные символы
	 * - файл с таким именем уже существует и не может быть перезаписан
	 * - дир файлов нечитабельна
	 *
	 * @param  string $name
	 * @param  string $type
	 * @param  string $sDir
	 * @return bool
	 */
	function create($name, $type=EL_ARCTYPE_GZIP, $sDir='./')
	{
	  $this->reset();
	  $this->_sDir  = realpath($sDir).'/';

		if ( !$this->_setType($type, true) )
		{
			return $this->_setExitCode(1, 'Unsupported archive type');
		}
		if ( !$this->_setName($name) )
		{
		  return $this->_setExitCode(1, 'Invalid file name "%s"', $name);
		}
		if ( !is_dir($this->_sDir) || !is_readable($this->_sDir) )
		{
		  return $this->_setExitCode(1, 'Directory %s is not readable', $this->_sDir);
		}
		if ( file_exists($this->_aName) )
		{// уже существует

		  if ( !$this->_overwrite )
		  { // низяя перезаписать
		    return $this->_setExitCode(1, 'File %s already exists', $this->_aName);
		  }
		  elseif ( !is_writable($this->_aName) )
		  { // нет правов таких
		    return $this->_setExitCode(1, 'File %s is not writable', $this->_aName);
		  }
		}
		elseif ( !is_writable(dirname($this->_aName)) )
		{
		  return $this->_setExitCode(1, 'Directory %s is not writable', dirname($this->_aName));
		}
		return true;
	}

	/**
	 * Добавляет файлы в архив
	 * Получает имя файла или массив имен
	 *
	 * @param mix $files
	 * @return bool
	 */
	function addFiles( $files )
	{
		if ( empty($this->_aName) || empty($this->_aType) )
		{
			return $this->_setExitCode(1, 'Could not add files to unexisted archive! Create archive first.');
		}
		if ( !is_array($files) )
		{
			return $this->_addFile( $files );
		}
		foreach ( $files as $file )
		{
		  if ( !$this->_addFile($file) )
		  {
		    return false;
		  }
		}
		return true;
	}

	/**
	 * Сохраняет архив на диск
	 * Возвращает true если файл создан и код возврата - 0
	 *
	 * @return true
	 */
	function save()
	{
	  if ( empty($this->_aName) || empty($this->_aType) )
		{
			return $this->_setExitCode(1, 'Could not save unexisted archive!');
		}
		if ( empty($this->_files) )
		{
		  return $this->_setExitCode(1, 'Could not save archive with no files!');
		}
		if ( false == ($m = $this->canCreate($this->_aType)) || !method_exists($this, $m))
		{
		  return $this->_setExitCode(1, 'Archiver error! Method not found!');
		}
    $this->$m();
		return file_exists($this->_aName) && 0 == $this->_exitCode ;
	}

	/**
	 * Создает архив, добавляет в него файлы и записывает на диск
	 * Обертка для 3-х предыдущих методов
	 *
	 * @param  string $name  имя файла архива
	 * @param  int    $type  тип архива
	 * @param  mix    $files файл и массив файлов для добавления
	 * @param  string $sDir  дир, относительно которой добавлять файлы
	 * @return bool
	 */
	function createArchive( $name, $type, $files, $sDir='./'  )
	{
		return $this->create($name, $type, $sDir) && $this->addFiles($files) && $this->save();
	}

	/**
	 * Распаковывает архив в целевую директорию
	 *
	 * @param string $file
	 * @param string $tDir
	 * @return bool
	 */
	function extract( $file, $tDir='./' )
	{
	  $this->reset();

	  $sFile = realpath(trim($file));
	  $tDir  = realpath(trim($tDir));

	  if ( !file_exists($sFile) )
	  {
	    return $this->_setExitCode(1, 'File %s does not exists', $file);
	  }
	  if ( !is_readable($sFile) )
	  {
	    return $this->_setExitCode(1, 'File %s is not readable', $file);
	  }
	  if ( !is_dir($tDir) )
	  {
	    return $this->_setExitCode(1, 'Directory %s does not exists', $tDir);
	  }
	  if ( !is_writable($tDir) )
	  {
	    return $this->_setExitCode(1, 'Directory %s is not writable', $tDir);
	  }

		//if ( !function_exists('mime_content_type') || '' == ($mime = mime_content_type($sFile) ) )
		//{
			//$mime = exec( 'file -b '.escapeshellarg($sFile) );
		//}
		$mime = elMimeContentType($sFile);

		if ( false == ($m = $this->canExtract($mime, true)) )
		{
		  return $this->_setExitCode(1, 'Unsupported archive type');
		}
		if ( !method_exists($this, $m))
		{
		  return $this->_setExitCode(1, 'Archiver error! Method not found!');
		}
		$this->$m( realpath($file), realpath($tDir));
		return 0==$this->_exitCode;
	}



	//*****************************************************************//
	//	                  PRIVATE METHODS
	//****************************************************************//

	/**
	 * Устанавливает имя файла создаваемого архива
	 * если оно корректное
	 *
	 * @param  string $name
	 * @return bool
	 */
	function _setName($name)
	{
	  if (preg_match('/[^a-z0-9\-_\.\/]/i', $name))
	  {
	    return false;
	  }
	  $this->_aName = realpath( dirname($name)).'/'.basename($name).'.'.$this->_handles[$this->_aType][0];
	  return true;
	}

	/**
	 *  Устанавливает тип архива если он поддерживается
	 *
	 * @param  int  $type   тип архива
	 * @param  bool $create создаем архив?
	 * @return bool
	 */
	function _setType($type, $create=false)
	{
	  $pos = $create ? 1 : 2;
	  if (!empty($this->_handles[$type][$pos]))
	  {
	    return $this->_aType = $type;
	  }
	  return false;
	}

	/**
	 * Устанавливает код возврата
	 * значения отличные от 0 - ошибка предыдущей операции
	 * Добавляет сообщение об ошибке в массив $this->_pOutput
	 *
	 * @param int    $code
	 * @param string $msg
	 * @param mix    $args
	 */
  function _setExitCode($code, $msg, $args=null)
  {
    elLoadMessages('Errors');
    $this->_pOutput[] = empty($args) ? m($msg) : vsprintf( m($msg), $args);
    $this->_exitCode  = $code;
  }

  /**
   * Добавляет один файл к архиву
   * если такой файл существует в $this->_sDir и читабелен
   *
   * @param  string $file
   * @return bool
   */
	function _addFile( $file )
	{
	  $file = trim($file);
	  if ( './' == substr($file, 0, 2))
	  {
	    $file = substr($file, 2);
	  }
	  $path = $this->_sDir.$file;

		if ( !$file || !file_exists($path) || !is_readable($path) )
		{
		  return $this->_setExitCode(1, 'Could not add file to archive! File "%s" does not exists or not readable.', $file);
		}
    $this->_files[] = escapeshellarg( './'.$file );
		return true;
	}

	/***************************************************************************
	*    создание архивов
	**************************************************************************/

	function _createGzip()
	{
	   $cmd  = 'tar czf '.escapeshellarg($this->_aName).' -C '.escapeshellarg($this->_sDir).' '.implode(' ', $this->_files);
	   $this->_pOutput[] = 'CMD: '.$cmd;
	   elWriteLog($cmd, 'UpdateServer');
	   exec( $cmd, $this->_pOutput, $this->_exitCode );
	}

	function _createBzip()
	{
		$cmd  = 'tar cjfv '.escapeshellarg($this->_aName).' -C '.escapeshellarg($this->_sDir).' '.implode(' ', $this->_files);
	  $this->_pOutput[] = 'CMD: '.$cmd;
	  exec( $cmd, $this->_pOutput, $this->_exitCode );
	}

	function _createRar()
	{
		$cmd = 'rar a -inul '.escapeshellarg($this->_aName).' '.implode(' ', $this->_files) ;
		$this->_pOutput[] = 'CMD: '.$cmd;
		$cwd = getcwd();
		chdir($this->_sDir);
		exec( $cmd, $this->_pOutput, $this->_exitCode );
		chdir($cwd);
	}

	function _createZip()
	{
		$cmd = 'zip -r9 '.escapeshellarg($this->_aName).' '.implode(' ', $this->_files) ;
		$this->_pOutput[] = 'CMD: '.$cmd;
		$cwd = getcwd();
		chdir($this->_sDir);
		exec( $cmd, $this->_pOutput, $this->_exitCode );
		chdir($cwd);
	}

	/***************************************************************************
	*    распаковка архивов
	**************************************************************************/

	/**
	 * Распаковывает tar.gz
	 *
	 * @param string $file
	 * @param string $tDir
	 */
	function _extractGzip($file, $tDir)
	{
		$sDir     = dirname($file);
		$fileName = basename($file);
		$cwd      = getcwd();
		$cmd      = 'tar xzfv '.escapeshellarg($fileName).' --exclude ".ht*"'; 

		$this->_pOutput[] = 'CMD: '.$cmd;

		if ( $sDir != $tDir )
		{
		  if ( !@copy($sDir.'/'.$fileName, $tDir.'/'.$fileName) )
		  {
		    $this->_setExitCode(1, 'Could not copy %s to %s!', array($sDir.'/'.$fileName, $tDir.'/'.$fileName) );
		  }
		  $rm = true;
		}

		chdir($tDir);
        exec($cmd, $this->_pOutput, $this->_exitCode);//elPrintR($this->_pOutput); elPrintR($this->_exitCode);
        chdir( $cwd );         
        if ( !empty($rm) )
        {
            @unlink($tDir.'/'.$fileName);
        }
	}

	/**
	 * Распаковывает tar.bz2
	 *
	 * @param string $file
	 * @param string $tDir
	 */
	function _extractBzip($file, $tDir)
	{
		$sDir     = dirname($file);
		$fileName = basename($file);
		$cwd      = getcwd();
		$cmd      = 'tar xjfv '.escapeshellarg($fileName);
		$this->_pOutput[] = 'CMD: '.$cmd;

		if ( $sDir != $tDir )
		{
		  if ( !@copy($sDir.'/'.$fileName, $tDir.'/'.$fileName) )
		  {
		    $this->_setExitCode(1, 'Could not copy %s to %s!', array($sDir.'/'.$fileName, $tDir.'/'.$fileName) );
		  }
		  $rm = true;
		}

		chdir($tDir);
    exec($cmd, $this->_pOutput, $this->_exitCode);
    chdir( $cwd );
    if ( !empty($rm) )
    {
      @unlink($tDir.'/'.$fileName);
    }
	}

	/**
	 * Распаковывает zip
	 *
	 * @param string $file
	 * @param string $tDir
	 */
  function _extractZip($file, $tDir)
	{
	  $cmd = 'unzip -o '.escapeshellarg($file).' -d '.escapeshellarg($tDir);
	  $this->_pOutput[] = 'CMD: '.$cmd;
		exec( $cmd, $this->_pOutput, $this->_exitCode);
	}

	/**
	 * Распаковывает rar rar'ом
	 *
	 * @param string $file
	 * @param string $tDir
	 */
	function _extractRar($file, $tDir)
	{
	  $cmd = 'rar x '.escapeshellarg($file).' '.escapeshellarg($tDir);
	  $this->_pOutput[] = 'CMD: '.$cmd;
		exec( 'rar x '.escapeshellarg($file).' '.escapeshellarg($tDir), $this->_pOutput, $this->_exitCode);
	}

	/**
	 * Распаковывает rar unrar'ом
	 *
	 * @param string $file
	 * @param string $tDir
	 */
	function _extractUnRar($file, $tDir)
	{
	  $cmd = 'unrar x '.escapeshellarg($file).' '.escapeshellarg($tDir);
	  $this->_pOutput[] = 'CMD: '.$cmd;
		exec($cmd, $this->_pOutput, $this->_exitCode);
	}

}


?>