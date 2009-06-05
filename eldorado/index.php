<?php
session_name('ELSID');
session_set_cookie_params( 60*60*24*30 );
session_start();

/**
 * Site base URL
 * In all the way Your need't define it. Eldorado can correctly define Your site base URL.
 * But if this did not happened (I never seen no one :-) Mail me in that case. ).
 * You need to define EL_BASE_URL here by hand,
 * if your have any aliases for your site and want to have one "main" site address
 * uncomment following line and enter your site URL
*/
//define ('EL_BASE_URL', 'http://your.site.tld');


 /** site root directory
 */
define ('EL_DIR',       './');

/**
 * Core directory (may be placed outside site root directory)
 */
define ('EL_DIR_CORE',  './core/');

/**
 * Other paths
 */
define ('EL_DIR_STORAGE_NAME',  'storage');
define ('EL_DIR_STORAGE',       EL_DIR.EL_DIR_STORAGE_NAME.'/');

define ('EL_DIR_STYLES',      EL_DIR.'style/');
define ('EL_DIR_BACKUP',      EL_DIR.'backup/');
define ('EL_DIR_CACHE',       EL_DIR.'cache/');
define ('EL_DIR_LOG',         EL_DIR.'log/');
define ('EL_DIR_TMP',         EL_DIR.'tmp/');
define ('EL_DIR_CONF',        EL_DIR.'conf/');

/**
 * errors processing conf
 */
define ('EL_ERROR_DISPLAY',      E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE);
define ('EL_ERROR_LOG',          E_USER_ERROR);
define ('EL_ERROR_MAIL',         0);
define ('EL_ERROR_MAIL_TIMEOUT', 60*60);


include_once(EL_DIR_CORE.'common.php');



$core = & new elCore;

$core->load();
$core->run();


?>