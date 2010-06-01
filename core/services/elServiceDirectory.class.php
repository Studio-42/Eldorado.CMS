<?php
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';

class elServiceDirectory extends elService {
	
	/**
	 * methods mapping
	 *
	 * @var array
	 **/
	var $_mMap = array('slave' => array('m' => 'findSlave'));
	
	/**
	 * display dirs list or dir content in json
	 *
	 * @return void
	 **/
	function defaultMethod() {
		$dm = & elSingleton::getObj('elDirectoryManager');
		$id = isset($this->_args[0]) ? trim($this->_args[0]) : '';

		if ($id) {
			if ($dm->directoryExists($id)) {
				$dir = $dm->get($id);
				exit(elJSON::encode($dir->records(!empty($this->_args[1]))));
			} else {
				exit(elJSON::encode(array('error' => m('Directory does not exists'))));
			}
		} else {
			exit(elJSON::encode($dm->getList()));
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function findSlave() {
		$dm = & elSingleton::getObj('elDirectoryManager');
		$id = isset($this->_args[1]) ? trim($this->_args[1]) : '';
		$key = isset($this->_args[2]) ? intval($this->_args[2]) : 0;

		// echo "$id $key";
		if (false != ($dir = $dm->findSlave($id, $key))) {
			exit(elJSON::encode($dir->records(false)));
		}
		exit(elJSON::encode(array('error' => m('Directory does not exists'))));
	}
	
	
}

?>