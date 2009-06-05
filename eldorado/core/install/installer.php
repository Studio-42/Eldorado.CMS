<?php
session_start();
error_reporting(0);
set_time_limit(0);
$GLOBALS['elInstallerVer'] = '0.9.6';
$GLOBALS['elInstLangs'] = array('en_US' => 'English', 'ru_RU' => 'Русский');
$GLOBALS['elInstLang']  = 'en_US';
$GLOBALS['elInstMsg'] = array(
	'en_US' => array(),
	'ru_RU' => array(
		'Back'                                  => 'Назад',
		'Finish'                                => 'Завершить',
		'Continue'                              => 'Продолжить',
		'Decline'                               => 'Не принимаю',
		'Accept'                                => 'Принимаю',

		'Select installation language'          => 'Выбор языка установки',
		'Please, select installation language'  => 'Пожалуйста, выберите язык установки',
		'Wellcome to Eldorado.CMS installation' => 'Добро пожаловать в установку Eldorado.CMS',
		'Installation type'                     => 'Тип установки',
		'Site design installation'              => 'Установка шаблонов дизайна сайта',
		'Root password'                         => 'Пароль администратора сайта',
		'Installation complite'                 => 'Завершение установки',
		
		'Thanks for choosing our web-site content management system Eldorado.CMS.' => 
					'Благодарим за выбор нашей системы управления сайтами Eldorado.CMS.',
		'Before install Eldorado.CMS You should read this product\'s license text and accept it.' => 
					'До начала установки Eldorado.CMS вам следует ознакомиться с лицензией продукта и принять ее.',
		
		'Please, select installation type'                        => 'Пожалуйста, выберите тип установки',
		'Eldorado.CMS core installation'                          => 'Установка ядра и модулей Eldorado.CMS',
		'Standalone site installation'                            => 'Установка одиночного сайта',
		'Multi-site instalation. System core will be installed.'  => 'Мульти-сайтовая установка с установкой ядра системы',
		'Multi-site installation. System core already installed.' => 'Мульти-сайтовая установка. Ядро системы уже установлено',
		'For detailed information about installation types appeal to documentation.' => 
					'Для получения подробной информации о типах установки обратитесь к документации',
		'Please, upload archive file with Eldorado.CMS core. (eldorado.CMS-ver-xxx.tar.gz)' => 
					'Загрузите архив с ядром Eldorado.CMS. (eldorado.CMS-ver-xxx.tar.gz)',
		'Please, specify directory name in which Eldorado.CMS core will be installed' => 
					'Укажите имя директории, в которую будет установлено ядро системы Eldorado.CMS',
		'Please, specify directory name where Eldorado.CMS core was installed' => 
					'Укажите имя директории, в содержащей ядро системы Eldorado.CMS',
		'Base site configuration'    => 'Первоначальная настройка сайта',
		'MySQL data base parameters' => 'Параметры базы данных MySQL',
		'Db host'                    => 'Сервер MySQL (host)',
		'Db socket'                  => 'Сокет (если используется)',
		'Db user name'               => 'Имя пользователя',
		'Db user password'           => 'Пароль',
		'Db name'                    => 'База данных',
		'Site interface language'    => 'Язык интерфейса сайта',
		'Interface language'         => 'Язык интерфейса',
		'Other parameters'           => 'Прочие параметры',
		'Site name'                  => 'Название сайта',
	
		'Site design installation'            => 'Установка шаблонов дизайна сайта',
		'You should upload design templates archive from distr (eldorado-style-xxx.tar.gz) or select one from styles list available in core.' => 'Вы можете загрузить архив с шаблонами дизайна сайта из вашего дистрибутива системы (если таковой имеется в вашем дистрибутиве) или выбрать из списка один из стилей дизайна, доступных в ядре системы.',
		'For more information about design templates usage, please, read documentation' => 
					'Для получения подробной информации о шаблонах дизайна обратитесь к документации',
		'Upload archive'                        => 'Загрузите архив',
		'Or select style from list'             => 'или выберите стиль из списка',
		'Main site administrator has login "root". On this step You should set password and Master Password for user root' => 
					'Главный администратор сайта имеет имя пользователя (login) - "root". На данном шаге Вы должны создать пароль и Мастер-Пароль для пользователя root',
		'Password may contains only latin alfanum chars, digits or underscore and dash symbols and must be from 4 till 20 chars.' => 'Пароли могут содержать только латинские буквы, цифры и знаки подчеркивания и тире. Длинна паролей должна быть от 4 до 20 знаков.',
		'For more information about master password, please, read documentation' => 
					'Для получения подробной информации о Мастер-Пароле обратитесь к документации',
		'Enter password for user root'          => 'Введите пароль для пользователя root',
		'Enter Master Password for user root'   => 'Введите Мастер-Пароль для пользователя root',
		'Password'                              => 'Пароль',
		'Password confirm'                      => 'Подтверждение пароля',
		'Master Password'                       => 'Мастер-пароль',
		'Master Password confirm'               => 'Подтверждение Мастер-Пароля',
		'Installation complite'                 => 'Завершение установки',
		//errors
		'Server configuration error!'           => 'Ошибка конфигурации сервера',
		'Installation interrupted!'             => 'Установка прервана!',
		'Installer internal error!'             => 'Внутренняя ошибка программы установки!',
		'Product licence was not accepted!'     => 'Лицензионное соглашение не принято!',
		'Windows OS found! Sorry, but Eldorado.CMS work only on *nix OS or Mac OS X!' => 
					'Обнаружена операционная система Windows! Eldorado.CMS работает только на *nix или  Mac OS X!',
		'Current directory %s has no write permissions' => 
					'Отсутствуют права доступа на запись в текущую директорию %s',
		'PHP version %s was found! You should have PHP version 4.x'         => 'Обнаружена версия %s PHP! Требуется версия 4.x',
		'MySQL support in PHP was not found! Check Your PHP configuration!' => 
					'Не удается обнаружить поддержку базы данных MySQL! Проверьте настройки PHP!',
		'Could not access system command "%s"' => 'Не удается получить доступ к системной команде "%s"',

		'You are not accepted product licence. Installation interrupted!' => 
					'Вы не приняли условия лицензии продукта! Установка прервана!',
		'Directory ./core does not contains valid Eldorado.CMS core files! Please, check Your installation files!' => 
					'Директория ./core не содержит небходимых файлов ядра Eldorado.CMS! Проверьте ваши установочные файлы!',
		'Could not create direcories tree'                   => 'Не удалось создать дерево директорий',
		'Could not copy nessesery files from ./core/install' => 'Не удалось скопировать необходимые файлы из ./core/install',
		'Could not create file %s'                           => 'Не удалось создать файл %s',
		'Please, fill all fields marked with *'              => 'Пожалуйста, заполните все поля отмеченные *',
		'Could not access file "%s"'                         => 'Не удалось получить доступ к файлу "%s"',
		'File must have tar.gz or tgz extension'             => 'Файл должен иметь расширение tar.gz или tgz',
		'Upload file error'                                  => 'Ошибка загрузки файла',
		'Archive "%s" expanded, but does not contains "%s" folder!' => 
					'Архив "%s" успешно распакован, но не обрнаружена директория "%s"!',
		'Invalid style was selected!'                        => 'Выбран некорретный стиль',
		'Could not create symlink to "%s"'                   => 'Не удается создать символическую ссылку на "%s"',
		'You should upload style\'s archive or select one from list' 
			=> 'Загрузите архив с шаблонами дизайна или выберите стиль из списка',
		'Field "%s" contains invalid chars or has invalid lenght' => 
					'Поле "%s" содержит недопустимые символы или имеет некорректную длину',
		'Fields Password and Password confirm not equal'     => 'Пароль и подтерждение пароля не одинаковы',
		'Fields Master Password and Master Password confirm not equal' => 
					'Мастер-Пароль и подтверждение Мастер-Пароля не одинаковы',
		'Could not save password'                            => 'Не удалось сохранить пароль',
		'Expanding archive "%s" error! Command return value:%s and additional output: %s' => 
					'Ошибка распаковки архива "%s"! Команда распаковки вернула значение: %s и дополнительную информацию: %s',
		'File was not sent'                                  => 'Файл не был отправлен',
		'Path to Eldorado.CMS core could not be empty!'      => 'Путь к ядру системы Eldorado.CMS не может быть пустым!',
		'Directory %s already exists!'                       => 'Директория "%s" уже существует!',
		'Could not move Eldorado.CMS core to directory %s!'  => 
					'Не удалось переместить ядро системы Eldorado.CMS в директорию %s!',
		'Eldorado.CMS core directory %s does not exists'     => 'Директория с ядром системы Eldorado.CMS не существует',
		'Symlink ./core already exists but does not point to %s' => 
					'Символическая ссылка ./core уже существует, но указывает на %s',
		'File ./core already exists'                         => 'Файл ./core уже существует',
		'Could not save master password into file'           => 'Не удалось сохранить Мастер-Пароль в файле',
		'Can not connect to db on host %s'                   => 'Не удалось соединиться с сервером баз данных %s',
		'Could not create MySQL database "%s"! MySQL says: %s' => 'Не удалось создать базу данных "%s"! MySQL сказала: %s',
		'Can not change db to %s'                            => 'Не удалось переключиться на базу данных %s',
		'Could not read file "%s" or file is empty'          => 'Не удалось прочесть файл "%s" или файл пуст',
		'Please, put in site directory file named .htaccess with following lines: <br />%s' => 
					'Не удалось создать файл ./.httaccess. Для корректной работы сайта поместите в корневую директорию файл .htaccess следующего содержания: <br />%s'
		)
	);
	
$GLOBALS['elLicenceTxt'] = array(
	'en_US' => "Eldorado.CMS licence", 
	'ru_RU' => "Лицензионное соглашение на использование системы управления сайтом Eldorado.CMS

Прочтите внимательно нижеизложенное, прежде чем устанавливать, копировать или иным образом использовать приобретенный продукт 'Система управления Eldorado.CMS'. Любое использование Вами приобретенного продукта, в том числе его установка и копирование, означает Ваше согласие с условиями приведенного ниже Лицензионного соглашения.

Настоящее лицензионное соглашение (далее Соглашение) является юридическим документом, заключаемым между Вами, конечным пользователем (физическим или юридическим лицом) (далее Пользователь), и  студия «Терра Дизайн» (далее Студия) относительно программного продукта «Система управления Eldorado.CMS» (далее Система или Программное обеспечение).

Если Вы не согласны с условиями настоящего лицензионного соглашения, вы не имеете права использовать данную Программу. 


Лицензионное соглашение вступает в силу с момента приобретения или установки продукта и действует на протяжении всего срока использования продукта. 

1. ПРЕДМЕТ ЛЕЦЕНЗИОННОГО СОГЛАШЕНИЯ И УСЛОВИЯ ИСПОЛЬЗОВАНИЯ

•	Предметом настоящего лицензионного соглашения является право использования одной копии системы управления Eldorado.CMS.

•	В рамках одной копии системы управления пользователю разрешается создавать неограниченное, число сайтов на различных языках в рамках одного проекта или домена. 

•	Вы обязуетесь не распространять Систему управления Eldorado.CMS. Под распространением Программного обеспечения понимается тиражирование или предоставление доступа третьим лицам к воспроизведенным в любой форме компонентам Системы, в том числе сетевыми и иными способами, а также путем их продажи, проката, сдачи внаем или предоставления взаймы. Тиражированием не считается изготовление одной или нескольких резервных копий базы данных программы в целях обеспечения безопасности или архивации.

•	Конечный пользователь имеет право вносить любые изменения в код системы, добавлять и удалять файлы. Кроме изменения или удаления любой информации об авторских правах.

•	Запрещается использование системы управления в любых случаях, которые, нарушают законодательство РФ.

•	Данное соглашение распространяется на все версии и компоненты системы управления Eldorado.CMS, а так же обновления предоставляемые Пользователю, в период гарантийного обслуживания.



2. АВТОРСКИЕ И ИМУЩЕСТВЕННЫЕ ПРАВА


•	Все авторские и имущественные права на данную Систему управления принадлежат исключительно авторам Системы. Права на продажу и тиражирование системы принадлежат студии Терра Дизайн. Вам, конечному Пользователю, предоставляется неисключительное право, т.е. именная, непередаваемая и неисключительная Лицензия на использование Программы в указанных в документации целях и при соблюдении приведенных ниже условий. Лицензия предоставляется Вам, только Вам и никому больше, если на то нет письменного согласия Студии. 

•	Все права интеллектуальной собственности на информационное содержание, которое не является частью программы, но, доступ к которому предоставляет программа, принадлежат владельцам прав на это содержание и защищены законами об авторском праве и другими законами и международными соглашениями о правах на интеллектуальную собственность.


3. ГАРАНТИЙНЫЕ ОБЯЗАТЕЛЬСТВА

•	Студия обслуживает работоспособность Системы в течение 12 (двенадцати) месяцев со дня ее покупки при условии, что она используется с аппаратными средствами, операционными системами и серверами баз данных, для которых она была разработана, и в полном соответствии с Руководством по эксплуатации. 

•	В течении срока гарантийного  обслуживания Студия предоставляет:

o	Бесплатные обновление системы.
o	Консультации пользователя по телефону или электронной почте в рабочее время с 9 до 18 часов по Московскому времени.
o	Проведение консультационно-обучающих семинаров по работе с системой управления Eldorado.CMS, при наборе группы более 10 человек в определенно назначенное время.

•	Единственным гарантийным обязательством студии Терра Дизайн является бесплатное устранение неисправностей системы, не позволяющих ее использование по прямому назначению. Получение любых необходимых для устранения неисправностей исправлений системы в электронном виде или на любых других носителях осуществляется силами конечного Пользователя и за его счет.

•	За исключением вышесказанного, не существует никаких других явно выраженных или подразумеваемых гарантий в отношении Системы или ее составных частей, в том числе, гарантий пригодности использования Системы непосредственно для Ваших конкретных целей. 

•	Студия не несет ответственности за работу системы, в которую были внесены изменения Вами или третьими лицами. Студия в праве отказать в обслуживании в течении гарантийного периода если сбой в работе системы управления произошел по вине конечного пользователя.

"
	);	
	
$GLOBALS['elInstErrors'] = array();
	
function m($str)
{
	return !empty($GLOBALS['elInstMsg'][$GLOBALS['elInstLang']][$str])
		? $GLOBALS['elInstMsg'][$GLOBALS['elInstLang']][$str]
		: $str;
}

function elThrow($errLevel, $msg, $params=null)
{
	$GLOBALS['elInstErrors'][] = vsprintf(m($msg), $params);
}

function elDebug($msg) 
{ 
	//echo nl2br($msg);
}

$installer = & new elInstaller;
$installer->run();

class elInstallerConf
{
	var $dbLink       = null;
	var $reqConfValid = true;  
	var $tar          = '';
	var $instType     = 1;
	var $dirTree = array('backup', 'cache', 'conf', 'log', 'storage', 'storage/pageIcons');
	var $coreTree = array('editor','forms', 'lib', 'install', 'install/install.sql', 'install/index.php', 'install/counter.php', 'install/main.conf.xml');	

	function elInstallerConf()
	{
		if ( stristr(PHP_OS, 'win') && 'darwin' != strtolower(PHP_OS) )
		{
			$this->_reqConfInvalid('Windows OS found! Sorry, but Eldorado.CMS work only on *nix OS or Mac OS X!');
		}
		if ( 4 != substr(PHP_VERSION, 0, 1) )
		{
			$this->_reqConfInvalid('PHP version %s was found! You should have PHP version 4.x', PHP_VERSION);
		}
		if ( !function_exists('mysql_connect'))
		{
			dl('mysql');
			if ( !function_exists('mysql_connect'))
			{
				$this->_reqConfInvalid('MySQL support in PHP was not found! Check Your PHP configuration!');
			}
		}
		$this->dir = getcwd().'/';
		if (!is_writable($this->dir) )
		{
			$this->_reqConfInvalid('Current directory %s has no write permissions', $this->dir);
		}
		$which = exec('which which');
		if (!strstr($which, '/which'))
		{
			$this->_reqConfInvalid('Could not access system command "%s"', 'which');
		}
		$this->tar = exec('which tar');
		if (!strstr($this->tar, '/tar'))
		{
			$this->_reqConfInvalid('Could not access system command "%s"', 'tar');
		}
	}
	
	
	function getLangsList()
	{
		$locales = array_merge_recursive( glob('./core/locale/*', GLOB_ONLYDIR), glob('./local/locale/*', GLOB_ONLYDIR));
		$langs = array();
		foreach ( $locales as $l )
		{
			$lang   = substr(basename($l), 0, 5); 
			$region = substr(basename($l), 3, 2);
			$langs[$lang] = (!empty($GLOBALS['elInstLangs'][$lang]) ? $GLOBALS['elInstLangs'][$lang] : $lang).' ('.$region.')';
		}
		return !empty($langs) ? $langs : array('en_US' => 'English');
	}
	
	function getStylesList()
	{
		include_once('./core/locale/'.$GLOBALS['elInstLang'].'.UTF-8/elInstall.php');
		$styles = array();
		$list = glob('./core/styles/*', GLOB_ONLYDIR);
		foreach ( $list as $d)
		{
			$d = basename($d);
			$styles[$d] = !empty($elStyleName[$d]) ? $elStyleName[$d] : $d;
		}
		
		return $styles;
	}
	
	/**
	 * upload/extract core, create base directory tree and copy config files
	 *
	 * @return bool
	 */
	function installCore()
	{
		if ( $this->instType < 3 && !$this->_uploadCore() ) //upload and extract core
		{
			return false;
		}
		if ( 2 == $this->instType && !$this->_installType2() )
		{
			return false;
		}
		elseif ( 3 == $this->instType && !$this->_installType3() )
		{
			return false;
		}
		
		if ( !$this->_checkCoreInstallation() )
		{
			if ( 1 < $this->instType )
			{
				@unlink('./core');
			}
			 return elThrow(E_USER_WARNING, 
			 	'Directory ./core does not contains valid Eldorado.CMS core files! Please, check Your installation files!');
		}
		// create directories tree
		foreach ($this->dirTree as $dir)
		{
			if ( !file_exists('./'.$dir) && !mkdir('./'.$dir, 0775) )
			{
				return elThrow(E_USER_WARNING, 'Could not create direcories tree') ;
			}
		}
		//copy config files
		if (!copy('./core/install/index.php',     './index.php') 
		||  !copy('./core/install/main.conf.xml', './conf/main.conf.xml')
		||  !copy('./core/install/counter.php',   './counter.php')
		||  !copy('./core/install/install.sql',   './conf/install.sql') )
		{
			return elThrow(E_USER_WARNING, 'Could not copy nessesery files from ./core/install');
		}
		if ( !$this->createHtaccess('./storage', 'RewriteEngine Off'))
		{
			elThrow(E_USER_WARNING, 'Could not create file %s', './storage/.htaccess');
		}
		if ( !$this->createHtaccess('./backup', 'RewriteEngine Off'))
		{
			elThrow(E_USER_WARNING, 'Could not create file %s', './backup/.htaccess');
		}
		return true;
	}
	
		
	function configure()
	{
		if ( empty($_POST['host']) || empty($_POST['user']) || empty($_POST['pass']) || empty($_POST['name']))
		{
			return elThrow(E_USER_WARNING, 'Please, fill all fields marked with *' );
		}
		$host   = trim($_POST['host']);
		$sock   = trim($_POST['sock']);
		$user   = trim($_POST['user']);
		$pass   = trim($_POST['pass']);
		$db     = trim($_POST['name']);
		$langs  = $this->getLangsList();
		$locale = !empty($langs[$_POST['lang']]) ? $_POST['lang'].'.UTF-8' : 'en_US.UTF-8';
		
		if (!include_once './core/lib/elDbInstall.class.php' )
		{
			return elThrow(E_USER_WARNING, 'Could not access file "%s"', './core/lib/elDbInstall.class.php');
		}
		$this->db = & new elDbInstall($user, $pass, $db, $host, $sock); 
		if ( !$this->db->initDb() )
		{
			return false;
		}
		
		$this->db->localizeDb($locale);
		
		if ( !include_once './core/lib/elXmlConf.class.php')
		{
			return elThrow(E_USER_WARNING, 'Could not access file "%s"', './core/lib/elXmlConf.class.php' );
		}
		$conf = & new elXmlConf('main.conf.xml');
		$conf->set('host',   $host,   'db');
		$conf->set('sock',   $sock,   'db');
		$conf->set('user',   $user,   'db');
		$conf->set('pass',   $pass,   'db');
		$conf->set('db',     $db,     'db');
		$conf->set('locale', $locale, 'common');
		if ( !empty($_POST['siteName']) )
		{
			$conf->set('siteName', trim($_POST['siteName']), 'common');
		}
		if ( !$conf->save() || !empty($GLOBALS['elInstErrors']) )
		{
			return false;
		}
		return true;
	}

	function installStyle()
	{
		if ( !empty($_FILES['styleFile']) && !empty($_FILES['styleFile']['size']) )
		{
			if ( !preg_match('/\.tgz$/i',     $_FILES['styleFile']['name']) 
			&&   !preg_match('/\.tar\.gz$/i', $_FILES['styleFile']['name']) )
			{
				return elThrow(E_USER_WARNING, 'File must have tar.gz or tgz extension') ;
			}
			if (!move_uploaded_file($FILES['styleFile']['tmp_name'], './'.$FILES['styleFile']['name']))
			{
				return elThrow(E_USER_WARNING, 'Upload file error');
			}
			if ( !$this->_expandArc('./'.$FILES['styleFile']['name']) )
			{
				return false; 
			}
			if ( !file_exists('./style') || !is_dir('./style') )
			{
				return elThrow(E_USER_WARNING, 'Archive "%s" expanded, but does not contains "%s" folder!', 
											array($FILES['styleFile']['name'], 'style'));
			}
			$this->_copyPageIcons();
		}
		elseif ( !empty($_POST['style']) )
		{
			$style = trim($_POST['style']);
			if (!is_dir('./core/styles/'.$style))
			{
				return elThrow(E_USER_WARNING, 'Invalid style was selected!');
			}
			if (!symlink('./core/styles/'.$style, './style'))
			{
				return elThrow(E_USER_WARNING, 'Could not create symlink to "%s"', './core/styles/'.$style);
			}
			$this->_copyPageIcons();
		}
		else
		{
			return elThrow(E_USER_WARNING, 'You should upload style\'s archive or select one from list');
		}
		return true;
	}
	
	function _copyPageIcons()
	{
		if ( !is_dir('./storage') || !is_writable('./storage') || !is_dir('./style/pageIcons') )
		{
			return;
		}
		if ( !is_dir('./storage/pageIcons') && !@mkdir('./storage/pageIcons', 0775) )
		{
			return;
		}
		$d = dir('./style/pageIcons');
		if ( empty($d->handle) )
		{
			return;
		}
		while ( false !== ($entr = $d->read()) )
		{
			if (is_file($d->path.'/'.$entr))
			{
				copy($d->path.'/'.$entr, './storage/pageIcons/'.$entr);
			}
		}
		$d->close();
	}
	
	function savePasswd()
	{
		if ( empty($_POST['p1']) || empty($_POST['p2']) || empty($_POST['p1']) || empty($_POST['p2']))
		{
			return elThrow(E_USER_WARNING, 'Please, fill all fields marked with *' );
		}
		$ps = array('p1'=>m('Password'), 'p2'=>m('Password confirm'), 'mp1'=>m('Master Password'), 'mp2'=>m('Master Password confirm') );
		foreach ($ps as $p=>$l)
		{
			${$p} = trim($_POST[$p]);
			if ( !preg_match('/^[a-z0-9\-_]{4,20}$/i', ${$p}))
			{
				return elThrow(E_USER_WARNING, 'Field "%s" contains invalid chars or has invalid lenght', $l);
			}
		}
		if ($p1 != $p2)
		{
			return elThrow(E_USER_WARNING, 'Fields Password and Password confirm not equal');
		}
		if ($mp1 != $mp2)
		{
			return elThrow(E_USER_WARNING, 'Fields Master Password and Master Password confirm not equal');
		}
		if ( !@include_once('./core/lib/elXmlConf.class.php') )
		{
			return elThrow(E_USER_WARNING, 'Could not access file "%s"', './core/lib/elXmlConf.class.php');
		}
		if (!@include_once './core/lib/elDbInstall.class.php' )
		{
			return elThrow(E_USER_WARNING, 'Could not access file "%s"', './core/lib/elDbInstall.class.php');
		}
		$conf = & new elXmlConf('main.conf.xml');
		$this->db = & new elDbInstall($conf->get('user', 'db'), $conf->get('pass', 'db'), $conf->get('db', 'db'), 
																	$conf->get('host', 'db'), $conf->get('sock', 'db'));
		if ( !$this->db->connect() )
		{
			return false;
		}
		if ( !$this->db->query('UPDATE el_user SET pass=MD5("'.$p1.'" ) WHERE uid=1') )
		{
			return elThrow(E_USER_WARNING, 'Could not save password');
		}
		$this->db->close();
		//failed to save MP is not an error
		if ( false != ($fp = fopen('./conf/mp', 'w')) )
		{
			fwrite($fp, md5($mp1)."\n");
			fclose($fp);
		}
		return true;
	}
	
	
	////////////
	
	function _expandArc($file)
	{
		$cmd   = $this->tar.' xzf '.escapeshellarg($file); 
		$otput = array(); 
		$ret   = null;
		$res   = exec($cmd, $output, $ret); 
		@unlink($file);
		if ( 0 <> $ret )
		{
			return elThrow(E_USER_WARNING, 'Expanding archive "%s" error! Command return value:%s and additional output: %s',
											array($file, $ret, implode('<br />', $output)) );
		}
		return true;
	}
	
	function _uploadCore()
	{
		if (empty($_FILES['coreFile']['name']) || empty($_FILES['coreFile']['size']))
		{
			return elThrow(E_USER_WARNING, 'File was not sent') ;
		}
		if ( !preg_match('/\.tgz$/i', $_FILES['coreFile']['name']) && !preg_match('/\.tar\.gz$/i', $_FILES['coreFile']['name']) )
		{
			return elThrow(E_USER_WARNING, 'File must have tar.gz or tgz extension') ;
		}
		if (!move_uploaded_file($_FILES['coreFile']['tmp_name'], $this->dir.$_FILES['coreFile']['name']))
		{
			return elThrow(E_USER_WARNING, 'Upload file error');
		}
		return $this->_expandArc('./'.$_FILES['coreFile']['name']);
	}
	
	function _getCorePath()
	{
		if ( empty($_POST['corePath']) )
		{
			return elThrow(E_USER_WARNING, 'Path to Eldorado.CMS core could not be empty!') ;
		}
		$corePath = '/' == substr($_POST['corePath'], 0, -1) 
				? substr($_POST['corePath'], 0, strlen($_POST['corePath']))
				: $_POST['corePath'];
		$testDirs     = $this->dirTree;
		$testDirs[]   = '.';
		$testDirs[]   = './style';
		foreach ( $testDirs as $dir )
		{
			if ($dir == $corePath)
			{
				$msg = 'Eldorado.CMS core directory could not be placed in "%s" because of system use this path for other needs';
				return elThrow(E_USER_WARNING, $msg, $dir);
			}
		}
		return $corePath;
	}
	
	function _installType2()
	{
		if ( false == ($corePath = $this->_getCorePath()) )
		{
			return false;
		}
		if ('./core' == $corePath)
		{
			return true;
		}
		if ( file_exists($corePath) )
		{
			return elThrow(E_USER_WARNING, 'Directory %s already exists!', $corePath) ;
		}
		if ( !rename('./core', $corePath))
		{
			return elThrow(E_USER_WARNING, 'Could not move Eldorado.CMS core to directory %s!', $corePath) ;
		}
		if ( !$this->_coreSymlink($corePath) )
		{
			return elThrow( E_USER_WARNING, 'Could not create symlink to Eldorado.CMS core directory %s!', $corePath );
		}
		return true;
	}
	
	function _installType3()
	{
		if ( false == ($corePath = $this->_getCorePath()) )
		{
			return false;
		}
		if ( !is_dir($corePath) )
		{
			return elThrow(E_USER_WARNING, 'Eldorado.CMS core directory %s does not exists', $corePath);
		}
		if ( !$this->_coreSymlink($corePath) )
		{
			return elThrow( E_USER_WARNING, 'Could not create symlink to Eldorado.CMS core directory %s!', $corePath );
		}
		return true;
	}

	function _coreSymlink($corePath)
	{
		if ( is_link('./core') )
		{
			$coreExist = readlink('./core'); 
			return realpath($coreExist) == realpath($corePath)
				? true
				: elThrow(E_USER_WARNING, 'Symlink ./core already exists but does not point to %s', $corePath);
		}
		if ( file_exists('./core') ) // file, dir or invalid symlink
		{
			return elThrow(E_USER_WARNING, 'File ./core already exists');
		}
		return @symlink($corePath, './core');
	}
	
	function _checkCoreInstallation()
	{
		foreach ($this->coreTree as $f)
		{
			if (!file_exists('./core/'.$f))
			{
				return false;
			}
		}
		return true;
	}
	
	function createHtaccess($dir, $content)
	{
		if ( false == ($fp = @fopen($dir.'/.htaccess', 'w')) )
		{
			return false;
		}
		fwrite($fp, $content."\n");
		fclose($fp);
		return true;
	}

	function _reqConfInvalid($msg, $params=null)
	{
		$this->reqConfValid = false;
		elThrow(E_USER_WARNING, $msg, $params);
	}

}


class elInstaller
{
	var $steps    = array(
		0 => array('Select installation language', 'langSelect'),
		1 => array('Wellcome to Eldorado.CMS installation', 'wellcome'),
		2 => array('Installation type', 'instType'),
		3 => array('Eldorado.CMS core installation', 'instCore'),
		4 => array('Base site configuration', 'configure'),
		5 => array('Site design installation', 'instStyle'),
		6 => array('Root password', 'passwd'),
		7 => array('Installation complite', 'instFinish'),
		);
	var $step      = 0;
	var $rnd       = null;
	var $configurator = null;
	var $dir       = './';
	var $URL       = '';
	var $instType  = 1;
	var $instTypes = array(
		1 => 'Standalone site installation', 
		2 => 'Multi-site instalation. System core will be installed.',
		3 => 'Multi-site installation. System core already installed.'
		);
	
	function elInstaller()
	{
		$this->dir = getcwd().'/';
		$this->URL = $_SERVER['PHP_SELF'];
		$this->rnd = & new elRndInstaller($this->URL);
		$this->configurator = & new elInstallerConf();
		
		if ( !empty($_POST['step']) )
		{
			$this->step = (int)$_POST['step'];
		}
		elseif ( !empty($_GET['step']) )
		{
			$this->step = (int)$_GET['step'];
		}
		
		$complite = !empty($_SESSION['complite']) && !empty($this->steps[$_SESSION['complite']])
			? (int)$_SESSION['complite'] : 0;
		
		if ( empty($this->steps[$this->step]) || $this->step > $complite+1)
		{
			$this->step = 0;
		}
		$this->rnd->step = $this->step;
				
		 
		
		if ($this->step == sizeof($this->steps)-1)
		{
			$this->rnd->lastStep = true;
		}
		if (!empty($_SESSION['lang']) && !empty($GLOBALS['elInstLangs'][$_SESSION['lang']]) )
		{
			$GLOBALS['elInstLang'] = $_SESSION['lang'];
		}
		
		if ( !$this->configurator->reqConfValid )
		{
			if ( 0 == $this->step )
			{
				$GLOBALS['elInstErrors'] = array();
			}
			else
			{
				array_unshift($GLOBALS['elInstErrors'], m('Server configuration error!'));
				$this->rnd->rnd(m('Server configuration error!'));
				exit;
			}
		}
		
		if ( $this->step > 1 && empty($_SESSION['accept']) )
		{
			unset($_SESSION['complite']);
			elThrow(E_USER_WARNING, 'You are not accepted product licence. Installation interrupted!');
			$this->rnd->rnd( m('Installation interrupted!'));
			exit();
		}
		if ( $this->step >1 && !empty($_SESSION['instType']) && !empty($this->instTypes[$_SESSION['instType']]))
		{
			$this->instType = $this->configurator->instType = (int)$_SESSION['instType'];
		}
	}
	
	
	
	function run()
	{
		if ( empty($this->steps[$this->step][1]) || !method_exists($this, $this->steps[$this->step][1]))
		{
			elThrow('Installer internal error!');
			$this->rnd->rnd(m('Installer internal error!'));
			exit();
		}
		$this->rnd->title = m($this->steps[$this->step][0]);
		$this->{$this->steps[$this->step][1]}();
	}
	
	/**
	 * First step
	 *
	 */
	function langSelect()
	{
		if ( !empty($_POST['lang']) && !empty($GLOBALS['elInstLangs'][$_POST['lang']]))
		{
			$GLOBALS['elInstLang'] = $_SESSION['lang'] = $_POST['lang'];
			$this->complite();
		}
		$this->rnd->rndLangSelectForm();
	}

	/**
	 * Second step - accept licence
	 *
	 */
	function wellcome()
	{
		if ( isset($_POST['accept']))
		{
			$_SESSION['accept'] = $_POST['accept'];
			$this->complite();
		}
		$this->rnd->rndWellcome( );
	}
	
	/**
	 * Third step - select installation type
	 *
	 */
	function instType()
	{
		if (!empty($_POST['instType']))
		{
			$_SESSION['instType'] = !empty($this->instTypes[$_POST['instType']]) ? (int)$_POST['instType'] : 1;
			$this->complite();
		}
		$this->rnd->rndInstTypeForm($this->instTypes, $this->instType);
	}
	
	/**
	 * 4 step - install core
	 *
	 */
	function instCore()
	{
		if (!empty($_POST))
		{
			if ( $this->configurator->installCore() )
			{
				$this->complite();
			} 
		}
		$this->rnd->rndInstCoreForm($this->instType, $this->instTypes); 
	}
	
	function configure()
	{
		if (!empty($_POST))
		{
			if ( $this->configurator->configure() )
			{
				$this->complite();
			}
		}
		$this->rnd->rndConfigure($this->configurator->getLangsList());
	}
	
	function instStyle()
	{
		if ( !empty($_POST) || !empty($_FILES) )
		{
			if ( $this->configurator->installStyle() )
			{
				$this->complite();
			}
		}
		$this->rnd->rndStyleForm( $this->configurator->getStylesList() );
	}
	
	function passwd()
	{
		if (!empty($_POST))
		{
			if ($this->configurator->savePasswd())
			{
				$_POST = array();
				$this->complite();
			}
		}
		$this->rnd->rndPasswdForm();
	}
	
	function instFinish()
	{
		if ( !file_exists('./conf/mp') )
		{
			elThrow(E_USER_WARNING, 'Could not save master password into file');
		}
		$ht  = "RewriteEngine On\n";
		$ht .= "RewriteBase ".dirname( $_SERVER['PHP_SELF'] )."\n";
		$ht .= "RewriteRule robots.txt robots.txt [L]\n";
		$ht .= "RewriteRule favicon.ico favicon.ico [L]\n";
		$ht .= "RewriteRule counter.php(.*) counter.php$1 [L]\n";
		$ht .= "RewriteRule (.*) index.php [L]\n";
		if ( !$this->configurator->createHtaccess('./', $ht) )
		{
			elThrow(E_USER_WARNING, 'Please, put in site directory file named .htaccess with following lines: <br />%s', 
							nl2br($ht));
		}
		//@unlink('./installer);
		$this->rnd->rndFinish();
	}
	
	function complite()
	{
		$_SESSION['complite'] = $this->step; 
		exit( header('Location: '.$this->URL.'?step='.($this->step+1)));
	}
	
}

/**
 * Installation renderer class
 *
 */

class elRndInstaller
{
	var $tplMain = 
	"<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n
	 <html>
	<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>{TITLE}</title></head>\n
	<style>\n
	.title { color:navy; font-weight:bold; text-align:center;}
	.subhead { background-color:#f5f5f5; font-weight:bold; padding:5px }
	</style>\n
	<body>\n
	<div style=\"border:1px solid red;position:absolute;left:25%;top:25%;width:50%;height:50%;padding:7px;\" align=\"justify\">\n
	<form method=\"POST\" action=\"{URL}\" enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"step\" value=\"{STEP}\" />
	<table width=\"100%\" height=\"100%\" border=\"0\" cellpadding=\"7\">
	<tr><td height=\"25\" class=\"title\">{TITLE}</td></tr>
	<tr><td>
	{ERRORS}\n
	{CONTENT}\n
	</td></tr>
	<tr><td height=\"20\" align=\"center\">{BUTTONS}</td></tr>
	</table>
	</form>
	</div>
	<div style=\"position:absolute;bottom:5px;right:5px;font-size:small;\">Eldorado.CMS Installer ver {INST_VER}</div>
	</body>\n</html>\n";

	var $step = 0;
	var $lastStep = false;
	var $title   = '';
	var $content = '';
	var $URL = '';
	var $buttons = '';
	
	function elRndInstaller($URL)
	{
		$this->URL = $URL; 
	}
	
	function getErrors()
	{
		if ( !empty($GLOBALS['elInstErrors']) )
		{
			return '<div style="color:red;font-weight:bold">'.implode('<br />', $GLOBALS['elInstErrors']).'</div>';
		}
		return '';		
	}
	
	function rnd($title='')
	{
		if ($title)
		{
			$this->title = $title;
		}
		echo str_replace(array('{TITLE}', '{CONTENT}', '{URL}', '{BUTTONS}', '{STEP}', '{ERRORS}', '{INST_VER}'), 
										array($this->title, $this->content, $this->URL, $this->getButtons(), $this->step, $this->getErrors(),
										$GLOBALS['elInstallerVer']), 
										$this->tplMain );
	}
	
	
	function rndLangSelectForm()
	{
		$this->content .= m('Please, select installation language').': ';
		$this->content .= '<select name="lang">';
		foreach ($GLOBALS['elInstLangs'] as $lang=>$name)
		{
			$sel = $lang == $GLOBALS['elInstLang'] ? ' selected="on"' : '';
 			$this->content .= '<option value="'.$lang.'"'.$sel.'>'.$name.'</option>';
		}
		$this->content .= '</select>';
		$this->rnd();
	}
	
	
	function rndWellcome()
	{
		$this->content  = m('Thanks for choosing our web-site content management system Eldorado.CMS.').'<br />';
		$this->content .= m('Before install Eldorado.CMS You should read this product\'s license text and accept it.');
		$this->content .= '<input type="hidden" name="accept" value="" />';
		$this->content .= '<textarea name="licence" style="width:100%" rows="16">';
		$txt = !empty($GLOBALS['elLicenceTxt'][$GLOBALS['elInstLang']])
			? $GLOBALS['elLicenceTxt'][$GLOBALS['elInstLang']]
			: $GLOBALS['elLicenceTxt']['en'];
		$this->content .= htmlspecialchars( $txt );
		$this->content .= '</textarea>';
		$this->buttons .= '<input type="submit" value="&lt;&lt;&nbsp; '.m('Back').'" 
							onClick="this.form.elements[\'step\'].value='.($this->step-1).';this.form.submit();" />';
		$this->buttons .= '<input type="submit" value="'.m('Decline').'" />';
		$this->buttons .= '<input type="submit" value="'.m('Accept').'" 
							onClick="this.form.elements[\'accept\'].value=1;this.form.submit();" />';
		$this->rnd();
	}
	
	function rndInstTypeForm($instTypes, $type)
	{
		$this->content .= m('Please, select installation type').'<br />';
		
		$this->content .= '<ul>';
		foreach ($instTypes as $ID=>$name)
		{
			$sel = $ID == $type ? ' checked="on"' : '';
			$this->content .= '<input type="radio" name="instType" value="'.$ID.'"'.$sel.' /> '.m($name).'<br />';	
		}
		$this->content .= '</ul>';
		$this->content .= m('For detailed information about installation types appeal to documentation.');
		
		$this->rnd();
	}
	
	function rndInstCoreForm($instType, $instTypes)
	{
		$this->content .= '<b>'.m('Installation type').': '.m($instTypes[$instType]).'</b><p />';
		if ( $instType<3 )
		{
			$this->content .= m('Please, upload archive file with Eldorado.CMS core. (eldorado.CMS-ver-xxx.tar.gz)').'<br />';
			$this->content .= '<input type="file" name="coreFile" /><p />';
			if ( 2 == $instType )
			{
				$this->content .= m('Please, specify directory name in which Eldorado.CMS core will be installed').'<br />';
				$this->content .= '<input type="text" style="width:100%" name="corePath" />';
			}
		}
		else
		{
			$this->content .= m('Please, specify directory name where Eldorado.CMS core was installed').'<br />';
			$this->content .= '<input type="text" style="width:100%" name="corePath" />';
		}
		$this->rnd();
	}
	
	function rndConfigure($langs)
	{
		$this->content .= '<table width="100%" border="0" cellpadding="1">';
		$this->content .= '<tr><td colspan="2" class="subhead">'.m('MySQL data base parameters').'</td></tr>';
		$this->content .= '<tr><td>'.m('Db host').'*:</td>';
		$this->content .= '<td><input type="text" name="host" value="localhost" /></td></tr>';
		$this->content .= '<tr><td>'.m('Db socket').':</td>';
		$this->content .= '<td><input type="text" name="sock" /></td></tr>';
		$this->content .= '<tr><td>'.m('Db user name').'*:</td>';
		$this->content .= '<td><input type="text" name="user" /></td></tr>';
		$this->content .= '<tr><td>'.m('Db user password').'*:</td>';
		$this->content .= '<td><input type="text" name="pass" /></td></tr>';
		$this->content .= '<tr><td>'.m('Db name').'*:</td>';
		$this->content .= '<td><input type="text" name="name" /></td></tr>';
		
		$this->content .= '<tr><td colspan="2" class="subhead">'.m('Site interface language').'</td></tr>';
		$this->content .= '<tr><td>'.m('Interface language').':</td>';
		$this->content .= '<td><select name="lang">';
		foreach ($langs as $l => $name)
		{
			$sel = $l == $GLOBALS['elInstLang'] ? ' selected="on"' : '';
			$this->content .= '<option value="'.$l.'"'.$sel.'>'.$name.'</option>';
		}
		$this->content .= '</select></td></tr>';

		$this->content .= '<tr><td colspan="2" class="subhead">'.m('Other parameters').'</td></tr>';
		$this->content .= '<tr><td>'.m('Site name').'*:</td>';
		$this->content .= '<td><input type="text" name="siteName" /></td></tr>';
		$this->content .= '</table>';
		$this->rnd();
	}
	
	function rndStyleForm($stylesList)
	{
		$this->content .= m('You should upload design templates archive from distr (eldorado-style-xxx.tar.gz) or select one from styles list available in core.');
		$this->content .= "<p>".m('For more information about design templates usage, please, read documentation')."</p>\n";
		$this->content .= '<table align="center">';
		$this->content .= '<tr><td>'.m('Upload archive').':</td>';
		$this->content .= '<td><input type="file" name="styleFile" /></td></tr>';
		$this->content .= '<tr><td> '.m('Or select style from list').': </td>';
		$this->content .= "<td><select name=\"style\">\n";
		foreach ($stylesList as $style=>$name)
		{
			$this->content .= '<option value="'.$style.'">'.$name.'</option>'."\n";
		}
		$this->content .= "</sellect>\n</td></tr></table>\n";
		$this->rnd();
	}
	
	function rndPasswdForm()
	{
		$this->content .= m('Main site administrator has login "root". On this step You should set password and Master Password for user root')."\n";
		$this->content .= '<p>'.m('Password may contains only latin alfanum chars, digits or underscore and dash symbols and must be from 4 till 20 chars.').'</p>';
		$this->content .= "<p>".m('For more information about master password, please, read documentation')."</p>\n";
		$this->content .= '<table align="center">';
		$this->content .= '<tr><td colspan="2" class="subhead">'.m('Enter password for user root').'</td></tr>';
		$this->content .= '<tr><td>'.m('Password').'*: </td><td><input type="password" name="p1" /></td></tr>';
		$this->content .= '<tr><td>'.m('Password confirm').'*: </td><td><input type="password" name="p2" /></td></tr>';
		$this->content .= '<tr><td colspan="2" class="subhead">'.m('Enter Master Password for user root').'</td></tr>';
		$this->content .= '<tr><td>'.m('Master Password').'*: </td><td><input type="password" name="mp1" /></td></tr>';
		$this->content .= '<tr><td>'.m('Master Password confirm').'*: </td><td><input type="password" name="mp2" /></td></tr>';
		$this->content .= '</table>';
		$this->rnd();
	}
	
	function rndFinish()
	{
		$this->rnd();
	}
	
	function getButtons()
	{
		if ( $this->buttons )
		{
			return $this->buttons;
		}
		$this->buttons  = '<div style="text-align:center;margin-top:15px;">';
		if ($this->step>0)
		{
			$this->buttons .= '<input type="submit" value="&lt;&lt;&nbsp; '.m('Back').'" 
												onClick="this.form.elements[\'step\'].value='.($this->step-1).';this.form.submit();" />';
		}
		if ( $this->lastStep )
		{
			$this->buttons .= '<input type="hidden" name="finish" value="1" />';
			$this->buttons .= '<input type="submit" value="'.m('Finish').'" />';
		}
		else
		{
			$this->buttons .= '<input type="submit" value="'.m('Continue').' &gt;&gt;" />';
		}
		$this->buttons .= '</div>';
		return $this->buttons;
	}
}



?> 