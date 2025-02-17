<?php

/**
 * Other paths
 */
define('EL_DIR_STORAGE_NAME', 'storage');
define('EL_DIR_STORAGE',      EL_DIR.EL_DIR_STORAGE_NAME.DIRECTORY_SEPARATOR);
define('EL_DIR_STYLES',       EL_DIR.'style'.DIRECTORY_SEPARATOR);
define('EL_DIR_BACKUP',       EL_DIR.'backup'.DIRECTORY_SEPARATOR);
define('EL_DIR_CACHE',        EL_DIR.'cache'.DIRECTORY_SEPARATOR);
define('EL_DIR_LOG',          EL_DIR.'log'.DIRECTORY_SEPARATOR);
define('EL_DIR_TMP',          EL_DIR.'tmp'.DIRECTORY_SEPARATOR);
define('EL_DIR_CONF',         EL_DIR.'conf'.DIRECTORY_SEPARATOR);

/**
 * errors processing conf
 */
define('EL_ERROR_DISPLAY',      E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE);
define('EL_ERROR_LOG',          E_USER_ERROR);
define('EL_ERROR_MAIL',         0);
define('EL_ERROR_MAIL_TIMEOUT', 60*60);

/**
 * Begin session
 */
session_name('ELSID');
session_set_cookie_params(60*60*24*30);
session_start();

/**
 * set default timezone for PHP5
 */
if (function_exists('date_default_timezone_set')) {
	date_default_timezone_set('Europe/Moscow');
}

/**
 * define base URL, if does not define previousely
 */
if (!defined('EL_BASE_URL') && isset($_SERVER['HTTP_HOST']))
{
	$sPath = dirname($_SERVER['PHP_SELF']);
	define ('EL_BASE_URL', 'http://'.$_SERVER['HTTP_HOST'] . ('/' == $sPath ? '' : $sPath));
}

/**
 * Paths for includes (Core files)
 */
$paths = EL_DIR_CORE.'lib/:'
		.EL_DIR_CORE.'forms/:'
        .EL_DIR_CORE.'modules/:'
        .EL_DIR_CORE.'plugins/:';
ini_set('include_path', '.:'.$paths);

/**
 * Site pages permissions
 */
define('EL_READ',   1);
define('EL_WRITE',  3);
define('EL_FULL',   7);

/**
 * Messages queues indexes
 */
define('EL_MSGQ',   1);
define('EL_WARNQ',  2);
define('EL_DEBUGQ', 3);

/**
 * Window modes and url suffixes
 */
define('EL_WM_NORMAL', 1);
define('EL_WM_POPUP',  3);
define('EL_WM_XML',    4);
define('EL_WM_JSON',   5);
define('EL_URL_POPUP', '_popup_');
define('EL_URL_XML',   '_xml_');
define('EL_URL_JSON',   '_json_');

/**
 * Pages scopes and display limits
 */
define('EL_PAGE_DISPL_ALL',      2);
define('EL_PAGE_DISPL_MAP',      1);
define('EL_PAGE_DISPL_LIMIT_NA', 1);
define('EL_PAGE_DISPL_LIMIT_A',  2);

/**
 * Js and css files types
 */
define('EL_JS_CSS_SRC',     0);
define('EL_JS_CSS_FILE',    1);
define('EL_JS_SRC_ONLOAD',  2);
define('EL_JS_SRC_ONREADY', 3);

define('EL_ADD_MENU_TOP',  0);
define('EL_ADD_MENU_BOT',  1);
define('EL_ADD_MENU_SIDE', 2);

define('EL_ADD_MENU_NO',   0);
define('EL_ADD_MENU_TEXT', 1);
define('EL_ADD_MENU_ICO',  2);
define('EL_ADD_MENU_TI',   3);

define('EL_POS_LEFT',   'l');
define('EL_POS_TOP',    't');
define('EL_POS_RIGHT',  'r');
define('EL_POS_BOTTOM', 'b');

// common catalogs constants - display cat descrip
define('EL_CAT_DESCRIP_NO',      0);
define('EL_CAT_DESCRIP_IN_LIST', 1);
define('EL_CAT_DESCRIP_IN_SELF', 2);
define('EL_CAT_DESCRIP_IN_BOTH', 3);

/*********  global variables - storages  **************/

$GLOBALS['parseColumns'] = array( 
	EL_POS_LEFT   => 0,
	EL_POS_RIGHT  => 0,
	EL_POS_TOP    => 0,
	EL_POS_BOTTOM => 0);

/**
 * Object storage.
 * Dont access objects directly.
 * Use elSingleton::getObj() static method
 */
$GLOBALS['_elStorage_'] = array();

/**
 * Loaded plugins storage
 * Dont access objects directly.
 * Use elSingleton::getPlugin() or elSingleton::getPluginByName() static methods
 */
$GLOBALS['_elPlugins_'] = array();

/**
 * Storage for javascript source or file names (See elAddJs func)
 * Some js files from EL_DIR_JS AND EL_DIR_JS_LOCAL dirs included automaticaly:
 * common.lib.js
 * file with the same name as current module
 */
$GLOBALS['_js_'] = array(
	EL_JS_CSS_FILE    => array('jquery.js', 'common.min.js'),
	EL_JS_CSS_SRC     => array(),
	EL_JS_SRC_ONLOAD  => array(),
	EL_JS_SRC_ONREADY => array()
);
$GLOBALS['_css_']       = array(
	'ui-theme'     => '',
	EL_JS_CSS_FILE => array('styling.css', 'normal.css'),
	EL_JS_CSS_SRC  => array()
);

/**
 * Array contains current path inside page
 * Dont use directly. Use elAppendToPagePath() func
 */
$GLOBALS['pagePath'] = array();

/**
 * Load main libraries
 */
include_once EL_DIR_CORE.'lib/elActionLog.lib.php';
include_once EL_DIR_CORE.'lib/elCore.class.php';
include_once EL_DIR_CORE.'lib/elCore.lib.php';
include_once EL_DIR_CORE.'lib/elMsgBox.class.php';
include_once EL_DIR_CORE.'lib/elModule.class.php';
include_once EL_DIR_CORE.'lib/elModuleRenderer.class.php';
if (substr(PHP_VERSION, 0, 1) > 4) 
{
	include_once EL_DIR_CORE.'lib/elSingleton5.class.php';
	include_once EL_DIR_CORE.'lib/elMemberAttribute5.class.php';
	include_once EL_DIR_CORE.'lib/elDataMapping5.class.php';
}
else 
{
	include_once EL_DIR_CORE.'lib/elSingleton.class.php';
	include_once EL_DIR_CORE.'lib/elMemberAttribute.class.php';
	include_once EL_DIR_CORE.'lib/elDataMapping.class.php';
}
include_once EL_DIR_CORE.'lib/elUser.class.php';

/**
 * error handling
 */
error_reporting(E_ALL);
register_shutdown_function('shutdown');
set_error_handler('elErrorHandler');

/**
 * check for magic quotes and workaround
 */
if (version_compare(PHP_VERSION, '5.3.0') < 0)
{
	set_magic_quotes_runtime(0);
}
if (ini_get('magic_quotes_gpc'))
{
	$_GET  = array_map('elRStripSlashes', $_GET);
	$_POST = array_map('elRStripSlashes', $_POST);
}

/**
 * Load common localization
 */
elLoadMessages('Common');

/**
 * Core version and name
 */
define('EL_VER',  '3.9.5');
define('EL_NAME', 'Kioto');

