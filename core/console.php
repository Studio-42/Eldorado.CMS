<?php

/**
 * Include this file when you need to run ELDORADO.CMS from
 * console or cron and write your code after
 **/
define('EL_DIR',       realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR);
define('EL_DIR_CORE',  EL_DIR.'core'.DIRECTORY_SEPARATOR);

include_once(EL_DIR_CORE.'bootstrap.php');

