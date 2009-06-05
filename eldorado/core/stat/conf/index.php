<?
 /*****************************************************************
 *
 *   модуль: Администрирование
 *
 ****************************************************************/
 //include("../includes/config.php");
 include("../class/sql.class.php");
 include("../class/core.class.php");

 // Ядро системы
 $core=new core();
 $core->menuItem = 3;
 $core->menusubItem = 1;
 $core->init();
 //$core->check_admin();
 
 $action=trim($_GET['action'] == "") ? trim($_POST['action']) : trim($_GET['action']);
 if(count($_POST) < 1) unset($_POST);

 if (isset($_POST) && $action == "edit") {

	reset ($_POST);

	while (list($key, $value)=each($_POST)) {
		$$key=htmlspecialchars(trim(substr($value, 0, 255)));
	}


	$core->exec("UPDATE wc_settings SET value='$graph' WHERE name='graph' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$ttffont' WHERE name='ttffont' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$linesh' WHERE name='linesh' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$default' WHERE name='default' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$default_gr' WHERE name='default_gr' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$gr_3d' WHERE name='gr_3d' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$gr_w' WHERE name='gr_w' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$gr_h' WHERE name='gr_h' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$password' WHERE name='password' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$access' WHERE name='access' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$url' WHERE name='url' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$per_page' WHERE name='per_page' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$item_per_page' WHERE name='item_per_page' LIMIT 1;");

	$status=base64_encode("Информация успешно изменена!");
	header ("Location: index.php?status=$status");
	exit;
 }

 $graph_array = array(
	"none_img" => "Невидимый",
	"counter"  => "Счетчик",
	"text"     => "Текстовый"
	);

 $default_array = array(
	"consolidated" => "Сводная статистика",
	"attendance" => "Хиты, Хосты, Сессии",
	"host" => "Хосты",
	"hit" => "Хиты",
	"session" => "Сессии",
	"referer" => "Ссылающиеся страницы",
	"domain" => "Ссылающиеся домены", 
	"forum" => "Ссылающиеся форумы",
	"catalog" => "Ссылающиеся каталоги и рейтинги",
	"search" => "Ссылающиеся поисковые системы",
	"os" => "Операционные системы",
	"platform" => "Платформы",
	"browser" => "Браузеры",
	"browser_features" => "Характеристики браузеров",
	"screen" => "Разрешение экрана",
	"color" => "Глубина цвета",
	"cookies" => "Использование Cookies",
	"java" => "Использование JavaScript",
	"ip" => "IP адреса",
	"country" => "Страны",
	"city" => "Города",
	"region" => "Регионы",
	"popularity" => "Популярность страниц",
	"ways" => "Пути по сайту",
	"input" => "Точки входа",
	"output" => "Точки выхода",
	"depth" => "Глубина просмотра сайта",
	"time" => "Время просмотра сайта"
	);

 $default_graph = array(
	"b1" => "Столбчатая (вар.1)",
	"b2" => "Столбчатая (вар.2)",
	"b3" => "Гистограмма",
	"l" => "Линейный график",
	"a" => "С областями"
	);


 $core->admin_header('../');
?>

<h3>Общие настройки</h3>

<?
 echo !empty($_GET['status']) != "" ? "<span class='green'>" . base64_decode($_GET['status']). "</span><br>" : "";
 echo !empty($_GET['error'])  != "" ? "<span class='red'>" . base64_decode($_GET['error']). "</span><br>" : "";
?>


<form action="index.php" method="post">
<input type="hidden" value="edit" name="action">

<table border="0" cellspacing="4" cellpadding="2" class="tableform">
<tr>
	<td align="right">URL системы:</td>
	<td><input type="text" name="url" size="40" value="{BASE_URL}"></td>
</tr>
<tr>
	<td align="right">Тип счетчика:</td>
	<td><select name="graph" class=bginput>
	<?
	foreach ($graph_array as $key => $val) {
		if ($core->gbl_config['graph'] == $key) {
			echo "<option value='".$key."' selected>".$val."</option>";
		} else {
			echo "<option value='".$key."'>".$val."</option>";
		}
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td align="right">Используемый шрифт графика:</td>
	<td><input type="text" name="ttffont" size="20" value="<?= $core->gbl_config['ttffont'] ?>"></td>
</tr>
<tr>
	<td align="right">Толщина линии графика:</td>
	<td><input type="text" name="linesh" size="10" value="<?= $core->gbl_config['linesh'] ?>"> пикс.</td>
</tr>
<tr>
	<td align="right">Основная страница статистики:</td>
	<td><select name="default" class=bginput>
	<?
	foreach ($default_array as $key => $val) {
		if ($core->gbl_config['default'] == $key) {
			echo "<option value='".$key."' selected>".$val."</option>";
		} else {
			echo "<option value='".$key."'>".$val."</option>";
		}
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td align="right">Основной график:</td>
	<td><select name="default_gr" class=bginput>
	<?
	foreach ($default_graph as $key => $val) {
		if ($core->gbl_config['default_gr'] == $key) {
			echo "<option value='".$key."' selected>".$val."</option>";
		} else {
			echo "<option value='".$key."'>".$val."</option>";
		}
	}
	?>
	</select>
	</td>
</tr>
<tr>
	<td align="right">Использовать 3D график?</td>
	<td><select name="gr_3d" class=bginput>
		<option value='1' selected>Да</option>
		<option value='0' <?= $core->gbl_config['gr_3d'] == "0" ? "selected" : "" ?>>Нет</option>
	</select>
	</td>
</tr>
<tr>
	<td align="right">Ширина графика:</td>
	<td><input type="text" name="gr_w" size="10" value="<?= $core->gbl_config['gr_w'] ?>"> пикс.</td>
</tr>
<tr>
	<td align="right">Высота графика:</td>
	<td><input type="text" name="gr_h" size="10" value="<?= $core->gbl_config['gr_h'] ?>"> пикс.</td>
</tr>
<tr>
	<td align="right">Показывать страниц:</td>
	<td><input type="text" name="per_page" size="10" value="<?= $core->gbl_config['per_page']; ?>"></td>
</tr>
<tr>
	<td align="right">Записей на страницу:</td>
	<td><input type="text" name="item_per_page" size="10" value="<?= $core->gbl_config['item_per_page']; ?>"></td>
</tr>
<tr>
	 <td>&nbsp;</td>
	 <td><br><input type="submit" value="Изменить" name="submit">&nbsp;&nbsp;<input type="button" value="Отмена" onClick="document.location.href='../index.php';"></td>
</tr>
</table>
</form>

<?

 switch ($core->gbl_config['graph']) {
	 case "none_img": 
		$code = "<noscript>\n<img src=\"{BASE_URL}counter.php\" border=\"0\" />\n</noscript>\n<script language=\"JavaScript\">\n<!--\nvar ref;\nvar col;\nvar scr;\nref = escape(document.referrer);\ncol = screen.colorDepth?screen.colorDepth: screen.pixelDepthescape;\nscr = screen.width+'x'+screen.height;\npg = escape(window.location.href);\nrnd = Math.random();\ndocument.write('<img src=\"{BASE_URL}counter.php?ref='+ref+'&col='+col+'&scr='+scr+'&pg='+pg+'&rnd='+rnd+'\" border=0>');\n-->\n</script>";
		$rows = 20;
	 break;
	 case "counter": 
		$code = "<noscript>\n<img src=\"{BASE_URL}counter.php\" border=\"0\" />\n</noscript>\n<script language=\"JavaScript\">\n<!--\nvar ref;\nvar col;\nvar scr;\nref = escape(document.referrer);\ncol = screen.colorDepth?screen.colorDepth: screen.pixelDepthescape;\nscr = screen.width+'x'+screen.height;\npg = escape(window.location.href);\nrnd = Math.random();\ndocument.write('<img src=\"{BASE_URL}counter.php?ref='+ref+'&col='+col+'&scr='+scr+'&pg='+pg+'&rnd='+rnd+'\" border=0>');\n-->\n</script>";
		$rows = 20;
	 break;
	 case "text":
	 	$code = "<noscript>\n<img src=\"{BASE_URL}counter.php\" border=\"0\" />\n</noscript>\n<script language=\"JavaScript\" src=\"{BASE_URL}counter.php\" />";
	 	$rows = 20;
	 break;
 }
?>

<h3>HTML код счетчика</h3>

<table border="0" cellspacing="2" cellpadding="2" class="tableform">
<tr>
	<td><textarea name="descr" cols="75" rows="<?= $rows ?>"><?= $code ?></textarea></td>
</tr>
</table>
</form>

<?
 $core->admin_footer();

 function get_version($myversion) {

 	return;
	$lazy_period = 60 * 60 * 24 * 3;

	$timefile = @file("../includes/version.txt");

	if(!preg_match("/Windows/i", php_uname()))	{ flock("../includes/version.txt",1); }
 	if(!preg_match("/Windows/i", php_uname()))	{ chmod("../includes/version.txt", 0644); }

	$oldtime = trim($timefile[0]);
	$version = trim($timefile[1]);
	$time=time();

	if (($time-$oldtime) > $lazy_period) {

		$file = file("http://www.webcount.ru/includes/version.txt"); 
		$version = trim($file[0]);

		$wf = @fopen("../includes/version.txt","w");
			fwrite($wf, $time."\n".$version);
		 	if(!preg_match("/Windows/i", php_uname()))	{ flock("../includes/version.txt", 3); }
		 	if(!preg_match("/Windows/i", php_uname()))	{ flock("../includes/version.txt", 3); }
		 	fclose($wf);
	}
	
	if ($version != $myversion) {
		return "$version<br><span class=red>Есть возможность обновления c сайта <a href=http://www.webcount.ru>http://www.webcount.ru</a></span>";
	}

	return $version;
 }
?>