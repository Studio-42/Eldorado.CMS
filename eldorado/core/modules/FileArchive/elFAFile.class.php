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
	function _makeForm( $parents )
	{
		parent::_makeForm($parents);
		
		elLoadJQueryUI();
		elAddCss('elfinder.css',          EL_JS_CSS_FILE);
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.form.min.js',     EL_JS_CSS_FILE);
		elAddJs('elfinder.min.js',        EL_JS_CSS_FILE);
		if (file_exists(EL_DIR.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'elfinder'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.EL_LANG.'.js'))
		{
			elAddJs('elfinder'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.EL_LANG.'.js', EL_JS_CSS_FILE);
		}
		
		$this->_form->add(new elCData('img',  "<a href='#' class='link download' id='ishop-sel-file'>".m('Select or upload file')."</a>"));
		$js = "
		$('#ishop-sel-file').click(function(e) {
			e.preventDefault();
			$('<div />').elfinder({
				url  : '".EL_URL."__finder__/', 
				lang : '".EL_LANG."', 
				editorCallback : function(url) { $('#f_url').val(url);}, 
				dialog : { width : 750, modal : true}});
		});
		";
		elAddJs($js, EL_JS_SRC_ONREADY);
		if ($this->fURL)
		{
			$fURL =  !strstr($this->fURL, EL_BASE_URL )  ? EL_BASE_URL.'/'.$this->fURL : $this->fURL;
		}
		else
		{
			$fURL = '';
		}
		$this->_form->add( new elText('f_url',  m('URL'), $fURL) );
		$this->_form->add( new elEditor('descrip', m('Description'), $this->descrip, array('class' => 'small')) );
		$this->_form->setRequired('f_url');
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
			'ltd'     => 'ltd'
			);
	}


	function _attrsForSave()
	{
		$attrs = parent::_attrsForSave();
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