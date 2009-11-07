<?php

define('EL_US_ERR_AUTH',            1);
define('EL_US_ERR_AUTH_NOKEY',      2);
define('EL_US_ERR_AUTH_NOGETKEY',   3);
define('EL_US_ERR_AUTH_XML_NODATA', 4);
define('EL_US_ERR_AUTH_XML_NOKEY',  5);
define('EL_US_ERR_AUTH_BADKEY',     6);

define('EL_US_ERR_REQUEST', 7);
define('EL_US_ERR_PACK',    8);
define('EL_US_ERR_SERVER',  9);

class elModuleUpdateServer extends elModule
{
  var $_errors = array (
    EL_US_ERR_AUTH            => 'Authentication failed',
    EL_US_ERR_AUTH_NOKEY      => 'Authentication failed! No license key!',
    EL_US_ERR_AUTH_NOGETKEY   => 'Authentication failed! Could not confirm license key!',
    EL_US_ERR_AUTH_XML_NODATA => 'Authentication failed! Server response is empty!',
    EL_US_ERR_AUTH_XML_NOKEY  => 'Authentication failed! Server does not confirm key!',
    EL_US_ERR_AUTH_BADKEY     => 'Authentication failed! Invalid confirm key! He-he!',
    EL_US_ERR_REQUEST         => 'Invalid request',
    EL_US_ERR_PACK            => 'Package build error',
    EL_US_ERR_SERVER          => 'Server error! Source not found'
    );

  var $_mMap = array(
    'version' => array('m' => 'getVersion'),
    'chlog'   => array('m' => 'getChangelog'),
    'update'  => array('m' => 'getUpdate')
    );

  /**
   * Объект сорс-менеджер
   *
   * @var object
   */
  var $_sm = null;
  /**
   * Объект лицензия
   *
   * @var object
   */
  var $_lc = null;

  var $_debug = '';

  var $_conf = array('sourceDir'=>'./source', 'debug'=>0);


  /**
   * Отправляет клиенту информацию о доступных обновлениях
   *
   */
  function getVersion()
  {
    $act = 'version';

    if ( false != ( $errCode = $this->_checkAuth($licenseKey, $authKey) ) )
    {
      $this->_sendError($act, $errCode);
    }
    if ( empty($_POST['version']) )
    {
      $this->_debug .= 'No info about installed version'."\n";
      $this->_sendError($act, EL_US_ERR_REQUEST);
    }
    if ( false == ($ver = $this->_sm->getVersion()) )
    {
      $this->_debug .= $this->_sm->error."\n";
      $this->_sendError($act, $this->_sm->errCode);
    }
    $this->_log($act);
    $this->_send( array('version' => $ver) );

  }

  /**
   * Отправляет клиенту CHANGELOG
   *
   */
  function getChangelog()
  {
    $act = 'changelog';

    if ( false != ( $errCode = $this->_checkAuth($licenseKey, $authKey) ) )
    {
      $this->_sendError($act, $errCode);
    }
    if ( empty($_POST['version']) )
    {
      $this->_debug .= 'No info about installed version'."\n";
      $this->_sendError($act, EL_US_ERR_REQUEST);
    }

    $this->_log($act);
    $this->_send(array('changelog' => $this->_sm->getChangelog()));
  }

  // Отправляет лиенту пакет обновлений
  function getUpdate()
  {
    $act = 'update';

    if ( false != ( $errCode = $this->_checkAuth($licenseKey, $authKey) ) )
    {
      $this->_sendError($act, $errCode);
    }
    if ( empty($_POST['version']) )
    {
      $this->_debug .= 'No info about installed version'."\n";
      $this->_sendError($act, EL_US_ERR_REQUEST);
    }
    if ( false == ($ver = $this->_sm->getVersion()) )
    {
      $this->_debug .= $this->_sm->error."\n";
      $this->_sendError($act, $this->_sm->errCode);
    }
    if ( false == ($file = $this->_sm->packUpdate($_POST['modules'], $licenseKey) ) )
    {
      $this->_debug .= $this->_sm->error."\n";
      $this->_sendError($act, $this->_sm->errCode);
    }
    $this->_log($act);
    $this->_send( array('version'=>$ver, 'core'=>$file) );
  }

  /**
   * Авторизует запрос на получение версии/обновления
   * Проверяет валидность лицензии по ключу лицензии
   * Получает ключ-потверждение с URL указаного в лицензии
   * Авторизует, если ключ переданый для авторизации и ключ-подтверждение совпадают
   * Возвращает код ошибки или 0 при успешной авторзации
   *
   * @return int
   */
  function _checkAuth()
  {
    list($licenseKey, $authKey) = $this->_args;
    if (!$licenseKey || !$authKey)
    {
      return EL_US_ERR_AUTH_NOKEY;
    }
    if ( !$this->_lc->fetchByKey($licenseKey) )
    {
      return EL_US_ERR_AUTH;
    }

    // Получаем ключ аутинтефикации с сайта по url из лицензии
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->_lc->URL . '/_xml_/__authkey__');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $xml = curl_exec($ch);
    if (curl_errno( $ch ))
    {
      curl_close($ch);
      $this->_debug .= curl_error( $ch )."\n";
      return EL_US_ERR_AUTH_NOGETKEY;
    }
    curl_close($ch);
    if (!$xml)
    {
      return EL_US_ERR_AUTH_XML_NODATA;
    }
    if (!preg_match('/<key>(.+?)<\/key>/', $xml, $match))
    {
      return EL_US_ERR_AUTH_XML_NOKEY;
    }
    $authKey2 = $match[1];

    // Ключи должны совпасть
    if ( $authKey <> $authKey2 )
    {
      return EL_US_ERR_AUTH_BADKEY;
    }
    return 0;
  }

  /**
   * Отправляет клиенту сообщение об ошибке
   * Пишет в лог сообщение об ошибке и доп. сообщение
   *
   * @param string $act
   * @param int    $code
   */
  function _sendError($act, $code)
  {
    $this->_log( $act, $code );
    $this->_send( array( 'errcode' => $code, 'errstr'  => $this->_errors[$code] ) );
  }

  /**
   * Отдает клиенту XML-файл
   *
   * @param array $data
   */
  function _send($data)
  {
    header('Content-type: text/xml; charset=utf-8');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<update>\n";
    foreach ($data as $tag => $content)
    {
      // Если передается файл
      if ( 'core' == $tag && false != ($fp = fopen($content, 'r')) )
      {
        echo "<$tag type=\"file\">";
        while ($str = fgets($fp))
        {
          echo base64_encode( $str)."\n";
        }
        fclose($fp);
        @unlink($content);
      }
      else
      {
        echo "<$tag>";
        echo htmlspecialchars($content);
      }
      echo "</$tag>\n";
    }
    echo "</update>\n";
    exit;
  }

  function _onInit()
  {
    $elDir = realpath(EL_DIR);
    $dir   = realpath( $this->_conf('sourceDir') );
    if ( $dir && $dir <> $elDir )
    {
      $c = file_get_contents($dir.'/core/lib/elCore.class.php');
      if ( !preg_match("/define\s*\('EL_VER',\s*'[^']+'\)/ism", $c)  )
      {
        $dir = $elDir;
      }
    }
    else
    {
      $dir = $elDir;
    }
    $this->_lc = & elSingleton::getObj('elUpdServLicense');

    $this->_sm = & elSingleton::getObj( 'elUSSourceManager' );
    $this->_sm->init( $dir );

  }


  function _log($act, $code = 0)
  {
    $db = & elSingleton::getObj('elDb');
    if ( !$db->isTableExists('el_userv_log') )
    {
      $sql = "CREATE TABLE IF NOT EXISTS el_userv_log (
        id     int            NOT NULL auto_increment,
        lkey   char(32)       NOT NULL,
        url    varchar(250)   NOT NULL,
      	ip     char(15)       NOT NULL,
      	act    enum('version', 'changelog', 'update') NOT NULL,
      	is_ok  enum('1', '0') NOT NULL,
      	error  text           NOT NULL,
      	crtime int(11)        NOT NULL,
      	debug  text           NOT NULL,
      	PRIMARY KEY(id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
      $db->query($sql);
    }

    $err = $code > 0
      ? !empty( $this->_errors[$code] ) ? $this->_errors[$code] : 'Unknown error'
      : '';

    $logRec = & elSingleton::getObj('elUpdServLogRecord');
    $logRec->setAttr('lkey',   $this->_lc->key);
    $logRec->setAttr('url',    $this->_lc->URL);
    $logRec->setAttr('ip',     $_SERVER['REMOTE_ADDR']);
    $logRec->setAttr('act',    $act);
    $logRec->setAttr('is_ok',  $code ? '0' : '1');
    $logRec->setAttr('crtime', time());
    $logRec->setAttr('error',  $err);
    $logRec->setAttr('debug',  $this->_debug);
    $logRec->save();
  }
}

?>
