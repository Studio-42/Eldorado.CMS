<?php

// Извлекает исходники из архива дистрибутива
class elUSSourceManager
{
  var $_baseDir = '';
  var $errCode = 0;
  var $error = '';
  var $_coreDirs = array(
    'core/common.php',
//    'core/editor',
    'core/forms',
    'core/js',
    'core/install',
    'core/lib',
    'core/locale',
    'core/plugins',
    'core/services',
//    'core/stat',
    'core/styles'
    );

  function init($dir)
  {
    $this->_baseDir = $dir;
  }

  function getVersion()
  {
    if ( $this->_baseDir == realpath(EL_DIR) )
    {
      return EL_VER;
    }
    $c = file_get_contents($this->_baseDir.'/core/lib/elCore.class.php');
    if ( preg_match("/define\s*\('EL_VER',\s*'([^']+)'\s*\)/ism", $c, $m) )
    {
      return trim($m[1]);
    }
    $this->errCode = EL_US_ERR_SERVER;
    return false;
  }

  function getChangelog()
  {
    return @file_get_contents( $this->_baseDir.'/core/CHANGELOG');
  }

  // Создает пакет обновлений, сохраняет его на диск и возвращает его имя
  function packUpdate($modules,  $licenseKey)
  {
    $modules = explode(',', $modules);
    $qnt = 0;
    foreach ($modules as $module)
    {
      $module = trim($module);
      if (empty($module))
      {
        continue;
      }
      if ( !is_dir($this->_baseDir.'/core/modules/'.$module) )
      {
        $this->errCode = EL_US_ERR_REQUEST;
        $this->error = sprintf( m('Module does not exists: %s'), $module );
        return false;
      }
      $this->_coreDirs[] = 'core/modules/'.$module;
      $qnt++;
    }
    if ( !$qnt )
    {
      $this->errCode = EL_US_ERR_REQUEST;
      $this->error   = sprintf( m('No one module was requested'), $module );
      return false;
    }

    $updateFile = EL_DIR_TMP.'core-'.$licenseKey;

    $arc = & elSingleton::getObj('elArk');
    $arc->setOverwrite(true);
    $this->_coreDirs[] = './core/docs';
    if ( !$arc->createArchive($updateFile, EL_ARCTYPE_GZIP, $this->_coreDirs, $this->_baseDir) )
    {
      $this->errCode = EL_US_ERR_PACK;
      $this->error   = $arc->getError();
      return false;
    }
    return $updateFile.'.tar.gz';

  }

}

?>
