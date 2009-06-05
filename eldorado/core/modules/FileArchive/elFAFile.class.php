<?php
include_once EL_DIR_CORE.'lib/elCatalogItem.class.php';
class elFAFile extends elCatalogItem
{
	var $ID       = 0;
	var $parentID    = 1;
	var $name     = '';
	var $fURL     = '';
	var $fSize    = 0;
	var $descrip  = '';
	var $mtime    = 0;
	var $ltd      = 0;
	var $cnt      = 0;
	var $_objName = 'File';
	var $_sortVars = array('name, f_url',	'f_url, name, mtime',	'mtime DESC, name, f_url', 'cnt DESC, name, f_url');

	//**************************************************************************************//
	// *******************************  PUBLIC METHODS  *********************************** //
	//**************************************************************************************//

	/**
   * Create edit item form object
  */
	function makeForm( $parents )
	{
		parent::makeForm($parents);

		if ($this->fURL)
		{
			$fURL =  !strstr($this->fURL, EL_BASE_URL )  ? EL_BASE_URL.'/'.$this->fURL : $this->fURL;
		}
		else
		{
			$fURL = '';
		}
		elAddJs('function SetUrl(URL) { document.getElementById("f_url").value = URL; }');
		$this->form->add( new elText('f_url',   m('URL'), $fURL) );
		$this->form->add( new elSubmit('om', m('Select file'), m('Open file manager'),
		array('onClick'=>'return popUp("'.EL_BASE_URL.'/'.EL_URL_POPUP.'/__fm__/", 400, 500);'))  );
		$this->form->add( new elEditor('descrip', m('Description'), $this->descrip) );

		$this->form->setRequired('f_url');
	}


	//**************************************************************************************//
	// =============================== PRIVATE METHODS ==================================== //
	//**************************************************************************************//

	/**
   * Create attributes to members map
   * @ returns array
  */
	function _initMapping()
	{
		return array( 
			'id'      => 'ID',
			'parent_id'  => 'parentID',
			'name'    => 'name',
			'f_url'   => 'fURL',
			'descrip' => 'descrip',
			'f_size'  => 'fSize',
			'mtime'   => 'mtime',
			'cnt'     => 'cnt',
			'ltd'     => 'tld'
			);
	}


	function _attrsForSave()
	{
		$attrs = $this->getAttrs();
		if ( $attrs['f_url'] )
		{
			$attrs['f_url']  = str_replace(EL_BASE_URL.'/', '', $attrs['f_url']);
			$attrs['f_size'] = round(filesize(EL_DIR.$attrs['f_url'])/1024, 2);
		}
		$attrs['mtime'] = time();
		return $attrs;
	}

}


?>