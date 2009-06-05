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
 include('./class/userinfo.class.php');
 include("./class/counter.class.php");
 include("./class/mail.class.php");

 $userInfo = new userInfo();
 $userInfo->init();
 $userInfo->session_user();

 if (!$userInfo->num_session) {
	$result=$userInfo->exec("INSERT INTO wc_main (time, ip, browser, os, platform, screen, color, java, cookies, language, country, city, region, organization, css2, css1, iframes, xml, dom, cache_forms, avoid_popup, cache_ssl, referer, searcheng, search_words, catalog, forum) VALUES (".time().", ".ip2long($userInfo->property('ip')).", '".$userInfo->property('long_name')." ".$userInfo->property('version')."', '".$userInfo->property('os')."', '".$userInfo->property('platform')."', '".$userInfo->property('screen')."', '".$userInfo->property('color')."', '".$userInfo->property('javascript')."', '".$userInfo->property('cookies')."', '".$userInfo->property('language')."', '".$userInfo->property('country')."', '".$userInfo->property('city')."', '".$userInfo->property('region')."', '".$userInfo->property('organization')."', '".$userInfo->has_feature('css2')."', '".$userInfo->has_feature('css1')."', '".$userInfo->has_feature('iframes')."', '".$userInfo->has_feature('xml')."', '".$userInfo->has_feature('dom')."', '".$userInfo->has_feature('cache_forms')."', '".$userInfo->has_feature('avoid_popup')."', '".$userInfo->has_feature('cache_ssl')."', '".$userInfo->property('referer')."', '".$userInfo->property('search')."', '".$userInfo->property('search_word')."', '".$userInfo->property('catalog')."', '".$userInfo->property('forum')."');");
	$result=$userInfo->exec("SELECT LAST_INSERT_ID();");
	list($number)=$result->fetch_row();
	$userInfo->num_session = $number;
	//write_log("main:\t".date("d.m H:i:s")."\t".$userInfo->property('ip')."\t".$userInfo->num_session."\n", 3, "log.txt");

	$result=$userInfo->exec("INSERT INTO wc_session (statid, time, page) VALUES ('".$userInfo->num_session."', ".time().", '".$userInfo->property('page')."' );");
	//write_log("sess:\t".date("d.m H:i:s")."\t".$userInfo->property('ip')."\t".$userInfo->num_session."\n", 3, "log.txt");
 } else {
	$result=$userInfo->exec("INSERT INTO wc_session (statid, time, page) VALUES ('".$userInfo->num_session."', ".time().", '".$userInfo->property('page')."' );");
	//write_log("sess:\t".date("d.m H:i:s")."\t".$userInfo->property('ip')."\t".$userInfo->num_session."\n", 3, "log.txt");
 }

 $userInfo->timestamp=time();
 $userInfo->online_user();

 // Ядро системы
 $core          = &new core();
 $core->begin   = $begin== "" ? date("d-m-Y", time()) : $begin;
 $core->end     = $end== "" ? date("d-m-Y", time()) : $end;
 $core->type_gr = $type_gr;
 $core->init();
 $GLOBALS['core'] = & $core;
 // Экспорт
 if (time() >= $core->gbl_config['export_date'] && $core->gbl_config['export_period'] != "none") {

	$new_timestamp = $core->export_date($core->gbl_config['export_period'], $core->gbl_config['export_period_day']);
	$core->exec("UPDATE wc_settings SET value='".$new_timestamp."' WHERE name='export_date' LIMIT 1;");
	//write_log("export:\t".date("d.m H:i:s")."\n", 3, "log.txt");

	switch ($core->gbl_config['export_period']) {
		case "by_day":
			$timestamp_start = $core->gbl_config['export_date'] - 60*60*24;
			break;
		case "by_month":
			$timestamp_start = mktime(0, 0, 0, date("m")-1, $core->gbl_config['export_period_day'], date("Y"));
			break;
		case "by_week":
			$timestamp_start = strtotime("last ".$core->gbl_config['export_period_day']);
			break;
	}
	$timestamp_end = $core->gbl_config['export_date'] - 60*60*24;

	$t_array = explode(",", $core->gbl_config['export_statistics']);
	if ($t_array[0]) {

		if (count($t_array) == 1) {
			$type = "type=".$t_array[0];
		} else {
			$type = implode("&type[]=", $t_array);
		}

		switch ($core->gbl_config['export_format']) {
			case "excel":
				$content_type = "application/vnd.ms-excel";
				$exp = "xls";
				break;
			case "word":
				$content_type = "application/msword";
				$exp = "doc";
				break;
			case "pdf":
				$content_type = "application/pdf";
				$exp = "pdf";
				break;
		}

		$sendmail= new mail;
		$sendmail->sendto = $core->gbl_config['export_email'];
		$sendmail->subject = $core->gbl_config['export_subject'];
		$sendmail->body("");

		umask(000);
		$file = implode('', file($core->gbl_config['url']."export.php?begin=".date("d-n-Y", $timestamp_start)."&end=".date("d-n-Y", $timestamp_end)."&".$type."&what=".$core->gbl_config['export_format']));
		if ($file) {
			$handle = fopen("./includes/statistics.$exp", "a");
			fwrite($handle, $file);
			fclose($handle);
			$sendmail->attach("./includes/statistics.$exp", $content_type);
		}
		$sendmail->send();
		unlink("./includes/statistics.$exp");
	}
 }

 $counter = new counter();
 $counter->init();
 exit;
?>