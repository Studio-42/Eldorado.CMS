<?php
elLoadMessages('ModuleICartConf');

class elModuleDirectory extends elModule
{

	var $_mMap = array(
		'edit'        => array('m' => 'edit', 'ico' => 'icoDocNew', 'l' => 'Add new Directory', 'g' => 'Actions'),
		'rm'          => array('m' => 'delete'),
		'clean'       => array('m' => 'clean'),
		'sort'        => array('m' => 'sort'),
		'add_records' => array('m' => 'addRecords'),
		'edit_record' => array('m' => 'editRecord'),
		'rm_record'   => array('m' => 'deleteRecord')
	);

	var $_mMapConf  = array();
	var $_dm = null;
	
	function defaultMethod() {
		$this->_initRenderer();
		$this->_rnd->rndList($this->_dm->getDetails());
	}
	
	/**
	 * create/rename directory
	 *
	 * @return void
	 **/
	function edit() {
		$id = $this->_arg(); 
		$dir = $this->_dm->get($id);
		if ($dir->editAndSave()) {
			elMsgBox::put('Data saved');
			elLocation(EL_URL);
		} else {
			$this->_initRenderer();
			$this->_rnd->addToContent($dir->formToHtml());
		}
	}
	
	/**
	 * remove all records from directory
	 *
	 * @return void
	 **/
	function clean() {
		if (!empty($_POST['clean'])) {
			$dir = $this->_dm->get($this->_arg());
			$dir->clean();
			elMsgBox::put(m('All records removed'));
		}
		elLocation(EL_URL);
	}
	
	/**
	 * remove directory
	 *
	 * @return void
	 **/
	function delete() {
		if (!empty($_POST['rm'])) {
			$dir = $this->_dm->get($this->_arg());
			$dir->delete();
			elMsgBox::put(m('Directory removed'));
		}
		elLocation(EL_URL);
	}
	
	/**
	 * Add new records into directory
	 *
	 * @return void
	 **/
	function addRecords() {
		$id = $this->_arg();
		if ($this->_dm->directoryExists($id)) {
			if (!empty($_POST['value'])) {
				$dir = $this->_dm->get($id);
				$cnt = $dir->add(explode("\n", str_replace("\r", '', $_POST['value'])));
				elMsgBox::put(sprintf(m('There are %d records was added into "%s"'), $cnt, $dir->label));
			}
		}
		elLocation(EL_URL);
	}
	
	/**
	 * edit record
	 *
	 * @return void
	 **/
	function editRecord() {
		$id = $this->_arg();
		if ($this->_dm->directoryExists($id) && !empty($_POST['id']) && !empty($_POST['value'])) {
			$dir = $this->_dm->get($id);
			$dir->update(trim($_POST['id']), trim($_POST['value']));
			elMsgBox::put(m('Data saved'));
		}
		elLocation(EL_URL);
	}
	
	/**
	 * delete record
	 *
	 * @return void
	 **/
	function deleteRecord() {
		$id = $this->_arg();
		$rec = (int)$this->_arg(1);
		if ($this->_dm->directoryExists($id) && $rec && !empty($_POST['rm'])) {
			$dir = $this->_dm->get($id);
			$dir->deleteRecord($rec);
			elMsgBox::put(m('Record removed'));
		}
		elLocation(EL_URL);
	}
	
	/**
	 * sort directory records
	 *
	 * @return void
	 **/
	function sort() {
		$id = $this->_arg();
		if ($this->_dm->directoryExists($id) && !empty($_POST['dir_sort']) && is_array($_POST['dir_sort'])) {
			$dir = $this->_dm->get($id);
			$dir->sort($_POST['dir_sort']);
			elMsgBox::put(m('Data saved'));
		}
		elLocation(EL_URL);
	}
	
	function _onInit() {
		$this->_dm = & elSingleton::getObj('elDirectoryManager');
	}


}
?>
