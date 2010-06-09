<?php
// ELDORADO.CMS

/**
 * Site base URL
 * In all the way Your need't define it. Eldorado can correctly define Your site base URL.
 * But if this did not happened (I never seen no one :-) Mail me in that case. ).
 * You need to define EL_BASE_URL here by hand,
 * if your have any aliases for your site and want to have one "main" site address
 * uncomment following line and enter your site URL
*/
//define ('EL_BASE_URL', 'http://your.site.tld');

/**
 * Site root directory
 */
define ('EL_DIR',       '.'.DIRECTORY_SEPARATOR);

/**
 * Core directory (may be placed outside site root directory)
 */
define ('EL_DIR_CORE',  EL_DIR.'core'.DIRECTORY_SEPARATOR);

include_once(EL_DIR_CORE.'bootstrap.php');

$core = & new elCore;
$core->load();
$core->run();

