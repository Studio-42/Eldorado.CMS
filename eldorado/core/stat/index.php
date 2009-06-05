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
 //elPrintR($GLOBALS['glb_config']);
 //$glb_config = $GLOBALS['glb_config'];
 $begin = $end = $type = $type_gr = $bytes = $rows = '';
 error_reporting (E_ERROR | E_PARSE);
 ignore_user_abort (true);
 //setlocale(LC_ALL, 'ru_RU');
 //ini_set("magic_quotes_gpc", "off");
 //ini_set("magic_quotes_runtime", "off");
 //ini_set("magic_quotes_sybase", "off");

 //include("./includes/config.php");
 include("./class/sql.class.php"); 
 include("./class/core.class.php");

 if (is_file("./_setup/index.php")) { echo "<li><span style='color: green;'>[Внимание]</span> Для установки необходимо выполнить следующие действия: <ul><li>Запустите файл <a href=./_setup/index.php>_setup/index.php</a>;</li><li>Удалите с сайта папку '_setup' и все находящиеся в ней файлы, так как это необходимо в целях безопасности системы!</li></ul>"; die; }
 if (!is_file("./includes/config.php")) { echo "<li><span style='color: green;'>[Внимание]</span> Файл установки 'includes/config.php' не создан. Для установки необходимо выполнить следующие действия: <ul><li>Запустите файл <a href=./_setup/index.php>_setup/index.php</a>;</li><li>Удалите с сайта папку '_setup' и все находящиеся в ней файлы, так как это необходимо в целях безопасности системы!</li></ul>"; die; }

 reset ($_GET);

 while (list($key, $value)=each($_GET)) {
	$$key=htmlspecialchars(trim(substr($value, 0, 255)));
 }

 // Ядро системы
 $core = & new core();
 $core->begin     = $begin == "" ? date("d-m-Y", time()) : $begin;
 $core->end       = $end == "" ? date("d-m-Y", time()) : $end;
 $core->type      = $type;
 $core->type_gr   = $type_gr;
 $core->init();
 $GLOBALS['core'] = &$core;

 $result = $core->exec("SELECT DISTINCT ip FROM wc_online;");
 $online = $result->num_rows() ? $result->num_rows() : "0";

 $start = date("d ", $core->start).$core->rusdate(date("m", $core->start), true).date(" Y г.", $core->start);
 $last  = date("d ", $core->update).$core->rusdate(date("m", $core->update), true).date(" Y г. в H:i", $core->update);

 $result = $core->exec("SHOW TABLE STATUS;");
 while($data=$result->fetch_array()) {
	$rows  += $data['Rows'];
	$bytes += $data['Data_length'];
 }

 $page = getInclude("template.htm");

 $page = str_replace("{selbegin}", $core->get_date("b", $core->begin), $page);
 $page = str_replace("{selend}", $core->get_date("e", $core->end), $page);
 $page = str_replace("{seltype}", $core->get_type(), $page);

 $page = str_replace("{calendar}", calendar(), $page);
 $page = str_replace("{begin}", $core->begin, $page);
 $page = str_replace("{end}", $core->end, $page);
 $page = str_replace("{type}", $core->type, $page);
 $page = str_replace("{type_gr}", $core->type_gr, $page);

 $page = str_replace("{header}", $core->header, $page);
 $page = str_replace("{content}", $core->html_data, $page);

 $page = str_replace("{pages}", $core->pages, $page);
 $page = str_replace("{version}", $core->version.$core->version_type, $page);
 $page = str_replace("{online}", $online, $page);
 $page = str_replace("{start}", $start, $page);
 $page = str_replace("{last}", $last, $page);
 $page = str_replace("{rows}", number_format($rows, 0, '.', ' '), $page);
 $page = str_replace("{bytes}", get_size($bytes), $page);
 echo $page;
 //exit;

 function calendar() {

	global $core;

	$month = date("m", $core->end_date);
	$year = date("Y", $core->end_date);
	$day = date("d", $core->end_date);

	$tmpd = getdate(mktime( 0,0,0,$month,1,$year ));
	$firstwday= $tmpd["wday"];
	$lastday = lastday($month, $year);

	if (!$firstwday) { $firstwday=7; }

	$kn1 = (($month-1)<1) ? 12 : $month-1;
	$kn2 = (($month-1)<1) ? $year-1 : $year;
	$kn3 = ((($month-1)<1) ? $year-1 : $year)-1;
	$kn4 = ((($month+1)>12) ? $year+1 : $year)+1;
	$kn5 = (($month+1)>12) ? 1 : $month+1;
	$kn6 = (($month+1)>12) ? $year+1 : $year;
	$kn7 = $month + 0;

	$nadp = $core->rusdate($month, false)." ".$year;

	$html = '
	<table cellspacing="2" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr class="evenrow">
		<td colspan="7" align="center" valign="top" style="padding:0">
			<table cellspacing="2" cellpadding="2" border="0" width="100%" class="tablelist" style="margin:0">
			<tr class="evenrow">
				<td nowrap align="left"><a class="cal1" href="?begin=01-'.$kn1.'-'.$kn2.'&end=01-'.$kn1.'-'.$kn2.'&type='.$core->type.'&type_gr='.$core->type_gr.'"><b>&#171;&#171;</b></a> &nbsp;<a class="cal1" href="?begin=01-'.$kn7.'-'.$kn3.'&end=01-'.$kn7.'-'.$kn3.'&type='.$core->type.'&type_gr='.$core->type_gr.'"><b>&#171;</b></a></td>
				<td nowrap align="center"><b class="cal1">'.$nadp.'</b></td>
				<td nowrap align="right"><a class="cal1" href="?begin=01-'.$kn7.'-'.$kn4.'&end=01-'.$kn7.'-'.$kn4.'&type='.$core->type.'&type_gr='.$core->type_gr.'"><b>&#187;</b></a> &nbsp;<a class="cal1" href="?begin=01-'.$kn5.'-'.$kn6.'&end=01-'.$kn5.'-'.$kn6.'&type='.$core->type.'&type_gr='.$core->type_gr.'"><b>&#187;&#187;</b></a></td>
			</tr>
			</table>
		</td>
	</tr>
	<tr class="evenrow">
		<th><b>Пн</b></th>
		<th><b>Вт</b></th><th><b>Ср</b></th>
		<th><b>Чт</b></th><th><b>Пт</b></th>
		<th><b>Сб</b></th><th><b>Вс</b></th>
	</tr>';

	$d = 1;
	$wday = $firstwday;
	$firstweek = true;

	while ($d <= $lastday) {
		if ($firstweek) {

			$html .= '<tr align="center" class="evenrow">' ;
			for ($i=2; $i <= $firstwday; $i++)  $html .= '<td>&nbsp;</td>';
			$firstweek = false;
		}

		if ($wday == 1) $html .=  '<tr align="center" class="evenrow">' ;

		//$bgcolor = 'class="oddrow"';
		$time_begin = mktime(0, 0, 0, $month, $d, $year) ;
		$time_end = mktime(23, 59, 59, $month, $d, $year) ;

      		if ( ($wday == 0) || ($wday == 6) || ($wday == 7) || ($day == $d)) { $bgcolor = 'style="font-weight: bold;"'; } else { $bgcolor = ''; }

		$now_timestamp = strtotime("$month/$d/$year");

		if ( $now_timestamp <= time() && $now_timestamp >= $core->start) {
			$html .=  '<td '.$bgcolor.' align="center"><a href="?begin='.$d.'-'.$kn7.'-'.$year.'&end='.$d.'-'.$kn7.'-'.$year.'&type='.$core->type.'&type_gr='.$core->type_gr.'">'.$d.'</a></td>' ;
		} else {
			$html .=  '<td '.$bgcolor.'  align="center">'.$d.'</td>' ;
		}

		if ($wday == 0)	$html .=  '</tr>' ;

		$wday++;
		$wday = $wday % 7;
		$d++;
	}
 	$html .= '</tr></table>';
 
 	return $html;
 }

 function lastday($mon, $year) {

	for ($tday=28; $tday <= 31; $tday++)  {
     
		$tdate = getdate( mktime( 0, 0, 0, $mon, $tday, $year ) ) ;
		if ( $tdate["mon"] != $mon )  { break; }
	}

	$tday-- ;

	return $tday ;
 }

 function getInclude($name) {

	 $file="./template/".$name;
	 $fp=@fopen($file, "r");
	 $content=@fread($fp, filesize($file));
	 @fclose($fp);

	 if (!$content) {
		 $content="<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка открытия файла $file.";
	 }

	 return $content;
 }

 function get_size($filesize) {

	$kilobyte	= 1024;
	$megabyte = 1048576;
	$gigabyte = 1073741824;
	$terabyte = 1099511627776;

	if ($filesize >= $terabyte)		{ return number_format($filesize/$terabyte, 2, ',', '.')."&nbsp;Тб."; }
	elseif ($filesize >= $gigabyte)	{ return number_format($filesize/$gigabyte, 2, ',', '.')."&nbsp;Гб."; }
	elseif ($filesize >= $megabyte)	{ return number_format($filesize/$megabyte, 2, ',', '.')."&nbsp;Мб."; }
	elseif ($filesize >= $kilobyte) 		{ return number_format($filesize/$kilobyte, 2, ',', '.')."&nbsp;Кб."; }
	else				{ return number_format($filesize, 0, ',', '.')."&nbsp;б."; }
 }
?>