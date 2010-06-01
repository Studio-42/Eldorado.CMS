<?php
include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elJSON.class.php';

class elServiceDirectory extends elService {
	
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
	
}

?>