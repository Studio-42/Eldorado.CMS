<?php
/**
 * System direcrories info/manipulations
 *
 * @package core
 **/
class elDirectoryManager {
	/**
	 * table name
	 *
	 * @var string
	 */
	var $_tb = 'el_directories_list';
	/**
	 * db object
	 *
	 * @var object
	 **/
	var $_db = null;
	
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elDirectoryManager() {
		$this->_db = & elSingleton::getObj('elDb');
	}
	
	/**
	 * Return directories list
	 *
	 * @return array
	 **/
	function getList() {
		return $this->_db->queryToArray('SELECT id, label FROM '.$this->_tb.' ORDER BY label', 'id', 'label');;
	}
	
	/**
	 * return detailed directories list
	 *
	 * @return array
	 **/
	function getDetails() {
		$dir  = & new elSysDirectory();
		$dirs = $dir->collection(false, true);
		$sql  = 'SELECT COUNT(id) AS num FROM el_directory_%s';
		foreach ($dirs as $id=>$d) {
			$this->_db->query(sprintf($sql, $id));
			$r = $this->_db->nextRecord();
			$dirs[$id]['records'] = $r['num'];
			if ($d['master_id'] && isset($dirs[$d['master_id']])) {
				$dirs[$id]['master']       = $dirs[$d['master_id']]['label'];
				$dirs[$id]['master_value'] = $this->getRecord($d['master_id'], $d['master_key']);
			}
		}
		return $dirs;
	}
	
	/**
	 * Return true if directory with this id exists
	 *
	 * @param  string  $id
	 * @return bool
	 **/
	function directoryExists($id) {
		$this->_db->query('SELECT id FROM '.$this->_tb.' WHERE id="'.mysql_real_escape_string($id).'"');
		return $this->_db->numRows() > 0;
	}
	
	/**
	 * return directory object
	 *
	 * @param  string $id
	 * @return elSysDirectory
	 **/
	function get($id='') {
		$dir = & new elSysDirectory();
		$dir->idAttr($id);
		$dir->fetch();
		return $dir;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function getRecord($dirID, $recID, $default=true) {
		$dir = $this->get($dirID);
		return $dir->record($recID, $default);
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
		} elseif ($id && $label) {
			$dir = & new elSysDirectory();
			$dir->idAttr($id);
			$dir->attr('label', $label);
			return $dir->save();
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function findSlave($masterID, $masterKey) {
		$sql = 'SELECT id, label, master_id, master_key FROM %s WHERE master_id="%s" AND master_key=%d LIMIT 1';
		$sql = sprintf($sql, $this->_tb, $masterID, $masterKey);
		$this->_db->query($sql);
		if ($this->_db->numRows()) {
			return new elSysDirectory($this->_db->nextRecord());
		}
	}

} // END class 

/**
 * Directory
 *
 * @package core
 **/
class elSysDirectory extends elDataMapping {
	var $_tb       = 'el_directories_list';
	var $ID        = '';
	var $label     = '';
	var $masterID  = '';
	var $masterKey = 0;
	var $_objName  = 'System directory';
	
	/**
	 * create/update directory
	 *
	 * @return bool
	 **/
	function save() {
		$attrs = $this->_attrsForSave();
		if (!$attrs['master_id']) {
			$attrs['master_key'] = 0;
		}
		
		
		$dm  = &elSingleton::getObj('elDirectoryManager');
		$db  = $this->_db();
		$tb  = 'el_directory_'.$attrs['id'];
		$sql = "CREATE TABLE IF NOT EXISTS `$tb` (
				`id` int(11) NOT NULL auto_increment,
				`value` mediumtext,
				`sort_ndx` int(11) NOT NULL,
				PRIMARY KEY(`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";
		if (!$db->isTableExists($tb) && !$db->query($sql)) {
			return false;
		}
			
		$attrs = array_map('m', $attrs);
		
		if ($dm->directoryExists($attrs['id'])) {
			$sql = 'UPDATE %s SET label="%s", master_id="%s", master_key=%d WHERE id="%s"';
			$sql = sprintf($sql, $this->_tb, $attrs['label'], $attrs['master_id'], $attrs['master_key'], $attrs['id']);
		} else {
			$sql = 'INSERT INTO %s (id, label, master_id, master_key) VALUES ("%s", "%s", "%s", %d)';
			$sql = sprintf($sql, $this->_tb, $attrs['id'], $attrs['label'], $attrs['master_id'], $attrs['master_key']);
		}
		return $db->query($sql);
	}
	
	/**
	 * remove directory and its table
	 *
	 * @return void
	 **/
	function delete() {
		parent::delete();
		$db = $this->_db();
		$db->query("DROP TABLE IF EXISTS `el_directory_".$this->ID."`");
	}
	
	/**
	 * return records
	 *
	 * @param  bool  $list  return records as list
	 * @return array
	 **/
	function records($list=true) {
		if ($this->ID) {
			$db = $this->_db();
			$tb = 'el_directory_'.$this->ID;
			if ($db->isTableExists($tb)) {
				$sql = 'SELECT id, value, sort_ndx FROM '.$tb.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), value';
				return $list ? $db->queryToArray($sql, 'id', 'value') : $db->queryToArray($sql);
			}
		}
		return array();
	}
	
	/**
	 * return record by id
	 *
	 * @param  int    $id
	 * @param  bool   $default
	 * @return string
	 **/
	function record($id, $default=false) {
		if ($this->ID) {
			$db = $this->_db();
			$tb = 'el_directory_'.$this->ID;
			if ($db->isTableExists($tb)) {
				$r = $db->queryToArray('SELECT value FROM '.$tb.' WHERE id='.intval($id), null, 'value');
				return isset($r[0]) ? $r[0] : ($default ? $this->defaultRecord() : '');
			}
		}
	}
	
	/**
	 * return first record from directory
	 *
	 * @return string
	 **/
	function defaultRecord() {
		if ($this->ID) {
			$db = $this->_db();
			$tb = 'el_directory_'.$this->ID;
			if ($db->isTableExists($tb)) {
				$sql = 'SELECT value FROM '.$tb.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), value LIMIT 0, 1';
				$db->query($sql);
				if ($db->numRows()) {
					$r = $db->nextRecord();
					return $r['value'];
				}
			}
		}
		return '';
	}
	
	/**
	 * remove all records
	 *
	 * @return void
	 **/
	function clean() {
		$db = $this->_db();
		$tb = 'el_directory_'.$this->ID;
		if ($this->ID && $db->isTableExists($tb)) {
			$db->query('TRUNCATE `'.$tb.'`');
		}
	}
	
	/**
	 * add new records into directory
	 *
	 * @param  array  $records
	 * @return int
	 **/
	function add($records) {
		if ($this->ID && is_array($records) && !empty($records)) {
			$_r = array();
			foreach ($records as $r) {
				$r = trim($r);
				if ($r) {
					$_r[] = array(mysql_real_escape_string($r));
				}
			}
			if (count($_r)) {
				$db = $this->_db();
				$db->prepare('INSERT INTO el_directory_'.$this->ID.' (value) VALUES ', '("%s")');
				$db->prepareData($_r, true);
				$db->execute();
				return $db->affectedRows();
			}
		}
		return 0;
	}
	
	/**
	 * update record by id
	 *
	 * @param  int     $id 
	 * @param  string  $value
	 * @return void
	 **/
	function update($id, $value) {
		$db = $this->_db();
		$db->query(sprintf('UPDATE el_directory_%s SET value="%s" WHERE id=%d LIMIT 1', $this->ID, mysql_real_escape_string($value), $id));
	}
	
	/**
	 * remove record by id
	 *
	 * @param  int     $id 
	 * @return void
	 **/
	function deleteRecord($id)	{
		$db = $this->_db();
		$tb = 'el_directory_'.$this->ID;
		$db->query(sprintf('DELETE FROM `%s` WHERE id=%d LIMIT 1', $tb, $id));
		$db->optimizeTable($tb);
	}
	
	/**
	 * update sort indexes for directory
	 *
	 * @param  string  $id
	 * @param  array   $ndxs
	 * @return void
	 **/
	function sort($ndxs) {
		$sql = 'UPDATE el_directory_'.$this->ID.' SET sort_ndx=%d WHERE id=%d LIMIT 1';
		$db = $this->_db();
		foreach ($ndxs as $id=>$ndx) {
			$db->query(sprintf($sql, $ndx, $id));
		}
	}
	
	/**
	 * create form
	 *
	 * @return void
	 **/
	function _makeForm() {
		parent::_makeForm();
		
		if (!$this->ID) {
			$this->_form->add(new elText('id', 'ID'));
			$this->_form->setElementRule('id', 'alfanum_lat');
		}
		$this->_form->add(new elHidden('_id_', $this->id));
		$this->_form->add(new elText('label', m('Name'), $this->label));
		$this->_form->setRequired('label');
		
		$dm = & elSingleton::getObj('elDirectoryManager');
		$list = array('' => m(' --- ')) + $dm->getList();
		if ($this->ID) {
			unset($list[$this->ID]);
		}
		$this->_form->add(new elSelect('master_id',  m('Depends on'),  $this->masterID, $list));
		$this->_form->add(new elSelect('master_key', m('Foreign key'), $this->masterKey, array()));
		
		$js = '$("#mfelSysDirectory #master_id").change(function(e) {
			var dir = $(this).val(), 
				row = $(this).parents("tr").next(),
				sel = row.find("select").empty();

			if (!dir) {
				row.hide();
			} else {
				row.show();
				
				$.ajax({
					url : "'.EL_BASE_URL.'/__dir__/"+dir+"/",
					type : "get",
					dataType : "json",
					success : function(data) {
						window.console.log(data)
						var l = data.length;
						while (l--) {
							sel.prepend($("<option/>").val(data[l].id).text(data[l].value));
						}
						sel.children().eq(0).attr("selected", "on");
					}
				});
			}
		}).change()';
		
		elAddJs($js, EL_JS_SRC_ONREADY);
	}
	
	/**
	 * valid form for unique dir id
	 *
	 * @return bool
	 **/
	function _validForm() {
		$data = $this->_form->getValue();
		if (!$this->ID) {
			$db = $this->_db();
			$db->query(sprintf('SELECT id FROM %s WHERE id="%s"', $this->_tb, mysql_real_escape_string($data['id'])));
			if ($db->numRows()) {
				$this->_form->pushError('id', m('System directory with the same name already exists'));
			}
		}
		return !$this->_form->hasErrors();
	}
	
	
	/**
	 * return attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		return array(
			'id'         => 'ID',
			'label'      => 'label',
			'master_id'  => 'masterID',
			'master_key' => 'masterKey'
			);
	}
	
} // END class 


?>