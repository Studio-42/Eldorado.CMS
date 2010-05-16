<?php
elLoadMessages('ModuleICartConf');

class elModuleDirectory extends elModule
{

	var $_mMap = array(
		'create'      => array('m' => 'create', 'ico' => 'icoDocNew', 'l' => 'Add new Directory', 'g' => 'Actions'),
		'rename'      => array('m' => 'rename'),
		'rm'          => array('m' => 'delete'),
		'clean'       => array('m' => 'clean'),
		'sort'        => array('m' => 'sort'),
		'add_records' => array('m' => 'addRecords'),
		'edit_record' => array('m' => 'editRecord'),
		'rm_record'   => array('m' => 'deleteRecord')
	);

	var $_mMapConf  = array();
	var $_dm = null;
	
	function defaultMethod()
	{
		// elPrintR($this->_dm->getDetails());
		$this->_initRenderer();
		$this->_rnd->rndList($this->_dm->getDetails());
		// elLocation(EL_URL . 'list');
	}
	
	/**
	 * create/rename directory
	 *
	 * @return void
	 **/
	function create() {
		
		$form = & elSingleton::getObj( 'elForm', 'mf_dir',  m('New directory') );
		$form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
		$form->add(new elText('id', 'ID'));
		$form->add(new elText('label', m('Name')));
		$form->setElementRule('id', 'alfanum_lat');
		$form->setRequired('label');
		
		if ($form->isSubmitAndValid()) {
			$data = $form->getValue();
			if ($this->_dm->directoryExists($data['id'])) {
				$form->pushError('id', m('Directory with same ID already exists'));
			}
		}
		
		if ($form->isSubmitAndValid()) {
			$data = $form->getValue();
			$this->_dm->create($data['id'], $data['label']);
			elMsgBox::put('Data saved');
			elLocation(EL_URL);
		} else {
			$this->_initRenderer();
			$this->_rnd->addToContent($form->toHtml());
		}
	}
	
	/**
	 * rename directory
	 *
	 * @return void
	 **/
	function rename() {
		$dir = $this->_arg();
		$name = !empty($_POST['name']) ? trim($_POST['name']) : '';
		
		if ($this->_dm->directoryExists($dir) && $name) {
			$this->_dm->rename($dir, $name);
			elMsgBox::put(m('Data saved'));
		}
		elLocation(EL_URL);
	}
	
	/**
	 * remove all records from directory
	 *
	 * @return void
	 **/
	function clean() {
		$dir = $this->_arg();
		if ($this->_dm->directoryExists($dir) && !empty($_POST['clean'])) {
			$this->_dm->clean($dir);
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
		$dir = $this->_arg();
		if ($this->_dm->directoryExists($dir) && !empty($_POST['rm'])) {
			$this->_dm->delete($dir);
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
		$dir = $this->_arg();
		$val = !empty($_POST['value']) ? trim($_POST['value']) : '';
		if ($this->_dm->directoryExists($dir) && $val) {
			$this->_dm->addRecords($dir, $val);
			elMsgBox::put(m('Data saved'));
		}
		elLocation(EL_URL);
	}
	
	/**
	 * edit record
	 *
	 * @return void
	 **/
	function editRecord() {
		$dir = $this->_arg();
		if ($this->_dm->directoryExists($dir) && !empty($_POST['id']) && !empty($_POST['value'])) {
			$this->_dm->updateRecord($dir, trim($_POST['id']), trim($_POST['value']));
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
		$dir = $this->_arg();
		$id = (int)$this->_arg(1);
		if ($this->_dm->directoryExists($dir) && $id && !empty($_POST['rm'])) {
			$this->_dm->deleteRecord($dir, $id);
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
		$dir = $this->_arg();

		if ($this->_dm->directoryExists($dir) && !empty($_POST['dir_sort']) && is_array($_POST['dir_sort'])) {
			$this->_dm->sort($dir, $_POST['dir_sort']);
		}
		elLocation(EL_URL);
	}
	
	
	

	function _onInit() {
		$this->_dm = & elSingleton::getObj('elDirectoryManager');
	}


}
?>
