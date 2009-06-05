<?php

/**
 * Core version and name
 */
define ('EL_VER',  '3.8.2');
define ('EL_NAME', 'Origami');

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
				'__reg__'         => 'Registration',
				'__fm__'          => 'FM',
				'__ver__'         => 'Version',
				'__search__'      => 'Sherlock',
				'__stat__'        => 'Stat',
				'__icart__'       => 'ICart',
				'__pl__'          => '',
				'__auth__'        => '',
				'__passwd__'      => '',
				'__logout__'      => '',
				'__clean_cache__' => '',
				'__authkey__'     => 'UpdateAuth',
				'__capt__'        => ''
				);

	// ******************** PUBLIC METHODS ******************** //


	function load()
	{
		$this->_ts     = utime();
		$nav           = & elSingleton::getObj('elNavigator');
		$nav->init($this->_srvMap);
		$this->pageID  = $nav->getCurrentPageID();
		$this->mName   = $nav->getCurPageModuleName();
		$this->args    = $nav->getRequestArgs();
		$this->ats     = & elSingleton::getObj('elATS');
		$this->ats->init($this->pageID);

		define ('EL_URL', $nav->getURL() ); //echo EL_URL;

		if ( !empty($this->args[0]) &&  EL_URL_PRNT == $this->args[0] )
		{
			array_shift($this->args);
			define('EL_WM',     EL_WM_PRNT);
			define('EL_WM_URL', EL_URL.EL_URL_PRNT.'/');
		}
		elseif ( !empty($this->args[0]) && EL_URL_POPUP == $this->args[0] )
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
		elAddJs('jquery.js',     EL_JS_CSS_FILE);
		elAddJs('common.lib.js', EL_JS_CSS_FILE);
		elAddJs('el.lib.js',     EL_JS_CSS_FILE);
		elAddJs('var elBaseURL="'.EL_BASE_URL.'/"; var elURL="'.EL_URL.'";');
	}



	function run()
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$isCart = 'EShop' == $this->mName && !empty($this->ars[0]) && 'order' == $this->args[0];
		if ( !( $conf->get('cacheAllow', 'common') && !$this->ats->allow(EL_WRITE) && empty($_POST) ) || $isCart )
		{
			$this->cacheAllow = false;
		}
		else
		{
			$this->cacheAllow = true;
			$this->cacheObj   = & elSingleton::getObj( 'elCache' );
			$this->cacheID    = md5( $this->pageID
						.'|'.implode(',', $this->args)
						.'|'.$this->ats->getUserID()
						.'|'.($_GET ? implode('&', $_GET) : '') );
			$this->cacheContent = $this->cacheObj->get($this->cacheID, EL_WM );

		}

		if ( EL_WM_XML == EL_WM )
		{
			return $this->_outputXML();
		}
		else
		{
			$this->_outputHTML( $conf->get('gzOutputAllow'),
								$conf->get('timer') ? utime() - $this->_ts : '',
								'n' != $conf->get('searchFormPosition', 'layout') );
			$period = $conf->get('auto', 'backup');
			if ($period && time()-$period*86400 > $conf->get('ts', 'backup'))
			{
				if ( $this->_autoBackup() )
				{
					$conf->set('ts', time(), 'backup');
				}
				else
				{
					@error_log( 'Could not create backup', 3, EL_DIR_LOG.$_SERVER['HTTP_HOST'].'.error-log');
					$conf->set('auto', 0, 'backup');
				}
				$conf->save();
			}
		}
	}

	// ======================   PRIVATE METHODS ========================= //

	function _autoBackup( )
	{
		if ( elSingleton::incLib('./modules/SiteBackup/elModuleSiteBackup.class.php') )
		{
			$this->module = & elSingleton::getObj('elModuleSiteBackup');
			$this->module->init( $this->pageID, $this->args, $this->mName, EL_FULL );
			return $this->module->create(true);
		}
		return false;
	}

	function _loadModule( )
	{
		// special requests - processed by core
		if ( isset($this->args[0]) && isset($this->_srvMap[$this->args[0]]) )
		{
			$this->rnd             = & elSingleton::getObj('elSiteRenderer');
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
			header('HTTP/1.x 404 Not Found'); 
			elThrow(E_USER_ERROR, 'Error 404: Page %s not found.', $_SERVER['REQUEST_URI']);
		}
		return true;
	}

	function _outputXML()
	{
		if ( $this->cacheAllow && $this->cacheContent )
		{
			$xml = $this->_cacheContent;
		}
		else
		{
			if ( !$this->_loadModule() )
			{
				elLocation(EL_URL);
			}
			$xml = $this->module->toXML();

			if ($this->cacheAllow )
			{
				$this->cacheObj->save($this->cacheID, $xml, EL_WM_XML );
			}
		}

		header('Content-type: text/xml; charset=utf-8');
		echo $xml;
	//echo htmlspecialchars($xml);
	}


	function _outputHTML( $compress=true, $timer=0, $sherlock=false )
	{
		$this->rnd             = & elSingleton::getObj('elSiteRenderer');
		$this->rnd->userName   = $this->ats->isUserAuthed() ? $this->ats->user->getFullName() : '';
		$this->rnd->isRegAllow = $this->ats->isUserRegAllow();
		$this->rnd->adminMode  = $this->ats->allow(EL_WRITE);

		if ( $this->cacheAllow && $this->cacheContent )
		{
			$this->rnd->renderFromCache($this->cacheContent);
		}
		else
		{
			if ($sherlock)
			{
				$js  = "var searchProgress = '".m('Searching for "%s"')."';\n";
				$js .= "var resTitle = '".m('Search results for')."';\n";
				$js .= "var noResMsg = '".m('There is nothing was found')."';";
				elAddJs( $js );
				
				elAddJs('ServiceSherlock.lib.js', EL_JS_CSS_FILE);
			}
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

			$this->rnd->render( $this->cacheAllow );
			if ( $this->cacheAllow )
			{
				$this->cacheObj->save($this->cacheID, $this->rnd->getContent(), EL_WM );
			}
		}

		if ( !headers_sent()  )
		{
			header('Content-type: text/html; charset=utf-8');
		}
		if ( $compress )
		{
			ob_start("ob_gzhandler");
		}
		$this->rnd->display( $timer,
							$this->ats->user->getFullName(),
							$this->ats->isUserAuthed(),
							$this->ats->isUserRegAllow() );
	}

	function _service()
	{
		$args    = $this->args;
		$service = array_shift($this->args);// echo $service;
		switch ( $service )
		{
			case "__pl__":
				if (false == ($name = array_shift($this->args)) || null  == ($plugin = &elSingleton::getPlugin($name)) )
				{
					elLocation(EL_BASE_URL);
				}
				//elprintR($plugin);
				$plugin->call($this->args);
				break;

			case "__clean_cache__":
				elCleanCache();
				elLocation(EL_URL);
				break;

			case "__passwd__":
				$this->ats->passwd( $this->ats->getUser(), EL_PASSWD_REMIND );
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
					header('HTTP/1.0 404 Not Found');
					elThrow(E_USER_WARNING, 'Error 404: Page not found.', null);
				}
				include_once(EL_DIR_CORE.'services/elService.class.php');
				$class = 'elService'.$this->_srvMap[$service];
				if (!include_once(EL_DIR_CORE.'services/'.$class.'.class.php'))
				{
					header('HTTP/1.0 404 Not Found');
					elThrow(E_USER_WARNING, 'Error 404: Page not found.', null);
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
