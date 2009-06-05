<?php
/**
 * class elSubModuleModulesControl
 * позволяет получить информацию об установленных в системе модулях,
 * устанавливать новые модули или удалять ненужные
 *
 */

class elSubModuleModulesControl extends elModule
{
    var $_mMapAdmin = array(
                            'install' => array('m'=>'install', 'l'=>'Install new modules', 'g'=>'Actions', 'ico'=>'icoModulesInstall'),
                            'rm'      => array('m'=>'rm')
                            );
    var $_mMapConf = array();
    
    var $_modules = array();
    
    var $_locked = array('Container',
                         'FileManager',
                         'Mailer',
                         'NavigationControl',
                         'PluginsControl',
                         'SimplePage',
                         'SiteBackup',
                         'SiteControl',
                         'SitemapGenerator',
                         'Stat',
                         'TemplatesEditor',
                         'UpdateClient',
                         'UpdateServer',
                         'UsersControl');
    
    var $_db = null;
    
    /**
     * выводит список установленных модулей
     */
    function defaultMethod()
    {
        $this->_initRenderer();
        $this->_rnd->rndModules($this->_modules, $this->_locked);
    }
    
    /**
     * устанавливает новые модули
     */
    function install()
    {
        $report = array('installed' => array(),
                        'failed'    => array());
        $form   = & $this->_makeInstallForm();
        
        if ( !$form->isSubmitAndValid() )
        {
            $this->_initRenderer();
            return $this->_rnd->addToContent( $form->toHtml() );
        }
        
        $file = $form->getElementValue('modArc');
        $ark  = & elSingleton::getObj('elArk'); 

        // Проверяем архив и готовим дир для распаковки
        if ( !$ark->canExtract(elMimeContentType($file['name']), true) )
        {
            $this->_installFailed('File %s is not an archive file, or this archive type does not supported!', $file['name']);
        }
        
        $dir = './tmp';
        if ( !is_dir($dir) && !mkdir($dir) )
        {
            $this->_installFailed('Could not create directory %s', $dir);
        }
        if ( !is_writable($dir) )
        {
            $this->_installFailed('Directory %s is not writable', $dir);
        }
        
        elRmdir($dir, true);
        $dir = $dir.'/install';
        if ( !mkdir($dir) )
        {
            $this->_installFailed('Could not create directory %s', $dir);
        }
        if ( !move_uploaded_file($file['tmp_name'], $dir.'/'.$file['name']) )
        {
            $this->_installFailed('Can not upload file "%s"', $file['name'], $dir);
        }
        // распаковываем архив
        if ( !$ark->extract($dir.'/'.$file['name'], $dir) )
        {
            $this->_installFailed('Could not extract archive file %s', $file['name'], $dir);
        }
        // проверяем наличие файла-конфига установки и его корректность
        if ( !is_file($dir.'/receipt.xml') )
        {
            $this->_installFailed('Archive "%s" does not contains all required files', $file['name'], $dir);
        }
        
        $xmlConf = &elSingleton::getObj('elXmlConf', 'receipt.xml', $dir.'/');

        if ( empty($xmlConf->groups) )
        {
            $this->_installFailed('There are no one module to intsall in archive "%s"', $file['name'], $dir);
        }
        
        // устанавливаем найденые в конфиге модули
        foreach ( $xmlConf->groups as $module=>$nfo )
        {
            // проверяем корректность конфига установки модуля
            if ( empty($nfo['elVersion']) || empty($nfo['locales']['en_US.UTF-8']) )
            {
                $report['failed'][$module] = array($module, sprintf(m('Module %s has invalid install receipt.'), $module) );
                continue;
            }
            
            $localName = !empty($nfo['locales'][EL_LOCALE]) ? $nfo['locales'][EL_LOCALE] : $nfo['locales']['en_US.UTF-8'];
            // проверяем наличие необходимых файлов
            if ( !is_file($dir.'/core/modules/'.$module.'/elModule'.$module.'.class.php') )
            {
                $report['failed'][$module] = array($localName, sprintf(m('Module %s does contains required files'), $localName) );
                continue;
            }
            // такой модуль уже установлен ?
            if ( is_file('./core/modules/'.$module.'/elModule'.$module.'.class.php') )
            {
                $report['failed'][$module] = array($localName, sprintf(m('Module %s already installed'), $localName) );
                continue;
            }
            // версия модуля и системы совпадают?
            if ( EL_VER != $nfo['elVersion'] )
            {
                $report['failed'][$module] = array( $localName, sprintf(m('Module %s version number is not equal system one. Module version: %s, system version: %s'), $localName, $nfo['elVersion'], EL_VER) );
                continue;
            }
            // копируем модуль
            if ( !$this->_copy($dir.'/core/modules/'.$module, './core/modules/'.$module) )
            {
                $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/core/modules/'.$module, './core/modules/'.$module) );
                continue;
            }
            // копируем шаблоны если есть
            if ( !$this->_copy($dir.'/style/modules/'.$module, './style/modules/'.$module) )
            {
                $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/style/modules/'.$module, './style/modules/'.$module) );
                $this->_rmModule($module, true);
                continue;
            }
            if ( !$this->_copy($dir.'/style/css/module'.$module.'.css', './style/css') )
            {
                $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/style/css/module'.$module.'.css', './style/css') );
                $this->_rmModule($module, true);
                continue;
            }
            // копируем js файл, если есть
            if ( !$this->_copy($dir.'/core/js/elModule'.$module.'.lib.js', './core/js') )
            {
                $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/core/js/elModule'.$module.'.lib.js', './core/js') );
                $this->_rmModule($module, true);
                continue;
            }
            // если в конфиге указаны необходимые файлы из других дир. - копируем их
            if ( !empty($nfo['requiredFiles']) && is_array($nfo['requiredFiles']) )
            {
                foreach ( $nfo['requiredFiles'] as $reqFile )
                {
                    if ( !is_file($dir.'/core/'.$reqFile) || !$this->_copy($dir.'/core/'.$reqFile, './core/'.dirname($reqFile)) )
                    {
                        $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/core/'.$reqFile, './core/'.dirname($reqFile)) );
                        $this->_rmModule($module, true);
                        break;
                    }
                }
                if ( !empty($report['failed'][$module]) )
                {
                    continue;
                }
            }
            // копируем файлы локализаций и добавляем название модуля в core/locale/*/elModulesNames.php если его там нет
            foreach ( $nfo['locales'] as $locale=>$moduleName )
            {
                if ( !is_dir('./core/locale/'.$locale) )
                {
                    continue;
                }
                if ( !$this->_copy($dir.'/core/locale/'.$locale.'/elModule'.$module.'.php', './core/locale/'.$locale)
                ||   !$this->_copy($dir.'/core/locale/'.$locale.'/elModuleAdmin'.$module.'.php', './core/locale/'.$locale) )
                {
                    $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/core/locale/'.$locale.'/elModule'.$module.'.php', './core/locale/'.$locale) );
                    $this->_rmModule($module, true);
                    continue;
                }
                $this->_updateLocalName($locale, $module, $moduleName);
            }
            // записывем название модуля в БД
            $sql = 'REPLACE INTO el_module SET module=\''.mysql_real_escape_string($module).'\', '
                .'descrip=\''.mysql_real_escape_string($localName).'\', '
                .'multi=\''.intval($nfo['multi']).'\', '
                .'search=\''.intval($nfo['search']).'\' ';
            $this->_db->query($sql);
            $report['installed'][$module] = $localName;
        }
        // формируем отчет
        elMsgBox::put( m('New modules instalation complited!') );
        if ( !empty($report['failed']) )
        {
            elMsgBox::put( m('Some modules was not be installed! See details below.') );
        }
        foreach ( $report['installed'] as $one )
        {
            elMsgBox::put( sprintf( m('Module "%s" - Successfully instaled!'), $one) );
        }
        foreach ( $report['failed'] as $one )
        {
            elMsgBox::put( sprintf( m('Module "%s" - Instalation failed! Reason: %s'), $one[0], $one[1]) );
        }
        elLocation( EL_URL.$this->_smPath );
    }
    
    
    /**
     * удаляет модуль, если он не используется и не является необходимым модулем
     */
    function rm()
    {
        $module = trim($this->_arg());
        //такого модуля нет
        if ( empty($this->_modules[$module]) )
        {  
            elThrow(E_USER_WARNING, 'Module "%s" does not exists', $module, EL_URL.$this->_smPath);
        }
        // модуль неоходим системе
        if ( in_array($module, $this->_locked) )
        {  
            elThrow(E_USER_WARNING, 'Module "%s" could not be removed! This module is a part of system core.', $module, EL_URL.$this->_smPath);
        }
        // модуль используется
        if ( $this->_modules[$module]['used'] )
        {
            elThrow(E_USER_WARNING, 'Module "%s" could not be removed! There are some pages used this module.', $module, EL_URL.$this->_smPath);
        }
        // пытаемся удалить модуль
        if ( !$this->_rmModule($module) )
        {
            elThrow(E_USER_WARNING, 'Could not remove module', $module, EL_URL.$this->_smPath);
        }
        elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), m('Module'), $this->_modules[$module]['name']) );
        elLocation( EL_URL.$this->_smPath );
    }
    
    /*****************************************/
    //              PRIVATE
    /****************************************/
    /**
     * Создает форму для загрузки архива с модулями для установки
     *
     **/
    function &_makeInstallForm()
    {
        $form = & elSingleton::getObj( 'elForm', 'moduleConf' );
	$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
        $form->setLabel( m('New modules instalation') );
        $form->add( new elCData('c1', m('You should upload new modules archive to install new modules into the system. Modules version must be equal to the system version number.')) );
        $f = & new elFileInput('modArc', m('New modules archive file'));
        $f->setFileExt( array('tar.gz', 'tgz') );
        $form->add( $f );
        return $form;
    }
    
    /**
     * При неудачном завершении устновки выдает сообщения об ошибке
     * и очищает дир, где распаковывался архив с новыми модулями (если необходимо)
     *
     **/
    function _installFailed($msg, $arg, $installDir='')
    {
        if ( !empty($installDir) )
        {
            elRmdir($installDir);
        }
        elThrow(E_USER_WARNING, 'New modules instalation was failed!');
        elThrow(E_USER_WARNING, $msg, $arg, EL_URL.$this->_smPath);
    }
    
    /**
     * Добавляет название модуля во все файлы core/locale/ /elModulesNames.php 
     *
     **/
    function _updateLocalName($locale, $key, $name)
    {
        $file = './core/locale/'.$locale.'/elModulesNames.php';
        if ( is_file($file) && false != ($cont = file_get_contents($file) ) )
        {
            if ( !preg_match("/elMsg\['".$key."'] = .*/", $cont, $m) )
            {
                $cont = preg_replace('/\?\>/', '\$elMsg[\''.$key.'\'] = \''.$name.'\';'."\n?>\n", $cont);
                if ( $fp = fopen($file, 'w') )
                {
                    fwrite($fp, $cont);
                    fclose($fp);
                }
            }
        }
    }
    
    /**
     * копирует src в trg, если src существует
     * возвращет false если src существует и не удалось скопировать
     **/
    function _copy($src, $trg)
    {
        if ( is_dir($src) )
        {
            return elCopyTree($src, $trg);
        }
        elseif ( is_file($src) )
        {
            return copy($src, $trg.'/'.basename($src));
        }
        return true;
    }

    /**
     * Удаляет файлы модуля и запись из БД
     *
     **/
    function _rmModule($module, $quiet=false)
    {
        $modDir   = './core/modules/'.$module;
        $styleDir = './style/modules/'.$module;
        $cssFile  = './style/css/module'.$module.'.css';
        $jsFile   = './core/js/'.$module.'.lib.js';
        
        // пытаемся удалить дир модуля
        if ( is_dir($modDir) && !elRmdir($modDir) )
        {
            return !$quiet ? elThrow(E_USER_NOTICE, 'Could not delete directory %s', $modDir) :false;
        }
        // удаляем запись в БД
        $this->_db->query('DELETE FROM el_module WHERE module=\''.mysql_real_escape_string($module).'\'');
        $this->_db->optimizeTable('el_menu');
        // удаляем шаблоны, если есть
        if ( is_dir($styleDir) && !elRmdir($styleDir) )
        {
            if ( !$quiet )
            {
                elThrow(E_USER_NOTICE, 'Could not delete directory %s', $styleDir);    
            }
        }
        if ( is_file($cssFile) && !unlink($cssFile) )
        {
            if ( !$quiet )
            {
                elThrow(E_USER_NOTICE, 'Could not delete file "%s"', $cssFile);    
            }
        }
        // удаляем js- файл, если есть
        if ( is_file($jsFile) && !unlink($jsFile) )
        {
            if ( !$quiet )
            {
                elThrow(E_USER_NOTICE, 'Could not delete file "%s"', $jsFile);    
            }
        }
        // удаляем файлы локализации
        $d = dir('./core/locale');
        while ( $entr = $d->read() )
        {
            if ( is_dir($d->path.'/'.$entr) )
            {
                $f1 = $d->path.'/'.$entr.'/elModule'.$module.'.php';
                $f2 = $d->path.'/'.$entr.'/elModuleAdmin'.$module.'.php';
                if ( is_file($f1) && !unlink($f1) )
                {
                    if ( !$quiet )
                    {
                        elThrow(E_USER_NOTICE, 'Could not delete file "%s"', $f1);     
                    }
                }
                if ( is_file($f2) && !unlink($f2) )
                {
                    if ( !$quiet )
                    {
                        elThrow(E_USER_NOTICE, 'Could not delete file "%s"', $f2);     
                    }
                }
            }
        }
        $d->close();
        return true;
    }
    
    /**
     * Инициализация объекта - загружаем список установленных модулей
     **/ 
    function _onInit()
    {
        $this->_db = & elSingleton::getObj('elDb');
        $sql = 'SELECT  md.module, IF(md.descrip!="", md.descrip, md.module) AS name, IF(mn.id IS NOT NULL, 1, 0) AS used'
                .' FROM el_module AS md LEFT JOIN el_menu AS mn ON mn.module=md.module GROUP BY md.module ORDER BY name';
        $this->_modules = $this->_db->queryToArray($sql, 'module');
    }
    
}

?>