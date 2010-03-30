<?php

class elEditor extends elFormInput
{
  	var $value    = '';
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
		elAddCss('elrte.css',      EL_JS_CSS_FILE);
		elAddCss('elfinder.css',   EL_JS_CSS_FILE);
		elAddJs('elrte.full.js',   EL_JS_CSS_FILE);
		elAddJs('elfinder.min.js', EL_JS_CSS_FILE);
		
		$file = 'i18n'.DIRECTORY_SEPARATOR.'elrte'.DIRECTORY_SEPARATOR.'elrte.'.EL_LANG.'.js';
		if (file_exists(EL_DIR_CORE.'js'.DIRECTORY_SEPARATOR.$file))
		{
			elAddJs($file, EL_JS_CSS_FILE);
		}
		$file = 'i18n'.DIRECTORY_SEPARATOR.'elfinder'.DIRECTORY_SEPARATOR.'elfinder.'.EL_LANG.'.js';
		
		if (file_exists(EL_DIR_CORE.'js'.DIRECTORY_SEPARATOR.$file))
		{
			// elAddJs($file, EL_JS_CSS_FILE);
		}
		
		$height = (int)$this->getAttr('height');
		if (!$height) {
			$height = 500;
		} else {
			unset($this->attrs['height']);
		}
		
		$js = "var opts = {
			cssClass : 'el-rte ".$this->getAttr('class')."',
			lang     : '".EL_LANG."',
			height   : ".$height.",
			toolbar  : 'eldorado',
			cssfiles : ['".EL_BASE_URL."/style/css/elrte-inner.css'],
			fmAllow  : true,
			fmOpen   : function(callback) {
				$('<div id=\"elfinder-rte\" />').elfinder({
					url : '".EL_URL."__finder__',
					lang : '".EL_LANG."',
					dialog : { width : 900, modal : true, title : '".m('Files')."' },
					editorCallback : callback
				})
			}
		}
		$('#".$this->getAttr('name').".rte').elrte(opts);
		";

		elAddJs($js, EL_JS_SRC_ONREADY);
		$value = preg_replace('~(href|src)="(/[^\s"]*)"~ism', "\\1=\"".EL_BASE_URL."\\2\"", $this->value);
		$this->setAttr('class', $this->getAttr('class').' rte');
    	return '<textarea '.$this->attrsToString().">\n".htmlspecialchars($value)."</textarea>\n";
  	}
}

?>