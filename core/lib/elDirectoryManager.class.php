<?php

class elDirectoryManager {
	/**
	 * table name
	 *
	 * @var string
	 */
	var $_tb   = 'el_directories_list';
	
	/**
	 * Directories list
	 *
	 * @var array
	 **/
	var $_list = array();
	
	/**
	 * db object
	 *
	 * @var object
	 **/
	var $_db = null;
	
	/**
	 * Last error text
	 *
	 * @var string
	 **/
	var $error = '';
	
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elDirectoryManager() {
		$this->_db = & elSingleton::getObj('elDb');
		$this->_list = $this->_db->queryToArray('SELECT id, label FROM '.$this->_tb.' ORDER BY label', 'id', 'label');
	}
	
	/**
	 * Return directories list
	 *
	 * @return array
	 **/
	function getList() {
		return $this->_list;
	}
	
	/**
	 * Return true if directory with this id exists
	 *
	 * @param  string  $id
	 * @return bool
	 **/
	function directoryExists($id) {
		return isset($this->_list[$id]);
	}
	
	/**
	 * Create directory
	 *
	 * @param  string  $id
	 * @return bool
	 **/
	function create($id, $label) {
		if ($this->directoryExists($id)) {
			return true;
		} 
		
		$sql1 = "CREATE TABLE IF NOT EXISTS `el_directory_$id` (
				`id` int(11) NOT NULL auto_increment,
				`value` mediumtext,
				`sort_ndx` int(11) NOT NULL,
				PRIMARY KEY(`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		$sql2 = 'INSERT INTO '.$this->_tb.' (id, label) VALUES ("'.mysql_real_escape_string($id).'", "'.mysql_real_escape_string($label).'")';
		return $this->_db->query($sql1) && $this->_db->query($sql2);
	}

	/**
	 * Remove directory
	 *
	 * @param  string  $id
	 * @return bool
	 **/
	function delete($id) {
		if (!$this->directoryExists($id)) {
			return true;
		}
		if ($this->_db->query("DROP TABLE IF EXISTS `el_directory_$id`")) {
			$this->_db->query("DELETE FROM ".$this->_tb.' WHERE id="'.mysql_real_escape_string($id).'" LIMIT 1');
			$this->_db->optimizeTable($this->_tb);
			return true;
		}
		return false;
	}
	
	/**
	 * Return directory
	 *
	 * @param  string  $id
	 * @return array
	 **/
	function get($id, $list=true) {
		$dir = array();
		if ($this->directoryExists($id)) {
			$sql = 'SELECT id, value, sort_ndx FROM el_directory_'.$id.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), value';
			$ret = $list ? $this->_db->queryToArray($sql, 'id', 'value') : $this->_db->queryToArray($sql);
		}
		return $ret;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function getRecord($id, $recID)	{
		if ($this->directoryExists($id)) {
			$r = $this->_db->queryToArray('SELECT value FROM `el_directory_'.$id.'` WHERE id='.intval($recID), null, 'value');
			return isset($r[0]) ? $r[0] : '';
		}
		return '';
	}
	
	
	/**
	 * Remove all records from directory
	 *
	 * @param  string  $id
	 * @return void
	 **/
	function clean($id) {
		if ($this->directoryExists($id)) {
			$this->_db->query('TRUNCATE `el_directory_'.$id.'`');
			return true;
		}
	}
	
	/**
	 * Add record into directory
	 *
	 * @param  string  $id
	 * @param  string  $value
	 * @return bool
	 **/
	function addRecord($id, $value) {
		if ($this->directoryExists($id)) {
			return $this->_db->query('INSERT INTO `el_directory_'.$id.'` (value) VALUES ("'.mysql_real_escape_string($value).'")')
				? $this->_db->insertID()
				:false;
		}
	}
	
	/**
	 * Add records into directory
	 *
	 * @param  string  $id
	 * @param  array  $value
	 * @return int|bool
	 **/
	function addRecords($id, $value) {
		
		if (is_string($value)) {
			$v    = array();
			$_tmp = explode("\n", str_replace("\r", '', $value));
			foreach ($_tmp as $val) {
				$val = trim($val);
				if (!empty($val)) {
					$v[] = $val;
				}
			}
			$value = $v;
		}
		
		if ($this->directoryExists($id) && !empty($value) && is_array($value)) {
			$value = array_map('mysql_real_escape_string', $value);
			$value = '("'.implode('"), ("', $value).'")';
			
			if ($this->_db->query('INSERT INTO `el_directory_'.$id.'` (value) VALUES '.$value)) {
				return $this->_db->affectedRows();
			}
		}
	}
	
	/**
	 * Update record in directory
	 *
	 * @param  string  $id
	 * @param  int    $recID
	 * @param  array  $value
	 * @return void
	 **/
	function updateRecord($id, $recID, $value) {
		if ($this->directoryExists($id)) {
			return $this->_db->query('UPDATE `el_directory_'.$id.'` SET value="'.mysql_real_escape_string($value).'" WHERE id='.intval($recID));
		}
	}
	
	/**
	 * remove record from directory by id
	 *
	 * @param  string  $id
	 * @param  int     $recID
	 * @return void
	 **/
	function deleteRecord($id, $recID) {
		if ($this->directoryExists($id)) {
			if ($this->_db->query('DELETE FROM `el_directory_'.$id.'` WHERE id="'.intval($recID).'" LIMIT 1')) {
				$this->_db->optimizeTable('el_directory_'.$id);
				return true;
			}
		}
	}
	
	/**
	 * remove records from directory by id
	 *
	 * @param  string  $id
	 * @param  array   $recIDs
	 * @return void
	 **/
	function deleteRecords($id, $recIDs) {
		if ($this->directoryExists($id) && is_array($recIDs) && !empty($recIDs)) {
			$this->_db->query('DELETE FROM `el_directory_'.$id.'` WHERE id IN ('.implode(',', $recIDs).')');
			$this->_db->optimizeTable('el_directory_'.$id);
		}
	}
}

?>