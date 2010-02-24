<?php

class elModuleTemplatesEditor extends elModule {
	
	function defaultMethod() {
		elLoadJQueryUI();
		elAddCss('elfinder.css', EL_JS_CSS_FILE);
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.form.min.js', EL_JS_CSS_FILE);
		elAddJs('elfinder.min.js', EL_JS_CSS_FILE);
		if (file_exists(EL_DIR.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.'elfinder.'.EL_LANG.'.js'))
		{
			elAddJs('i18n'.DIRECTORY_SEPARATOR.'elfinder.'.EL_LANG.'.js', EL_JS_CSS_FILE);
		}
		
		$js = "$('#finder').elfinder({
			url  : '".EL_URL."__finder__/',
			lang : '".EL_LANG."'
		});\n";
		elAddJs($js, EL_JS_SRC_ONREADY);
		$this->_initRenderer();
		$this->_rnd->addToContent('<p>'.m('To edit template, select file and press button "Edit"').'</p>');
		$this->_rnd->addToContent('<div id="finder">finder</div>');
	}

}

?>