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
 $core->menusubItem = 2;
 $core->init();
 $core->check_admin();
 
 $core->admin_header('../../../');
?>

<h3>Поисковые системы</h3>

<p>[ <a href=add.php?action=add>Добавить</a> ]</p>

<?
 echo $_GET['status'] != "" ? "<p class='green'>" . base64_decode($_GET['status']). "</p>" : "";
 echo $_GET['error'] != "" ? "<p class='red'>" . base64_decode($_GET['error']). "</p>" : "";
?>

<p>Фильтр нужен для упорядочивания по группам из основной массы статистических данных о посетителях.</p>

<table border="0" cellspacing="0" cellpadding="3" width="100%" class="tablelist">
<tr>
	<th width="10%" nowrap>ID</th>
	<th width="80%" nowrap>Название</th>
	<th nowrap>Операции</td>
</tr>

<?
 $res=$core->exec("SELECT * FROM wc_search ORDER BY id asc;");

 $flag=true;

 while ($row=$res->fetch_object()) {

	$class = $flag ? "oddrow" : "evenrow";
	$flag=!$flag;
?>

<tr class="<?= $class ?>">
	<td align=center><?= $row->id ?></td>
	<td><?= $row->text ?></td>
	<td align=center><a href="add.php?action=edit&id=<?= $row->id ?>" class="menu">ред-ть</a>&nbsp;|&nbsp;<a href="#" onClick="del('<?=$row->id?>','<?=$row->name?>'); return false;" class="menu">x</a></td>
</tr>

<?
 }
?>

</table>

<br>

<?
 $core->admin_footer();
?>

<script language="JavaScript1.2">
<!--
function del(id, name) {
	if (window.confirm("Вы действительно хотите удалить "+name+" (ID "+id+") ?")) window.location = "del.php?id="+id;
}
//-->
</script>