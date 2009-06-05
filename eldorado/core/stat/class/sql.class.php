<?
 /*****************************************************************
 * Класс работы с базой данных WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/

 class sql {

 function ___makecheck___() {

	 if (!function_exists('mysql_connect')) {
		 return false;
	 }
	 return true;
 }

 function connect($host, $user, $pass, $dbname) {
	 $this->conntype=0;
	 if (!$this->connection=mysql_connect($host, $user, $pass)) {
		 return false;
	 }
	 
	 $this->host=$host;
	 $this->user=$user;
	 $this->pass=base64_encode($pass);
	 if (!mysql_select_db($dbname, $this->connection)) {
		 return false;
	 }
	 $this->dbname=$dbname;
	 return true;
 }

 function pconnect($host, $user, $pass, $dbname) {

	 $this->conntype=1;

	 if (!$this->connection=@mysql_pconnect($host, $user, $pass)) {
		 return false;
	 }
	 $this->host=$host;
	 $this->user=$user;
	 $this->pass=base64_encode($pass);
	 if (!@mysql_select_db($dbname, $this->connection)) {
		 return false;
	 }
	 $this->dbname=$dbname;
	 return true;
 }

 function exec($query) {

	 if (!$result=@mysql_query($query, $this->connection)) {
		 $this->write_log("Ошибка запроса к базе!", __line__, __file__, "");
		 return false;
	 }
	 $return=new ___result___($result);
	 return $return;
 }

 function disconnect() {

	 @mysql_close ($this->connection);
 }

 function __sleep() {

	 $this->disconnect();
	 return array('host', 'user', 'pass', 'dbname');
 }

 function __wakeup() { $this->conntype ? $this->pconnect($this->host, $this->user, base64_decode($this->pass), $this->dbname) : $this->connect($this->host, $this->user, base64_decode($this->pass), $this->dbname); }
 }

 class ___result___ {

	 var $result;
	 var $rownum;

 function ___result___($result) {

	 $this->result=$result;
 }

 function fetch_array() {

	 return @mysql_fetch_array($this->result, MYSQL_ASSOC);
	 return false;
 }

 function fetch_object() {

	 return @mysql_fetch_object($this->result);
	 return false;
 }

 function fetch_row() {

	 return @mysql_fetch_row($this->result);
	 return false;
 }

 function free_result() {

	 @mysql_free_result ($this->result);
	 return true;
 }

 function num_rows() {

	 return @mysql_num_rows($this->result);
 }

 function num_fields() {

	 return @mysql_num_fields($this->result);
 }

 function field_name($i) {

	 return @mysql_field_name($this->result, $i);
 }
 }
?>