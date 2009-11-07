<?php

class elEditor extends elFormInput
{
  	var $value = '';
  	var $attrs = array( 'rows'  => EL_DEFAULT_TA_ROWS, 
                      'cols'  => EL_DEFAULT_TA_COLS, 
                      'style' => 'width:100%');
	var $isEditor = true;
	
  	function setValue($value)
  	{
    	$this->value = $value;
  	}

  	function getValue()
  	{
		return ('<br />' == $this->value || '<br>' == $this->value || '&nbsp;' == $this->value) ? '' : preg_replace('=('.EL_BASE_URL.'/)=ism', '/', $this->value);
  	}

	function toHtml()
  	{
		elLoadJQueryUI();
		
		elAddCss('elcolorpicker.css', EL_JS_CSS_FILE);
		elAddCss('elselect.css', EL_JS_CSS_FILE);
		elAddCss('eldialogform.css', EL_JS_CSS_FILE);
		elAddCss('elborderselect.css', EL_JS_CSS_FILE);
		elAddCss('elpaddinginput.css', EL_JS_CSS_FILE);
		elAddCss('eltree.css', EL_JS_CSS_FILE);
		elAddCss('contextmenu.css', EL_JS_CSS_FILE);
		elAddCss('elrte.css', EL_JS_CSS_FILE);
		elAddCss('elfinder.css', EL_JS_CSS_FILE);
		
		
		elAddJs('jquery.metadata.js', EL_JS_CSS_FILE);
		elAddJs('jquery.cookie.js', EL_JS_CSS_FILE);
		elAddJs('jquery.form.js', EL_JS_CSS_FILE);

		elAddJs('ellib/el.lib.complite.js', EL_JS_CSS_FILE);
		elAddJs('elfinder/elfinder.js', EL_JS_CSS_FILE);
		elAddJs('elfinder/i18n/ru.js', EL_JS_CSS_FILE);
		
		elAddJs('elrte/elRTE.complite.js', EL_JS_CSS_FILE);
		elAddJs('elrte/i18n/ru.js', EL_JS_CSS_FILE);
		
		$js = "var opts = {
			cssClass : 'el-rte ".$this->getAttr('class')."',
			lang     : 'ru',
			toolbar  : 'maxi',
			cssfiles : ['".EL_BASE_URL."/style/css/elrte-inner.css'],
			fmAllow  : true,
			fmOpen   : function(callback) {
				$('<div />').elfinder({
					url : '".EL_URL."__finder__',
					dialog : { width : 900, modal : true },
					editorCallback : callback
				})
			}
		}
		$('#".$this->getAttr('name').".rte').jqelrte(opts);
		";

		elAddJs($js, EL_JS_SRC_ONREADY);
		$value = preg_replace('~(href|src)="(/[^\s"]*)"~ism', "\\1=\"".EL_BASE_URL."\\2\"", $this->value);
		$this->setAttr('class', $this->getAttr('class').' rte');
    	return '<textarea '.$this->attrsToString().">\n".htmlspecialchars($value)."</textarea>\n";
  	}
}

?>