<?php
error_reporting(0);
if (!file_exists('./core/stat/stat.php') || !is_readable('./core/stat/stat.php'))
{
	exit;
}
include_once './core/lib/elSingleton.class.php';
include_once './core/lib/elCore.lib.php';

$GLOBALS['gbl_config'] = elGetStatConf();
chdir('./core/stat');
include_once './stat.php';
?>