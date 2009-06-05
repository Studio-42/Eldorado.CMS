<?php

set_time_limit(0);
ignore_user_abort(true);
elLoadMessages('Errors');

if ( !defined('EL_DIR_TMP') )
{
  define ('EL_DIR_TMP', EL_DIR.'tmp/');
}

if (!defined('EL_DIR_OLDVER_STORAGE'))
{
  define ('EL_DIR_OLDVER_STORAGE', EL_DIR.'old-version/');
}

define('EL_UC_ERR_AUTHFAIL',          1);
define('EL_UC_ERR_CONFIG_SYS',      100);
define('EL_UC_ERR_CONFIG_PHP',      101);
define('EL_UC_ERR_INVALID_CONF',    102);
define('EL_UC_ERR_XML',             103);
define('EL_UC_ERR_XML_EMPTY',       102);
define('EL_UC_ERR_NET_NO_CONNECT',  104);
define('EL_UC_ERR_NET_CURL',        105);
define('EL_UC_ERR_NET_INVALID_URL', 106);
define('EL_UC_ERR_NET_EMPTY_FILE',  107);
define('EL_UC_ERR_FS_READ',         108);
define('EL_UC_ERR_FS_WRITE',        109);
define('EL_UC_ERR_ARC',             110);
define('EL_UC_ERR_POST_INSTALL',    111);

class elModuleUpdateClient extends elModule
{
  var $_mMapAdmin = array(
  'check'     => array('m' => 'checkVersion',  'l'=>'Check version',     'g'=>'Actions'),
  'upgrade'   => array('m' => 'upgrade',       'l'=>'Install update',    'g'=>'Actions'),
  'chlog'     => array('m' => 'displayLog',    'l'=>'Display changelog', 'g'=>'Actions'),
  'downgrade' => array('m' => 'downgrade'),
  'log'       => array('m' => 'displayLog')
  );

  var $_updateClient = null;
  var $_confID       = 'updateClient';
  var $_conf = array(
    'serverURL'    => '',
    'licenseKey'   => '',
    'availableVer' => '',
    'checkVerTs'   => 0
    );

  /**
   * Показывает текущую и доступную версии, историю обновлений,
   * предоставлет возможность откатить последнее обновление
   *
   */
  function defaultMethod()
  {
    $worksOK = true;
    if ($this->_updateClient->errors)
    {
      if ( !empty($this->_updateClient->errors[EL_UC_ERR_CONFIG_SYS])
      ||   !empty($this->_updateClient->errors[EL_UC_ERR_CONFIG_PHP])
      ||   !empty($this->_updateClient->errors[EL_UC_ERR_FS_WRITE]))
      {
        $this->_displayErrors('Invalid server configuration!');
        $worksOK = true;
      }
      else
      {
        $this->_displayErrors('Could not get available version information!');
      }
    }


    $logRec = & elSingleton::getObj('elUCLogRecord');
    $this->_initRenderer();
    $this->_rnd->rndStart( $worksOK, $logRec->getList() );
  }

   /**
   * Принудительно обновляет инф о доступной версии
   *
   */
  function checkVersion()
  {
    if ( $this->_checkVer( true ) )
    {
      elMsgBox::put( m('Available version information updated') );
    }
    else
    {
      $this->_displayErrors('Could not get available version information!');
    }
    elLocation( EL_URL );
  }

  /**
   * Показывает  во всплывающем окне changelog доступной версии или install log/changelog из history
   * Если первый агрумент пустой - показывает changelog доступной версии
   * Если непустой - это ID записи в табл лога
   * Если второй агрумент пустой - показывает changelog из history
   * Если непустой - показывает install log
   * В случае ошибки закрывает окно и отправляет сообщение об ошибке в родительское окно
   *
   */
  function displayLog()
  {
    $logID = (int)$this->_arg();
    if ( false == ($logID = (int)$this->_arg()) )
    { // changelog доступной версии
      if ( false === ($log = $this->_updateClient->getChangelog()) )
      { // не удалось получить лог
        if ( EL_WM == EL_WM_POPUP )
        { // закрываем всплывающее окно
          $this->_displayErrors('Could not get changelog!');
          $_SESSION['msgNoDisplay'] = 1;
          return elAddJs('window.top.opener.location=\''.EL_URL.'\';window.close();', EL_JS_SRC_ONLOAD);
        }
        elThrow(E_USER_ERROR, 'Could not get changelog!', null, EL_URL);
      }
      $head = sprintf(m('Changelog. Version %s.'), $this->_conf('availableVer') );
    }
    else
    { // changelog или install log из журнала обновлений
      $log = & elSingleton::getObj('elUCLogRecord');
      $log->setUniqAttr((int)$this->_arg());
      if ( !$log->fetch() )
      { // нет такой записи в логе
        $this->_displayErrors( vsprintf('There is no object "%s" with ID="%d"',  array($log->getObjName(), $log->ID)));
        $_SESSION['msgNoDisplay'] = 1;
        return elAddJs('window.top.opener.location=\''.EL_URL.'\';window.close();', EL_JS_SRC_ONLOAD);
      }
      if ( !$this->_arg(1) )
      { // changelog
        $head = sprintf('Changelog. Version %s.', $log->version);
        $log = $log->changelog;
      }
      else
      { // install log
        $head = sprintf('Version %s. Installation log.', $log->version);
        $log = $log->log;
      }
    }
    $this->_initRenderer();
    $this->_rnd->rndLog($head, $log);
  }

  /**
   * Обновляет сайт
   *
   */
  function upgrade()
  {
    if ( false == ($ver = $this->_checkVer( true )) )
    { //не получили номер версии. сервер валяЦЦо?
      $this->_displayErrors('Could not get available version information!');
      elLocation(EL_URL);
    }

    $cmp = $this->_compareVersions($ver);
    if ( -1 <> $cmp )
    {
      if (1 == $cmp)
      {
        elThrow(E_USER_ERROR, 'Available version is less then current one or has incorrect format! Upgrade impossible!', null, EL_URL);
      }
      elMsgBox::put(m('You have latest version installed!'));
      elLocation( EL_URL );
    }

    // создаем логгера
    $log = & elSingleton::getObj('elUCLogRecord');
    $log->setAttr('version', $ver);
    $log->setAttr('act', 'Upgrade');

    if ( false !== ($chlog = $this->_updateClient->getChangelog()) )
    {
      $log->setAttr('changelog', $chlog);
    }
    else
    { // не получили changelog - так и запишем
      $this->_displayErrors('Could not get changelog!');
      $log->appendToLog( m('Could not get changelog!') );
      $log->appendToLog( $this->_errorsToStr() );
    }

    if ( false == ($backupFile = $this->_updateClient->upgrade() ) )
    { // облом! так и запишем!
      $log->setAttr('result', 'Failed');
      $log->appendToLog( m('New version installation failed!') );
      $log->save();

      if ( file_exists(EL_DIR_CORE.'lib/elCore.class.php') )
      {// все не так и плохо
        $this->_displayErrors('New version installation failed!');
        elLocation(EL_URL);
      }
      else
      {//ппц!
        echo m('New version installation - Fatal Error!').'<br />';
        echo nl2br($this->_errorsToStr());
        exit;
      }
    }
    $log->appendToLog( $this->_updateClient->log );
    $log->setAttr('result',      'Success');
    $log->setAttr('backup_file', $backupFile);
    $log->save();
    $this->_rmOldBackups($log);
    elMsgBox::put( m('New version installation - Success!') );
    elLocation(EL_URL);
  }

  /**
   * Откатывает сайт на предыдущую версию!
   *
   */
  function downgrade()
  {
    // достаем последний бэкап
    $db  = & elSingleton::getObj('elDb');
    $sql = 'SELECT id, backup_file FROM el_uplog WHERE act="Upgrade" AND backup_file!="" ORDER BY crtime DESC LIMIT 0, 1';
    $db->query($sql);
    if (!$db->numRows())
    { // а нету!
      elThrow(E_USER_ERROR, 'There is no backup found!', null, EL_URL);
    }
    $r = $db->nextRecord();
    if ( !file_exists(EL_DIR_OLDVER_STORAGE.$r['backup_file']) )
    {
      elThrow(E_USER_ERROR, 'File %s does not exists', $r['backup_file'], EL_URL);
    }
    $version = preg_replace('/^([0-9\.]+)\-.+$/', "\\1", $r['backup_file']);
    // создаем логгера
    $log = & elSingleton::getObj('elUCLogRecord');
    $log->setAttr('version', $version);
    $log->setAttr('act',     'Downgrade');

    // downgrade
    if ( false == ($this->_updateClient->downgrade($r['backup_file'])) )
    { // не получаЦЦо!
      $log->setAttr('result', 'Failed');
      $log->appendToLog( m('Downgrade to previous version failed!') );
      $log->save();

      if ( file_exists(EL_DIR_CORE.'lib/elCore.class.php') )
      {// все не так и плохо
        $this->_displayErrors('Downgrade to previous version failed!');
        elLocation(EL_URL);
      }
      else
      {//ппц!
        echo m('Downgrade to previous version - Fatal Error!').'<br />';
        echo nl2br($this->_errorsToStr());
        exit;
      }
    }
    $log->appendToLog( $this->_updateClient->log );
    $log->setAttr('result',      'Success');
    $log->save();
    // удаляем последний бэкап, ибо - нех!
    unlink($r['backup_file']);
    $db->query('UPDATE el_uplog SET backup_file="" WHERE id='.$r['id'] );

    elMsgBox::put( m('Downgrade to previous version - Success!') );
    elLocation(EL_URL);
  }



  //**************************************************************//
  //                 PRIVATE METHODS
  //**************************************************************//
  /**
   * Инициализация модуля
   * Прверяет права доступа
   * Создает объект $this->_updateClient
   * Если недоступны tar gzip curl xml - отключает все методы кроме дефолтного и конфига
   * Проверяет доступную версию - при ошибке отключет все методы кроме дефолтного и конфига
   * Если версия up to date - отключает метод для обновления просмотра changeloga новой версии
   *
   */
  function _onInit()
  {
    // неаторизованных - ф бабруйск!
    if ( EL_WRITE > $this->_aMode )
    {
      header('HTTP/1.0 403 Acces denied');
			elThrow(E_USER_WARNING, 'Error 403: Acces to page "%s" denied.', EL_URL, EL_BASE_URL);
    }

    $this->_updateClient = & elSingleton::getObj('elUpdateClient');
    $this->_updateClient->init( $this->_conf('serverURL'), $this->_conf('licenseKey') );

    if ($this->_updateClient->errors)
    {//чегой-то не хватает
      return $this->_removeMethods( array('upgrade', 'check', 'chlog') );
    }
    if ( 'conf' <> $this->_mh )
    {// в настройках за версией не лазаем
      $this->_checkVer();
    }
    if ( !$this->_conf('serverURL') || !$this->_conf('licenseKey') )
    {//конфиг кривой
      $this->_removeMethods( array('upgrade', 'check', 'chlog') );
    }
    elseif( empty($this->_conf['availableVer']) || EL_VER == $this->_conf['availableVer'] )
    {//у нас все пучком!
       $this->_removeMethods( array('upgrade', 'chlog') );
    }
  }

  /**
   * Получает номер доступной версии и сохраняет в конфиге.
   * Автоматически обновляет версию раз в сутки
   * если $force=true - обновляет версию в любом случае
   * Если ошибка авторизации - стирает ключ лицензии в конфиге
   * Если URL неправильный - стирает URL в конфиге
   * Возвращает номер версии и false
   *
   * @param  bool $force
   * @return mix
   */
  function _checkVer( $force=false )
  {
    if (!$force)
    { // обновляемся раз в день
      $ver = $this->_conf('availableVer');
      $ts  = $this->_conf('checkVerTs');
      if ( $ver && $ts+86400 > time() )
      {
        return true;
      }
    }
    $conf = & elSingleton::getObj('elXmlConf');
    if ( false != ($ver = $this->_updateClient->getAvailableVersion()) )
    {// версию получили - гут! сохраняем в конфиг
      $conf->set('availableVer', $ver,   $this->_confID);
      $conf->set('checkVerTs',   time(), $this->_confID);
    }
    else
    {
      if (!empty($this->_updateClient->errors[EL_UC_ERR_AUTHFAIL]))
      {// авторизация йок! трем ключ лицензии
        $conf->set('licenseKey',   '', $this->_confID);
        $this->_updateClient->init( $this->_conf('serverURL'), '' );
      }
      elseif (!empty($this->_updateClient->errors[EL_UC_ERR_NET_INVALID_URL]))
      {// Урла неправильная! трем урл
        $conf->set('serverURL',   '', $this->_confID);
        $this->_updateClient->init( '', $this->_conf('licenseKey') );
      }
      $conf->set('availableVer', '', $this->_confID);
      $conf->set('checkVerTs',   0,  $this->_confID);
    }
    $conf->save();
    $this->_loadConf();
    //$this->_updateClient->gc();
    return $ver;
  }

  function _compareVersions($newVer)
  {
    $new = explode('.', $newVer);
    $old = explode('.', EL_VER);
    $s = sizeof($new) >= sizeof($old) ? sizeof($new) : sizeof($old);

    for ($i=0; $i<$s; $i++)
    {
      if ( $old[$i] <> $new[$i] )
      {
        return $old[$i] < $new[$i] ? -1 : 1;
      }
    }
    return 0;
  }


  /**
   * Удаляет старые бэкапы.
   * Оставляет только один последний
   * Вызывается в конце обновления сайта
   *
   * @param object $log
   */
  function _rmOldBackups( &$log )
  {
    $rm = $log->getCollectionToArray('id, backup_file', 'id!='.$log->ID.' AND backup_file!=""');
    if ( empty($rm) )
    {
      $log->appendToLog('Removing old versions - Do nothing');
      $log->save();
      return;
    }
    $rmd = '';
    foreach ($rm as $one)
    {
      if (file_exists(EL_DIR_OLDVER_STORAGE.$one['backup_file']))
      {
        $rmd .=$one['backup_file'].',';
        @unlink( EL_DIR_OLDVER_STORAGE.$one['backup_file'] );
      }
    }
    $log->appendToLog('Removed old versions - '.$rmd);
    $log->save();
    $log->db->query('UPDATE el_uplog SET backup_file="" WHERE id IN ('.implode(',', array_keys($rm)).')');
    $log->db->optimizeTable('el_uplog');
  }


  /**
   * Перенаправляет ошибки, хранящиеся в $this->_updateClient->errors
   * в обработчик ошибок elThrow()
   *
   * @param string $prependMsg
   */
  function _displayErrors( $prependMsg = '')
  {
    if ($prependMsg)
    {
      elThrow(E_USER_ERROR, $prependMsg);
    }
    foreach ($this->_updateClient->errors as $errors)
    {
      foreach ($errors as $err)
      {
        elThrow(E_USER_ERROR, $err);
      }
    }
  }

  /**
   * Возвращает все сообщения об ошибках в одной строке (для записи в лог)
   *
   * @return string
   */
  function _errorsToStr()
  {
    $str = '';
    foreach ($this->_updateClient->errors as $errors)
    {
      foreach ($errors as $err)
      {
        $str .= $err."\n";
      }
    }
    return $str;
  }

  function &_makeConfForm()
  {
    $form = &parent::_makeConfForm();
    $conf = & elSingleton::getObj('elXmlConf');

    $form->add( new elText('serverURL',  m('Update server URL'), $this->_conf('serverURL') ));
    $form->add( new elText('licenseKey', m('License key'),       $this->_conf('licenseKey')));
    $form->setElementRule('serverURL', 'regexp', 'http_url');
    $form->setRequired('licenseKey');
    return $form;
  }
}

?>
