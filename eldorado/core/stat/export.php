<?
 /*****************************************************************
 *
 * Бесплатный PHP скрипт подсчета статистики сайта WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/

 error_reporting (E_ERROR | E_PARSE);
 ignore_user_abort (true);

 //include("./includes/config.php");
 include("./class/sql.class.php");
 include("./class/core.class.php");
 include("./class/export.class.php");

 reset ($_GET);

 if (is_array($_GET['type'])) {
	$type_array = $_GET['type'];
 } elseif ($_GET['type']) {
 	$type_array[] = $_GET['type'];
 } else {
 	die("<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка получения отчета.\n");
 }

 $hasIconv = function_exists('iconv');
 
 while (list($key, $value)=each($_GET)) {
	$$key=htmlspecialchars(trim(substr($value, 0, 255)));
	if ($hasIconv)
	{
		//$$key = iconv("UTF-8", "Windows-1251", $$key);
	}
 }

 // Ядро системы
 $GLOBALS['core'] = &new core();
 $core->begin     =  $begin== "" ? date("d-m-Y", time()) : $begin;
 $core->end       = $end== "" ? date("d-m-Y", time()) : $end;
 $core->type_gr   = $type_gr;

 

 if (!$what) { die("<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка получения отчета.\n"); }

 $export=new export();
 $export->data=$core->export_data;
 $export->type_array = $type_array;
 $export->what = $what;
 $export->init();
 exit;
?>