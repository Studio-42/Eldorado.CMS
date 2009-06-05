<?php
include_once(EL_DIR_CORE.'lib/elCatalogModule.class.php');

class elModuleFileArchive extends elCatalogModule
{
	var $tbc        = 'el_fa_%d_cat';
	var $tbi        = 'el_fa_%d_item';
	var $tbi2c      = 'el_fa_%d_i2c';
	var $_itemClass = 'elFAFile';
  var $_conf      = array(
  	'deep'              => 0,
    'catsCols'          => 1,
    'itemsCols'         => 2,
    'itemsSortID'       => 1,
    'itemsPerPage'      => 10,
    'displayCatDescrip' => 1,
    'displayLmd'        => 0, // показывать дату модификации
    'displayCnt'        => 0  // показывать счетчик скачиваний
    );


	function viewItem()
	{
		$this->_item = $this->_getItem();
		if ( !$this->_item->ID )
		{
			elThrow(E_USER_WARNING, 'Object "%s" with ID="%d" does not exists',
				array($this->_item->getObjName(), $this->_arg(1)), EL_URL.$this->_cat->ID);
		}

		if (!file_exists(EL_DIR.$this->_item->fURL) || !is_readable(EL_DIR.$this->_item->fURL) )
		{
			elThrow(E_USER_WARNING, 'File %s does not exists', basename($this->_item->fURL), EL_URL.$this->_cat->ID);
		}

		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=".basename($this->_item->fURL) );
		header("Content-Location: ".EL_DIR.$this->_item->fURL);
		header("Content-Length: " .filesize(EL_DIR.$this->_item->fURL));
		header("Connection:close");
		readfile(EL_DIR.$this->_item->fURL);

		$db = &elSingleton::getObj('elDb');
		$db->query('UPDATE '.$this->_item->tb.' SET cnt=cnt+1 WHERE id='.$this->_item->ID);
		exit;
	}



}

?>