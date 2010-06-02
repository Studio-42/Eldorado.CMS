<?php

/**
 * Core version and name
 */
define ('EL_VER',  '3.9.4');
define ('EL_NAME', 'Kioto');

class elCore
{
	var $pageID  = 0;
	var $args    = array();
	var $mName   = '';
	var $mImport = false;
	var $ats     = null;
	var $module  = null;
	var $rnd     = null;
	var $ts      = 0;
	var $_srvMap = array(
				'__profile__'     => 'Profile',
				'__finder__'      => 'Finder',
				'__ver__'         => 'Version',
				'__search__'      => 'Sherlock',
				'__icart__'       => 'ICart',
				'__pl__'          => '',
				'__auth__'        => '',
				'__logout__'      => '',
				'__clean_cache__' => '',
				'__authkey__'     => 'UpdateAuth',
				'__capt__'        => '',
				'__dir__'         => 'Directory'
				);

	// ******************** PUBLIC METHODS ******************** //


	function load()
	{
		$this->_ts     = utime();
		$nav           = & elSingleton::getObj('elNavigator');
		$nav->init($this->_srvMap);
		$this->pageID  = $nav->getCurrentPageID();
		$this->ats     = & elSingleton::getObj('elATS');
		$this->ats->init($this->pageID);
		$this->mName   = $nav->getCurPageModuleName();
		$this->args    = $nav->getRequestArgs();

		define ('EL_URL', $nav->getURL() ); 

		if ( !empty($this->args[0]) && EL_URL_POPUP == $this->args[0] )
		{
			array_shift($this->args);
			$append = !empty($this->args[0]) && 0 === strpos($this->args[0], '__') ? '/'.$this->args[0].'/' : '/';
			define('EL_WM',     EL_WM_POPUP);
			define('EL_WM_URL', EL_URL.EL_URL_POPUP.$append); //echo EL_WM_URL;
		}
		elseif (!empty($this->args[0]) && EL_URL_XML == $this->args[0] )
		{
			array_shift($this->args);
			define('EL_WM',     EL_WM_XML);
			define('EL_WM_URL', EL_URL);
		}
		else
		{
			define('EL_WM',     EL_WM_NORMAL);
			define('EL_WM_URL', EL_URL);
		}
		
		if (EL_WM != EL_WM_XML)
		{
			foreach ($_GET as $v)
			{
				if (false != strpos($v, '>') && false != strpos($v, '<'))
				{
					$this->_output404();
				}
			}
		}
		
	}

	function run()
	{
		if ( EL_WM_XML == EL_WM )
		{
			return $this->_outputXML();
		}
		else
		{
			$conf = & elSingleton::getObj('elXmlConf');
			$this->_outputHTML( $conf->get('gzOutputAllow'), $conf->get('timer') ? utime() - $this->_ts : '');
			$this->_cron();
		}
	}

	// ======================   PRIVATE METHODS ========================= //

	function _cron()
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$next = $conf->get('next', 'cron');
		if ($next == null) // first init
		{
			$next = time() - 1; // make sure we run now
		}

		if ($next <= time()) // run cron
		{
			$conf->set('next', time() + 86400, 'cron');
			$conf->save(); // save config and run crontabs

			ignore_user_abort(true); // detach from browser control
			flush();
			//set_time_limit(60);

			foreach (array('./core/hook', './hook') as $dir)
			{
				if (is_dir($dir) && ($dh = opendir($dir)) !== false)
				{
					while (false !== ($cfile = readdir($dh)))
					{
						if (!is_file($dir.'/'.$cfile))
						{
							continue;
						}
						if (!preg_match('/^(elCron.+)\.class\.php$/', $cfile, $m))
						{
							continue;
						}

						$cname = $m[1];
						if (include_once($dir.'/'.$cfile))
						{ // run task
							$cron = new $cname;
							$cron->run();
						}
					} // end while
				} // end if
			} // end foreach
		} // end if run cron
		return true;
	}

	function _loadModule( )
	{
		// special requests - processed by core
		if ( isset($this->args[0]) && isset($this->_srvMap[$this->args[0]]) )
		{
			$this->rnd = & elSingleton::getObj('elSiteRenderer');
			$this->rnd->prepare( implode('/', $this->args), false );
			return $this->_service();
		}

		// check permissions
		if ( !$this->ats->allow(EL_READ) )
		{
			// user not authed
			if ( !$this->ats->isUserAuthed() )
			{
				return $this->ats->auth();
			}
			else
			{
				// authed but has no permissions
				header('HTTP/1.0 403 Acces denied');
				elThrow(E_USER_WARNING, 'Error 403: Acces to page "%s" denied.', EL_URL, EL_BASE_URL);
			}
		}
		if ($this->ats->allow(EL_WRITE))
		{
			elLoadJQueryUI();
			elAddCss('elmenu-float.css',     EL_JS_CSS_FILE);
			elAddJs('elcookie.min.js', EL_JS_CSS_FILE);
			elAddJs('jquery.elmenu.min.js', EL_JS_CSS_FILE);
		}

		$class = 'elModule'.$this->mName;
		$dir   = 'modules/'.$this->mName.'/';
		$mode  = $this->ats->getPageAccessMode();

		// There is no module for this page - invalid configuration
		// generate error and do nothing
		if ( !elSingleton::incLib( $dir.$class.'.class.php', true) )
		{
			return elThrow(E_USER_ERROR, m('Page "%s" has incorrect module configuration'), EL_URL, null, null, __FILE__, __LINE__ );
		}
		if ( EL_READ < $mode && elSingleton::incLib( $dir.'elModuleAdmin'.$this->mName.'.class.php', true) )
		{
			$class = 'elModuleAdmin'.$this->mName;
		}

		//create module object
		$this->module = & elSingleton::getObj($class);
		
		$this->module->init( $this->pageID, $this->args, $this->mName, $mode );
		
		//check for invalid requests
		if ( !$this->module->checkArgs() )
		{
			$this->_output404();
		}
		return true;
	}

	function _output404()
	{
		header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
		// die here
		if ($error = file_get_contents(EL_DIR_STYLES.'404.html'))
		{
			$err_title = m('The page you were looking for doesn\'t exist (404)');
			$err_msg   = m('You may have mistyped the address or the page may have moved.');
			$error = str_replace('{TITLE}',   $err_title, $error);
			$error = str_replace('{MESSAGE}', $err_msg,   $error);
			die($error);
		}
		elThrow(E_USER_ERROR, 'Error 404: Page not found.');
	}

	function _outputXML()
	{
		if ( !$this->_loadModule() )
		{
			elLocation(EL_URL);
		}
		$xml = $this->module->toXML();

		header('Content-type: text/xml; charset=utf-8');
		echo $xml;
	//echo htmlspecialchars($xml);
	}


	function _outputHTML( $compress=true, $timer=0 )
	{
		$this->rnd = & elSingleton::getObj('elSiteRenderer');

		if ( EL_WM_NORMAL == EL_WM )
		{
		   elSingleton::loadPlugins($this->pageID);
		}

		if ( $this->_loadModule() )
		{
			$this->rnd->prepare( implode('/', $this->args) );
			$this->module->run();
			$this->module->stop();
		}
		else
		{
			$this->rnd->prepare( implode('/', $this->args), false );
		}

		elSingleton::unloadPlugins();

		$this->rnd->render();

		if ( !headers_sent()  )
		{
			header('Content-type: text/html; charset=utf-8');
		}
		if ( $compress )
		{
			ob_start("ob_gzhandler");
		}
		$this->rnd->display( $timer);
	}

	function _service()
	{
		$args    = $this->args;
		$service = array_shift($this->args);// echo $service;
		switch ( $service )
		{
			case "__pl__":
			
				if (false == ($name = array_shift($this->args)) || null  == ($plugin = elSingleton::getPlugin($name)) )
				{
					elLocation(EL_BASE_URL);
				}
				// elprintR($plugin);
				$plugin->call($this->args);
				break;

			case "__clean_cache__":
				elCleanCache();
				elLocation(EL_URL);
				break;

			case "__auth__":
				$this->ats->auth();
				break;

			case "__logout__":
				$this->ats->logOutUser();
				break;
			
			case "__capt__":
				$captID = trim($this->args[0]);
				if ( empty($_SESSION['captchas'][$captID])   )
				{
					$_SESSION['captchas'][$captID] = substr(md5(uniqid('')),-9,5);
				}
				elDisplayCaptcha($_SESSION['captchas'][$captID]);
				exit;
				break;
			
			default:

				if (empty($this->_srvMap[$service]))
				{
					$this->_output404();
				}
				include_once(EL_DIR_CORE.'services/elService.class.php');
				$class = 'elService'.$this->_srvMap[$service];
				if (!include_once(EL_DIR_CORE.'services/'.$class.'.class.php'))
				{
					$this->_output404();
				}

				$srv = & elSingleton::getObj($class);
				$srv->name = $this->_srvMap[$service];
				$srv->init($this->args);

				if ( EL_WM_XML != EL_WM )
				{
					$srv->run();
					$srv->stop();
				}
				else
				{
				  if (!headers_sent())
				  {
					 header('Content-type: text/xml; charset=utf-8');
				  }
					echo $srv->toXML();
				}
		}
	}

}

?>
