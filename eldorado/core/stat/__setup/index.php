<html>
<head>
<title>Система установки</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link rel="stylesheet" type="text/css" href="../template/style.css">
</head>

<body>

<?
error_reporting  (E_ERROR | E_PARSE);
set_time_limit(0);
umask(000);

/****************************************************************/
$min_php = 4.3;			// Минимальная версия PHP
$sql_db = "base";			// Название базы данных
$sql_username = "root";		// Пользователь базы данных
$sql_serverip = "localhost";		// Сервер базы данных
/****************************************************************/

$error = false;

 // Первый шаг
 if ($_GET['step'] == 1) {

	if ($_GET['license'] == "false") {
		print "<p class=red>Вы должны согласиться с лицензией перед тем, как использовать систему.</p>"; flush();
		exit;
	}

	?>
	<script language="JavaScript">
	<!--
	function check_form() {

		if (document.all.sql_db.value=="") {
			alert("Укажите название базы данных!");
			return false;
		} else if (document.all.sql_username.value=="") {
			alert("Укажите имя пользователя базы данных!");
			return false;
		} else if (document.all.sql_serverip.value=="") {
			alert("Укажите сервер базы данных!");
			return false;
		} else {
			return true;
		}
	}
	-->
	</script>

	<h3>База данных (Шаг 1 из 2):</h3>
	<form action='index.php' method='GET'>
	<input type='hidden' name='step' value='2'>
	
	<p>Здесь необходимо ввести настройки базы данных для работы системы.</p>
	<p>ВНИМАНИЕ: Заполняйте все поля. Иначе система может не работать!</p>
	
	<table border="0" cellspacing="2" cellpadding="2" class="tableform">
	<tr>
		<td>Название базы данных:<br><small>(она должна быть создана к этому времени)</small></td>
		<td><input type='text' name='sql_db' value='<?= $sql_db; ?>'></td>
	</tr>
	<tr>
		<td>Пользователь базы данных:</td>
		<td><input type='text' name='sql_username' value='<?= $sql_username; ?>'></td>
	</tr>	
	<tr>
		<td>Пароль базы данных:</td>
		<td><input type='text' name='sql_password' value=''></td>
	</tr>
	<tr>
		<td>Сервер базы данных:</td>
		<td><input type='text' name='sql_serverip' value='<?= $sql_serverip; ?>'></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input type='checkbox' name='create' value='true'> Создать базу данных</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
		<td><br><p><input type='submit' value='Продолжить' onClick='return check_form();'></p></td>
	</tr>
	</table>
	</form>
	<?

 // Завершение установки
 } elseif ($_GET['step'] == 2) {

	print "<h3>Завершение установки</h3>"; flush();

	$setup_file = "
	\$gbl_config['sql_db'] = \"".$_GET['sql_db']."\";
	\$gbl_config['sql_username'] = \"".$_GET['sql_username']."\";
	\$gbl_config['sql_password'] = \"".$_GET['sql_password']."\";
	\$gbl_config['sql_serverip'] = \"".$_GET['sql_serverip']."\";
	";

	$config_status = 1;
	$setup_file = ereg_replace("\t",'',$setup_file);
	$fw = @fopen("../includes/config.php", "w");
	if (!@fwrite($fw, "<?".$setup_file."?>")) {
		$config_status = 0;
	}
	@fclose($fw);

	print "<ul>"; flush();
	print "<li>Сохранение настроек в файл 'includes/config.php' - ".status($config_status); flush();
	print "</ul>"; flush();

	print "<ul>"; flush();

	$connection = mysql_connect( $_GET['sql_serverip'], $_GET['sql_username'], $_GET['sql_password']);

	if ($_GET['create']) {
		print "<li>Создание базы данных..."; flush();
		mysql_query("CREATE DATABASE ".$_GET["sql_db"], $connection);
	}

	if (!mysql_select_db( $_GET['sql_db'])) {
		print  "<li>Невозможно соединиться с базой данных - ".status(0); flush();
	} else {
		print "<li>Создание таблиц..."; flush();
		$warning = query_upload();

		if ($warning) { print  "<li>".$warning." - ".status(0); $error = true; flush(); }
	}
	print "</ul>"; flush();
	mysql_close($connection);

	if ($error) {
		print "<p class=red>Для продолжения установки Вы должны устранить все ошибки.</p>"; flush();

		if (!$config_status) {
			print "<p class=red>Если файл ./includes/config.php не создан, то возможно на его запись нет доступа (Поставте права на папку 'includes': 777).</p>"; flush();
		}


	} else {
		print "<h3>Установка завершена!</h3><p>ВНИМАНИЕ: Для исключения неправомерных действий, удалите файлы установки из папки '_setup'.</p>"; flush();
	}

 // Начало установки
 } else {
  	$handle = @fopen("../_doc/License.txt", "r");
	$license = @fread($handle, @filesize("../_doc/License.txt"));
	@fclose($handle);

	if ($license) {
		print "<h3>Лицензия</h3><textarea cols=65 rows=15 style='width:100%'>".$license."</textarea>"; flush();
		print "<p><input type='checkbox' name='license' value='true'> Я согласен сусловиями лицензии</p>"; flush();
	}
 
	print "<h3>Проверка системы:</h3>"; flush();
	print "<ul>"; flush();
	print "<li>Проверка версии PHP - ".check_version(phpversion(), $min_php); flush();
	print "<li>Проверка модуля MySQL - ".status(extension_loaded('mysql')); flush();
	print "<li>Проверка версии Apache - ".check_version(apache_get_version(), "1.3"); flush();
	print "<li>Проверка модуля GD - ".status(extension_loaded('gd')); flush();
	print "<li>Проверка модуля ICONV - ".status(extension_loaded('iconv')); flush();
	print "</ul>"; flush();

	if ($error) {
		print "<p class=red>Для продолжения установки Вы должны устранить все ошибки.</p>"; flush();
		$button_style = "disabled";
	}

	print "<p><input type='submit' value='Начать установку' ".$button_style." onClick=\"window.location.href='index.php?step=1&license='+document.all.license.checked;\"></p>"; flush();
 }

/*============================================================*/
function query_upload() {

	if (!file_exists("./base.sql")) {
    		return "Файл 'base.sql' не найден.";
	}

	$fp = @fopen("./base.sql", "rb");

	$command = "";
	$counter = 0;

	print "<p>Ждите ...</p>"; flush();

	while (!feof($fp)) {
		$c = chop(fgets($fp, 1500000));
		$c = ereg_replace("^[ \t]*#.*", "", $c);
		$command .= $c;

		if (ereg(";$", $command)) {
			$command = ereg_replace(";$", "", $command);

			if (ereg("CREATE TABLE ", $command)) {
				$table_name = ereg_replace(" .*$", "", eregi_replace("^.*CREATE TABLE ", "", $command));

				print "<li>Создание таблицы: [$table_name]  - "; flush();

				mysql_query($command);

				$myerr = mysql_error();
				if (!empty($myerr)) {
					break;
				} else {
					print status(true); flush();
				}
			} else {
				mysql_query($command);

				$myerr = mysql_error();
				if (!empty($myerr)) {
					break;
				} else {
					$counter++;
					if (!($counter % 20)) { print ""; flush(); }
				}
			}

			$command = "";
		}
	}

	@fclose($fp);

	return $myerr;
}

/*============================================================*/
function status($var) {

	GLOBAL $error;

	if ($var)	{ return "<span class=green>[Ok]</span>"; }
	else	{ $error = true; return "<span class=red>[Ошибка]</span>"; }
}

/*============================================================*/
function check_version($my, $ok) {

	GLOBAL $error;

	if ($my >= $ok)	{ return "<span class=green>[Ok - ".$my."]</span>"; }
	else		{ $error = true; return "<span class=red>[Ошибка - ".$my."]</span>"; }
}
?>

</body>
</html>