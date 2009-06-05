<?php

class elModuleTemplatesEditor extends elModule
{
  /**
   * Список директорий которые не показываем
   *
   * @var array
   */
  var $_pathsExclude = array(
    './style/images',
    './style/icons',
    './style/stat-images',
    './style/hmenu-images',
    './style/pageIcons'
    );

  /**
   * Дерево директорий и файлов
   *
   * @var array
   */
  var $_tree      = array();

  /**
   * Методы
   *
   * @var array
   */
  var $_mMapAdmin = array(
    'edit' => array('m'=>'edit'),
    'create'=>array('m'=>'create')
	);
	
	var $_mMapConf = array();

  /**
   * Метод по умолчанию
   *
   */
  function defaultMethod()
  {
    $this->_readFS(); //elprintR($this->_tree);
    $this->_initRenderer();
    $this->_rnd->rndFilesList($this->_tree);
  }

  /**
   * Сохраняет новый файл-шаблон
   *
   */
  function create()
  {
    $this->_readFS();
    $fileName = trim($_POST['tplName']);
    $ext      = preg_replace('/^.+\.([a-z]{3,4})$/i', "\\1", $fileName);
    $content  = trim($_POST['tpl']);
    if (empty($this->_tree[$_POST['teNewTplDirHash']]))
    {
      elThrow(E_USER_WARNING, 'Directory does not exists', null, EL_URL);
    }

    $dir = $this->_tree[$_POST['teNewTplDirHash']]['path'];
    if (!preg_match('/^[a-z0-9\-\._]+$/i', $fileName))
    {
      elThrow(E_USER_WARNING,
        'Name "%s" contains illegal symbols. Only latin alfanum, underscore, dot and dash symbols are accepted',
        $fileName, EL_URL);
    }

    if (!in_array($ext, array('html', 'css', 'js')))
    {
      elThrow(E_USER_WARNING, 'Invalid file extension! Only "html", "css" or "js" accepted', null, EL_URL);
    }

    $target = $dir.'/'.$fileName;
    if (file_exists($target))
    {
      elThrow(E_USER_WARNING, 'File %s already exists', $fileName, EL_URL);
    }

    if ( false == ($fp = fopen($target, 'w')))
    {
      elThrow(E_USER_WARNING, 'Could write to file %s', $fileName, EL_URL);
    }

    if (empty($content))
    {
      elThrow(E_USER_WARNING, 'There is no data for save in template file', $fileName, EL_URL);
    }

    fwrite($fp, $content);
    fclose();
    elMsgBox::put( m('Data saved'));
    elLocation(EL_URL);
  }

  /**
   * Редактирует существующий шаблон
   *
   */
  function edit()
  {
    $hash = $this->_arg(0);
    if ( null == ($file = $this->_getFile($hash)) )
    {
      elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%s"', array('file', $hash),  EL_URL);
    }

    if (empty($_POST['tpl']))
    {
      elThrow(E_USER_WARNING, 'There is no data for save in template file', null,  EL_URL);
    }

    if (false == ($fp = fopen($file['path'], 'w')))
    {
      elThrow(E_USER_WARNING, 'Could write to file %s', $file['name'],  EL_URL);
    }
    fwrite($fp, $_POST['tpl']);
    fclose();
    elMsgBox::put( m('Data saved') );
    elLocation(EL_URL);
  }

  /**
   * XML для ajax
   *
   * @return string
   */
  function toXml()
  {
    $hash   = $this->_arg();
    $reply  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
		$reply .= "<response>\n";
		$reply .= "<method>loadTpl</method>\n";
    $reply .= "<result>\n";

    elLoadMessages('Errors');

    if ( null == ($file = $this->_getFile($hash)))
    {
      $errMsg = sprintf(m('File %s does not exists'), '');
    }
    elseif ( 2 > $file['perm'])
    {
      $errMsg = sprintf( m('File %s does not writable'), $file['name']);
    }
    elseif ( 1 > $file['perm'])
    {
      $errMsg = sprintf( m('File %s is not readable'), $file['name']);
    }

    if (!empty($errMsg))
    {
      $reply .= "<error>".$errMsg."</error>\n";
    }
    else
    {
      $reply .=  "<content><![CDATA[".file_get_contents($file['path'])."]]></content>\n"
        ."<fileName>".$file['name']."</fileName>\n"
        ."<url><![CDATA[".(EL_URL.'edit/'.$hash.'/')."]]></url>\n";
    }

		$reply .= "</result>\n";
		$reply .= "</response>\n";

		return $reply;
  }


  //*************************************************//
  //               PRIVATE
  //*************************************************//

  /**
   * Инициализация
   *
   */
  function _onInit()
  {
    elLoadMessages('TplFilesDescriptions');
    $this->_readFS();
  }

  /**
   * возвращает локализованое описание файла по его пути или пустую строку
   *
   * @param  string $path
   * @return string
   */
  function _getFileDescrip($path)
  {
    return !empty($GLOBALS['styleFiles'][$path]) ? m($GLOBALS['styleFiles'][$path]) : '';
  }

  /**
   * Читает /style и создает дерево папок и файлов
   *
   * @param string $dir
   */
  function _readFS($dir='./style')
  {
    global $styleFiles;

    if (in_array($dir, $this->_pathsExclude))
    {
      return;
    }
    if (file_exists($dir.'/descrip.php'))
    {
      include_once $dir.'/descrip.php';
    }

    $hash  = md5($dir);
    $path  = $dir;
    $level = './style' == $dir ? 0 : sizeof( explode('/', str_replace('./style/', '', $dir)));
    $perm  = 0;
    if (is_writable($dir))
    {
      $perm = 2;
    }
    elseif (is_readable($dir))
    {
      $perm = 1;
    }
    $this->_tree[$hash] = array(
      'name'    => basename($dir),
      'path'    => $path,
      'perm'    => $perm,
      'files'   => array(),
      'level'   => $level,
      'descrip' => $this->_getFileDescrip($path) );

    if (1 > $perm )
    {
      return;
    }

    $d = dir($dir);

    while ( false != ($entr = $d->read()) )
    {
      $path = $d->path.'/'.$entr;
      if ( '.' != $entr && '..' != $entr && is_dir($path) )
      {
        $this->_readFS($path);
      }
      elseif(strstr($entr, '.html') || strstr($entr, '.css') || strstr($entr, '.js'))
      {
        $perm = is_writable($path) ? 2 : (is_readable($path) ? 1 : 0);
        $this->_tree[$hash]['files'][md5($path)] = array(
          'name'    => basename($path),
          'path'    => $path,
          'level'   => $level+1,
          'perm'    => $perm,
          'descrip' => $this->_getFileDescrip($path),
          'mtime'   => date(EL_DATETIME_FORMAT, filemtime($path)) );
      }
    }
  }

  /**
   * Возвращает содержимое файла по его хэшу
   *
   * @param  string $hash
   * @return string
   */
  function _getFile($hash)
  {
    foreach ($this->_tree as $dir)
    {
      foreach ($dir['files'] as $h=>$f)
      {
        if ($h == $hash)
        {
          return $f;
        }
      }
    }
    return null;
  }
}

?>