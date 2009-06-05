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
 $core->init();
 $core->check_admin();

 $id=$_GET['id'] == "" ? $_POST['id'] : $_GET['id'];
 $action=$_GET['action'] == "" ? $_POST['action'] : $_GET['action'];

 if (trim($core->version_type) == "Demo") {
	$error=base64_encode("Это действие не доступно в Demo режиме!");
	header ("Location: index.php?error=$error");
	exit;
 }

 if (!is_numeric($id)) {
	 $error=base64_encode("Ошибка при передаче данных!");
	 header ("Location: index.php?error=$error");
	 exit;
 } else {
	 $core->exec("DELETE FROM wc_color WHERE id=$id;");

	 $status=base64_encode("Запись успешно удалена!");
	 header ("Location: index.php?status=$status");
	 exit;
 }
exit;
?>