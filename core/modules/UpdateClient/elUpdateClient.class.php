<?php

class elUpdateClient
{
  /**
   * Объект - архиватор
   *
   * @var object
   */
  var $_archiver = null;

  /**
   * URL сервера обновлений
   *
   * @var string
   */
  var $_URL = '';

  /**
   * Ключь лицензии
   *
   * @var string
   */
  var $_key = '';

  /**
   * Объект - парсер XML
   *
   * @var object
   */
  var $_xmlParser = null;

  /**
   * Стек для хранения текущих нодов при обработке XML
   *
   * @var array
   */
  var $_stack = array();

  /**
   * Enter description here...
   *
   * @var array
   */
  var $_xml = array();

  /**
   * Массив с сообщениями об ошибках
   * ключ - код ошибки
   * значение - массив(!) сообщений с этим кодом
   *
   * @var array
   */
  var $errors = array();

  /**
   * Строка с логом установки или даунгрейда
   *
   * @var string
   */
  var $log = '';

  /**
   * Дефолтные сообщения об ошибках по кодам
   *
   * @var array
   */
  var $_errCodes = array(
    EL_UC_ERR_AUTHFAIL        => 'Authentication failed on update server! Invalid license key!',
    EL_UC_ERR_CONFIG_SYS      => 'System command "%s" does not available',
    EL_UC_ERR_CONFIG_PHP      => 'PHP module "%s" does not installed',
    EL_UC_ERR_INVALID_CONF    => 'Invalid configuration! Check update server URL and license key!',
    EL_UC_ERR_XML             => 'XML error: "%s", file: %s, line: %s',
    EL_UC_ERR_XML             => 'XML error: empty XML file',
    EL_UC_ERR_NET_CURL        => 'CURL error: %s',
    EL_UC_ERR_NET_NO_CONNECT  => 'Could not connect to update server!',
    EL_UC_ERR_NET_INVALID_URL => 'Invalid update server URL! Fix it and try again!',
    EL_UC_ERR_NET_EMPTY_FILE  => 'Server return an empty file!',
    EL_UC_ERR_FS_READ         => 'Could read file %s',
    EL_UC_ERR_FS_WRITE        => 'Could write to file %s',
    EL_UC_ERR_POST_INSTALL    => 'Post-install script failed!',
    );


   /**
   * Инициализация объекта
   * Проверяет права на запись в ./ ./core ./tmp
   * Проверяет наличие curl xml tar gzip
   *
   * @param string $URL
   * @param string $key
   */
  function init($URL, $key)
  {
    $this->_URL  = !empty($URL) && '/' != substr($URL, -1, 1) ? $URL.'/' : $URL;
    $this->_key  = $key;
    $this->_arc  = & elSingleton::getObj('elArk');
    $this->_arc->setOverwrite(true);

    if ( !is_writable(EL_DIR) )
    {
      $this->setError(EL_UC_ERR_FS_WRITE, 'Directory %s is not writable', EL_DIR);
    }
    if ( !is_writable(EL_DIR_CORE) )
    {
      $this->setError(EL_UC_ERR_FS_WRITE, 'Directory %s is not writable', EL_DIR_CORE);
    }
    if (!is_dir(EL_DIR_TMP) && !mkdir(EL_DIR_TMP)  )
    {
      $this->setError(EL_UC_ERR_FS_WRITE, 'Could not create directory %s', EL_DIR_TMP);
    }
    elseif ( !is_writable(EL_DIR_TMP) )
    {
      $this->setError(EL_UC_ERR_FS_WRITE, 'Directory %s is not writable', EL_DIR_TMP);
    }
    if (!is_dir(EL_DIR_OLDVER_STORAGE) && !mkdir(EL_DIR_OLDVER_STORAGE)  )
    {
      $this->setError(EL_UC_ERR_FS_WRITE, 'Could not create directory %s', EL_DIR_OLDVER_STORAGE);
    }
    elseif ( !is_writable(EL_DIR_OLDVER_STORAGE) )
    {
      $this->setError(EL_UC_ERR_FS_WRITE, 'Directory %s is not writable', EL_DIR_OLDVER_STORAGE);
    }
    if ( false == ( $test = exec('which tar')) || '/' != $test[0] ) // Is this paranoia???
    {
      $this->setError(EL_UC_ERR_CONFIG_SYS, '', 'tar');
    }
    if ( false == ( $test = exec('which gzip')) || '/' != $test[0] )
    {
      $this->setError(EL_UC_ERR_CONFIG_SYS, '', 'gzip');
    }
    if ( !function_exists('curl_init') )
    {
      $this->setError(EL_UC_ERR_CONFIG_PHP, '', 'CURL');
    }
    if ( !function_exists('xml_parser_create') )
    {
      $this->setError(EL_UC_ERR_CONFIG_PHP, '', 'XML (libexpat)');
    }
  }

  /**
   * Получает доступную версию
   * Возвращает номер версии или false при ошибке
   *
   * @return mix
   */
  function getAvailableVersion()
  {
    return $this->_request('version') ? $this->_xml['UPDATE.VERSION'] : false;
  }

  /**
   * Получает changelog доступной версии
   * Возвращает его текст или false при ошибке
   *
   * @return mix
   */
  function getChangelog()
  {
    return $this->_request('chlog') ? $this->_xml['UPDATE.CHANGELOG'] : false;
  }

  /**
   * Откатывает на предидущую версию
   *
   * @param unknown_type $backupFile
   * @return unknown
   */
  function downgrade($backupFile)
  {
    if ( !$this->_arc->extract(EL_DIR_OLDVER_STORAGE.$backupFile, EL_DIR ) )
    {
      $this->setError(EL_UC_ERR_ARC, 'Resore previous version - Failed!');
      return $this->setError(EL_UC_ERR_ARC, $this->_arc->getError());
    }
    return true;
  }

  /**
   * Обновляет сайт
   *
   * @return bool
   */
  function upgrade()
  {
    umask(0);

    if ( !$this->_request('update') )
    {
      return false;
    }

    // backup
    $backupName = EL_VER.'-'.(time()).'-core';

    if ( !$this->_arc->createArchive( EL_DIR_OLDVER_STORAGE.$backupName, EL_ARCTYPE_GZIP, EL_DIR_CORE) )
    { // не удалось забэкапить
      $this->setError(EL_UC_ERR_ARC, 'Backup current version - Failed!');
      return $this->setError(EL_UC_ERR_ARC, $this->_arc->getError());
    }

    $backupName .= '.tar.gz';
    $this->_log( m('Backup current version - Success!').' - '.$backupName );

    // установка ядра
    if ( !$this->_arc->extract($this->_xml['UPDATE.CORE.PATH'], EL_DIR ) )
    { // не удалось распаковать ядро
      $this->setError(EL_UC_ERR_ARC, 'New version installation - Failed!');
      $this->setError(EL_UC_ERR_ARC, $this->_arc->getError());
      return $this->_rollBack($backupName);
    }

    @unlink($this->_xml['UPDATE.CORE.PATH']);
    $this->_log( 'Install new base system - Success' );

    // послеустановочный скрипт
    if ( file_exists(EL_DIR_CORE.'install/post-install.php') && @include_once EL_DIR_CORE.'install/post-install.php' )
    {
      if ( function_exists('elPostInstall') )
      {
        $this->_log( m('Run post-install script') );
        $GLOBALS['msgs'] = array();
        if ( !elPostInstall(  ) )
        {
          $this->setError(EL_UC_ERR_POST_INSTALL, implode("\n", $GLOBALS['msgs']) );
          return $this->_rollBack($backupName);
        }
        $fp = fopen('./tmp/log2', 'w');
        fwrite($fp, implode("\n", $GLOBALS['msgs']) );
        fclose($fp);
        $this->_log( implode("\n",$GLOBALS['msgs']) );
        $this->_log( m('Post-install script - Success') );
      }
    }
    return $backupName;
  }

  /**
   * Восстанавливает текущую версию при неудачном апгрейде
   * всегда возвращает false
   *
   * @param  string $backupName имя файла-бэкапа
   * @return bool
   */
  function _rollBack($backupName)
  {
    // откат
      if ( !$this->downgrade($backupName) )
      { // не удалось откатиться
        return false;
      }
      // откатились - пронесло!
      if (!empty($this->_xml['UPDATE.CORE.PATH']))
      {
        @unlink($this->_xml['UPDATE.CORE.PATH']);
      }
      $this->setError(EL_UC_ERR_ARC, 'Restore current version - Success!');
      return false;
  }

  //**************************************************************//
  //                 PRIVATE METHODS
  //**************************************************************//

  /**
   * Выполняет запрос к серверу обновлений
   * Возвращает рез-т работы метода _parseXml()
   * или false
   *
   * @param  string $request
   * @return mix
   */
  function _request($request)
  {
    if ( !$this->_URL || !$this->_key )
    {
      return $this->setError(EL_UC_ERR_INVALID_CONF);
    }

    $xmlFile  = EL_DIR_TMP.$request.'.xml';
    $URL      = $this->_URL.$request.'/'.urlencode($this->_key).'/'.urlencode($this->_setAuthKey());
    $postOpts = 'version='.EL_VER;

    if ( 'update' == $request )
    {
      $db        = elSingleton::getObj('elDb');
      $mods      = $db->queryToArray('SELECT module FROM el_module', null, 'module');
      $postOpts .= '&modules='.implode(',', $mods);
    }
    elDebug('CURL URL: '.$URL);
    elDebug('CURL POSTFIELDS: '.$postOpts);

    if ( false == ($fp = fopen($xmlFile, 'w')) )
    {
      return $this->setError(EL_UC_ERR_FS_READ, '', $xmlFile);
    }
    if ( false == ($ch = curl_init( $URL )) )
    {
      fclose($fp);
      return $this->setError(EL_UC_ERR_NET_CURL, '', curl_error($ch));
    }

    curl_setopt($ch, CURLOPT_FILE,       $fp);
    curl_setopt($ch, CURLOPT_HEADER,     false);
    curl_setopt($ch, CURLOPT_POST,       true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postOpts );

    $ret = curl_exec($ch);
    $nfo = curl_getinfo($ch);
    elDebug($nfo);

    if ( !$ret )
    {
      return $this->setError(EL_UC_ERR_NET_NO_CONNECT);
    }
    elseif ( 200 <> $nfo['http_code'] || empty($nfo['content_type']) || 'text/xml' <> substr($nfo['content_type'], 0, 8))
    {
      return $this->setError(EL_UC_ERR_NET_INVALID_URL);
    }
    elseif ( !$nfo['size_download'] )
    {
      return $this->setError(EL_UC_ERR_NET_EMPTY_FILE);
    }
    curl_close($ch);
    fclose($fp);
    return $this->_parseXml($xmlFile);
 }

  /**
   * Генерит разовый ключ обратной авторизации
   * Сохраняет ключ и адрес сервера обновлений в конфиге
   * Возвращает ключ
   *
   * @return string
   */
  function _setAuthKey()
  {
    $conf = & elSingleton::getObj('elXmlConf');
    $key  = rand();
    $url  = parse_url($this->_URL);
    $conf->set('authKey', $key, 'updateClient');
    $conf->set('serverAddress', gethostbyname($url['host']), 'updateClient');
    $conf->save();
    return $key;
  }

  /**
   * Локализует и добавляет сообщение в лог
   *
   * @param string $msg
   */
  function _log($msg)
  {
    $this->log .= $msg."\n";
  }


  /**
   * Парсит XML файл
   * Возвращает true или false в случае ошибки
   *
   * @param  string $xmlFile
   * @return bool
   */
  function _parseXml($xmlFile)
  {
    if ( false == ($fp = fopen($xmlFile, 'r')) )
    {
      return $this->setError(EL_UC_ERR_FS_READ, '', $xmlFile);
    }

    $this->_xmlParser = xml_parser_create();
    xml_set_object($this->_xmlParser, $this);
    xml_set_element_handler($this->_xmlParser, "_startElement", "_endElement");
    xml_set_character_data_handler($this->_xmlParser, "_characterData");
    //xml_parser_set_option($this->_parser, XML_OPTION_SKIP_WHITE, 1);

    while ( !feof($fp) )
    {
      if ( !xml_parse($this->_xmlParser, fread($fp, 102400)) )
      {
        $args = array(
          xml_error_string(xml_get_error_code($this->_xmlParser)),
          xml_get_current_line_number($this->_xmlParser),
          $xmlFile
          );
        xml_parser_free($this->_xmlParser);
        fclose($fp);
        unlink($xmlFile);
        return $this->setError(EL_UC_ERR_XML, '', $args);
      }
    }

    xml_parser_free($this->_xmlParser);
    fclose($fp);
    unlink($xmlFile);

    if ( !empty($this->_xml['UPDATE.ERRCODE']) )
    {
      $this->setError( $this->_xml['UPDATE.ERRCODE'], $this->_xml['UPDATE.ERRSTR'] );
      return elDebug($this->_xml['UPDATE.DEBUG']);
    }
    return empty($this->_xml) ? $this->setError( EL_UC_ERR_XML_EMPTY ) : true;
  }

  /**
   * добавляет сообщение об ошибке в $this->_errors и $this->log
   * если нет сообщения - берется дефолтное для данного типа ошибок
   *
   * @param int    $code код ошибки
   * @param string $msg  сообщение
   * @param mix    $args аргументы для добавления с сообщение
   */
  function setError($code, $msg=null, $args=null)
  {
    if (!$msg)
    {
      $msg = $this->_errCodes[$code];
    }
    if ( !empty($args) )
    {
      $msg = vsprintf( m($msg), $args );
    }
    $this->errors[$code][] = $msg;
    $this->log .= $msg."\n";
  }

  function gc()
  {
    exec('rm -rf '.EL_DIR_TMP.'*');
  }

  function _startElement($parser, $name, $attrs)
  {
    $this->_stack[] = $name;

    if ( !empty($attrs['TYPE']) && $attrs['TYPE'] == 'file')
    {
      $node = join('.', $this->_stack);
      $this->_xml[$node . '.PATH'] = tempnam(EL_DIR_TMP, "$name-");
      if ( false == ($this->_xml[$node . '.FP'] = fopen($this->_xml[$node.'.PATH'], 'w')) )
      {
        return $this->setError(EL_UC_ERR_FS_WRITE, '', $this->_xml[$node.'.PATH']);
      }
      elDebug('XML: open for write '.$this->_xml[$node.'.PATH']);
    }
  }

  function _endElement($parser, $name)
  {
    $node = join('.', $this->_stack);
    if ( !empty($this->_xml[$node . '.FP']) )
    {
      fclose($this->_xml[$node.'.FP']); elDebug('XML: close file '.$node.'.FP');
      $from =  $this->_xml[$node . '.PATH'];
      $to   = EL_DIR_TMP.strtolower($name).'.tar.gz';
      if ( false == ($sFp = fopen($from, 'r')) )
      {
        return $this->setError(EL_UC_ERR_FS_READ, '', $from);
      }
      if ( false == ($tFp = fopen($to, 'w')) )
      {
        return $this->setError(EL_UC_ERR_FS_WRITE, '', $to);
      }
      elDebug('XML: open for read '.$from);
      elDebug('XML: open for write '.$to);
      while ($line = fgets($sFp))
      {
        fwrite($tFp, base64_decode($line));
      }
      fclose($sFp);
      fclose($tFp);
      unlink($this->_xml[$node . '.PATH']);
      $this->_xml[$node . '.PATH'] = $to;

    }
    array_pop($this->_stack);
  }

  function _characterData($parser, $data)
  {
    $node = join('.', $this->_stack);
    // Если это данные бинарного файла и файл был успешно открыт
    if ( !empty($this->_xml[$node . '.FP']) )
    {
      if ( !fwrite($this->_xml[$node . '.FP'], $data) )
      {
        return $this->setError(EL_UC_ERR_FS_WRITE, '', $this->_xml[$node . '.FP']);
      }
    }
    elseif ( !empty($this->_xml[$node]) )
    {
      $this->_xml[$node] .= $data;
    }
    else
    {
      $this->_xml[$node] = $data;
    }
  }

}

?>
