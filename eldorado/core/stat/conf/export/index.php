<?
 /*****************************************************************
 *
 *   модуль: Администрирование
 *
 ****************************************************************/

 //include("../../includes/config.php");
 include("../../class/sql.class.php");
 include("../../class/core.class.php");

 // Ядро системы
 $core=new core();
 $core->menuItem = 2;
 $core->menusubItem = 1;
 $core->init();
 //$core->check_admin();

 $action=trim($_GET['action'] == "") ? trim($_POST['action']) : trim($_GET['action']);
 if(count($_POST) < 1) unset($_POST);

 if (isset($_POST) && $action == "edit") {

	$statistics_array = $_POST['statistics'];

	reset ($_POST);

	while (list($key, $value)=each($_POST)) {
		$$key=htmlspecialchars(trim(substr($value, 0, 255)));
	}

	if (trim($core->version_type) == "Demo") {
		$error=base64_encode("Это действие не доступно в Demo режиме!");
		header ("Location: index.php?error=$error");
		exit;
	}

	$core->exec("UPDATE wc_settings SET value='$period' WHERE name='export_period' LIMIT 1;");
	if ($period == "by_month") {
		$period_day = $month;
	} elseif ($period == "by_week") {
		$period_day = $week;
	} else {
		$period_day = "";
	}

	$core->exec("UPDATE wc_settings SET value='$period_day' WHERE name='export_period_day' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$email' WHERE name='export_email' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$subject' WHERE name='export_subject' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='".implode(",", $statistics_array)."' WHERE name='export_statistics' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='$format' WHERE name='export_format' LIMIT 1;");
	$core->exec("UPDATE wc_settings SET value='".$core->export_date($period, $period_day)."' WHERE name='export_date' LIMIT 1;");

	$status=base64_encode("Информация успешно изменена!");
	header ("Location: index.php?status=$status");
	exit;
 }

 $stat_array = array(
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

 $core->admin_header('../../');
?>

<h3>Отчет по почте</h3>

<?
 echo $_GET['status'] != "" ? "<span class='green'>" . base64_decode($_GET['status']). "</span><br>" : "";
 echo $_GET['error'] != "" ? "<span class='red'>" . base64_decode($_GET['error']). "</span><br>" : "";
?>

<p>Система будем периодически высылать нужный отчет на электронную почту.<br>Следующий запуск: <?= ($core->gbl_config['export_date'] != "" ? date("d.m.Y H:i:s", $core->gbl_config['export_date']) : "никогда"); ?></p>

<form action="index.php" method="post" name="main">
<input type="hidden" value="edit" name="action">

<table border="0" cellspacing="4" cellpadding="2" class="tableform">
<tr>
	<td align="right" rowspan="4">Периодичность:</td>
	<td><input type="radio" name="period" value="none" <?= ($core->gbl_config['export_period'] == "none" ? "checked" : ""); ?>>Никогда</td>
</tr>
<tr>
	<td><input type="radio" name="period" value="by_day" <?= ($core->gbl_config['export_period'] == "by_day" ? "checked" : ""); ?>>Каждый день</td>
</tr>
<tr>
	<td><input type="radio" name="period" value="by_week" <?= ($core->gbl_config['export_period'] == "by_week" ? "checked" : ""); ?>>По  
		<select name="week" class="bginput">
		<option value="Monday" <?= ($core->gbl_config['export_period_day'] == "Monday" ? "selected" : ""); ?>>понедельникам</option>
		<option value="Tuesday" <?= ($core->gbl_config['export_period_day'] == "Tuesday" ? "selected" : ""); ?>>вторникам</option>
		<option value="Wednesday" <?= ($core->gbl_config['export_period_day'] == "Wednesday" ? "selected" : ""); ?>>средам</option>
		<option value="Thursday" <?= ($core->gbl_config['export_period_day'] == "Thursday" ? "selected" : ""); ?>>четвергам</option>
		<option value="Friday" <?= ($core->gbl_config['export_period_day'] == "Friday" ? "selected" : ""); ?>>пятницам</option>
		<option value="Saturday" <?= ($core->gbl_config['export_period_day'] == "Saturday" ? "selected" : ""); ?>>субботам</option>
		<option value="Sunday" <?= ($core->gbl_config['export_period_day'] == "Sunday" ? "selected" : ""); ?>>воскресеньем</option>
	</select></td>
</tr>
<tr>
	<td><input type="radio" name="period" value="by_month" <?= ($core->gbl_config['export_period'] == "by_month" ? "checked" : ""); ?>>Каждый месяц 
		<select name="month" class="bginput">
		<?
	 	for($i=1; $i<=30; $i++) {
			echo "<option value='".$i."' ".($core->gbl_config['export_period_day'] == $i ? "selected" : "").">".$i."</option>";
		}
		?></select> числа</td>
</tr>
<tr>
	<td colspan="2"><br></td>
</tr>
<tr>
	<td align="right">На E-Mail:</td>
	<td><input value="<?= $core->gbl_config['export_email']; ?>" type="text" name="email" size="20"></td>
</tr>
<tr>
	<td align="right">Тема письма:</td>
	<td><input value="<?= $core->gbl_config['export_subject']; ?>" type="text" name="subject" size="50"></td>
</tr>
<tr>
	<td align="right">Отчет в виде:</td>
	<td><select name="format" class="bginput">
		<option value="excel" <?= ($core->gbl_config['export_format'] == "excel" ? "selected" : ""); ?>>В виде Excel файла</option>
		<option value="word" <?= ($core->gbl_config['export_format'] == "word" ? "selected" : ""); ?>>В виде Word файла</option>
		<option value="pdf" <?= ($core->gbl_config['export_format'] == "pdf" ? "selected" : ""); ?>>В виде PDF файла</option>
	</select></td>
</tr>
<tr>
	<td colspan="2" align="right"><br><p><small>[ <a href="" OnClick="return select_allform(true);"><small>Выделить все</small></a>&nbsp;|&nbsp;<a href="" OnClick="return select_allform(false);"><small>Снять выделение</small></a> ]</p></td>
</tr>
<tr>
	<td align="right">Содержимое отчета:</td>
	<td>
	<?
	$temp_statistics_array  = explode(",", $core->gbl_config['export_statistics']);
	foreach($temp_statistics_array as $key => $val) { $statistics_array[$val] = true; }

	foreach ($stat_array as $key => $val) {
		echo "<input type='checkbox' name='statistics[]' value='".$key."' ".($statistics_array[$key] != false ? "checked" : "")."> ".$val."<br>";
	}
	?>
	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><br><input type="submit" value="Изменить" name="submit">&nbsp;&nbsp;<input type="button" value="Отмена" onClick="document.location.href='../index.php';"></td>
</tr>
</table>
</form>

<script language="JavaScript1.2">
<!--
function select_allform(what) {

	var k = 0;

	while(main.elements[k]) {
		if(main.elements[k].name == 'statistics[]') {
			if (what == true) {
				main.elements[k].checked = true;
			} else {
				main.elements[k].checked = false;
			}
		}
		k++;
	}
	return false;
}
//-->
</script>

<?
 $core->admin_footer();
?>