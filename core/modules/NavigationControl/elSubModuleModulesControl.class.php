<?php
/**
 * class elSubModuleModulesControl
 * позволяет получить информацию об установленных в системе модулях,
 * устанавливать новые модули или удалять ненужные
 *
 */
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFS.class.php';
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFileInfo.class.php';

class elSubModuleModulesControl extends elModule
{
    var $_mMapAdmin = array(
		'install' => array('m'=>'install', 'l'=>'Install new modules', 'g'=>'Actions', 'ico'=>'icoModulesInstall'),
		'rm'      => array('m'=>'rm')
		);
    var $_mMapConf = array();
    
    var $_modules = array();
    
    var $_locked = array(
		'Container',
		'Finder',
		'Mailer',
		'NavigationControl',
		'PluginsControl',
		'SimplePage',
		'SiteBackup',
		'SiteControl',
		'SitemapGenerator',
		// 'GAStat',
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

        $dir = EL_DIR_TMP.DIRECTORY_SEPARATOR.'install';
        if ( !elFS::mkdir($dir) )
        {
            $this->_installFailed('Could not create directory %s', $dir);
        }
		$filename = $dir.DIRECTORY_SEPARATOR.$file['name'];
        if ( !move_uploaded_file($file['tmp_name'], $filename) )
        {
            $this->_installFailed('Can not upload file "%s"', $file['name'], $dir);
        }
		// Проверяем архив и готовим дир для распаковки
        if ( !$ark->canExtract(elFileInfo::mimetype($filename), true) )
        {
			
            $this->_installFailed('File %s is not an archive file, or this archive type does not supported!', $file['name']);
        }
        
        // распаковываем архив
        if ( !$ark->extract($filename, $dir) )
        {
            $this->_installFailed('Could not extract archive file %s', $file['name'], $dir);
        }
        // проверяем наличие файла-конфига установки и его корректность
        if ( !is_file($dir.DIRECTORY_SEPARATOR.'receipt.xml') )
        {
            $this->_installFailed('Archive "%s" does not contains all required files', $file['name'], $dir);
        }
        
        $xmlConf = &elSingleton::getObj('elXmlConf', 'receipt.xml', $dir.DIRECTORY_SEPARATOR);

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
            if ( !is_file($dir.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'elModule'.$module.'.class.php') )
            {
                $report['failed'][$module] = array($localName, sprintf(m('Module %s does contains required files'), $localName) );
                continue;
            }
            // такой модуль уже установлен ?
            if ( is_file(EL_DIR_CORE.'modules'.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'elModule'.$module.'.class.php') )
            {
                $report['failed'][$module] = array($localName, sprintf(m('Module %s already installed'), $localName) );
                continue;
            }
            // версия модуля и системы совпадают?
            if ( $this->_compareVer($nfo['elVersion'], EL_VER) != -1 )
            {
                $report['failed'][$module] = array( $localName, sprintf(m('Module %s version number is not equal system one. Module version: %s, system version: %s'), $localName, $nfo['elVersion'], EL_VER) );
                continue;
            }
            // копируем модуль
            if ( !elFS::copy($dir.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$module, EL_DIR_CORE.'modules') )
            {
                $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/core/modules/'.$module, './core/modules/'.$module) );
                continue;
            }
            // копируем шаблоны если есть
			$style = $dir.DIRECTORY_SEPARATOR.'style'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$module;
            if ( is_dir($style) && !elFS::copy($style, EL_DIR_STYLES.'modules') )
            {
                $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/style/modules/'.$module, './style/modules/'.$module) );
                $this->_rmModule($module, true);
                continue;
            }
			$css = $dir.DIRECTORY_SEPARATOR.'style'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$module.'.css';
            if ( is_file($css) && !$this->_copy($css, EL_DIR_STYLES.'css') )
            {
                $report['failed'][$module] = array($localName, sprintf( m('Could not copy %s to %s!'), $dir.'/style/css/module'.$module.'.css', './style/css') );
                $this->_rmModule($module, true);
                continue;
            }
			$js = $dir.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'elModule'.$module.'.lib.js';
            // копируем js файл, если есть
            if (is_file($js) && !$this->_copy($js, EL_DIR_CORE.'js') )
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
					$f = $dir.DIRECTORY_SEPARATOR.'core'.$reqFile;
                    if ( !is_file($f) && !elFS::copy($f, EL_DIR_CORE) )
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
                if ( !is_dir(EL_DIR_CORE.DIRECTORY_SEPARATOR.'locale'.$locale) )
                {
                    continue;
                }
				$l  = $dir.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'locale'.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.'elModule'.$module.'php';
                $la = $dir.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'locale'.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.'elModuleAdmin'.$module.'php';
				
				if ($l)
				{
					elFS::copy($l, EL_DIR_CORE.'locale'.DIRECTORY_SEPARATOR.$locale);
				}
				if ($la)
				{
					elFS::copy($la, EL_DIR_CORE.'locale'.DIRECTORY_SEPARATOR.$locale);
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
        
		if ( empty($report['failed']) )
		{
			elMsgBox::put( m('New modules installation complited!') );
		}
        else
        {
			if ($report['installed'])
			{
				elMsgBox::put( m('New modules installed with errors!') );
			}
			else
			{
				elMsgBox::put( m('New modules installation failed!'), EL_WARNQ );
			}
			// elMsgBox::put( m('Some modules was not be installed! See details below.'),  sizeof($report['failed']) >= sizeof($report['installed']) ? EL_WARNQ : EL_MSGQ);
        }
        foreach ( $report['installed'] as $one )
        {
            elMsgBox::put( sprintf( m('Module "%s" - Successfully instaled!'), $one) );
        }
        foreach ( $report['failed'] as $one )
        {
            elMsgBox::put( sprintf( m('Module "%s" - Instalation failed! Reason: %s'), $one[0], $one[1]), EL_WARNQ );
        }
		elFS::rmdir($dir);
        elLocation( EL_URL.$this->_smPath );
    }
    
	function _compareVer($modVer, $sysVer)
	{
		$modVer = explode('.', $modVer);
		$sysVer = explode('.', $sysVer);
		if ($modVer[0] < $sysVer[0]) {
			return -1;
		} 
		if ($modVer[0] > $sysVer[0]) {
			return 1;
		}
		if ($modVer[1] < $sysVer[1]) {
			return -1;
		} 
		if ($modVer[1] > $sysVer[1]) {
			return 1;
		}
		if ($modVer[2] < $sysVer[2]) {
			return -1;
		} 
		if ($modVer[2] > $sysVer[2]) {
			return 1;
		}
		return 0;
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
        $form->setLabel( m('New modules installation') );
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
		$dir = EL_DIR_TMP.DIRECTORY_SEPARATOR.'tmp';
		is_dir($dir) && elFS::rmdir($dir);
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
     * Удаляет файлы модуля и запись из БД
     *
     **/
    function _rmModule($module, $quiet=false)
    {
        $modDir   = EL_DIR_CORE.'modules'.DIRECTORY_SEPARATOR.$module;
        $styleDir = EL_DIR_STYLES.'modules'.DIRECTORY_SEPARATOR.$module;
        $cssFile  = EL_DIR_STYLES.'css'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$module.'.css';
        $jsFile   = EL_DIR_CORE.'js'.DIRECTORY_SEPARATOR.$module.'.lib.js';
        
        // пытаемся удалить дир модуля
        if ( !elFS::rmdir($modDir) )
        {
            return !$quiet ? elThrow(E_USER_NOTICE, 'Could not delete directory %s', $modDir) :false;
        }
        // удаляем запись в БД
        $this->_db->query('DELETE FROM el_module WHERE module=\''.mysql_real_escape_string($module).'\'');
        $this->_db->optimizeTable('el_menu');
        // удаляем шаблоны, если есть
        if ( is_dir($styleDir) && !elFS::rmdir($styleDir) )
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