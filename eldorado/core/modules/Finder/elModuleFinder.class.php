<?php

class elModuleFinder extends elModule {
	
	var $_mMapConf = array();
	
	function defaultMethod() {
		elLoadJQueryUI();

		elAddCss('contextmenu.css', EL_JS_CSS_FILE);
		elAddCss('eldialogform.css', EL_JS_CSS_FILE);
		elAddCss('elfinder.css', EL_JS_CSS_FILE);
		
		elAddJs('jquery.metadata.js', EL_JS_CSS_FILE);
		elAddJs('jquery.cookie.js', EL_JS_CSS_FILE);
		elAddJs('jquery.form.js', EL_JS_CSS_FILE);
		elAddJs('ellib/eli18n.js', EL_JS_CSS_FILE);
		elAddJs('ellib/widgets/eldialogform.js', EL_JS_CSS_FILE);
		elAddJs('ellib/widgets/jquery.eltree.js', EL_JS_CSS_FILE);
		elAddJs('ellib/widgets/jquery.elcontextmenu.js', EL_JS_CSS_FILE);
		elAddJs('elfinder/elfinder.js', EL_JS_CSS_FILE);
		if (file_exists(EL_DIR.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'elfinder'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.EL_LANG.'.js'))
		{
			elAddJs('elfinder'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.EL_LANG.'.js', EL_JS_CSS_FILE);
		}
		
		$js = "$('#finder').elfinder({url: '".EL_URL."__finder__/'});\n";
		elAddJs($js, EL_JS_SRC_ONREADY);
		$this->_initRenderer();
		$this->_rnd->addToContent('<div id="finder">finder</div>');
	}

}

?>