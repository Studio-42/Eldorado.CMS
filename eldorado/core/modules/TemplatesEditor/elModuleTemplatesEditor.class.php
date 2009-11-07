<?php

class elModuleTemplatesEditor extends elModule {
	
	function defaultMethod() {
		elLoadJQueryUI();

		elAddCss('contextmenu.css', EL_JS_CSS_FILE);
		elAddCss('eldialogform.css', EL_JS_CSS_FILE);
		elAddCss('elrtee.css', EL_JS_CSS_FILE);
		elAddCss('elfinder.css', EL_JS_CSS_FILE);
		
		elAddJs('jquery.metadata.js', EL_JS_CSS_FILE);
		elAddJs('jquery.cookie.js', EL_JS_CSS_FILE);
		elAddJs('jquery.form.js', EL_JS_CSS_FILE);
		elAddJs('ellib/eli18n.js', EL_JS_CSS_FILE);
		elAddJs('ellib/widgets/eldialogform.js', EL_JS_CSS_FILE);
		elAddJs('ellib/widgets/jquery.eltree.js', EL_JS_CSS_FILE);
		elAddJs('ellib/widgets/jquery.elcontextmenu.js', EL_JS_CSS_FILE);
		elAddJs('elfinder/elfinder.js', EL_JS_CSS_FILE);
		elAddJs('elfinder/i18n/ru.js', EL_JS_CSS_FILE);
		
		
		$js = "$('#finder').elfinder({url: '".EL_URL."__finder__/'});\n";
		elAddJs($js, EL_JS_SRC_ONREADY);
		$this->_initRenderer();
		$this->_rnd->addToContent('<p>'.m('To edit template, select file and press button "Edit"').'</p>');
		$this->_rnd->addToContent('<div id="finder">finder</div>');
	}

}

?>