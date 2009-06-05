<?
 /*****************************************************************
 *
 *   модуль: Администрирование
 *
 ****************************************************************/

 include("../../../includes/config.php");
 include("../../../class/sql.class.php");
 include("../../../class/core.class.php");

 // Ядро системы
 $core=new core();
 $core->menuItem = 1;
 $core->menusubItem = 5;
 $core->init();
 $core->check_admin();

 $action=trim($_GET['action'] == "") ? trim($_POST['action']) : trim($_GET['action']);
 $id=trim($_GET['id'] == "") ? trim($_POST['id']) : trim($_GET['id']);
 if(count($_POST) < 1) unset($_POST);
 
 $error="";

 function local_check() {

	 global $core, $action, $gbl_config;

	 $error="";

	 if (!eregi("^[А-Яа-яA-Za-z0-9() -<=>]+$", $_POST['text'])) {
		 $error.="Введите название.<br>\n";
	 }

	 if (!eregi("^[А-Яа-яA-Za-z0-9() -<=>]+$", $_POST['name'])) {
		 $error.="Введите значение.<br>\n";
	 }

	 if (!eregi("^[А-Яа-яA-Za-z0-9() -<=>]+$", $_POST['val'])) {
		 $error.="Введите параметр.<br>\n";
	 }

	 if (trim($core->version_type) == "Demo") {
		$error.="Это действие не доступно в Demo режиме!";
	 }

	 return $error;
 }

 if (isset($_POST) && $action == "add") {

	 reset ($_POST);

	 while (list($key, $value)=each($_POST)) {
		 $$key=htmlspecialchars(trim(substr($value, 0, 255)));
	 }

	 $error=local_check();

	 if ($error == "") {

		 $core->exec("INSERT INTO wc_forum (name, value, text) VALUES ('$name', '$val', '$text');");

		 $status=base64_encode("Добавление выполнено!");
		 header("location: index.php?status=$status");
		 exit;
	 }
	 $header="Добавление";

 } elseif (isset($_POST) && $action == "edit") {

	 if (!is_numeric($id)) {
		 $error=base64_encode("Ошибка при передаче данных!");
		 header ("Location: index.php?error=$error");
		 exit;
	 }

	 reset ($_POST);

	 while (list($key, $value)=each($_POST)) {
		 $$key=htmlspecialchars(trim(substr($value, 0, 255)));
	 }

	 $error=local_check();

	 if ($error == "") {

		 $core->exec("UPDATE wc_forum SET name='$name', value='$val', text='$text' WHERE id=$id LIMIT 1;");

		 $status=base64_encode("Информация успешно изменена!");
		 header ("Location: index.php?status=$status");
		 exit;
	 }

	 $header="Редактирование";

 } elseif ($action == "add") {

	 $header="Добавление";

 } elseif ($action == "edit") {

	 if (!is_numeric($id)) {
		 $error=base64_encode("Ошибка при передаче данных!");
		 header ("Location: index.php?status=$status");
		 exit;
	 }

	 $res=$core->exec("SELECT * FROM wc_forum WHERE id=$id LIMIT 1;");
	 $row=$res->fetch_object();
	 $name=$row->name;
	 $value=$row->value;
	 $text=$row->text;
	 $header="Редактирование";
 }

 $core->admin_header('../../../');
?>

<h3><?= $header ?></h3>

<p>[ <a href=index.php>Список</a> ]</p>

<?
 echo $error != "" ? "<p class='red'>".$error."</p>" : "";
?>

<form action="add.php" method="post">
<input type="hidden" value="<?= $action ?>" name="action">
<input type="hidden" value="<?= $id ?>" name="id">

<table border="0" cellspacing="4" cellpadding="2" class="tableform">
<? if ($action == "edit") { ?>
<tr>
	<td align="right">ID:</td>
	<td><b><?= $id ?></b></td>
</tr>
<? } ?>
<tr>
	<td align="right">Название:</td>
	<td><input type="text" name="text" value="<?= $text ?>" size="100" style="width:400px;">&nbsp;<sup>*</sup></td>
</tr>
<tr>
	<td align="right">Значение:</td>
	<td><input type="text" name="name" value="<?= $name ?>" size="50" style="width:200px;">&nbsp;<sup>*</sup></td>
</tr>
<tr>
	<td align="right">Параметр:</td>
	<td><input type="text" name="val" value="<?= $value ?>" size="50" style="width:200px;">&nbsp;<sup>*</sup></td>
</tr>
<tr>
	 <td>&nbsp;</td>
	 <td><br><input type="submit" value="<?= ($action == 'edit' ? 'Изменить' : 'Добавить') ?>" name="submit">&nbsp;&nbsp;<input type="button" value="Отмена" onClick="document.location.href='index.php';"></td>
</tr>
</table>
</form>

<?
 $core->admin_footer();
?>