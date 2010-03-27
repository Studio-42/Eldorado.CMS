<?php
session_name('ELSID');
session_set_cookie_params( 60*60*24 );
session_start();
define('EL_FS_ONLY_DIRS', 1);
define('EL_FS_ONLY_FILES', 2);
define('EL_FS_DIRMODE', 0777);
error_reporting(0);

$installer = & new elInstaller();
$installer->run();

//echo '<pre>';
//print_r($_SESSION);
//print_r($_SERVER);

class elInstaller {
	var $_steps = array(
		'lang'     => array('m' => 'selectLanguage', 'l' => 'Select language'),
		'license'  => array('m' => 'license',        'l' => 'Select language'),
		'type'     => array('m' => 'installType',    'l' => 'Select instalation type'),
		'db'       => array('m' => 'dbConf',         'l' => 'Configure database'),
		'user'     => array('m' => 'createUser',     'l' => 'Create administrator account'),
		'style'    => array('m' => 'styleSelect',    'l' => 'Select site style'),
		'misc'     => array('m' => 'miscConf',       'l' => 'Site options'),
		'complite' => array('m' => 'complite',       'l' => 'Instalation complite')
		);
		
	var $_step ='lang';
	var $_lang = 'en';
	var $_rnd = null;
	var $_tr  = null;
	var $_langs = array(
		'en' => 'English',
		'ru' => 'Русский',
		'ua' => 'Украинский'
		);
	var $_locales = array(
		'en' => 'en_US.UTF-8',
		'ru' => 'ru_RU.UTF-8',
		'ua' => 'uk_UA.UTF-8'
		);
	var $_rootDir = '';
		
		
	function elInstaller() 
	{
		
		if (!empty($_SESSION['step']) && !empty($this->_steps[$_SESSION['step']]))
		{
			$this->_step = $_SESSION['step'];
		}
		if (!empty($_SESSION['lang']) && !empty($this->_langs[$_SESSION['lang']]))
		{
			$this->_lang = $_SESSION['lang'];
		}
		else
		{
			foreach ($this->_langs as $lang=>$v)
			{
				if ($lang != 'en' && preg_match('/'.$lang.'(,|\-|;)/', $_SERVER['HTTP_ACCEPT_LANGUAGE']))
				{
					$this->_lang = $lang;
					break;
				}
			}
		}
		$this->_tr  = & new elInstallerTranslator($this->_lang);
		$this->_rnd = & new elInstallerRnd($this->_lang, $this->_tr);
		
		if (!$this->_rootDir)
		{
			if (!empty($_SESSION['rootDir']) && is_dir($_SESSION['rootDir']))
			{
				$this->_rootDir = $_SESSION['rootDir'];
			}
			else 
			{
				$dir = dirname(__FILE__); 
				$reg = '|core'.DIRECTORY_SEPARATOR.'install$|';
				if (preg_match($reg, $dir))
				{
					$dir = preg_replace($reg, '', $dir);
				}

				$this->_rootDir = $dir.DIRECTORY_SEPARATOR;
				$_SESSION['rootDir'] = $this->_rootDir;
			}
		}
		
		// $_SESSION = array();
	}
	
	function run()
	{
		if (!empty($_POST['cancel_install']))
		{
			session_destroy();
			header('Location: '.$_SERVER['REQUEST_URI']);
		}

		$m = $this->_steps[$this->_step]['m'];
		$this->$m();
	}
	
	function selectLanguage()
	{
		if (!empty($_POST['lang']) && !empty($this->_langs[$_POST['lang']]))
		{
			$_SESSION['lang'] = trim($_POST['lang']);
			$_SESSION['step'] = 'license';
			header('Location: '.$_SERVER['REQUEST_URI']);
		}
		$this->_rnd->rndSelectLanguage($this->_langs);
	}
	
	function license()
	{
		if (isset($_POST['accept']))
		{
			if ($_POST['accept'] == 1) 
			{
				$_SESSION['step'] = 'type';
				header('Location: '.$_SERVER['REQUEST_URI']);
			}
			else
			{
				$_SESSION = array();
				return $this->_rnd->rndError('License was not accepted!<br />Unable to complite installation!');
			}
		}
		$this->_rnd->rndLicense();
	}
	
	function installType()
	{
		if (!is_writable($this->_rootDir) || (!elFS::mkdir($this->_rootDir.'tmp')))
		{
			return $this->_rnd->rndError('Site directory has no write permissions!<br />Unable to complite installation!');
		}

		$files = $this->_isFilesExists();
		
		if ($files && $this->_isInstalled())
		{
			return $this->_reInstall();
		}
		
		if (!$files && !$this->_tar())
		{
			return $this->_rnd->rndError('Unable to find tar and gzip command! This need to unpack archive. To install ELDORADO.CMS, upload files using FTP/SSH. <br />Instalation interrupted.');
		}
		
		if (!empty($_POST['src']))
		{
			return $this->_install($_POST['src']);
		}
		
		$this->_rnd->rndInstallSelect($this->_installVariants());
	}
	
	function _tar()
	{
		exec('tar --version', $o, $c);
		$tar = $c == 0 && !empty($o);
		exec('gzip --version', $o, $c);
		return $tar && $c == 0 && !empty($o);
	}
	
	function _installVariants()
	{
		$files = $this->_isFilesExists(); 
		$tar   = $this->_tar();
		$vars = array();
		if ($files)
		{
			$vars['files'] = $this->_tr->translate('Files on server');
		}
		if ($tar)
		{
			$found = elFS::find($this->_rootDir, '/.+\.tar\.gz$/i', false);
			foreach ($found as $f)
			{
				$f = basename($f);
				$vars[$f] = $this->_tr->translate('Archive').' ('.$f.')';
			}
			if ($vars)
			{
				$vars['upload'] = $this->_tr->translate('Upload archive');
			}
			
		}
		return $vars;
	}
	
	function dbConf()
	{
		$err = '';
		if (isset($_POST['host']))
		{
			$host   = !empty($_POST['host']) ? trim($_POST['host']) : '';
			$sock   = !empty($_POST['sock']) ? trim($_POST['sock']) : '';
			$user   = !empty($_POST['user']) ? trim($_POST['user']) : '';
			$passwd = !empty($_POST['passwd']) ? trim($_POST['passwd']) : '';
			$dbname = !empty($_POST['db']) ? trim($_POST['db']) : '';
			if (!$host || !$user || !$dbname) 
			{
				return $this->_rnd->rndDbConf('Host, User or Db name could not be empty.');
			}
			$db = & new elInstallerDb();
			if (!$db->connect($host, $sock, $user, $passwd))
			{
				return $this->_rnd->rndDbConf('Unable to connect to MySQL server!<br />Check entered data.');
			}
			if (!$db->isDbExists($dbname) && !$db->createDb($dbname))
			{
				return $this->_rnd->rndDbConf('Unable to create data base! Check data base user permissions.');
			}
			if (!$db->selectDb($dbname))
			{
				return $this->_rnd->rndDbConf('Unable to connect to data base!<br />Check entered data.');
			}
			
			$conf = $this->_getConf();
			if (!$conf)
			{
				$this->_rnd->rndError('Unable to include required file! Check installation archive!<br />Unable to complite installation.');
			}
			$conf->set('host',   $host, 'db');
			!empty($sock) && $conf->set('sock', $sock, 'db');
			$conf->set('user',   $user, 'db');
			$conf->set('pass',   $passwd, 'db');
			$conf->set('db',     $dbname, 'db');
			$conf->set('locale', $this->_locales[$this->_lang], 'common');
			if (!$conf->save())
			{
				$this->_rnd->rndError('Unable to save config file!<br />Unable to complite installation.');
			}
			
			if (!include_once($this->_rootDir.'core'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'elDbDump.class.php'))
			{
				$this->_rnd->rndError('Unable to include required file! Check installation archive!<br />Unable to complite installation.');
			}
			
			$installDir = $this->_rootDir.'core'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;
			$file = file_exists($installDir.'install-'.$this->_lang.'.sql') ? $installDir.'install-'.$this->_lang.'.sql' : $installDir.'install.sql';
			
			$dump = & new elDbDump($db);
			if (!$dump->restore($file))
			{
				return $this->_rnd->rndError('Unable to insert data into data base!<br />Unable to complite installation.');
			}
			
			$_SESSION['step'] = 'user';
			header('Location: '.$_SERVER['REQUEST_URI']);
		}
		
		$this->_rnd->rndDbConf($err);
	}
	
	function createUser()
	{
		if (isset($_POST['passwd1']))
		{
			$p1 = trim($_POST['passwd1']);
			$p2 = isset($_POST['passwd2']) ? trim($_POST['passwd2']) : $_POST['passwd2'];
			if (!$p1 || !$p2)
			{
				return $this->_rnd->rndUserPassword('Password could not be empty!');
			}
			if ($p1 != $p2)
			{
				return $this->_rnd->rndUserPassword('Passwords not equal!');
			}
			if (!preg_match('/[a-z0-9\-\.\!\s@]{6,}/i', $p1))
			{
				return $this->_rnd->rndUserPassword('Password should contains not less then 6 alfanumeric and/or punctuation sybmbols');
			}
			
			$t     = time();
			$db    = $this->_getDb();
			$email = mysql_real_escape_string($_POST['email']);
			if (!$db)
			{
				return $this->_rnd->rndUserPassword('Unable connect to data base!');
			}
			$sql = 'REPLACE INTO el_user (uid, login, pass, email, f_name, crtime, mtime) VALUES '
				.'(1, "root", "'.md5($p1).'", "'.$email.'", "Administrator", '.$t.', '.$t.')';
			if (!$db->query($sql))
			{
				return $this->_rnd->rndUserPassword('Unable to create user! Unable to complite install.');
			}
			$_SESSION['step'] = 'style';
			header('Location: '.$_SERVER['REQUEST_URI']);
		}
		$this->_rnd->rndUserPassword();
	}
	
	function styleSelect()
	{
		if (file_exists($this->_rootDir.'style'.DIRECTORY_SEPARATOR.'hormal.html'))
		{
			$_SESSION['step'] =  $this->_step = 'misc';
			return $this->run();
		}
		$styles = elFS::ls($this->_rootDir.'core'.DIRECTORY_SEPARATOR.'styles', EL_FS_ONLY_DIRS); 
		if (sizeof($styles) == 1 && !$this->_tar())
		{
			if (!$this->_setStyle($styles[0]))
			{
				return $this->_rnd->rndError('Unable to set site style! Unable to complite installation.');
			}
			else
			{
				$_SESSION['step'] =  $this->_step = 'misc';
				return $this->run();
			}
		}

		if (isset($_POST['style_select']))
		{
			if (!empty($_FILES['upl_style']['name']))
			{
				$tmpDir = $this->_rootDir.'tmp'.DIRECTORY_SEPARATOR;
				$err = '';
				if (empty($_FILES['upl_style']['tmp_name']) || !$_FILES['upl_style']['size'] || $_FILES['upl_style']['error'])
				{
					$err = 'Upload file error.';
				}
				elseif (!move_uploaded_file($_FILES['upl_style']['tmp_name'], $tmpDir.$_FILES['upl_style']['name']))
				{
					$err = 'Upload file error.';
				}
				if ($err)
				{
					return $this->_rnd->rndStyleSelect($styles, $err);
				}
				$cwd = getcwd();
				chdir($tmpDir);
				exec('tar xzf '.escapeshellarg('./'.$_FILES['upl_style']['name']), $o, $c);
				chdir($cwd);
				@unlink($tmpDir.$_FILES['upl_style']['name']);
				if ($c>0)
				{
					return $this->_rnd->rndStyleSelect($styles, 'Unable unpack archive.');
				}
				if (!is_dir($tmpDir.'style'))
				{
					return $this->_rnd->rndStyleSelect($styles, 'Archive does not contains "style" directory.');
				}
				if (is_dir($this->_rootDir.'style'))
				{
					elFS::rmDir($this->_rootDir.'style');
				} 
				elseif (is_link($this->_rootDir.'style'))
				{
					@unlink($this->_rootDir.'style');
				}
				if (!elFS::move($tmpDir.'style', $this->_rootDir))
				{
					return $this->_rnd->rndStyleSelect($styles, 'Unable to move "style" directory.');
				}
				$this->_copyIcons();
			}
			else
			{
				$style = !empty($_POST['style']) && in_array($_POST['style'], $styles) ? $_POST['style'] : $styles[0];
				if (!$this->_setStyle($style))
				{
					return $this->_rnd->rndError('Unable to set site style! Unable to complite installation.');
				}
			}
			$_SESSION['step'] = 'misc';
			header('Location: '.$_SERVER['REQUEST_URI']);
			
		}
		$this->_rnd->rndStyleSelect($styles);
	}
	
	
	function miscConf()
	{
		if (isset($_POST['misc']))
		{
			$name     = !empty($_POST['site_name']) ? trim($_POST['site_name']) : '';
			$contacts = !empty($_POST['contacts'])  ? trim($_POST['contacts'])  : '';
			$phone    = !empty($_POST['phone'])     ? trim($_POST['phone'])     : '';
			$email    = !empty($_POST['email'])     ? trim($_POST['email'])     : '';
			if ($name || $contacts || $phone)
			{
				$conf = $this->_getConf();
				if (!$conf)
				{
					$this->_rnd->rndError('Unable to include required file! Check installation archive!<br />Unable to complite installation.');
				}
				$name     && $conf->set('siteName', $name,     'common');
				$contacts && $conf->set('contacts', $contacts, 'common');
				$phone    && $conf->set('phones',   $phone,    'common');
				$conf->save();
			}
			if ($email)
			{
				$db = $this->_getDb();
				if (!$db)
				{
					$this->_rnd->rndError('Unable to include required file! Check installation archive!<br />Unable to complite installation.');
				}
				$db->query('TRUNCATE el_email');
				$db->query('INSERT INTO el_email (label, email, is_default) VALUES ("default", "'.mysql_real_escape_string($email).'", 1)');
			}
			$_SESSION['step'] = 'complite';
			header('Location: '.$_SERVER['REQUEST_URI']);
		}
		
		$this->_rnd->rndMiscConf();
	}
	
	function complite()
	{
		//$path = preg_replace('|(~[^/]+/)|i', '', $_SERVER['REQUEST_URI']);
		//$path = str_replace('/'.basename(__FILE__), '', $path);
		$path = str_replace('core/install/installer.php', '', $_SERVER['REQUEST_URI']);
		if (!$path)
		{
			$path = '/';
		}

		$ht  = "RewriteEngine On\n";
		$ht .= "RewriteBase  ".$path."\n";
		$ht .= "RewriteRule ^conf\/(.*)\.(sql|xml) index.php [NS,F]\n";
		$ht .= "RewriteRule ^(index\.php|robots\.txt)(.*) $1$2 [L]\n";
		$ht .= "RewriteRule (.*)\.(php|phtml) index.php [L]\n";
		$ht .= "RewriteRule ^storage(.*)  storage$1 [L]\n";
		$ht .= "RewriteRule ^style/(.*) style/$1 [L]\n";
		$ht .= "RewriteRule ^(.*)\.(jpg|gif|png|swf|ico|html|css|js|xml|gz|txt|htc)(.*) $1.$2$3 [L]\n";
		$ht .= "RewriteRule (.*) index.php [L]\n";

		$err = '';
		if (false == ($fp = fopen($this->_rootDir.'.htaccess', 'w')))
		{
			$err = $this->_tr->translate('Unable to write to .htaccess file!<br />You have to manually put following lines into .htaccess file.');
			$err .= '<br />'.nl2br($ht);
		}
		else {
			fwrite($fp, $ht);
			fclose($fp);
		}
		if ($fp = fopen($this->_rootDir.'robots.txt', 'w'))
		{
			$str  = "User-Agent: *\n";
			$str .= "Disallow: /*__\n";
			$str .= "Disallow: /*.exe\n";
			$str .= "Disallow: /*.zip\n";
			fwrite($fp, $str);
			fclose($fp);
		}
		
		if (!copy($this->_rootDir.'core'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'index.php', $this->_rootDir.'index.php'))
		{
			return $this->_rnd->rndError('Unable to copy "index.php" file! <br />Unable to complite installation.');
		}
		$this->_rnd->url = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'.$_SERVER['SERVER_NAME'].substr($_SERVER['REQUEST_URI'], 0, -strlen(basename(__FILE__)));
		$this->_rnd->rndComplite($err);
		session_destroy();
		$_SESSION = array();
		
		@file_get_contents('http://www.eldorado-cms.ru/counter.php');
		@unlink(__FILE__);
	}
	
	/*************************************************************/
	
	function _isFilesExists($dir='')
	{
		$dir     = $dir ? $dir : $this->_rootDir;
		$core    = $dir.'core'.DIRECTORY_SEPARATOR;
		$install = $core.'install'.DIRECTORY_SEPARATOR;
		$modules = $core.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR;
		$dirs = array(
			$core, 
			$install, 
			$modules,
			$core.DIRECTORY_SEPARATOR.'forms', 
			$core.DIRECTORY_SEPARATOR.'js', 
			$core.DIRECTORY_SEPARATOR.'lib',
			$core.DIRECTORY_SEPARATOR.'locale',
			$core.'plugins',
			$core.'services',
			$modules.'SimplePage',
			$modules.'Container',
			$modules.'UsersControl',
			$modules.'NavigationControl',
			$modules.'SiteControl'
			);
		
		foreach ($dirs as $d)
		{
			if (!is_dir($d))
			{
				return false;
			}
		}
		
		if (!is_file($core.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'elCore.class.php')
		||  !is_file($install.'main.conf.xml') 
		||  !is_file($install.'install.sql') 
		||  !is_file($install.'index.php'))
		{
			return false;
		}
		return true;
	}
	
	function _isInstalled()
	{
		if ($this->_isFilesExists()
		&& is_file($this->_rootDir.'index.php')
		&& is_file($this->_rootDir.'.htaccess')
		&& $this->_getDb())
		{
			return true;
		}
		return false;
	}
	
	function _getConf()
	{
		if (include_once $this->_rootDir.'core'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'elXmlConf.class.php')
		{
			return new elXmlConf('main.conf.xml', $this->_rootDir.'conf'.DIRECTORY_SEPARATOR, false);
		}
	}
	
	function _getDb()
	{
		if (false != ($conf = $this->_getConf()))
		{
			$db = & new elInstallerDb();
			if ($db->connect($conf->get('host', 'db'), $conf->get('sock', 'db'), $conf->get('user', 'db'), $conf->get('pass', 'db')) && $db->selectDb($conf->get('db', 'db')))
			{
				return $db;
			}
		}
	}
	
	function _reInstall()
	{
		if (isset($_POST['passwd1']) && isset($_POST['passwd2']))
		{
			$p1 = trim($_POST['passwd1']);
			$p2 = trim($_POST['passwd2']);			
			if (empty($p1) || empty($p2))
			{
				return $this->_rnd->rndRootPasswd('Password could not be empty');
			}
			if ($p1 != $p2)
			{
				return $this->_rnd->rndRootPasswd('Passwords not equal');
			}
			$db = $this->_getDb();
			$res = $db->query('SELECT uid FROM el_user WHERE login="root" AND pass="'.md5($p1).'"');
			
			if ($res && $db->nextRecord())
			{
				return $this->_install('files');
			}
			$_SESSION = array();
			return $this->_rnd->rndError('Invalid password! Unable to complite installation');
		}
		return $this->_rnd->rndRootPasswd();
	}
	
	function _install($src)
	{
		$tmpDir = $this->_rootDir.'tmp'.DIRECTORY_SEPARATOR;
		if ($src == 'files')
		{
			if (!$this->_isFilesExists())
			{
				return $this->_rnd->rndInstallSelect($this->_installVariants(), 'To install ELDORADO.CMS You have to upload archive');
			}
		}
		elseif ($src == 'upload')
		{
			
			$err = '';
			if (empty($_FILES['arc']['name']))
			{
				$err = 'Please, upload ELDORADO.CMS archive';
			} 
			elseif (empty($_FILES['arc']['tmp_name']))
			{
				$err = 'Upload file error';
			} 
			elseif (!preg_match('/tar\.gz$/i', $_FILES['arc']['name']))
			{
				$err = 'Invalid archive type';
			} 
			elseif (!move_uploaded_file($_FILES['arc']['tmp_name'], $tmpDir.$_FILES['arc']['name']))
			{
				$err = 'Upload file error';
			}
			if ($err)
			{
				return $this->_rnd->rndInstallSelect($this->_installVariants(), $err);
			}
			
			if (false != ($err = $this->_unpackCore($_FILES['arc']['name'])))
			{
				return $this->_rnd->rndInstallSelect($this->_installVariants(), $err);
			}
		}
		else
		{
			$file = false;
			$found = elFS::find($this->_rootDir, '/.+\.tar\.gz$/i', false);
			foreach ($found as $f)
			{
				if ($src == basename($f))
				{
					$file = $f;
					break;
				}
			}
			if (!$file)
			{
				return $this->_rnd->rndInstallSelect($this->_installVariants(), 'Selected archive does not exists');
			}
			if (!elFS::copy($file, $tmpDir))
			{
				return $this->_rnd->rndInstallSelect($this->_installVariants(), 'Unable unpack archive');
			}
			if (false != ($err = $this->_unpackCore($src)))
			{
				elFS::move($src, $this->_rootDir);
				return $this->_rnd->rndInstallSelect($this->_installVariants(), $err);
			}
		}

		if (!elFS::mkdir($this->_rootDir.'conf')
		||  !elFS::mkdir($this->_rootDir.'storage'))
		{
			return $this->_rnd->rndError('Unable to create directories!<br />Unable to complite installation!');
		}
		$warn = '';
		if (!elFS::mkdir($this->_rootDir.'backup'))
		{
			$warn .= 'backup';
		}
		if (!elFS::mkdir($this->_rootDir.'cache'))
		{
			$warn .= ' cache';
		}
		if (!elFS::mkdir($this->_rootDir.'log'))
		{
			$warn .= ' log';
		}
		
		$installDir = $this->_rootDir.'core'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;
		if (!@copy($installDir.'main.conf.xml', $this->_rootDir.'conf'.DIRECTORY_SEPARATOR.'main.conf.xml'))
		{
			return $this->_rnd->rndError('Unable to copy config file!<br />Unable to complite installation!');
		}
		if (!is_writable($this->_rootDir.'conf'.DIRECTORY_SEPARATOR.'main.conf.xml') && !@chmod($this->_rootDir.'conf'.DIRECTORY_SEPARATOR.'main.conf.xml', 0666))
		{
			return $this->_rnd->rndError('Unable to set write permissions for config file!<br />Unable to complite installation!');
		}
		
		if ($warn)
		{
			$_SESSION['warn'] = $warn;
		}
		$_SESSION['step'] = 'db';
		header('Location: '.$_SERVER['REQUEST_URI']);
		
	}
	
	function _unpackCore($file)
	{

		$tmpDir = $this->_rootDir.'tmp'.DIRECTORY_SEPARATOR;
		$cwd = getcwd();
		chdir($tmpDir);
		exec('tar xzf '.escapeshellarg('./'.$file), $o, $c);
		chdir($cwd);
		if ($c>0)
		{
			elFS::rmdir($tmpDir);
			return 'Unable unpack archive';
		}
		if (!$this->_isFilesExists($tmpDir))
		{
			elFS::rmdir($tmpDir);
			return 'Archive does not contains required files';
		}
		
		is_dir($this->_rootDir.'core') && elFS::rmDir($this->_rootDir.'core');
		
		if (!elFS::move($tmpDir.'core', $this->_rootDir))
		{
			elFS::rmdir($tmpDir);
			return 'Unable to copy "core" directories!<br />Unable to complite installation!';
		}
		
		if (is_dir($this->_rootDir.'style'))
		{
			elFS::rmDir($this->_rootDir.'style');
		} 
		elseif (is_link($this->_rootDir.'style'))
		{
			@unlink($this->_rootDir.'style');
		}
		if (is_dir($tmpDir.'style'))
		{
			elFS::move($tmpDir.'style', $this->_rootDir);
			$this->_copyIcons();
		}
		
		@unlink($tmpDir.$file);
	}
	
	
	function _setStyle($style)
	{
		$style = $this->_rootDir.'core'.DIRECTORY_SEPARATOR.'styles'.DIRECTORY_SEPARATOR.$style;
		if (is_dir($style))
		{
			if (!symlink($style, 'style'))
			{
				if (!elFS::copy($style, $this->_rootDir))
				{
					return false;
				}
			}
			$this->_copyIcons();
			return true;
			
		}
	}
	
	function _copyIcons()
	{
		elFS::copy($this->_rootDir.'style'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'pageIcons', $this->_rootDir.'storage');
		elFS::copy($this->_rootDir.'style'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'forum'.DIRECTORY_SEPARATOR.'avatars',   $this->_rootDir.'storage');
	}
	
}

class elInstallerRnd {

	var $url = '';
	
	function elInstallerRnd($lang, &$tr)
	{
		$this->lang = $lang;
		$this->tr = & $tr;
	}


	function rndSelectLanguage($langs)
	{
		$content = '<fieldset><legend>'.$this->tr->translate('Select instalation language').'</legend>';
		$content .= '<select name="lang">';
		foreach ($langs as $k=>$v)
		{
			$content .= '<option value="'.$k.'"'.($k == $this->lang ? ' selected="on"' : '').'>'.$v.'</option>';
		}
		$content .= '</select></fieldset>';
		$this->_rnd($content, '', false);
	}
	
	function rndLicense()
	{
		$content = "<p>".$this->tr->translate('Thanks for choosing ELDORADO.CMS!<br />Before install, please, read license.')."</p>";
		$content .= '<textarea name="license">'.$this->tr->license().'</textarea>';
		$content .= '<label class="chk"><input type="radio" name="accept" value="0" /> '.$this->tr->translate('Not accept').'</label>';
		$content .= '<label class="chk"><input type="radio" name="accept" value="1" /> '.$this->tr->translate('Accept').'</label>';
		
		
		$this->_rnd('', $content);
	}
	
	function rndError($err)
	{
		$_SESSION = array();
		$content = '<p class="err">'.$this->tr->translate($err).'</p>';
		$this->_rnd('', $content);
	}
	
	function rndRootPasswd($err='')
	{
		$content = '<p>'.$this->tr->translate('Installed copy of ELDORADO.CMS was detected!<br />If You continue, all existed data will be lost!<br />To continue enter site administrator password!').'</p>';
		if ($err)
		{
			$content .= '<p class="err">'.$this->tr->translate($err).'</p>';
		}
		$content .= '<p><label>'.$this->tr->translate('Password').'</label><input type="password" name="passwd1" /></p>';
		$content .= '<p><label>'.$this->tr->translate('Repeat').'</label><input type="password" name="passwd2" /></p>';
		$this->_rnd('', $content);
	}
	
	function rndInstallSelect($variants, $err=null)
	{
		$content = '';
		if ($variants)
		{
			$content .= '<p>'.$this->tr->translate('To install ELDORADO.CMS select installation source').'</p>';
			$content .= '<p><label>'.$this->tr->translate('Install from').'</label>';
			$js       = "document.getElementById('upload').style.display=this.value == 'upload' ? 'block' : 'none' ";
			$content .= '<select name="src" onchange="'.$js.'">';
			foreach ($variants as $k=>$v)
			{
				$content .= '<option value="'.$k.'">'.$v.'</option>';
			}
			$content .= '</select></p>';

		}
		else 
		{
			$content .= '<p>'.$this->tr->translate('To install ELDORADO.CMS You have to upload archive').'</p>';
			$content .= '<input type="hidden" name="src" value="upload" />';
		}
		$content .= '<p id="upload" '.($variants ? 'style="display:none"' : '').'><label>'.$this->tr->translate('Select file').'</label><input type="file" name="arc" /></p>';
		$this->_rnd($content, $err);
	}
	
	
	function rndDbConf($err)
	{
		$content = '<p>'.$this->tr->translate('Enter MySQL Db connection data').'</p>';
		if ($err)
		{
			$content .= '<p class="err">'.$this->tr->translate($err).'</p>';
		}
		$content .= '<p><label>'.$this->tr->translate('Host').'</label><input type="text" name="host" value="'.(isset($_POST['host']) ? $_POST['host'] : 'localhost').'" /></p>';
		$content .= '<p><label>'.$this->tr->translate('Socket').'</label><input type="text" name="sock" value="'.(isset($_POST['sock']) ? $_POST['sock'] : '').'" /></p>';			
		$content .= '<p><label>'.$this->tr->translate('User').'</label><input type="text" name="user" value="'.(isset($_POST['user']) ? $_POST['user'] : '').'" /></p>';						
		$content .= '<p><label>'.$this->tr->translate('Password').'</label><input type="password" name="passwd" value="'.(isset($_POST['passwd']) ? $_POST['passwd'] : '').'" /></p>';
		$content .= '<p><label>'.$this->tr->translate('Db name').'</label><input type="text" name="db" value="'.(isset($_POST['db']) ? $_POST['db'] : '').'" /></p>';
		$this->_rnd('', $content);
	}
	
	function rndUserPassword($err='')
	{
		$content = '<p>'.$this->tr->translate('Main administrator in ELDORADO.CMS has login "root".<br /> Please, enter new password and e-mail for user "root".').'</p>';
		if ($err)
		{
			$content .= '<p class="err">'.$this->tr->translate($err).'</p>';
		}
		$content .= '<p><label>'.$this->tr->translate('Password').'</label><input type="password" name="passwd1" /></p>';
		$content .= '<p><label>'.$this->tr->translate('Repeat').'</label><input type="password" name="passwd2" /></p>';
		$content .= '<p><label>'.$this->tr->translate('E-mail').'</label><input type="text" name="email" value="'.(isset($_POST['email']) ? trim($_POST['email']) : '').'" /></p>';
		$this->_rnd('', $content);
	}
	
	function rndStyleSelect($styles, $err='')
	{
		$content = '<input type="hidden" name="style_select" value="1" />';
		if ($err)
		{
			$content .= '<p class="err">'.$this->tr->translate($err).'</p>';
		}
		if (sizeof($styles)==1)
		{
			$content .= '<p>'.$this->tr->translate('You use default site design or upload You own (archive with "style" directory).').'</p>';
		}
		else
		{
			$content .= '<p>'.$this->tr->translate('You may select one of existed site designs or upload You own (archive with "style" directory).').'</p>';
			$sel = '<select name="style">';
			foreach ($styles as $s)
			{
				$sel .= '<option value="'.$s.'">'.$s.'</option>';
			}
			$sel .= '</select>';
			$content .= '<p><label>'.$this->tr->translate('Select style').'</label> '.$sel.'</p>';
		}
		
		$content .= '<p><label>'.$this->tr->translate('Upload style').'</label><input type="file" name="upl_style" /></p>';
		return $this->_rnd('', $content);
	}
	
	
	function rndMiscConf()
	{
		$content = '<input type="hidden" name="misc" value="1" />';
		$content .= '<p>'.$this->tr->translate('Here You may enter common information about site.<br /> But, also, You may do it later on site.').'</p>';
		$content .= '<p><label>'.$this->tr->translate('Site name').'</label> <input type="text" name="site_name" /></p>';
		$content .= '<p><label>'.$this->tr->translate('Contact information').'</label> <input type="text" name="contacts" /></p>';
		$content .= '<p><label>'.$this->tr->translate('Phone number').'</label> <input type="text" name="phone" /></p>';
		$content .= '<p><label>E-mail</label> <input type="text" name="email" /></p>';
		return $this->_rnd('', $content);
	}
	
	function rndComplite($err)
	{
		$content = '';
		if ($err)
		{
			$content .= '<p class="err">'.$this->tr->translate($err).'</p>';
		}
		$content .= '<p>'.$this->tr->translate('ELDORADO.CMS was succesfully instaled!<br /> Press "Ok" to go to You new site.').'</p>';
		return $this->_rnd('', $content, false);
	}
	
	function _rnd($content, $err='', $cancel=true)
	{
		if ($err)
		{
			$content = '<p class="err">'.$this->tr->translate($err).'</p>'.$content;
		}
		$cancelMsg = $this->tr->translate('Do You really interrupt installation?');
		$cancel = $cancel 
			? "<input type='submit' value='Cancel' onclick=\"if (confirm('".$cancelMsg."')) { document.getElementById('cancel_install').value =1; return true; } return false;\" />" 
			: '';
		
		$from = array('{title}', '{content}', '{url}', '{cancel}');
		$to   = array($this->tr->translate('ELDORADO.CMS installation'), $content, $this->url, $cancel);
		$tpl  = str_replace($from, $to, $this->_tpl);
		if ( !headers_sent()  )
		{
			header('Content-type: text/html; charset=utf-8');
		}
		echo $tpl;
	}
	
	
	var $_tpl = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">
		<html>
		<head>
			<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
			<title>{title}</title>
			<style type=\"text/css\" media=\"screen\">
				body { 
					background-color: #eee; 

				}
				body * {
					font:.9em Verdana, Tahoma, Geneva, Helvetica, sans-serif;
				}
				textarea { width: 100%; height:120px;  }
				input, textarea { border:1px solid #ccc;  }
				input, textarea, select { font-size:1em }
				label.chk { display: block; padding: 1px}
				p label { width: 150px; display:inline-block}
				p input, p select { width: 270px; }
				#main { 
					width: 450px; 
					border:1px solid #ccc; 
					border-bottom:2px solid #ccc;
					border-right:2px solid #ccc;
					margin:120px auto; 
					border-radius:9px;
					-moz-border-radius:9px;
					-webkit-border-radius:9px;
					padding: 25px 9px 9px;
					background: #fff url(./core/logo2.png) 10px 10px no-repeat;
					
					position:relative;
				}
				#label {
					position:absolute;
					top : -12px;
					left : 19px;
					
					font-weight:bold;
					line-height:22px;
					padding:3px 12px;
					background-color: #eee;
					border:1px solid #ccc; 
					border-radius:5px;
					-moz-border-radius:5px;
					-webkit-border-radius:5px;

				}
				#submit {
					border-top: 1px solid #ccc;
					margin-top:12px;
					padding:7px 3px;
					text-align:right;
				}
				#submit input {
					background:#eee;
					border:1px solid #ccc; 
					border-radius:5px;
					-moz-border-radius:5px;
					-webkit-border-radius:5px;
					padding:3px 7px
				}
				fieldset {
					border:1px solid #ccc; 
					border-radius:5px;
					-moz-border-radius:5px;
					-webkit-border-radius:5px;	
				}
				legend {
					padding:3px 9px;
					border-radius:3px;
					-moz-border-radius:3px;
					-webkit-border-radius:3px;
					
				}
				.err { color: red}
				
			</style>
		</head>
		<body>
		<form method=\"POST\" enctype=\"multipart/form-data\" action=\"{url}\">
		<input type='hidden' name='cancel_install' id='cancel_install' value='0' />
		<div id='main'>
			<div id='label'>{title}</div>
			{content}
			<div id='submit'>
				
				<input type='submit' value='OK' />
				{cancel}
			</div>
		</div>
		</form>
		</body>
		</html>
		";

	var $lang = 'en';
	var $tr = null;
	
	
}

class elInstallerTranslator {
	var $lang = 'en';
	
	
	
	function elInstallerTranslator($lang)
	{
		$this->lang = $lang;
		$this->_license['ua'] = $this->_license['en'] = $this->_license['ru'];
	}
	
	function translate($msg)
	{
		return !empty($this->msgs[$this->lang][$msg]) ? $this->msgs[$this->lang][$msg] : $msg;
	}
	
	function formatTranslate($msg, $args)
	{
		return vsprintf($this->translate($msg), $args);
	}
	
	function license()
	{
		return $this->_license[$this->lang];
	}
	
	var $msgs = array(
		'ru' => array(
			'ELDORADO.CMS installation' => 'Установка ELDORADO.CMS',
			'Select instalation language' => 'Выберите язык установки',
			'Thanks for choosing ELDORADO.CMS!<br />Before install, please, read license.' => 'Спасибо, что выбрали ELDORADO.CMS!<br />Перед установкой, пожалуйста, ознакомьтесь с условиями лицензионного соглашения',
			'Not accept' => 'Не принимаю',
			'Accept'     => 'Принимаю',
			'License was not accepted!<br />Unable to complite installation!' => 'Вы не приняли условия лицензионного соглашения! <br />Установка прервана!',
			'To install ELDORADO.CMS select installation source' => 'Укажите, из какого источника установить ELDORADO.CMS.',
			'To install ELDORADO.CMS You have to upload archive' => 'Чтобы установить ELDORADO.CMS необходимо загрузить архив с системой на сервер.',
			'Install from' => 'Установить из',
			'Archive' => 'Архива',
			'Files on server' => 'Файлов на сервере',
			'Upload archive' => 'Загрузить архив',
			'Select file' => 'Выбрать файл',
			'Upload file error' => 'Ошибка загрузки файла',
			'Password could not be empty' => 'Поле "Пароль" должно быть заполнено',
			'Enter MySQL Db connection data' => 'Введите данные для доступа к MySQL базе',
			'Host, User or Db name could not be empty.' => 'Поля "Хост", "Логин" и "Имя базы данных" должны быть заполнены',
			'Unable to connect to MySQL server!<br />Check entered data.' => 'Не удалось соединиться с MySQL сервером! <br /> Проверьте введенные данные.',
			'Unable to create data base! Check data base user permissions.' => 'Не удается создать базу данных! Проверить права пользователя базы данных.',
			'Unable to connect to data base!<br />Check entered data.' => 'Не удается подключиться к базе данных! <br /> Проверьте введенные данные.',
			'Unable to include required file! Check installation archive!<br />Unable to complite installation.' => 'Не удается подключить нужный файл! Проверьте установочный архив! <br /> Невозможно завершить установку.',
			'Unable to connect to DB server' => 'Не удается подключиться к серверу БД',
			'Does not connected to DB server' => 'Нет соединения с сервером БД',
			'SQL query failed' => 'Ошибка SQL запросов',
			
			'Archive does not contains required files' => 'Архив не содержит необходимых файлов',
			'Unable unpack archive' => 'Невозможно распаковать архив',
			'Unable to set write permissions for config file!<br />Unable to complite installation!' => 'Не удается установить права на запись для файла конфигурации <br /> Невозможно завершить установку',
			'Unable to copy config file!<br />Unable to complite installation!' => 'Не удается скопировать файл конфигурации! <br /> Невозможно завершить установку!',
			'Unable to create directories!<br />Unable to complite installation!' => 'Не удается создать папки! <br /> Невозможно завершить установку!',
			'Selected archive does not exists' => 'Выбранный архив не существует',
			'Please, upload ELDORADO.CMS archive' => 'Пожалуйста, загрузите архив с ELDORADO.CMS',
			'Invalid password! Unable to complite installation' => 'Неправильный пароль! Невозможно завершить установку!',
			'Passwords not equal' => 'Пароли не совпадают',
			'Password could not be empty' => 'Поле "Пароль" не может быть пустым',
			'Unable to copy "index.php" file! <br />Unable to complite installation.' => 'Невозможно скопировать "файл index.php"! <br /> Невозможно завершить установку!',
			'Unable to write to .htaccess file!<br />You have to manually put following lines into .htaccess file.' => 'Не удается записать .htaccess файл! <br /> Запишите следующие строки в .htaccess файл',
			'Unable to set site style! Unable to complite installation.' => 'Не удается установить стили для сайта! Невозможно завершить установку!',
			'Unable to move "style" directory.' => 'Не удается переместить папку "style".',
			'Archive does not contains "style" directory.' => 'Архив не содержит папку "style"',
			'Unable to create user! Unable to complite install.' => 'Невозможно создать пользователя! Невозможно завершить установку!',
			'Unable connect to data base!' => 'Не удается подключиться к базе данных!',
			'Password should contains not less then 6 alfanumeric and/or punctuation sybmbols' => 'Пароль должен содержать не менее 6 символов',
			'Passwords not equal!' => 'Пароли не совпадают',
			'Password could not be empty!' => 'Поле "Пароль" не может быть пустым',
			'Unable to insert data into data base!<br />Unable to complite installation.' => 'Не удается вставить данные в базу данных! <br /> Невозможно завершить установку.',
			'Main administrator in ELDORADO.CMS has login "root".<br /> Please, enter new password and e-mail for user "root".' => 'Логин главного администратора сайта - "root"<br/> Пожалуйста, введите для него пароль и e-mail адрес.',
			'ELDORADO.CMS was succesfully instaled!<br /> Press "Ok" to go to You new site.' => 'Поздравляем! ELDORADO.CMS успешно установлена <br /> нажмите "ОК", чтобы перейди на сайт.',
			'Do You really interrupt installation?' => 'Вы действительно хотите прервать установку?',
			'Here You may enter common information about site.<br /> But, also, You may do it later on site.' => 'Здесь вы можете ввести общую информацию о сайте. <br /> Также доступно через "Контрольный центр" - "Настройки сайта"',
			'Upload style' => 'Загрузить стиль',
			'Select style' => 'Выбрать стиль',
			'Site name' => 'Имя сайта',
			'Contact information' => 'Контактная информация',
			'Phone number' => 'Номер телефона',
			'You may select one of existed site designs or upload You own (archive with "style" directory).' => 'Вы можете выбрать один из существовавших дизайна сайта или загрузить свой.',
			'You use default site design or upload You own (archive with "style" directory).' => 'Вы можете использовать дизайн сайта по умолчанию или загрузить свой',
			'Repeat' => 'Еще раз',
			'Password' => 'Пароль',
			'Db name' => 'Имя базы данных',
			'User' => 'Логин',
			'Host' => 'Хост',
			'Socket' => 'Сокет',
			'Installed copy of ELDORADO.CMS was detected!<br />If You continue, all existed data will be lost!<br />To continue enter site administrator password!' => 'Найдена установленная копия ELDORADO.CMS! <br /> Если вы продолжите установку, все существовали данные будут потеряны! <br /> Чтобы продолжить введите пароль администратора сайта!',
			'Unable to save config file!<br />Unable to complite installation.' => 'Не удается сохранить файл конфигурации! <br /> Невозможно завершить установку!',
			'Unable to switch to required DB' => 'Не удаеться подключиться к БД',
			'Unable to copy %s to %s' => 'Не удается скопировать %s в %s',
			'File %s has no write permissions' => 'Файл %s не имеет прав на запись',
			'Unable to find tar and gzip command! This need to unpack archive. To install ELDORADO.CMS, upload files usinf FTP/SSH. <br />Instalation interrupted.' => 'Невозможно найти TAR и GZIP команду! Это необходимо для распаковки архива. Чтобы установить ELDORADO.CMS, закачайте распакованные файлы системы на сервер через FTP / SSH. <br /> Инсталляция прервана.',
			'Invalid archive type' => 'Неправильный тип архива'
			),
		'ua' => array(
			'ELDORADO.CMS installation' => 'Установка ELDORADO.CMS',
			'Select instalation language' => 'Виберіть мову установки',
			'Thanks for choosing ELDORADO.CMS!<br />Before install, please, read license.' => 'Дякуємо, що обрали ELDORADO.CMS! <br /> Перед установкою, будь ласка, ознайомтеся з умовами ліцензійної угоди',
			'Not accept' => 'Не приймаю',
			'Accept'     => 'Приймаю',
			'License was not accepted!<br />Unable to complite installation!' => 'Ви не погодилися з умовами ліцензійної угоди! <br />Установка перервана!',
			'To install ELDORADO.CMS select installation source' => 'Вкажіть, з якого джерела встановити ELDORADO.CMS.',
			'To install ELDORADO.CMS You have to upload archive' => 'Щоб встановити ELDORADO.CMS необхідно завантажити архів з системою на сервер.',
			'Install from' => 'Встановити з',
			'Archive' => 'Архіву',
			'Files on server' => 'Файли на сервері',
			'Upload archive' => 'Завантажити архів',
			'Select file' => 'Вибрати файл',
			'Upload file error' => 'Помилка завантаження файлу',
			'Password could not be empty' => 'Поле "Пароль" має бути заповнено',
			'Enter MySQL Db connection data' => 'Введіть дані для доступу до MySQL базі',
			'Host, User or Db name could not be empty.' => 'Поля "Хост", "Логін" і "Ім&rsquo;я бази даних" повинні бути заповнені',
			'Unable to connect to MySQL server!<br />Check entered data.' => 'Не вдалося з&rsquo;єднатися з сервером MySQL! <br /> Перевірте введені дані.',
			'Unable to create data base! Check data base user permissions.' => 'Не вдається створити базу даних! Перевірити права користувача бази даних.',
			'Unable to connect to data base!<br />Check entered data.' => 'Не вдається підключитися до бази даних! <br /> Перевірте введені дані.',
			'Unable to include required file! Check installation archive!<br />Unable to complite installation.' => 'Не вдається підключити потрібний файл! Перевірте установочний архів! <br /> Неможливо завершити установку.',
			'Unable to connect to DB server' => 'Не вдається підключитися до сервера БД',
			'Does not connected to DB server' => 'Немає з&rsquo;єднання з сервером БД',
			'SQL query failed' => 'Помилка SQL запитів',
			'Site directory has no write permissions!<br />Unable to complite installation!' => 'Папка захищена від запису! <br />Продовжити встановлення неможливо. Виправте будь ласка.',
			'Archive does not contains required files' => 'Архів не містить необхідних файлів',
			'Unable unpack archive' => 'Неможливо розпакувати архів',
			'Unable to set write permissions for config file!<br />Unable to complite installation!' => 'Не вдається встановити права на запис для файлу конфігурації <br /> Неможливо завершити установку',
			'Unable to copy config file!<br />Unable to complite installation!' => 'Не вдається скопіювати файл конфігурації! <br /> Неможливо завершити установку!',
			'Unable to create directories!<br />Unable to complite installation!' => 'Не вдається створити папки! <br /> Неможливо завершити установку!',
			'Selected archive does not exists' => 'Обраний архів не існує',
			'Please, upload ELDORADO.CMS archive' => 'Будь ласка, завантажте архів з ELDORADO.CMS',
			'Invalid password! Unable to complite installation' => 'Неправильний пароль! Неможливо завершити установку!',
			'Passwords not equal' => 'Паролі не збігаються',
			'Password could not be empty' => 'Поле "Пароль" не може бути порожнім',
			'Unable to copy "index.php" file! <br />Unable to complite installation.' => 'Неможливо скопіювати "файл index.php"! <br /> Неможливо завершити установку!',
			'Unable to write to .htaccess file!<br />You have to manually put following lines into .htaccess file.' => 'Не вдається записати. Htaccess файл! <br /> Запишіть наступні рядки в .htaccess файл',
			'Unable to set site style! Unable to complite installation.' => 'Не вдається встановити стилі для сайту! Неможливо завершити установку!',
			'Unable to move "style" directory.' => 'Не вдається перемістити папку "style".',
			'Archive does not contains "style" directory.' => 'Архів не містить папку "style"',
			'Unable to create user! Unable to complite install.' => 'Неможливо створити користувача! Неможливо завершити установку!',
			'Unable connect to data base!' => 'Не вдається підключитися до бази даних!',
			'Password should contains not less then 6 alfanumeric and/or punctuation sybmbols' => 'Пароль повинен містити не менше 6 символів',
			'Passwords not equal!' => 'Паролі не збігаються',
			'Password could not be empty!' => 'Паролі не збігаються',
			'Unable to insert data into data base!<br />Unable to complite installation.' => 'Не вдається вставити дані в базу даних! <br /> Неможливо завершити установку.',
			'Main administrator in ELDORADO.CMS has login "root".<br /> Please, enter new password and e-mail for user "root".' => 'Логін головного адміністратора сайту - "root" <br/> Будь ласка, введіть для нього пароль та e-mail адресу.',
			'ELDORADO.CMS was succesfully instaled!<br /> Press "Ok" to go to You new site.' => 'Вітаємо! ELDORADO.CMS успішно встановлено <br /> натисніть "ОК", щоб перейди на сайт.',
			'Do You really interrupt installation?' => 'Ви дійсно хочете перервати встановлення?',
			'Here You may enter common information about site.<br /> But, also, You may do it later on site.' => 'Тут ви можете ввести загальну інформацію про сайт. <br /> Також доступне через "Контрольний центр" - "Установки сайту"',
			'Upload style' => 'Завантажити стиль',
			'Select style' => 'Вибрати стиль',
			'Site name' => 'Ім&rsquo;я сайту',
			'Contact information' => 'Контактна інформація',
			'Phone number' => 'Номер телефону',
			'You may select one of existed site designs or upload You own (archive with "style" directory).' => 'Ви можете вибрати один з існуючих дизайну сайту або завантажити свій.',
			'You use default site design or upload You own (archive with "style" directory).' => 'Ви можете використати дизайн сайту за умовчанням або завантажити свій',
			'Repeat' => 'Ще раз',
			'Password' => 'Пароль',
			'Db name' => 'Ім&rsquo;я бази даних',
			'User' => 'Логін',
			'Host' => 'Хост',
			'Socket' => 'Сокет',
			'Installed copy of ELDORADO.CMS was detected!<br />If You continue, all existed data will be lost!<br />To continue enter site administrator password!' => 'Знайдена встановлена копія ELDORADO.CMS! <br /> Якщо ви продовжите, всі існували дані будуть втрачені! <br /> Щоб продовжити введіть пароль адміністратора сайту!',
			'Unable to save config file!<br />Unable to complite installation.' => 'Не вдається зберегти файл конфігурації! <br /> Неможливо завершити установку!',
			'Unable to switch to required DB' => 'Не вдається підключитися до БД',
			'Unable to copy %s to %s' => 'Не вдається скопіювати %s в %s',
			'File %s has no write permissions' => 'не має прав на запис',
			'Unable to find tar and gzip command! This need to unpack archive. To install ELDORADO.CMS, upload files usinf FTP/SSH. <br />Instalation interrupted.' => 'Неможливо знайти TAR і GZIP команду! Це необхідно розпакувати архів. Щоб встановити ELDORADO.CMS, закачувати файли через FTP / SSH. <br /> Інсталяція перервана.',
			'Invalid archive type' => 'Неправильний тип архіву'
			)
		);
	
	
	var $_license = array(
		'ru' => "Лицензионное соглашение на использование системы управления сайтом ELDORADO.CMS

	Прочтите внимательно нижеизложенное, прежде чем устанавливать, копировать или иным образом использовать приобретенный продукт 'Система управления ELDORADO.CMS'. Любое использование Вами приобретенного продукта, в том числе его установка и копирование, означает Ваше согласие с условиями приведенного ниже Лицензионного соглашения.

	Настоящее лицензионное соглашение (далее Соглашение) является юридическим документом, заключаемым между Вами, конечным пользователем (физическим или юридическим лицом) (далее Пользователь), и  ООО «Студия 42» (далее Студия) относительно программного продукта «Система управления ELDORADO.CMS» (далее Система или Программное обеспечение).

	Если Вы не согласны с условиями настоящего лицензионного соглашения, вы не имеете права использовать данную Программу. 


	Лицензионное соглашение вступает в силу с момента приобретения или установки продукта и действует на протяжении всего срока использования продукта. 

	1. ПРЕДМЕТ ЛИЦЕНЗИОННОГО СОГЛАШЕНИЯ И УСЛОВИЯ ИСПОЛЬЗОВАНИЯ

	•	Предметом настоящего лицензионного соглашения является право использования одной копии системы управления ELDORADO.CMS.

	•	В рамках одной копии системы управления пользователю разрешается создавать неограниченное, число сайтов на различных языках в рамках одного проекта или домена. 

	•	Вы обязуетесь не распространять Систему управления ELDORADO.CMS. Под распространением Программного обеспечения понимается тиражирование или предоставление доступа третьим лицам к воспроизведенным в любой форме компонентам Системы, в том числе сетевыми и иными способами, а также путем их продажи, проката, сдачи внаем или предоставления взаймы. Тиражированием не считается изготовление одной или нескольких резервных копий базы данных программы в целях обеспечения безопасности или архивации.

	•	Конечный пользователь имеет право вносить любые изменения в код системы, добавлять и удалять файлы. Кроме изменения или удаления любой информации об авторских правах.

	•	Запрещается использование системы управления в любых случаях, которые, нарушают законодательство РФ.

	•	Данное соглашение распространяется на все версии и компоненты системы управления ELDORADO.CMS, а так же обновления предоставляемые Пользователю, в период гарантийного обслуживания.



	2. АВТОРСКИЕ И ИМУЩЕСТВЕННЫЕ ПРАВА


	•	Все авторские и имущественные права на данную Систему управления принадлежат исключительно авторам Системы. Права на продажу и тиражирование системы принадлежат ООО «Студия 42». Вам, конечному Пользователю, предоставляется неисключительное право, т.е. именная, непередаваемая и неисключительная Лицензия на использование Программы в указанных в документации целях и при соблюдении приведенных ниже условий. Лицензия предоставляется Вам, только Вам и никому больше, если на то нет письменного согласия Студии. 

	•	Все права интеллектуальной собственности на информационное содержание, которое не является частью программы, но, доступ к которому предоставляет программа, принадлежат владельцам прав на это содержание и защищены законами об авторском праве и другими законами и международными соглашениями о правах на интеллектуальную собственность.


	3. ГАРАНТИЙНЫЕ ОБЯЗАТЕЛЬСТВА

	•	Студия обслуживает работоспособность Системы в течение 12 (двенадцати) месяцев со дня ее покупки при условии, что она используется с аппаратными средствами, операционными системами и серверами баз данных, для которых она была разработана, и в полном соответствии с Руководством по эксплуатации. 

	•	В течении срока гарантийного  обслуживания Студия предоставляет:

	o	Бесплатные обновление системы.
	o	Консультации пользователя по телефону или электронной почте в рабочее время с 9 до 18 часов по Московскому времени.
	o	Проведение консультационно-обучающих семинаров по работе с системой управления ELDORADO.CMS, при наборе группы более 10 человек в определенно назначенное время.

	•	Единственным гарантийным обязательством ООО «Студия 42» является бесплатное устранение неисправностей системы, не позволяющих ее использование по прямому назначению. Получение любых необходимых для устранения неисправностей исправлений системы в электронном виде или на любых других носителях осуществляется силами конечного Пользователя и за его счет.

	•	За исключением вышесказанного, не существует никаких других явно выраженных или подразумеваемых гарантий в отношении Системы или ее составных частей, в том числе, гарантий пригодности использования Системы непосредственно для Ваших конкретных целей. 

	•	Студия не несет ответственности за работу системы, в которую были внесены изменения Вами или третьими лицами. Студия в праве отказать в обслуживании в течении гарантийного периода если сбой в работе системы управления произошел по вине конечного пользователя.

	"
	

		);
	
}


class elInstallerDb
{
	var $_link = null;
	var $_res  = null;
	var $error = '';
	
	function connect($host, $sock, $user, $pass)
	{
		$host = !empty($sock) ? ':'.$sock : '';
		if (false == ($this->_link = mysql_connect($host, $user, $pass)))
		{
			return $this->_error('Unable to connect to DB server');
		}
		$this->query('SET SESSION character_set_client=\'utf8\'');
    	$this->query('SET SESSION character_set_connection=\'utf8\'');
    	$this->query('SET SESSION character_set_results=\'utf8\'');
		
		return true;
	}
	
	function selectDb($db)
	{
		return mysql_select_db($db) ? true : $this->_error('Unable to switch to required DB');
	}

	function isDbExists($db)
	{
		if ($this->query('SHOW DATABASES'))
		{
			while ($r = $this->nextRecord())
			{
				if ($r['Database'] == $db)
				{
					return true;
				}
			}
		}
	}
	
	function createDb($db)
	{
		return $this->query('CREATE DATABASE `'.$db.'` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;');
	}
	
	function query($sql)
	{
		if (!$this->_link)
		{
			return $this->_error('Does not connected to DB server');
		}
		
		$this->_res = mysql_query($sql, $this->_link);
		return $this->_res ? $this->_res : $this->_error('SQL query failed');
	}
	
	function nextRecord()
	{
	    if ( !$this->_res )
	    {
	      return $this->_error('There is no valid MySQL result resource');
	    }
	    if ( false == ($record = mysql_fetch_array($this->_res, MYSQL_ASSOC)) )
	    {
	      	mysql_free_result( $this->_res );
	      	$this->_res = null;
	    }
	    return $record;
	  }
	
	function tablesList()
	{
		$tables = array();
		$resID = $this->query('SHOW TABLES');
	  	while ( $r = mysql_fetch_array($resID))
	  	{
	  		$tables[] = $r[0];
	  	}
		return $tables;
	}
	
	function _error($err)
	{
		$this->error = $err;
		return false;
	}
}

if (!defined('EL_URL'))
{
	define('EL_URL', $_SERVER['REQUEST_URI']);
}

function elThrow($err, $msg)
{
	echo $msg;
}


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
	 * Возвращает массив директории и файлы в заданной директории с подробной информацией о них ( см FileInfo::info())
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

	function find($path, $regexp, $deep=true)
	{
		$ret = array();
		$path = realpath($path);
		if (!$path || !is_dir($path))
		{
			return $ret;
		}
		
		if ( false == ($d = dir($path)))
		{
			trigger_error(sprintf('Unable to open directory %s', $path), E_USER_WARNING);
			return false;
		}
		while( $entr = $d->read())
		{
			if ('.'!=$entr && '..'!=$entr)
			{
				if (preg_match($regexp, $entr))
				{
					$ret[] = $d->path.DIRECTORY_SEPARATOR.$entr;
				}
				if (is_dir($d->path.DIRECTORY_SEPARATOR.$entr) && $deep)
				{
					$ret += elFS::find($d->path.DIRECTORY_SEPARATOR.$entr, $regexp);
				}
			}
		}
		$d->close();
		return $ret;
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
	 * последовательно создает дерево директории по указанному пути
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
	 * рекурсивно удаляет директорию со всеми вложенными директориями и файлами
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

