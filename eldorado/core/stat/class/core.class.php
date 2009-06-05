<?
 /*****************************************************************
 * Класс управления системой WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/
 class core extends sql {

	var $version = "21.09.2006";
	var $version_type = "";
	var $pages = '';
	
 function init() {

	global $gbl_config;
	if (ereg( "([0-9]{1,2})[-]([0-9]{1,2})[-]([0-9]{2,4})", $this->begin, $regs)) {
		if (checkdate ($regs[2], $regs[1],$regs[3])) {
			$this->start_date = mktime(0,0,0, $regs[2], $regs[1], $regs[3]);
		}
	}

	if (ereg( "([0-9]{1,2})[-]([0-9]{1,2})[-]([0-9]{2,4})", $this->end, $regs)) {
		if (checkdate ($regs[2], $regs[1],$regs[3])) {
			$this->end_date = mktime(23,59,59, $regs[2], $regs[1], $regs[3]);
		}
	}

	if (!$this->start_date) $this->start_date = mktime(0,0,0, date("m"), date("d"), date("y"));
	if (!$this->end_date)  $this->end_date = mktime(23,59,59, date("m"), date("d"), date("y"));

	if (date("Ymd", $this->start_date) > date("Ymd", $this->end_date)) {

		$this->start_date = $this->start_date + $this->end_date;
		$this->end_date = $this->start_date - $this->end_date;
		$this->start_date = $this->start_date - $this->end_date; 
		$this->variant = "per";

	} elseif (date("Ymd", $this->start_date) < date("Ymd", $this->end_date)) {

		$this->variant = "per";

	} else  {
		$this->variant = "day";
	}

	unset($this->export_data);
	$this->export_data[0] = '';

	if(!$this->connect($gbl_config['sql_serverip'], $gbl_config['sql_username'], $gbl_config['sql_password'], $gbl_config['sql_db'])) { die("<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка соединения с базой!"); }

	$result=$this->exec("SELECT name, value FROM wc_settings;");
	while ($data=$result->fetch_row()) {
		 $this->gbl_config[$data[0]]=$data[1];
	}

	$result=$this->exec("SELECT time FROM wc_session ORDER BY time ASC LIMIT 1;");
	list($this->start)=$result->fetch_row();

	$result=$this->exec("SELECT time FROM wc_session ORDER BY time DESC LIMIT 1;");
	list($this->update)=$result->fetch_row();

	if (!$this->type)	$this->type = $this->gbl_config['default'];
	if (!$this->type_gr)	$this->type_gr = $this->gbl_config['default_gr'];

	if ( $this->type == "consolidated" )		{ $this->get_consolidated();  }
	elseif ( $this->type == "attendance" )		{ $this->get_attendance(); }
	elseif ( $this->type == "host" )		{ $this->get_host(); }
	elseif ( $this->type == "hit" )			{ $this->get_hit(); }
	elseif ( $this->type == "session" )		{ $this->get_session(); }
	elseif ( $this->type == "referer" )		{ $this->get_referer(); }
	elseif ( $this->type == "domain" )		{ $this->get_domain(); }
	elseif ( $this->type == "forum" )		{ $this->get_forum(); }
	elseif ( $this->type == "catalog" )		{ $this->get_catalog(); }
	elseif ( $this->type == "search" )		{ $this->get_search(); }
	elseif ( $this->type == "search_word" )		{ $this->get_search_word(); }
	elseif ( $this->type == "os" )			{ $this->get_os(); }
	elseif ( $this->type == "platform" )		{ $this->get_platform(); }
	elseif ( $this->type == "browser" )		{ $this->get_browser(); }
	elseif ( $this->type == "browser_features" )	{ $this->get_browser_features(); }
	elseif ( $this->type == "screen" )		{ $this->get_screen(); }
	elseif ( $this->type == "color" )		{ $this->get_color(); }
	elseif ( $this->type == "cookies" )		{ $this->get_cookies(); }
	elseif ( $this->type == "java" )		{ $this->get_java(); }
	elseif ( $this->type == "whois" )		{ $this->get_whois(); }
	elseif ( $this->type == "ip" )			{ $this->get_ip(); }
	elseif ( $this->type == "country" )		{ $this->get_country(); }
	elseif ( $this->type == "city" )			{ $this->get_city(); }
	elseif ( $this->type == "region" )		{ $this->get_region(); }
	elseif ( $this->type == "organization" )		{ $this->get_organization(); }
	elseif ( $this->type == "popularity" )		{ $this->get_popularity(); }
	elseif ( $this->type == "ways" )		{ $this->get_ways(); }
	elseif ( $this->type == "input" )		{ $this->get_input(); }
	elseif ( $this->type == "output" )		{ $this->get_output(); }
	elseif ( $this->type == "depth" )		{ $this->get_depth(); }
	elseif ( $this->type == "time" )		{ $this->get_time(); }
	else					{ $this->get_consolidated(); }
 }

 function get_time() {

  	if ($this->variant == "day") {
		$this->header = "Время просмотра сайта на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Время просмотра сайта с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT statid, time FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND page <> 'unknown' ORDER BY id;");
	while($data=$result->fetch_row()) {

		$array_time[$data[0]][] = $data[1];
	}

	foreach($array_time as $key => $val) {

		if (count($val) >1 ) {
			$start_time = $val[0];
			$end_time = $val[count($val)-1];
			$min = ($end_time - $start_time) / 60;
			$min = round($min, 0);
			if (!$min) { $min = 1; }
			$t_array_time[$min]++;
			$count_time ++;		
		}
	}

	arsort($t_array_time);

	foreach($t_array_time as $key => $val) {

		 $class = $flag ? "oddrow" : "evenrow";
		 $flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left">'.$this->format_time($key).'</td>
			<td align="center">'.$val.'</td>
			<td align="center">'.$this->percent($val ,$count_time).' %</td>
		</tr>';
		$this->export_data[] = array($this->format_time($key), $val, $this->percent($val ,$count_time).' %');
	}

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_time.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_time, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">&nbsp;</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_depth() {

  	if ($this->variant == "day") {
		$this->header = "Глубина просмотра сайта на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Глубина просмотра сайта с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT statid, page FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND page <> 'unknown' ORDER BY id;");
	while($data=$result->fetch_row()) {

		$data[0] = rtrim($data[0], "/");
		if (preg_match("/^(http:\/\/)?([^?]+)/i", $data[1], $matches)) { $data[1] = "http://".$matches[2]; }
		$array_depth[$data[0]][$data[1]] ++;
	}

	foreach($array_depth as $key => $val) {

		$t_array_depth[count($val)]++;
		$count_depth ++;
	}

	arsort($t_array_depth);

	foreach($t_array_depth as $key => $val) {

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left">'.$key.' стр.</td>
			<td align="center">'.$val.'</td>
			<td align="center">'.$this->percent($val ,$count_depth).' %</td>
		</tr>';
		$this->export_data[] = array($key, $val, $this->percent($val ,$count_depth).' %');
	}

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_depth.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_depth, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">&nbsp;</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_output() {

  	if ($this->variant == "day") {
		$this->header = "Точки выхода на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Точки выхода с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT statid, page FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND page <> 'unknown' ORDER BY id;");
	while($data=$result->fetch_row()) {

		$data[0] = rtrim($data[0], "/");
		if (preg_match("/^(http:\/\/)?([^?]+)/i", $data[1], $matches)) { $data[1] = "http://".$matches[2]; }
		$data[1] = str_replace("http://".$_SERVER['SERVER_NAME'], "", $data[1]);
		$array_output[$data[0]][$data[1]] = 1;
	}

	arsort($array_output);

	foreach($array_output as $key => $val) {

		foreach($array_output[$key] as $k=>$v) {

			$string_output = " » <a href=$k target=_blank>$k</a>";
		}

		$array_string_output[$string_output] += 1;
		$count_output++;

		unset($string_output);
	}

	arsort($array_string_output);

	foreach($array_string_output as $key => $val) {

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left">'.$key.'</td>
			<td align="center">'.$array_string_output[$key].'</td>
			<td align="center">'.$this->percent($array_string_output[$key] ,$count_output).' %</td>
		</tr>';
		$this->export_data[] = array($key, $array_string_output[$key], $this->percent($array_string_output[$key] ,$count_output).' %');
	}

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_output.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_output, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">Страница</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('Страница', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_input() {

  	if ($this->variant == "day") {
		$this->header = "Точки входа на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Точки входа с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT statid, page FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND page <> 'unknown' ORDER BY id;");
	while($data=$result->fetch_row()) {

		$data[0] = rtrim($data[0], "/");
		if (preg_match("/^(http:\/\/)?([^?]+)/i", $data[1], $matches)) { $data[1] = "http://".$matches[2]; }
		$data[1] = str_replace("http://".$_SERVER['SERVER_NAME'], "", $data[1]);
		$array_input[$data[0]][$data[1]] = 1;
	}

	arsort($array_input);

	foreach($array_input as $key => $val) {

		foreach($array_input[$key] as $k=>$v) {
			
			$string_input .= " » <a href=$k target=_blank>$k</a>";
			break;
		}

		$array_string_input[$string_input] += 1;
		$count_input++;

		unset($string_input);
	}

	arsort($array_string_input);

	foreach($array_string_input as $key => $val) {

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left">'.$key.'</td>
			<td align="center">'.$array_string_input[$key].'</td>
			<td align="center">'.$this->percent($array_string_input[$key] ,$count_input).' %</td>
		</tr>';
		$this->export_data[] = array($key, $array_string_input[$key], $this->percent($array_string_input[$key] ,$count_input).' %');
	}

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_input.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_input, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">Страница</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('Страница', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_ways() {

  	if ($this->variant == "day") {
		$this->header = "Пути по сайту на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Пути по сайту с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT statid, page FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND page <> 'unknown' ORDER BY id;");
	while($data=$result->fetch_row()) {

		$data[0] = rtrim($data[0], "/");
		if (preg_match("/^(http:\/\/)?([^?]+)/i", $data[1], $matches)) { $data[1] = "http://".$matches[2]; }
		$data[1] = str_replace("http://".$_SERVER['SERVER_NAME'], "", $data[1]);
		$array_ways[$data[0]][$data[1]] = 1;
	}

	arsort($array_ways);

	foreach($array_ways as $key => $val) {

		foreach($array_ways[$key] as $k=>$v) {
			
			$string_ways .= " » <a href=$k target=_blank>$k</a>";
		}

		$array_string_ways[$string_ways] += 1;
		$count_ways++;

		unset($string_ways);
	}

	arsort($array_string_ways);

	foreach($array_string_ways as $key => $val) {

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left">'.$key.'</td>
			<td align="center">'.$array_string_ways[$key].'</td>
			<td align="center">'.$this->percent($array_string_ways[$key] ,$count_ways).' %</td>
		</tr>';
		$this->export_data[] = array($key, $array_string_ways[$key], $this->percent($array_string_ways[$key] ,$count_ways).' %');
	}

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_ways.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_ways, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">Путь</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('Путь', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_popularity() {

	if ($this->variant == "day") {
		$this->header = "Популярность страниц на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Популярность страниц с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT if(STRCMP(LEFT(page,13),'http%3A%2F%2F')=0,IF(LOCATE('%2F',page,13),SUBSTRING(page,LOCATE('%2F',page,13)),'/'),page), count(page) as count FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND page <> 'unknown' GROUP BY page;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_popularity +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT if(STRCMP(LEFT(page,13),'http%3A%2F%2F')=0,IF(LOCATE('%2F',page,13),SUBSTRING(page,LOCATE('%2F',page,13)),'/'),page), count(page) as count FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND page <> 'unknown' GROUP BY page ORDER BY count desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_popularity[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_popularity as $key => $val) {

		$descr = "<a href=$key target=_blank>$key</a>";

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_popularity[$key].'</td>
			<td align="center">'.$this->percent($array_popularity[$key] ,$total_popularity).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_popularity[$key], $this->percent($array_popularity[$key], $total_popularity).' %');

		$itter++;
	}

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_popularity.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_popularity, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Страница</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Страница', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_organization() {

  	if ($this->variant == "day") {
		$this->header = "Организации на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Организации с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT organization, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND organization <> 'unknown' GROUP BY organization;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_organization +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT organization, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND organization <> 'unknown' GROUP BY organization ORDER BY numb DESC, organization ASC limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_organization[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_organization as $key => $val) {

		$descr = $this->string_to($key);
		$top_array[] = $key;
		$top_array_text[] = $descr;

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_organization[$key].'</td>
			<td align="center">'.$this->percent($array_organization[$key] ,$total_organization).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_organization[$key], $this->percent($array_organization[$key], $total_organization).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "organization");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_organization.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_organization, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Организация</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Организация', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_region() {

  	if ($this->variant == "day") {
		$this->header = "Регионы на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Регионы с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT region, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND region <> 'unknown' GROUP BY region;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_region +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT region, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND region <> 'unknown' GROUP BY region ORDER BY numb DESC, region ASC limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_region[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_region as $key => $val) {

		$descr = $this->string_to($key);
		$top_array[] = $key;
		$top_array_text[] = $descr;

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_region[$key].'</td>
			<td align="center">'.$this->percent($array_region[$key] ,$total_region).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_region[$key], $this->percent($array_region[$key], $total_region).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "region");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_region.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_region, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Регион</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Регион', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_city() {

  	if ($this->variant == "day") {
		$this->header = "Города на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Города с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT city, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND city <> 'unknown' GROUP BY city;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_city +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT city, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND city <> 'unknown' GROUP BY city ORDER BY numb DESC, city ASC limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_city[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_city as $key => $val) {

		$descr = $this->string_to($key);
		$top_array[] = $key;
		$top_array_text[] = $descr;

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_city[$key].'</td>
			<td align="center">'.$this->percent($array_city[$key] ,$total_city).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_city[$key], $this->percent($array_city[$key], $total_city).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "city");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_city.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_city, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Город</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Город', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_country() {

  	if ($this->variant == "day") {
		$this->header = "Страны на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Страны с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT country, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND country <> 'unknown' GROUP BY country;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_country +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT country, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND country <> 'unknown' GROUP BY country ORDER BY numb DESC, country ASC limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_country[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_country as $key => $val) {

		$descr = $this->string_to($key);
		$top_array[] = $key;
		$top_array_text[] = $descr;

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_country[$key].'</td>
			<td align="center">'.$this->percent($array_country[$key] ,$total_country).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_country[$key], $this->percent($array_country[$key], $total_country).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "country");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_country.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_country, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Страна</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Страна', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_whois() {

	if ( !$_GET['ip'] )	{ $_GET['ip'] = "127.0.0.1"; }

	$this->header = "Детальная информация по IP: ".$_GET['ip'];

	$table .= '
	<tr class="oddrow">
		<td align="left">IP:</td>
		<td align="left">'.$_GET['ip'].'</td>
	</tr>
	<tr class="evenrow">
		<td align="left">Название:</td>
		<td align="left">'.gethostbyaddr($_GET['ip']).'</td>
	</tr>';

	 include("./includes/geoip/geoipcity.inc");

	 if(file_exists("./includes/geoip/GeoIPOrg.dat")) {
		$giorg = geoip_open("./includes/geoip/GeoIPOrg.dat", GEOIP_STANDARD);
		$org = geoip_org_by_addr($giorg, $_GET['ip']);
		if ($org) {
			$table .= '
			<tr class="oddrow">
				<td align="left">Организация:</td>
				<td align="left">'.$org.'</td>
			</tr>';
		}
		geoip_close($giorg);
	 }

	 if(file_exists("./includes/geoip/GeoIPCity.dat")) {
		$gi = geoip_open("./includes/geoip/GeoIPCity.dat", GEOIP_STANDARD);
		$record = geoip_record_by_addr($gi, $_GET['ip']);

		if ($record->latitude) {
			$table .= '
			<tr class="evenrow">
				<td align="left">Широта:</td>
				<td align="left">'.$record->latitude.'</td>
			</tr>';
		}
		if ($record->longitude) {
			$table .= '
			<tr class="oddrow">
				<td align="left">Долгота:</td>
				<td align="left">'.$record->longitude.'</td>
			</tr>';
		}
		if ($record->country_code) {
			$table .= '
			<tr class="evenrow">
				<td align="left">Код страны:</td>
				<td align="left">'.$record->country_code.'</td>
			</tr>';
		}
		if ($record->country_name) {
			$table .= '
			<tr class="oddrow">
				<td align="left">Страна:</td>
				<td align="left">'.$record->country_name.'</td>
			</tr>';
		}
		if ($record->city) {
			$table .= '
			<tr class="evenrow">
				<td align="left">Город:</td>
				<td align="left">'.$record->city.'</td>
			</tr>';
		}
		if ($FIPS[$record->country_code][$record->region]) {
			$table .= '
			<tr class="oddrow">
				<td align="left">Регион:</td>
				<td align="left">'.$FIPS[$record->country_code][$record->region].'</td>
			</tr>';
		}
	}

	$sp=fsockopen("whois.ripe.net", 43);
	if(!$sp) {
		$message = "Не удалось подключиться к сервису Whois!";
	}
	fputs($sp, $_GET['ip']."\r\n");

	while(!feof($sp)) { $message .= fread($sp, 1024); }
	fclose($sp);

	$table .= '
	<tr class="evenrow">
		<td align="left">Whois:</td>
		<td align="left">'.nl2br($message).'</td>
	</tr>';

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="20%">Пораметр</th>
		<th width="80%">Значение</th>
	</tr>'
	.$table.
	'</table>';
 }

 function get_ip() {

  	if ($this->variant == "day") {
		$this->header = "IP адреса на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "IP адреса с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT ip, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY ip ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_ip +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT ip, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND ip <> 'unknown' GROUP BY ip ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_ip[long2ip($data[0])] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_ip as $key => $val) {

		$descr = "<a href=?begin=".date("d-m-Y", $this->start_date)."&end=".date("d-m-Y", $this->end_date)."&type=whois&type_gr=$this->type_gr&ip=$key>$key</a>";

		$top_array[] = ip2long($key);
		$top_array_text[] ='IP: '.$key;

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_ip[$key].'</td>
			<td align="center">'.$this->percent($array_ip[$key], $total_ip).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_ip[$key], $this->percent($array_ip[$key], $total_ip).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "ip");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_ip.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_ip, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">IP адрес</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'IP адрес', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_java() {

  	if ($this->variant == "day") {
		$this->header = "Использование JavaScript на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Использование JavaScript с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT java FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." ORDER BY java;");
	while($data=$result->fetch_row()) {
		$data[0] = str_replace("unknown", "неизвестна", $data[0]);
		$array_java[$data[0]] ++;
		$count_java ++;
	}

	arsort($array_java);

	foreach($array_java as $key => $val) {

		if ( $key != "unknown" ) {
			$descr = $key;
			$top_array[] = $key;
		} else {
			$descr = str_replace("unknown", "Неопределено", $key);
		}

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left">Версия '.$descr.'</td>
			<td align="center">'.$array_java[$key].'</td>
			<td align="center">'.$this->percent($array_java[$key] ,$count_java).' %</td>
		</tr>';
		$this->export_data[] = array('Версия '.$descr, $array_java[$key], $this->percent($array_java[$key] ,$count_java).' %');
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array, "java");

	foreach ($this->graph_legend_array as $key => $val) {
		$this->graph_legend_array[$key] = "Версия ".$this->graph_legend_array[$key];
	}

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_java.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_java, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">JavaScript</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('JavaScript', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_cookies() {

  	if ($this->variant == "day") {
		$this->header = "Использование Cookies на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Использование Cookies с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT cookies FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";");
	while($data=$result->fetch_row()) {

		$array_cookies[$data[0]] ++;
		$count_cookies ++;
	}
	
	if ( !$array_cookies["false"] ) $array_cookies["false"] = 0;
	if (!$array_cookies['true']) $array_cookies['true'] = "&#151;";
	if (!$array_cookies['false']) $array_cookies['false'] = "&#151;";

	$table .= '
	<tr class=evenrow>
		<td align="left">Использование Cookies</td>
		<td align="center">'.$array_cookies['true'].'</td>
		<td align="center">'.$array_cookies['false'].'</td>
	</tr>';
	$this->export_data[1] = array('Использование Cookies', $array_cookies['true'], $array_cookies['false']);

	$top_array[] = $key;

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array, "cookies");

	$this->graph_legend_array[0] = "Использование Cookies";

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">Cookies</th>
		<th width="15%">Да</th>
		<th width="15%">Нет</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('Cookies', 'Да', 'Нет');
	ksort($this->export_data);
 }

 function get_color() {

  	if ($this->variant == "day") {
		$this->header = "Глубина цвета на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Глубина цвета с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT color, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND color <> 'unknown' GROUP BY color ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_color +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT color, count(*) AS numb, wc_color.text FROM wc_main left join wc_color ON wc_color.name = wc_main.color WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND color <> 'unknown' GROUP BY color ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		list($b, $v) = split (" ", $data[0]);
		$array_color[$data[0]]['color'] = $data[0];
		$array_color[$data[0]]['text'] = $data[2].' '.$v;
		$array_color[$data[0]]['count'] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_color as $key => $val) {

		$top_array[] = $val['color'];
		$top_array_text[] = $val['text'];

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$val['text'].'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'], $total_color).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $val['text'], $val['count'], $this->percent($val['count'], $total_color).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "color");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_color.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_color, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Глубина цвета</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Глубина цвета', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_screen() {

  	if ($this->variant == "day") {
		$this->header = "Разрешение экрана на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Разрешение экрана с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT screen, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND screen <> 'unknown' GROUP BY screen ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_screen +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT screen, count(*) AS numb, wc_screen.text FROM wc_main left join wc_screen ON wc_screen.name = wc_main.screen WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND screen <> 'unknown' GROUP BY screen ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		list($b, $v) = split (" ", $data[0]);
		$array_screen[$data[0]]['screen'] = $data[0];
		$array_screen[$data[0]]['text'] = $data[2].' '.$v;
		$array_screen[$data[0]]['count'] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_screen as $key => $val) {

		$top_array[] = $val['screen'];
		$top_array_text[] = $val['text'];

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$val['text'].'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'], $total_screen).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $val['text'], $val['count'], $this->percent($val['count'], $total_screen).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "screen");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_screen.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_screen, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Разрешение</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Разрешение', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_browser_features() {

  	if ($this->variant == "day") {
		$this->header = "Характеристики браузеров на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Характеристики браузеров с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT * FROM wc_browser_features;");
	while($data=$result->fetch_row()) {
		$array_name_browser[$data[1]] = $data[3];
	}

	$result=$this->exec("SELECT css2, css1, iframes, xml, dom, avoid_popup, cache_forms, cache_ssl FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";");
	while($data=$result->fetch_row()) {

		$array_browser['css2'][$data[0]] ++;
		$array_browser['css1'][$data[1]] ++;
		$array_browser['iframes'][$data[2]] ++;
		$array_browser['xml'][$data[3]] ++;
		$array_browser['dom'][$data[4]] ++;
		$array_browser['avoid_popup'][$data[5]] ++;
		$array_browser['cache_forms'][$data[6]] ++;
		$array_browser['cache_ssl'][$data[7]] ++;
		$count_browser ++;
	}

	foreach($array_name_browser as $key => $val) {

		if (!$array_browser[$key]['true']) $array_browser[$key]['true'] = "&#151;";
		if (!$array_browser[$key]['false']) $array_browser[$key]['false'] = "&#151;";

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left">'.$val.'</td>
			<td align="center">'.$array_browser[$key]['true'].'</td>
			<td align="center">'.$array_browser[$key]['false'].'</td>
		</tr>';
		$this->export_data[] = array($val, $array_browser[$key]['true'], $array_browser[$key]['false']);
	}

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="70%">Характеристика</th>
		<th width="15%">Да</th>
		<th width="15%">Нет</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(80, 10, 10);
	$this->table_align = array('L', 'C', 'C');
	$this->export_data[0] = array('Характеристика', 'Да', 'Нет');
	ksort($this->export_data);
 }

 function get_browser() {

  	if ($this->variant == "day") {
		$this->header = "Браузеры на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Браузеры с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}
	$total_amount = $total_browser = 0;
	$result=$this->exec("SELECT platform, browser, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY browser ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_browser +=  $data[2];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT platform, browser, count(*) AS numb, wc_browser.text FROM wc_main left join wc_browser ON wc_browser.name = if(locate(' ',browser,1)=0, concat(browser,' '), left(browser, locate(' ',browser,1))) WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND browser <> 'unknown' GROUP BY browser ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {	
		list($b, $v) = split (" ", $data[1]);
		$array_browser[$data[1]]['browser'] = $v;
		$array_browser[$data[1]]['count'] = $data[2];
		$array_browser[$data[1]]['text'] = $data[3];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_browser as $key => $val) {

		$top_array[] = $key;
		$top_array_text[] = $val['text'].' '.$val['browser'];

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$val['text'].' '.$val['browser'].'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'], $total_browser).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $val['text'].' '.$val['browser'], $val['count'], $this->percent($val['count'], $total_browser).' %');
		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "browser");
//elPrintR($this->graph_data_array);
	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_browser.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_browser, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Браузер</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Браузер', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_platform() {

  	if ($this->variant == "day") {
		$this->header = "Платформы на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Платформы с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT platform, platform, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY platform ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_platform +=  $data[2];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT platform, platform, count(*) AS numb, wc_os.text FROM wc_main left join wc_os ON wc_os.name = wc_main.platform WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND platform <> 'unknown' GROUP BY platform ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {	
		$data[1] = strtoupper($data[1]);
		$array_platform[$data[1]]['platform'] = $data[0];
		$array_platform[$data[1]]['count'] = $data[2];
		$array_platform[$data[1]]['text'] = $data[3];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_platform as $key => $val) {

		$top_array[] = $key;
		$top_array_text[] = $val['text'];

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$val['text'].'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'], $total_platform).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $val['text'], $val['count'], $this->percent($val['count'], $total_platform).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "platform");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_platform.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_platform, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Платформа</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Платформа', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_os() {

  	if ($this->variant == "day") {
		$this->header = "Операционные системы на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Операционные системы с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT platform, os, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY os ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_os +=  $data[2];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT platform, os, count(*) AS numb, wc_os.text FROM wc_main left join wc_os ON wc_os.name = wc_main.platform WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND platform <> 'unknown' GROUP BY os ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {	
		$data[1] = strtoupper($data[1]);
		$array_os[$data[1]]['os'] = $data[0];
		$array_os[$data[1]]['count'] = $data[2];
		$array_os[$data[1]]['text'] = $data[3];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_os as $key => $val) {

		$top_array[] = $key;
		$top_array_text[] = $val['text'].' '.$key;

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$val['text'].' '.$key.'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'], $total_os).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $val['text'], $val['count'], $this->percent($val['count'], $total_os).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "os");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_os.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_os, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Система</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Система', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_search_word() {

  	if ($this->variant == "day") {
		$this->header = "Поисковые слова на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Поисковые слова с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	if ( $_GET['id'] )	{ $t_query = " AND searcheng='$_GET[id]' "; }
	else		{ $t_query = ""; }

	$result=$this->exec("SELECT search_words, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND search_words <> 'unknown' $t_query GROUP BY search_words ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_search_words +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "&id=$_GET[id]");

	$result=$this->exec("SELECT search_words, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND search_words <> 'unknown' $t_query GROUP BY search_words ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_search_word[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_search_word as $key => $val) {

		$descr = $this->string_to($key);

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_search_word[$key].'</td>
			<td align="center">'.$this->percent($array_search_word[$key] ,$total_amount).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_search_word[$key], $this->percent($array_search_word[$key] ,$total_amount).' %');

		$itter++;
	}

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_amount.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_amount, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Слова</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Слова', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_search() {

	if ($this->variant == "day") {
		$this->header = "Ссылающиеся поисковые системы на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Ссылающиеся поисковые системы с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT searcheng, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND searcheng <> 'unknown' GROUP BY searcheng ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_search +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT searcheng, count(*) AS numb, wc_search.text, name FROM wc_main left join wc_search ON wc_search.name = wc_main.searcheng WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND searcheng <> 'unknown' GROUP BY searcheng ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_search[$data[0]]['count'] = $data[1];
		$array_search[$data[0]]['text'] = $data[2];
		$array_search[$data[0]]['name'] = $data[3];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_search as $key => $val) {

		$descr = "<a href=?begin=".date("d-m-Y", $this->start_date)."&end=".date("d-m-Y", $this->end_date)."&type=search_word&type_gr=$this->type_gr&id=$key>$val[text]</a>";
		$top_array[] = $val['name'];
		$top_array_text[] = $val['text'];

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'] ,$total_search).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $val['count'], $this->percent($val['count'] ,$total_search).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "searcheng");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_search.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_search, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Поисковые система</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Поисковые система', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_catalog() {

	if ($this->variant == "day") {
		$this->header = "Ссылающиеся каталоги и рейтинги на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Ссылающиеся каталоги и рейтинги с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT catalog, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND catalog <> 'unknown' GROUP BY catalog ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_catalog +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT catalog, count(*) AS numb, wc_catalog.text, name, value FROM wc_main left join wc_catalog ON wc_catalog.value = wc_main.catalog WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND catalog <> 'unknown' GROUP BY catalog ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_catalog[$data[0]]['count'] = $data[1];
		$array_catalog[$data[0]]['text'] = $data[2];
		$array_catalog[$data[0]]['name'] = $data[3];
		$array_catalog[$data[0]]['value'] = $data[4];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_catalog as $key => $val) {

		$descr = "<a href=http://".$val['name']." target=_blank>".$val['text']."</a>";
		$top_array[] = $val['value'];
		$top_array_text[] = $val['text'];

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'] ,$total_catalog).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $val['count'], $this->percent($val['count'] ,$total_catalog).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "catalog");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_catalog.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_catalog, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Каталог или рейтинг</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Каталог или рейтинг', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_forum() {

	if ($this->variant == "day") {
		$this->header = "Ссылающиеся форумы на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Ссылающиеся форумы с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT forum, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND forum <> 'unknown' GROUP BY forum ORDER BY numb desc;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_forum +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT forum, count(*) AS numb, wc_forum.text, name, value FROM wc_main left join wc_forum ON wc_forum.value = wc_main.forum WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND forum <> 'unknown' GROUP BY forum ORDER BY numb desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_forum[$data[0]]['count'] = $data[1];
		$array_forum[$data[0]]['text'] = $data[2];
		$array_forum[$data[0]]['name'] = $data[3];
		$array_forum[$data[0]]['value'] = $data[4];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_forum as $key => $val) {

		$descr = "<a href=http://".$val['name']." target=_blank>".$val['text']."</a>";
		$top_array[] = $val['value'];
		$top_array_text[] = $val['text'];

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$val['count'].'</td>
			<td align="center">'.$this->percent($val['count'] ,$total_forum).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $val['count'], $this->percent($val['count'] ,$total_forum).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array_text, "forum");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_forum.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_forum, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Форум</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Форум', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_domain() {

	if ($this->variant == "day") {
		$this->header = "Ссылающиеся домены на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Ссылающиеся домены с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT if(locate('/',referer,8)=0, concat(referer,'/'), left(referer, locate('/',referer,8))) as ref, count(referer) as count FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND referer <> 'unknown' GROUP BY ref;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_domain +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT if(locate('/',referer,8)=0, concat(referer,'/'), left(referer, locate('/',referer,8))) as ref, count(referer) as count FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND referer <> 'unknown' GROUP BY ref ORDER BY count desc limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_domain[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_domain as $key => $val) {

		$descr = "<a href=$key target=_blank>$key</a>";
		$top_array[] = $key;

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_domain[$key].'</td>
			<td align="center">'.$this->percent($array_domain[$key] ,$total_domain).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_domain[$key], $this->percent($array_domain[$key] ,$total_domain).' %');

		$itter++;
	}

	list($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array) = $this->graph_top_result($top_array, $top_array, "referer");

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_domain.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_domain, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Домен</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Домен', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_referer() {

	if ($this->variant == "day") {
		$this->header = "Ссылающиеся страницы на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Ссылающиеся страницы с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT referer, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND referer <> 'unknown' GROUP BY referer;");
	while($data=$result->fetch_row()) {
		$total_amount++;
		$total_referer +=  $data[1];
	}
	$this->pages=$this->get_pages($total_amount, "");

	$result=$this->exec("SELECT referer, count(*) AS numb FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." AND referer <> 'unknown' GROUP BY referer ORDER BY numb DESC, referer ASC limit ".($this->start_item - 1).",".$this->gbl_config['item_per_page'].";");
	while($data=$result->fetch_row()) {
		$array_referer[$data[0]] = $data[1];
	}

	$itter=$this->start_item;
	$flag=true;

	foreach($array_referer as $key => $val) {
		
		$temp_url = urldecode($key);
		$temp_url = parse_url($temp_url);
		$url = $temp_url['scheme']."://".$temp_url['host'].$temp_url['path'];

		if (strlen($key) > strlen($url) and strlen($key) > 40) {
			$url .= "...".substr($key, strlen($key) - 15, strlen($key));
		} else {
			$url = $key;
		}

		$valid_url = str_replace(" ", "%20", $key);
		$descr = "<a href=$valid_url target=_blank>$url</a>";

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align=center>'.$itter.'</td>
			<td align="left">'.$descr.'</td>
			<td align="center">'.$array_referer[$key].'</td>
			<td align="center">'.$this->percent($array_referer[$key] ,$total_referer).' %</td>
		</tr>';
		$this->export_data[] = array($itter, $descr, $array_referer[$key], $this->percent($array_referer[$key] ,$total_referer).' %');

		$itter++;
	}

	$table .= '
	<tr class=evenrow>
		<td align="left" colspan="2"><b>Итого:</b></td>
		<td align="center"><b>'.$total_referer.'</b></td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('', 'Итого:', $total_referer, '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="5%">N</th>
		<th width="65%">Страница</th>
		<th width="15%">Кол-во</th>
		<th width="15%">Процент в группе</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(10, 70, 10, 10);
	$this->table_align = array('C', 'L', 'C', 'C');
	$this->export_data[0] = array('N (Top'.$this->gbl_config['item_per_page'].')', 'Страница', 'Кол-во', 'Процент в группе');
	ksort($this->export_data);
 }

 function get_session() {

	if ($this->variant == "day") {
		$this->header = "Сессии по часам на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Сессии по часам с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(statid)) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY statid ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_session[$data[0]] += $data[1];
		$count_session +=  $data[1];
		$key = $data[0];
	}

	if ($this->variant == "day" AND $this->start_date == mktime(0,0,0)) {
		$key = $key;
	} else {
		$key = 24;
	}

	$array_session = $this->nullarray($array_session, $key);

	$start =  $this->start_date-3600*24;
	$end =  $this->end_date-3600*24;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(statid)) FROM wc_session WHERE time >= ".$start." AND time < ".$end." GROUP BY statid ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_session_yesterday[$data[0]] += $data[1];
	}
	$array_session_yesterday = $this->nullarray($array_session_yesterday, $key);

	$start =  $this->start_date-3600*24*7;
	$end =  $this->end_date-3600*24*7;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(statid)) FROM wc_session WHERE time >= ".$start." AND time < ".$end." GROUP BY statid ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_session_7[$data[0]] += $data[1];
	}
	$array_session_7 = $this->nullarray($array_session_7, $key);

	$start =  $this->start_date-3600*24*30;
	$end =  $this->end_date-3600*24*30;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(statid)) FROM wc_session WHERE time >= ".$start." AND time < ".$end." GROUP BY statid ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_session_30[$data[0]] += $data[1];
	}

	$array_session_30 = $this->nullarray($array_session_30, $key);

	foreach($array_session as $key => $val) {

		$header_array[$key] = $key.":00";

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left"><b>'.$key.':00</b></td>
			<td align="center">'.$array_session[$key].'</td>
			<td align="center">'.$this->percent($array_session[$key] ,$count_session).' %</td>
			<td align="center">'.$this->change($key, $array_session[$key] ,$array_session[$key-1]).'</td>
		</tr>';
		$this->export_data[] = array($key.':00', $array_session[$key], $this->percent($array_session[$key] ,$count_session).' %', $this->change($key, $array_session[$key] ,$array_session[$key-1]));
	}

	if (isset($header_array))
	$this->graph_data_array = array($header_array, $array_session, $array_session_yesterday, $array_session_7, $array_session_30);

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_session.'</b></td>
		<td align="center">&nbsp;</td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_session, '', '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="25%">Время</th>
		<th width="25%">Сессии</th>
		<th width="25%">Процент в группе</th>
		<th width="25%">Изменение</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(55, 15, 15, 15);
	$this->table_align = array('L', 'C', 'C', 'C');
	$this->export_data[0] = array('Время:', 'Сессии', 'Процент в группе', 'Изменение');
	ksort($this->export_data);

	$this->graph_legend_array = array("Сессии", "Сессии день назад", "Сессии неделю назад", "Сессии месяц назад");
	$this->graph_num_array = array(1,2,3,4);
 }

 function get_hit() {

	if ($this->variant == "day") {
		$this->header = "Хиты по часам на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Хиты по часам с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(*) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY oktime ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_hit[$data[0]] = $data[1];
		$count_hit +=  $data[1];
		$key = $data[0];
	}

	if ($this->variant == "day" AND $this->start_date == mktime(0,0,0)) {
		$key = $key;
	} else {
		$key = 24;
	}

	$array_hit = $this->nullarray($array_hit, $key);

	$start =  $this->start_date-3600*24;
	$end =  $this->end_date-3600*24;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(*) FROM wc_session WHERE time >= ".$start." AND time < ".$end." GROUP BY oktime ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_hit_yesterday[$data[0]] = $data[1];
	}
	$array_hit_yesterday = $this->nullarray($array_hit_yesterday, $key);

	$start =  $this->start_date-3600*24*7;
	$end =  $this->end_date-3600*24*7;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(*) FROM wc_session WHERE time >= ".$start." AND time < ".$end." GROUP BY oktime ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_hit_7[$data[0]] = $data[1];
	}
	$array_hit_7 = $this->nullarray($array_hit_7, $key);

	$start =  $this->start_date-3600*24*30;
	$end =  $this->end_date-3600*24*30;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(*) FROM wc_session WHERE time >= ".$start." AND time < ".$end." GROUP BY oktime ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_hit_30[$data[0]] = $data[1];
	}
	$array_hit_30 = $this->nullarray($array_hit_30, $key);

	foreach($array_hit as $key => $val) {

		$header_array[$key] = $key.":00";

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left"><b>'.$key.':00</b></td>
			<td align="center">'.$array_hit[$key].'</td>
			<td align="center">'.$this->percent($array_hit[$key] ,$count_hit).' %</td>
			<td align="center">'.$this->change($key, $array_hit[$key] ,$array_hit[$key-1]).'</td>
		</tr>';
		$this->export_data[] = array($key.':00', $array_hit[$key], $this->percent($array_hit[$key] ,$count_hit).' %', $this->change($key, $array_hit[$key] ,$array_hit[$key-1]));
	}

	if (isset($header_array))
	$this->graph_data_array = array($header_array, $array_hit, $array_hit_yesterday, $array_hit_7, $array_hit_30);

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_hit.'</b></td>
		<td align="center">&nbsp;</td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_hit, '', '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="25%">Время</th>
		<th width="25%">Хиты</th>
		<th width="25%">Процент в группе</th>
		<th width="25%">Изменение</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(55, 15, 15, 15);
	$this->table_align = array('L', 'C', 'C', 'C');
	$this->export_data[0] = array('Время:', 'Хиты', 'Процент в группе', 'Изменение');
	ksort($this->export_data);

	$this->graph_legend_array = array("Хиты", "Хиты день назад", "Хиты неделю назад", "Хиты месяц назад");
	$this->graph_num_array = array(1,2,3,4);
 }

 function get_host() {

	if ($this->variant == "day") {
		$this->header = "Хосты по часам на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header= "Хосты по часам с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(ip)) FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY ip ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_host[$data[0]] += $data[1];
		$count_host +=  $data[1];
		$key = $data[0];
	}

	if ($this->variant == "day" AND $this->start_date == mktime(0,0,0)) {
		$key = $key;
	} else {
		$key = 24;
	}

	$array_host = $this->nullarray($array_host, $key);

	$start =  $this->start_date-3600*24;
	$end =  $this->end_date-3600*24;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(ip)) FROM wc_main WHERE time >= ".$start." AND time < ".$end." GROUP BY ip ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_host_yesterday[$data[0]] += $data[1];
	}
	$array_host_yesterday = $this->nullarray($array_host_yesterday, $key);

	$start =  $this->start_date-3600*24*7;
	$end =  $this->end_date-3600*24*7;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(ip)) FROM wc_main WHERE time >= ".$start." AND time < ".$end." GROUP BY ip ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_host_7[$data[0]] += $data[1];
	}
	$array_host_7 = $this->nullarray($array_host_7, $key);

	$start =  $this->start_date-3600*24*30;
	$end =  $this->end_date-3600*24*30;

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(ip)) FROM wc_main WHERE time >= ".$start." AND time < ".$end." GROUP BY ip ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_host_30[$data[0]] += $data[1];
	}
	$array_host_30 = $this->nullarray($array_host_30, $key);

	foreach($array_host as $key => $val) {

		$header_array[$key] = $key.":00";

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left"><b>'.$key.':00</b></td>
			<td align="center">'.$array_host[$key].'</td>
			<td align="center">'.$this->percent($array_host[$key] ,$count_host).' %</td>
			<td align="center">'.$this->change($key, $array_host[$key] ,$array_host[$key-1]).'</td>
		</tr>';
		$this->export_data[] = array($key.':00', $array_host[$key], $this->percent($array_host[$key] ,$count_host).' %', $this->change($key, $array_host[$key] ,$array_host[$key-1]));
	}

	if (isset($header_array))
	$this->graph_data_array = array($header_array, $array_host, $array_host_yesterday, $array_host_7, $array_host_30);

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_host.'</b></td>
		<td align="center">&nbsp;</td>
		<td align="center">&nbsp;</td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_host, '', '');

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="25%">Время</th>
		<th width="25%">Хосты</th>
		<th width="25%">Процент в группе</th>
		<th width="25%">Изменение</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(55, 15, 15, 15);
	$this->table_align = array('L', 'C', 'C', 'C');
	$this->export_data[0] = array('Время:', 'Хосты', 'Процент в группе', 'Изменение');
	ksort($this->export_data);

	$this->graph_legend_array = array("Хосты", "Хосты день назад", "Хосты неделю назад", "Хосты месяц назад");
	$this->graph_num_array = array(1,2,3,4);
 }

 function get_attendance() {

	if ($this->variant == "day") {
		$this->header = "Хиты, Хосты, Сессии на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	} else {
		$this->header = "Хиты, Хосты, Сессии с  ".date("d", $this->start_date)." ".$this->rusdate(date("n", $this->start_date), true)." ".date("Y", $this->start_date)." г. по  ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";
	}
	$count_host = 0;
	$array_host = array();
	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(ip)) FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY ip ORDER BY time;");
	
	while($data=$result->fetch_row()) {
		$array_host[$data[0]] += $data[1];
		$count_host +=  $data[1];
		$key = $data[0];
	}
	
	if ($this->variant == "day" AND $this->start_date == mktime(0,0,0)) {
		$key = $key;
	} else {
		$key = 24;
	}

	$array_host = $this->nullarray($array_host, $key);
	$average_host = sprintf("%01.2f", $count_host / count($array_host));

	$array_hit = array();
	$count_hit = 0;
	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(*) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY oktime ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_hit[$data[0]] += $data[1];
		$count_hit +=  $data[1];
	}
	$array_hit = $this->nullarray($array_hit, $key);
	$average_hit = sprintf("%01.2f", $count_hit / count($array_hit));

	$result=$this->exec("SELECT from_unixtime(time, '%k') AS oktime, count(distinct(statid)) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date." GROUP BY statid ORDER BY time;");
	while($data=$result->fetch_row()) {
		$array_session[$data[0]] += $data[1];
		$count_session +=  $data[1];
	}
	$array_session = $this->nullarray($array_session, $key);
	$average_session = sprintf("%01.2f", $count_session / count($array_session));

	foreach ($array_host as $key => $val) {

		$header_array[$key] = $key.":00";

		$class = $flag ? "oddrow" : "evenrow";
		$flag=!$flag;

		$table .= '
		<tr class='.$class.'>
			<td align="left"><b>'.$key.':00</b></td>
			<td align="center">'.$array_host[$key].'</td>
			<td align="center">'.$array_hit[$key].'</td>
			<td align="center">'.$array_session[$key].'</td>
		</tr>';
		$this->export_data[] = array($key.':00', $array_host[$key], $array_hit[$key], $array_session[$key]);
	}

	if (isset($header_array))
	$this->graph_data_array = array($header_array, $array_hit, $array_host, $array_session);

	$table .= '
	<tr class=evenrow>
		<td align="left"><b>Итого:</b></td>
		<td align="center"><b>'.$count_host.'</b></td>
		<td align="center"><b>'.$count_hit.'</b></td>
		<td align="center"><b>'.$count_session.'</b></td>
	</tr>
	<tr class=evenrow>
		<td align="left"><b>В среднем:</b></td>
		<td align="center"><b>'.$average_host.'</b></td>
		<td align="center"><b>'.$average_hit.'</b></td>
		<td align="center"><b>'.$average_session.'</b></td>
	</tr>';
	$this->export_data[] = array('Итого:', $count_host, $count_hit, $count_session);
	$this->export_data[] = array('В среднем:', $average_host, $average_hit, $average_session);

	if (!isset($key))	{ $table = "<tr class='oddrow'><td colspan='4'>За этот период статистики нет!</td></tr>"; }

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th width="25%">Время</th>
		<th width="25%">Хосты</th>
		<th width="25%">Хиты</th>
		<th width="25%">Сессии</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(55, 15, 15, 15);
	$this->table_align = array('L', 'C', 'C', 'C');
	$this->export_data[0] = array('Время:', 'Хосты', 'Хиты', 'Сессии');
	ksort($this->export_data);

	$this->graph_legend_array = array("Хиты", "Хосты", "Сессии");
	$this->graph_num_array = array(1,2,3);
 }

 function get_consolidated() {

 	$this->header = "Сводная статистика посещаемости на ".date("d", $this->end_date)." ".$this->rusdate(date("n", $this->end_date), true)." ".date("Y", $this->end_date)." г.";

	$result=$this->exec("SELECT count(*) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";" );
	list($hit_today)=$result->fetch_row();

	$start =  $this->start_date-3600*24;
	$end =  $this->end_date-3600*24;

	$result=$this->exec("SELECT count(*) FROM wc_session WHERE time >= ".$start." AND time < ".$end.";" );
	list($hit_yesterday)=$result->fetch_row();

	$start =  $this->start_date-3600*24*7;

	$result=$this->exec("SELECT count(*) FROM wc_session WHERE time >= ".$start." AND time < ".$this->end_date.";" );
	list($hit_unique7)=$result->fetch_row();

	$start =  $this->start_date-3600*24*30;

	$result=$this->exec("SELECT count(*) FROM wc_session WHERE time >= ".$start." AND time < ".$this->end_date.";" );
	list($hit_unique30)=$result->fetch_row();

	$result=$this->exec("SELECT count(*) FROM wc_session;" );
	list($hit_all)=$result->fetch_row();
	$table = '';
	$table .= '
	<tr class=oddrow>
		<td><b>Хиты:</b></td>
		<td align="center">'.$hit_today.'</td>
		<td align="center">'.$hit_yesterday.'</td>
		<td align="center">'.$hit_unique7.'</td>
		<td align="center">'.$hit_unique30.'</td>
		<td align="center"><b>'.$hit_all.'</b></td>
	</tr>';
	$this->export_data[1] = array('Хиты:', $hit_today, $hit_yesterday, $hit_unique7, $hit_unique30, $hit_all);

	$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";" );
	list($host_today)=$result->fetch_row();

	$start =  $this->start_date-3600*24;
	$end =  $this->end_date-3600*24;

	$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main WHERE time >= ".$start." AND time < ".$end.";" );
	list($host_yesterday)=$result->fetch_row();

	$start =  $this->start_date-3600*24*7;

	$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main WHERE time >= ".$start." AND time < ".$this->end_date.";" );
	list($host_unique7)=$result->fetch_row();

	$start =  $this->start_date-3600*24*30;

	$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main WHERE time >= ".$start." AND time < ".$this->end_date.";" );
	list($host_unique30)=$result->fetch_row();

	$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main;" );
	list($host_all)=$result->fetch_row();

	$table .= '
	<tr class=evenrow>
		<td><b>Хосты:</b></td>
		<td align="center">'.$host_today.'</td>
		<td align="center">'.$host_yesterday.'</td>
		<td align="center">'.$host_unique7.'</td>
		<td align="center">'.$host_unique30.'</td>
		<td align="center"><b>'.$host_all.'</b></td>
	</tr>';
	$this->export_data[2] = array('Хосты:', $host_today, $host_yesterday, $host_unique7, $host_unique30, $host_all);
		
	$result=$this->exec("SELECT count(distinct(statid)) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";" );
	list($sess_today)=$result->fetch_row();

	$start =  $this->start_date-3600*24;
	$end =  $this->end_date-3600*24;

	$result=$this->exec("SELECT count(distinct(statid)) FROM wc_session WHERE time >= ".$start." AND time < ".$end.";" );
	list($sess_yesterday)=$result->fetch_row();

	$start =  $this->start_date-3600*24*7;

	$result=$this->exec("SELECT count(distinct(statid)) FROM wc_session WHERE time >= ".$start." AND time < ".$this->end_date.";" );
	list($sess_unique7)=$result->fetch_row();

	$start =  $this->start_date-3600*24*30;

	$result=$this->exec("SELECT count(distinct(statid)) FROM wc_session WHERE time >= ".$start." AND time < ".$this->end_date.";" );
	list($sess_unique30)=$result->fetch_row();

	$result=$this->exec("SELECT count(distinct(statid)) FROM wc_session;" );
	list($sess_all)=$result->fetch_row();

	$table .= '
	<tr class=oddrow>
		<td><b>Сессии:</b></td>
		<td align="center">'.$sess_today.'</td>
		<td align="center">'.$sess_yesterday.'</td>
		<td align="center">'.$sess_unique7.'</td>
		<td align="center">'.$sess_unique30.'</td>
		<td align="center"><b>'.$sess_all.'</b></td>
	</tr>';
	$this->export_data[3] = array('Сессии:', $sess_today, $sess_yesterday, $sess_unique7, $sess_unique30, $sess_all);

	$this->html_data = '
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tablelist">
	<tr>
		<th>&nbsp;</th>
		<th width="20%">Сегодня</th>
		<th width="20%">Вчера</th>
		<th width="20%">За 7 дней</th>
		<th width="20%">За 30 дней</th>
		<th width="20%">Всего</th>
	</tr>'
	.$table.
	'</table>';
	$this->table_percent = array(25, 15, 15, 15, 15, 15);
	$this->table_align = array('L', 'C', 'C', 'C', 'C', 'C');
	$this->export_data[0] = array('', 'Сегодня', 'Вчера', 'За 7 дней', 'За 30 дней', 'Всего');
	ksort($this->export_data);
 }

 function rusdate($string, $skl = false) {

	$string = $string +0;
	$rusmount = array(1 => "Январь", 2 => "Февраль", 3 => "Март", 4 => "Апрель", 5 => "Май", 6 => "Июнь", 7 => "Июль", 8 => "Август", 9 => "Сентябрь", 10 => "Октябрь", 11 => "Ноябрь", 12 => "Декабрь");
	$rusmount_skl = array(1 => "Января", 2 => "Февраля", 3 => "Марта", 4 => "Апреля", 5 => "Мая", 6 => "Июня", 7 => "Июля", 8 => "Августа", 9 => "Сентября", 10 => "Октября", 11 => "Ноября", 12 => "Декабря");

	if ($skl)	$mouth = $rusmount_skl[$string];
	else	$mouth = $rusmount[$string];

 	return $mouth;
 }

 function admin_header($path) {

	$mainItems = array(
		1 => array('url' => '<a href="{URL}conf/filters/browser/index.php" class="mainmenu">Фильтры</a>'),
		2 => array('url' => '<a href="{URL}conf/export/index.php" class="mainmenu">Отчет по почте</a>'),
		3 => array('type' => 'admin', 'url' => '<a href="'.$path.'conf/index.php" class="mainmenu">Настройки</a>'));

	if ($this->menuItem == 1) {

		$menuhtml = array(
			1 => '<a href="'.$path.'conf/filters/browser/index.php" class="mainmenu">Браузеры</a>',
			2 => '<a href="'.$path.'conf/filters/search/index.php" class="mainmenu">Поиск</a>',
			3 => '<a href="'.$path.'conf/filters/catalog/index.php" class="mainmenu">Каталоги</a>',
			4 => '<a href="'.$path.'conf/filters/color/index.php" class="mainmenu">Цвета</a>',
			5 => '<a href="'.$path.'conf/filters/forum/index.php" class="mainmenu">Форумы</a>',
			6 => '<a href="'.$path.'conf/filters/screen/index.php" class="mainmenu">Экран</a>',
			7 => '<a href="'.$path.'conf/filters/os/index.php" class="mainmenu">ОС</a>');

	} elseif ($this->menuItem == 2) {

		$menuhtml = array(
			1 => '<a href="index.php" class="mainmenu">Список</a>');

	} elseif ($this->menuItem == 3) {
		$menuhtml = array(
			1 => '<a href="index.php" class="mainmenu">Список</a>');
	}

	echo '
	<table border="0" width="100%" height="100%" cellpadding="0" cellspacing="0" style="margin-right:0px;">
	<tr valign="center">
		<td height="100">&nbsp;</td>
		<td width="10">&nbsp;&nbsp;&nbsp;</td>
		<td height="100">

		<table class="title" cellspacing="0" align="center">
		<tr>
			<td class="title-start">&#187;</td>';

			foreach ($mainItems as $key => $value) {

				if ($this->menuItem == $key) {
					echo '<td class="title-off"><b>'.strip_tags($value['url']).'</b></td>';
				} else{
					echo '<td class="title-on">'.$value['url'].'</td>';
				}
			}

		echo '</tr>
		</table>

		</td>
		<td width="10">&nbsp;&nbsp;&nbsp;</td>
	</tr>
	<tr valign="top">
		<td height="100%" width="160">
		<div style="width:160px;">
		<ul class="mainmenu">';

		foreach ($menuhtml as $key => $value) {
			if ($this->menusubItem == $key) {
				echo "<li><nobr><b>".strip_tags($value)."</b></nobr></li>";
			} else {
				echo "<li><nobr>".$value."</nobr></li>";
			}
		}

		echo '
		</ul>
		</div></td>
		<td width="10">&nbsp;&nbsp;&nbsp;</td>
		<td width="100%" class="text">';
 }

 function admin_footer() {

	echo '<br>

		</td>
		<td width="10">&nbsp;&nbsp;&nbsp;</td>
	</tr>
	
	</table>
	';
 }

 function check_admin() {

//	if (!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_PW'] || $_SERVER['PHP_AUTH_PW'] != $this->gbl_config['password']) {
//	
//		header("WWW-Authenticate: Basic realm=\"Управление системой\"");
//		header("HTTP/1.0 401 Unauthorized");
//		$this->write_log("Ошибка доступа к управлению системой!", __line__, __file__);
//	}
//
//	$this->user_type = $this->moderators[$_SERVER['PHP_AUTH_USER']]['type'];
 }

 function get_date($name, $timestamp) {

	$m_array = array(1=>"Января", 2=>"Февраля", 3=>"Марта", 4=>"Апреля", 5=>"Мая", 6=>"Июня", 7=>"Июля", 8=>"Августа", 9=>"Сентября", 10=>"Октября", 11=>"Ноября", 12=>"Декабря");

 	list ($day, $month, $year) = split("-", $timestamp);
		 	
	$d = "<select name=".$name."d class=bginput>";
	for($i=1; $i<=31; $i++) {
		if ($i==$day)	{ $d .= "<option value='".$i."' selected>".$i."</option>"; }
		else		{ $d .= "<option value='".$i."'>".$i."</option>"; }
	}
	$d .= "</select> ";

	$m = "<select name=".$name."m class=bginput>";
	foreach ($m_array as $i => $v) {
		if ($i==$month)	{ $m .= "<option value='".$i."' selected>".$v."</option>"; }
		else		{ $m .= "<option value='".$i."'>".$v."</option>"; }
	}
	$m .= "</select> ";

	$y = "<select name=".$name."y class=bginput>";
	if ($year > 2000)	{ $pr1 = $year -2; $pr2 = $year +2; }
	else		{ $pr1 = 1950; $pr2 = 2000; }
			 
	for($i=$pr1; $i<=$pr2; $i++) {
		if ($i==$year)	{ $y .= "<option value='".$i."' selected>".$i."</option>"; }
		else		{ $y .= "<option value='".$i."'>".$i."</option>"; }
	}
	$y .= "</select> ";

	 return $d.$m.$y;
 }

 function get_type() {

	$menu_array = array("attendance" => "Хиты, Хосты, Сессии",
			"host" => "Хосты",
			"hit" => "Хиты",
			"session" => "Сессии",
			"referer" => "Страницы",
			"domain" => "Домены",
			"forum" => "Форумы",
			"catalog" => "Каталоги/рейтинги",
			"search" => "Поисковые системы",
			"search_word" => "Поисковые слова",
			"os" => "Операц. системы",
			"platform" => "Платформы",
			"browser" => "Браузеры",
			"browser_features" => "Хар-ки браузеров",
			"screen" => "Разрешение экрана",
			"color" => "Глубина цвета",
			"cookies" => "Cookies",
			"java" => "JavaScript",
			"ip" => "IP адреса",
			"country" => "Страны",
			"city" => "Города",
			"region" => "Регионы",
			"organization" => "Организации",
			"popularity" => "Популярность",
			"ways" => "Пути по сайту",
			"input" => "Точки входа",
			"output" => "Точки выхода",
			"depth" => "Глубина просмотра",
			"time" => "Время просмотра"
	);

	$d = "<select name=type class=bginput>";
	foreach ($menu_array as $key => $val) {
		if ($this->type == $key)	{ $d .= "<option value='".$key."' selected>".$val."</option>"; }
		else			{ $d .= "<option value='".$key."'>".$val."</option>"; }
	}
	$d .= "</select> ";

	return $d;
 }

 function percent($amount, $total) {

	 if ($total == 0) {
		 return 0;
	 }
	 $percent = round($amount * 1000 / $total, 0) / 10;

	 return $percent;
 }

 function change($key, $amount, $lastamount) {

	if (!$lastamount) $lastamount = 0;

	if ( !$key and !$lastamount ) {
		$change = '<b>&#151;</b>';
	} elseif ( $amount < $lastamount) {
		$zn = $this->percent_change($amount, $lastamount);
		$change = '<b>&darr;</b>&nbsp; '.$zn.' %';
	} elseif ( $amount > $lastamount ) {
    		$zn = $this->percent_change($amount, $lastamount);
    		if (!$lastamount) { $zn = 100; }
    		$change = '<b>&uarr;</b>&nbsp; '.$zn.' %';
	} else {
		$change = '<b>&#151;</b>';
	}

	return $change;
 }

 function percent_change($amount, $lastamount) {

	 if ($lastamount == 0) {
		 return 0;
	 }

	 $percent = ($amount - $lastamount) / $lastamount * 100;
	 $percent = round($percent, 0);

	 return $percent;
 }
 
 function format_time($min) {

	$time = date("H ч. i мин.", mktime (0,$min,0,01,01,2005));
	$time = str_replace("00 ч. ", "", $time);

	return $time;
 }

 function nullarray($array, $key) {

	ksort($array);

	if (!count($array)) {
		return array_fill(0, $key, 0);
	}elseif (count($array) < $key) {
		return array_pad($array, -$key, 0);
	} elseif (count($array) > $key) {
		return array_slice($array, 0, $key);
	} else {
		return $array;
	}
 }

 function nulldatearray($array, $datearray, $key) {

	foreach($datearray as $key => $val) {
		if (!empty($array[$val])) {
			$temp_array[] = $array[$val];
		} else {
			$temp_array[] = '0';
		}
	}
	return $temp_array;
 }

 function graph_top_result($array_input, $array_input_text, $pole) {

	$count = 5;
	$days = 5;

	$timestamp =  $this->start_date-3600*24*($days - 1);

	if (count($array_input)) {

		for ($i=1; $i <= $days; $i++) {
			$this->graph_data_array[0][] = date("d/m", $timestamp);
			$sql_array[] = "from_unixtime(time, '%d/%m')='".date("d/m", $timestamp)."'";
			$timestamp = $timestamp + 3600*24;
		}
	}

	$quety = implode(" OR ", $sql_array);

	foreach ($array_input as $key => $val) {

		if (!$count) break;
		$count--;

		$this->graph_legend_array[] = $array_input_text[$key];
		$this->graph_num_array [] = $key + 1;

		$result=$this->exec("SELECT count(*) as total, from_unixtime(time, '%d/%m') as oktime, ".$pole." FROM wc_main WHERE ".$pole." <> 'unknown' AND ".$pole." LIKE '".addslashes($val)."%' AND (".$quety.") GROUP BY oktime ORDER BY time ASC;");

		unset($temp_data_array);

		while($data=$result->fetch_row()) {
			$temp_data_array[$data[1]] = $data[0];
		}

		$temp_data_array = $this->nulldatearray($temp_data_array, $this->graph_data_array[0], $days);
		$temp_data_array = $this->nullarray($temp_data_array, $days);

		$this->graph_data_array[] = $temp_data_array;
	}

	return array($this->graph_data_array, $this->graph_legend_array, $this->graph_num_array);
 }

 function get_pages($total_amount, $url) {

	$page=empty($_GET['page']) ? $_POST['page'] : $_GET['page'];
	$page=is_numeric($page) ? $page : 1;
	$block=ceil($page / $this->gbl_config['per_page']);
	$num_pages=floor($total_amount / $this->gbl_config['item_per_page']);
	$this->start_item=($page - 1) * $this->gbl_config['item_per_page'] + 1;
	$end_item=$page * $this->gbl_config['item_per_page'];

	if ($total_amount == 0) {
		$this->start_item=1;
		$end_item=0;
	}

	if ($end_item > $total_amount) {
		$end_item=$total_amount;
	}

	if ($total_amount > $num_pages * $this->gbl_config['item_per_page']) {
		$num_pages++;
	}

	$page_amount = $this->start_item + $this->gbl_config['item_per_page'] -1;
	if ($page_amount > $total_amount) { $page_amount = $total_amount; }
//echo 'FUCK IT';
	
	//$this->sort_array[$sort_by] = $sort_ord == "desc" ? "&darr;" : "&uarr;";
	$this->sort_array['sort_by'] = $sort_ord == "desc" ? "&darr;" : "&uarr;";
	//elPrintR($this->sort_array); exit();
	
	if ($block > 1) {
		$pred = "<a href='index.php?begin=".date("d-m-Y", $this->start_date)."&end=".date("d-m-Y", $this->end_date)."&type=$this->type&type_gr=$this->type_gr&page=".(($block - 1) * $this->gbl_config['per_page'])."$url'>предыдущие ".$this->gbl_config['per_page']."</a>";
		$preddot = "<a href='index.php?begin=".date("d-m-Y", $this->start_date)."&end=".date("d-m-Y", $this->end_date)."&type=$this->type&type_gr=$this->type_gr&page=".(($block - 1) * $this->gbl_config['per_page'])."$url'>...</a> ";
	} else {
		$pred = "предыдущие";
		$preddot = "";
	}
	$allpage = '';
	for ($i=(($block - 1) * $this->gbl_config['per_page'] + 1); $i <= ($block * $this->gbl_config['per_page'] > $num_pages ? $num_pages : $block * $this->gbl_config['per_page']); $i++) {
		
		if ($page == $i) {
			$allpage .= "<span style='background-color: #E8E9EC; padding:2px 5px 5px 5px;'>$i</span>";
		} else {
			$allpage .= "<span style='padding:2px 5px 5px 5px;'><a href='index.php?begin=".date("d-m-Y", $this->start_date)."&end=".date("d-m-Y", $this->end_date)."&type=$this->type&type_gr=$this->type_gr&page=$i$url'>$i</a></span>";
		}
	}

	if (($block * $this->gbl_config['per_page'] + 1) < $num_pages) {
		$sled = "<a href='index.php?begin=".date("d-m-Y", $this->start_date)."&end=".date("d-m-Y", $this->end_date)."&type=$this->type&type_gr=$this->type_gr&page=".(($block * $this->gbl_config['per_page'] > $num_pages ? $num_pages : $block * $this->gbl_config['per_page']) + 1)."$url'>следующие ".$this->gbl_config['per_page']."</a>";
		$sleddot = " <a href='index.php?begin=".date("d-m-Y", $this->start_date)."&end=".date("d-m-Y", $this->end_date)."&type=$this->type&type_gr=$this->type_gr&page=".(($block * $this->gbl_config['per_page'] > $num_pages ? $num_pages : $block * $this->gbl_config['per_page']) + 1)."$url'>...</a>";
	} else {
		$sled = "следующие";
		$sleddot = "";
	}

	return "
	<br>
	<table border='0' cellspacing='0' cellpadding='3' width='100%' class='tablelist'>
	<tr class='evenrow' valign=top>
	<td align=left><span>Показано с $this->start_item по $page_amount (из $total_amount)</span></td>
	<td align=right><span>&larr;</span>&nbsp;$pred&nbsp;|&nbsp;$sled&nbsp;<span>&rarr;</span></a>&nbsp;<div style='margin-top:7px;'>$preddot $allpage $sleddot&nbsp;</div></td>
	</tr>
	</table>";
 }

 function string_to($str) {

	$t_str_up = "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$t_str_low = "абвгдеёжзийклмнопрстуфхцчшщъыьэюяabcdefghijklmnopqrstuvwxyz";

	$str = strtr($str, $t_str_up, $t_str_low);
	$str[0] = strtr($str[0], $t_str_low, $t_str_up);

	if(is_array($str)) { return implode("", $str); }

	return $str;
 }

 function export_date($period, $period_day) {

	switch ($period) {
		case "by_day":
			return mktime(0, 0, 0, date("m"), date("d")+1, date("Y"));
			break;
		case "by_month":
			return mktime(0, 0, 0, date("m")+1, $period_day, date("Y"));
			break;
		case "by_week":
			return strtotime("next ".$period_day);
			break;
	}
	return "";
 }

 function write_log($message, $line = "-", $file = "-") {

	echo "<li><span style='color: red;'>[Ошибка]</span> ".$message;
	die;
 }
 }
?>