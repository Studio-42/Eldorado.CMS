<?
 /*****************************************************************
 * Класс вывода счетчика WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/
 class counter extends sql {
 
 function init() {

	global $gbl_config;

 	$this->start_date = mktime(0,0,0,date("m", time()),date("j", time()),date("Y", time()));
	$this->end_date   = mktime(23,59,59,date("m", time()),date("j", time()),date("Y", time()));

	if(!$this->connect($gbl_config['sql_serverip'], $gbl_config['sql_username'], $gbl_config['sql_password'], $gbl_config['sql_db'])) { die("<li><span style='color: #E74B4B;'>[Ошибка]</span> Ошибка соединения с базой!"); }

	$result=$this->exec("SELECT name, value FROM wc_settings;");
	while ($data=$result->fetch_row()) {
		 $this->gbl_config[$data[0]]=$data[1];
	}

	if ($this->gbl_config['graph'] == "text" || !function_exists('imagecreatefrompng')) 
	{

		$result=$this->exec("SELECT count(*) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";" );
		list($hit_today)=$result->fetch_row();

		$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";" );
		list($host_today)=$result->fetch_row();

		$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main;");
		list($host_all)=$result->fetch_row();

		echo "document.write('<table class=\"text_counter\">');";
		echo "document.write('<tr><td bgcolor=\"#EEEEEE\" align=\"center\" colspan=\"2\">Статистика</td></tr>');";
		echo "document.write('<tr><td>Хитов:</td><td align=\"right\">$hit_today</td></tr>');";
		echo "document.write('<tr><td>Хостов: </td><td align=\"right\">$host_today</td></tr>');";
		echo "document.write('<tr><td>Всего: </td><td align=\"right\">$host_all</td></tr>');";
		echo "document.write('</table>');";
//		echo "<table>";
//		echo "<tr><td bgcolor=#EEEEEE align=center colspan=2>Статистика</td></tr>";
//		echo "<tr><td>Хитов:</td><td align=right>$hit_today</td></tr>";
//		echo "<tr><td>Хостов: </td><td align=right>$host_today</td></tr>";
//		echo "<tr><td>Всего: </td><td align=right>$host_all</td></tr>";
//		echo "<tr><td colspan=2><a href=index.php>Подробнее...</a></td></tr>";

	} elseif ($this->gbl_config['graph'] == "counter") {

		$result=$this->exec("SELECT count(*) FROM wc_session WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";" );
		list($hit_today)=$result->fetch_row();

		$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main WHERE time >= ".$this->start_date." AND time < ".$this->end_date.";" );
		list($host_today)=$result->fetch_row();

		$result=$this->exec("SELECT count(distinct(ip)) FROM wc_main;");
		list($host_all)=$result->fetch_row();

		$im = $this->newimage(1,1,'./template/counter.png');

		$color['wite']	= ImageColorAllocate($im, 255,255,255);
		$color['ser']	= ImageColorAllocate($im, 235,235,235);
		$color['black']	= ImageColorAllocate($im, 0,0,0);
		$color['green']	= ImageColorAllocate($im, 0,153,0);
		$color['red']	= ImageColorAllocate($im, 170,0,0);

		ImageString($im, 1, 88-strlen($hit_today)*5-2, 3, $hit_today, $color['black']);
		ImageString($im, 1, 88-strlen($host_today)*5-2, 12, $host_today, $color['black']);
		ImageString($im, 1, 88-strlen($host_all)*5-2, 21, $host_all, $color['black']);

		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header("Content-type: image/png");
		ImagePNG($im);
		ImageDestroy($im);

	} elseif ($this->gbl_config['graph'] == "none_img") {

		$im = $this->newimage(1,1,'');
  
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header("Content-type: image/png");
		ImagePNG($im);
		ImageDestroy($im);

	} elseif ($this->gbl_config['graph'] == "none_java") {

		echo "document.write('');";
	}
 }

 function newimage($w, $h, $png="") {

	global $color;

	if ($png) {
		$im = ImageCreateFromPNG($png);
	} else {
		$im = ImageCreate($w, $h);
	}

	$color['wite']	= ImageColorAllocate($im, 255,255,255);
	$color['ser']	= ImageColorAllocate($im, 235,235,235);
	$color['black']	= ImageColorAllocate($im, 0,0,0);
	$color['green']	= ImageColorAllocate($im, 0,153,0);
	$color['red']	= ImageColorAllocate($im, 170,0,0);

	return $im;
 }
 
 function maxarray($array) {

	$array_sort = $array;
	rsort($array_sort);
	$t_max=array_shift($array_sort);

	$maxcount=1;
	while($t_max>=$maxcount) { $maxcount = $maxcount+10; }
	$maxcount --;

	return array($maxcount,$t_max);
 }
 }
?>