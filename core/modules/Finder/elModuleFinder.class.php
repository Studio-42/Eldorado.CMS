<?php

class elModuleFinder extends elModule {
	
	var $_mMapConf = array();
	
	function defaultMethod() {
		elLoadJQueryUI();
		elAddCss('elfinder.css', EL_JS_CSS_FILE);
		elAddJs('elfinder.min.js', EL_JS_CSS_FILE);

		$file = 'i18n'.DIRECTORY_SEPARATOR.'elfinder'.DIRECTORY_SEPARATOR.'elfinder.'.EL_LANG.'.js';
		
		if (file_exists(EL_DIR_CORE.'js'.DIRECTORY_SEPARATOR.$file))
		{
			elAddJs($file, EL_JS_CSS_FILE);
		}
		
		$js = "$('#finder').elfinder({
			url  : '".EL_URL."__finder__/',
			lang : '".EL_LANG."'
		});\n";
		elAddJs($js, EL_JS_SRC_ONREADY);
		$this->_initRenderer();
		$this->_rnd->addToContent('<div id="finder"></div>');
	}
}

?>