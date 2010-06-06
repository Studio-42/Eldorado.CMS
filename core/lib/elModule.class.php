<?php
/**
 * Base class for modules
 *
 * @package core
 **/
class elModule {
	/**
	 * module name without "el" prefix (case sensitive)
	 * required for many service actions
	 *
	 * @var string
	 **/
	var $name = '';
	/**
	 * current page ID
	 *
	 * @var int
	 **/
	var $pageID = 0;
	/**
	 * flag to protect system required modules from deletion
	 *
	 * @var bool
	 **/
	var $required = false;
	/**
	 * URL args -> class methods mapping
	 *
	 * @var array
	 **/
	var $_mMap = array();
	/**
	 * URL args -> class methods mapping (for admin mode)
	 *
	 * @var array
	 **/
	var $_mMapAdmin = array();
	/**
	 * URL args -> class methods mapping (configure module methods)
	 *
	 * @var array
	 **/
	var $_mMapConf  = array(
		'conf' => array('m'=>'configure', 'ico'=>'icoConf', 'l'=>'Configuration')
	);
	/**
	 * URL args -> class methods mapping (import/export methods)
	 *
	 * @var array
	 **/
	var $_mMapConfImport = array(
		'import' => array('m'=>'configureImport', 'l'=>'Data import configuration', 'ico'=>'icoImportConf'),
		'export' => array('m'=>'configureExport', 'l'=>'Data export configuration', 'ico'=>'icoExportConf')
		);
	/**
	 * module configuration
	 *
	 * @var array
	 **/
	var $_conf = array(); 
	/**
	 * group ID in conf file from which fetch config
	 * for usual pages (not from control center - page ID)
	 *
	 * @var string|int
	 **/
	var $_confID  = 0;
	/**
	 * args list passed by core (from URL)
	 *
	 * @var array
	 **/
	var $_args = array();
	/**
	 * Method's Handler which requested through URL now
	 *
	 * @var string
	 **/
	var $_mh = null;
	/**
	 * renderer object
	 *
	 * @var object
	 **/
	var $_rnd = null;
	/**
	 * Renderer class name
	 * class must be placed in module dir and named - elRnd[module name],
	 * otherwise base elModuleRenderer is used
	 *
	 * @var string
	 **/
	var $_rndClass  = 'elModuleRenderer';
	/**
	 * current user acces mode to page
	 *
	 * @var int
	 **/
	var $_aMode     = EL_READ;
	/**
	 * only in MultiModule - list of subModules (to render as tabs)
	 *
	 * @var array
	 **/
	var $_tabs      = null;
	/**
	 * only in MultiModule - key  current subModule
	 *
	 * @var string
	 **/
	var $_curTab    = '';
	/**
	 * only for MultiModule - URL part - path to current submodule if it is not default
	 *
	 * @var string
	 **/
	var $_smPath = '';
	/**
	 * does default method except arguments from URL
	 *
	 * @var bool
	 **/
	var $_defMethodNoArgs = false;
	/**
	 * memebers shared with renderer
	 *
	 * @var array|null
	 **/
	var $_sharedRndMembers = null;
	//**************************************************************************************//
	// *******************************  PUBLIC METHODS  *********************************** //
	//**************************************************************************************//

	/**
	 * initilize module
	 *
	 * @return void
	 **/
	function init($pageID, $args, $name, $aMode=EL_READ) {
		$this->pageID   = $pageID;
		$this->name     = $name;
		$this->_args    = $args;
		$this->_aMode   = $aMode;
		if (empty($this->_confID)) {
			$this->_confID  = $this->pageID;
		}

		if (EL_READ < $this->_aMode) {
			$this->_initAdminMode();
		} else {
			$this->_initNormal();
		}

		if (null != ($h = $this->_arg()) 
		&& !empty($this->_mMap[$h]['m']) 
		&& method_exists($this, $this->_mMap[$h]['m'])) {
			$this->_mh = array_shift($this->_args);
		} else {
			$this->_mh = '';
		}

		elAddCss('modules/'.$this->name.'.css');
		$this->_onInit();
	}


	/**
	 * check if method get args from URL
	 * used by core to detect 404
	 *
	 * @return bool
	 **/
	function checkArgs() {
		if (!empty($this->_args)) {
			return !(empty($this->_mh)
			       && $this->_defMethodNoArgs
			       && 'favicon.ico' != $this->_args[0] // maybe this in htaacess already?
			       && 'robots.txt' != $this->_args[0]); // and this
		}
		return true;
	}

	/**
	 * run module (called by core)
	 *
	 * @return void
	 **/
	function run() {
		if (!$this->_mh) {
			$method = 'defaultMethod';
		} else {
			if (method_exists($this, $this->_mMap[$this->_mh]['m'])) {
				$method = $this->_mMap[$this->_mh]['m'];
			} else {
				array_unshift($this->_args, $this->_mh);
				$this->_mh = '';
				$method    = 'defaultMethod';
			}
		}
		$this->$method();
	}

	/**
	 * Module default method (Abstract)
	 *
	 * @return void
	 */
	function defaultMethod() {}

	/**
	 * Return RSS for page or export page content as XML
	 *
	 * @return string
	 */
	function toXML() {
		$request = $this->_arg();
		if ( 'rss' == $request )
		{
			$chanelClass = 'elModule'.$this->name.'RSS';
			if (elSingleton::incLib('modules/'.$this->name.'/'.$chanelClass.'.class.php'))
			{
				$chanel = & elSingleton::getObj($chanelClass);
				$chanel->init($this);
				return $chanel->getContent();
			}
			return "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?><content></content>";
		}

		if ( null != ($class = $this->_getEIProccessorName()) )
		{
			$exporter = & elSingleton::getObj($class);
			$exporter->init( $this );
			if (!$exporter->isExportAllowed($err) )
			{
				return "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?><error>$err</error>";
			}
			return $exporter->export($request);
		}
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?><error>"
						.m('Module does not support export')."</error>";
	}

	/**
	 * Module complete work. Render its content now.
	 *
	 * @return void
	 **/
	function stop() {
		$this->_initRenderer();
		$this->_onBeforeStop();
		$acts = array();
		if (EL_READ < $this->_aMode) {
			foreach ($this->_mMap as $k=>$v) {
				if (!empty($v['g'])) {
					if (!isset($acts[$v['g']])) {
						$acts[$v['g']] = array();
					}
					$g = array( 'url'    => EL_URL.$this->_smPath.$k.'/'.(isset($v['apUrl']) ? $v['apUrl'].'/' : ''),
                      			'ico'    => !empty($v['ico']) ? $v['ico'] : '',
                      			'label'  => $v['l'],
                      			'onClick'=> !empty($v['onClick']) ? $v['onClick'] : ''
                    			);
                    $acts[$v['g']][] = $g;
				}
			}
			$pls = getPluginsManageList();

			if (!empty($pls)) {
				$acts['Plugins'] = $pls;
			}
			$acts['Help'] = array( 
				array(
					'url'     => '#',
					'label'   => m('Documentation index'),
					'onClick' => "return popUp('".EL_BASE_URL."/core/docs/index.html', 600, 600);",
					'ico'     =>'icoHelpTopics'
					)
				);
			$file = 'docs/modref.'.$this->name.'.html';
			if (file_exists(EL_DIR_CORE.$file)) {
				$acts['Help'][] = array(
					'url'    =>'#',
					'label'  => m('Module documentation'),
					'onClick'=>"return popUp('".EL_BASE_URL.'/core/'.$file."', 600, 600);",
					'ico'    =>'icoHelpModule'
					);
			}
		}
		$this->_rnd->renderComplite( $acts );
	}

	/**
	 * edit/update module configuration
	 *
	 * @return void
	 **/
	function configure() {
		if (EL_FULL <> $this->_aMode) {
			elThrow(E_USER_WARNING, 'Operation not allowed', '', EL_URL);
		}

		$form = & $this->_makeConfForm();
		if ($form->isSubmitAndValid() 
		&& null != ($newConf = $this->_validConfForm( $form))) {
			$this->_updateConf($newConf);
			elMsgBox::put(m('Configuration was saved'));
			elLocation(EL_WM_URL.$this->_smPath);
		}
		$this->_initRenderer();
		$this->_rnd->addToContent($form->toHtml());
	}

	/**
	 * Return object to proccess export/import data to/in XML
	 *
	 * @return object
	 */
	function &getEIProccessor() {
		$ei = null;
		if (false != ($class = $this->_getEIProccessorName())) {
			$ei    = & elSingleton::getObj($class);
			$ei->init($this);
		}
		return $ei;
	}

	function configureImport() {
		elAddJs( 'importExportAdmin.lib.js', EL_JS_CSS_FILE );
		$form = & elSingleton::getObj( 'elForm', 'moduleConf' );
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );

		$form->setLabel( m('Data import configuration'));
		$js = 'var r = $(this).parents("tr").eq(0).nextAll("tr"); if (this.value>0) { r.show(); } else { r.hide(); }';
		$form->add( new elSelect('import', m('Use data import'), (int)$this->_conf('import'), $GLOBALS['yn'], array('onchange'=>$js)) );
		$form->add( new elText('importURL',   m('Import URL'),       $this->_conf('importURL')) );
		$form->add( new elCData('c1',         m('Import parameter may contains serveral values delimited by ","')) );
		$form->add( new elText('importParam', m('Import parameter'), $this->_conf('importParam')) );
		$form->add( new elText('importKEY',   m('Import KEY'),       $this->_conf('importKEY')) );
		if ( function_exists('curl_init') )
		{
		    $form->add( new elSelect('useCurl',   m('Use cURL functions to fetch data from URL'),
		                $this->_conf('useCurl'), $GLOBALS['yn']) );
		}
		$period    = range(0, 30);
		$period[0] = m('No');
		$form->add( new elSelect('importPeriod', m('Automaticaly import period (days)'),
															(int)$this->_conf('importPeriod'), $period) );


		$iDate = $this->_conf('importTS') ? date(EL_DATETIME_FORMAT, $this->_conf('importTS')) : m('Never');
		$form->add( new elText('i-d', m('Last time import'), $iDate, null, true));
		$form->setElementRule('importURL', 'http_url', false);
		elAddJs('$("#import").trigger("change");', EL_JS_SRC_ONREADY);
		if ( !$form->isSubmitAndValid() )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $form->toHtml() );
		}
		else
		{

			$newConf = $form->getValue();
			$this->_saveImportConf($newConf);
			$this->_loadConf();

			if ( $this->_conf('import') )
			{
				$ei = & $this->getEIProccessor();
				if ( !$ei->import() )
				{
					$this->_saveImportConf(null);
					elLocation( EL_URL ); //error already produced by $ei
				}
				unset($ei);
			}
			elMsgBox::put( m('Data saved'));
			elLocation( EL_URL );
		}
	}

	function configureExport() {
		$form = & elSingleton::getObj( 'elForm', 'moduleConf' );
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->setLabel( m('Data export configuration'));
		
		$js = 'var r = $(this).parents("tr").eq(0).next("tr"); if (this.value>0) { r.show(); } else { r.hide(); }';
		$form->add( new elSelect('export', m('Use data export'), (int)$this->_conf('export'), $GLOBALS['yn'], array('onchange'=>$js)) );
		$form->add( new elText('exportKEY',   m('Export KEY'),       $this->_conf('exportKEY')) );
		elAddJs( 'checkExportForm();', EL_JS_SRC_ONLOAD);
		if ($form->isSubmitAndValid()) {
			$data = $form->getValue();
			$conf = & elSingleton::getObj('elXmlConf');
			$conf->set('export',    !empty($data['export'])    ? 1 : 0,                   $this->_confID);
			$conf->set('exportKEY', !empty($data['exportKEY']) ? $data['exportKEY'] : '', $this->_confID);
			$conf->save();
			elMsgBox::put( m('Data saved'));
			elLocation( EL_URL );
		}
		elAddJs("$('#export').trigger('change');", EL_JS_SRC_ONREADY);
		$this->_initRenderer();
		$this->_rnd->addToContent( $form->toHtml() );
	}

	/**
	 * abstract method
	 * Overloaded in childs classes. Called when new page created with this module.
	 * Here we do anithing we cant do in install.sql
	 * ATTENTION! Method _init DID NOT CALLED BEFORE THIS
	 *
	 * @return void
	 **/
	function onInstall() {}

	/**
	 * abstract method
	 * Overloaded in childs classes. Called while deleted page, was created with this module .
	 * Here we do anithing we cant do in uninstall.sql
	 * ATTENTION! Method _init DID NOT CALLED BEFORE THIS
	 *
	 * @return void
	 **/
	function onUninstall() {}



	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//

	/**
	 * When module is a subModule of MultiModule called by him to
	 * set tabs, current tab and path to current sub module
	 *
	 * @param  array  $tabs
	 * @param  string  $current
	 * @return void
	 **/
	function _setTabs($tabs, $current) {
		$this->_tabs   = $tabs;
		$this->_curTab = $current;
		$this->_smPath = $tabs[$current]['path'];
	}

	/**
	 * abstract method
	 * Do anything special for init current module
	 * called at the end of init() method
	 *
	 * @return void
	 **/
	function _onInit() {}

	/**
	 * abstract method
	 * Do something special before stop current module
	 * Called AFTER _initRenderer and BEFORE renderComplite
	 *
	 * @return void
	 **/
	function _onBeforeStop() {}

	/**
	 * initilize module in user mode
	 *
	 * @return void
	 **/
	function _initNormal() {
		elLoadMessages('Module'.$this->name);
		$this->_loadConf();
	}

	/**
	 * initilize module in admin mode
	 *
	 * @return void
	 **/
	function _initAdminMode() {
		elLoadMessages('CommonAdmin');
		elLoadMessages('ModuleAdmin'.$this->name);
		include_once 'elCoreAdmin.lib.php';
		$this->_initNormal();

		if (!$this->_hasImportedData()) {
			$this->_mMap = array_merge($this->_mMap, $this->_mMapAdmin);
		}

		if (EL_FULL == $this->_aMode) {
			if (!empty($this->_mMapConf)) {
				foreach ($this->_mMapConf as $k=>$v) {
					$this->_mMapConf[$k]['g'] = 'Configuration';
				}
				$this->_mMap = array_merge($this->_mMap, $this->_mMapConf);
			}
			if ($this->_getEIProccessorName()) {
				foreach ($this->_mMapConfImport as $k=>$v) {
					$this->_mMapConfImport[$k]['g'] = 'Configuration';
				}
				$this->_mMap = array_merge($this->_mMap, $this->_mMapConfImport);
			}
		}
	}

	function _hasImportedData() {
		return $this->_getEIProccessorName() && $this->_conf('importTS');
	}

	function _getEIProccessorName() {
		$class = 'elModule'.$this->name.'EIProccessor';
		return elSingleton::incLib('modules/'.$this->name.'/'.$class.'.class.php') ? $class : null;
	}

	function _saveImportConf($data) {
		$conf = & elSingleton::getObj('elXmlConf');
		$conf->set('import',       !empty($data['import']) ? 1 : 0,                            $this->_confID);
		$conf->set('importURL',    !empty($data['importURL'])    ? $data['importURL']    : '', $this->_confID);
		$conf->set('useCurl',      !empty($data['useCurl'])      ? 1                     :  0, $this->_confID);
		$conf->set('importParam',  !empty($data['importParam'])  ? $data['importParam']  : '', $this->_confID);
		$conf->set('importKEY',    !empty($data['importKEY'])    ? $data['importKEY']    : '', $this->_confID);
		$conf->set('importPeriod', !empty($data['importPeriod']) ? $data['importPeriod'] : '', $this->_confID);
		$conf->set('importTS',     !empty($data['import'])       ? time() : 0,                 $this->_confID);
		$conf->save();
	}

	/**
	 * initilize module renderer
	 *
	 * @return void
	 **/
	function _initRenderer() {
		if (!$this->_rnd) {
			if (file_exists(EL_DIR_CORE.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$this->name.DIRECTORY_SEPARATOR.'elRnd'.$this->name.'.class.php')) {
				$this->_rndClass = 'elRnd'.$this->name;
			}

			$this->_rnd = & elSingleton::getObj($this->_rndClass);
			
			if (!empty($this->_sharedRndMembers)) {
				foreach ($this->_sharedRndMembers as $m) {
					if (isset($this->$m)) {
						$this->_rnd->$m = $this->$m;
					}
				}
			}
			
			$this->_rnd->init($this->name, $this->_conf, EL_READ < $this->_aMode, $this->_tabs, $this->_curTab);
		}
	}

	/**
	 * return argument by number
	 *
	 * @param  int  $i
	 * @return string
	 **/
	function _arg($i=0) {
		return isset($this->_args[$i]) ? $this->_args[$i] : null;
	}

	/**
	 * load configuration from conf file
	 *
	 * @return void
	 **/
	function _loadConf() {
		$conf = &elSingleton::getObj('elXmlConf');
		$group = $conf->getGroup( $this->_confID );
		if (is_array($group)) {
			$this->_conf = array_merge( $this->_conf, $group );
		}
	}

	/**
	 * return config param by name
	 *
	 * @param  string $param  name
	 * @return mixed
	 **/
	function _conf($param) {
		return isset($this->_conf[$param]) ? $this->_conf[$param] : null;
	}

	/**
	 * create configuration form
	 *
	 * @return elForm
	 **/
	function &_makeConfForm() {
		$form = & elSingleton::getObj( 'elForm', 'moduleConf' );
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->setLabel( m('Configure') );
		return $form;
	}

	/**
	 * valid and clean data from configuration form and return new configuration
	 *
	 * @param  elForm  $form
	 * @return array
	 **/
	function _validConfForm(&$form) {
		return $form->getValue();
	}

	/**
	 * save new config
	 *
	 * @param  array  $newConf
	 * @return void
	 **/
	function _updateConf($newConf) {
		$conf = &elSingleton::getObj('elXmlConf');
		foreach ($newConf as $k=>$v) {
			if (isset($this->_conf[$k])) {
				$conf->set($k, $newConf[$k], $this->_confID);
			}
		}
		$conf->save();
	}

	/**
	 * Делает недоступными для вызова методы из массива $this->_mMap,
	 * если запрещается метод, который должен быть сейчас вызван,
	 * handler метода ($this->_mh) возвращается в массив аргументов ($this->_args)
	 * Используется когда нужно запретить доступ к методам после инициализации и до запуска модуля
	 *
	 * @param mix $handls
	 * @return array
	 */
	function _removeMethods($handls) {
		if (is_array($handls)) {
			foreach ($handls as $h)	{
				if (!empty($this->_mMap[$h])) {
					unset($this->_mMap[$h]);
					if ($this->_mh == $h) {
						array_unshift($this->_args, $this->_mh);
						$this->_mh = '';
					}
				}
			}
		} elseif (!empty($this->_mMap[$handls])) {
			unset($this->_mMap[$handls]);
			if ($this->_mh == $handls) {
				array_unshift($this->_args, $this->_mh);
				$this->_mh = '';
			}
		}
	}

} // END class


?>