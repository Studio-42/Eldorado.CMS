<?php
/**
 * IshopFinder form renderer
 *
 * @package default
 * @author Dmitry Levashov
 **/
class elIShopFinderFormRenderer extends elFormRenderer {
	/**
	 * renderer
	 *
	 * @var elTE
	 **/
	var $_rnd = null;
	/**
	 * tpl var name
	 *
	 * @var string
	 **/
	var $_hndl = 'ishopSearchForm';

	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elIShopFinderFormRenderer() {
		$this->_rnd = & elSingleton::getObj('elTE');
		$this->_rnd->setFile($this->_hndl, 'forms/ishopFinderForm.html');
		elAddCss('ishopFinderForm.css');
	}
	
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function beginForm($attrs, $label, $errors) {
		$this->_rnd->assignVars('attrs', $attrs);
		if ($label) {
			$this->_rnd->assignBlockVars('FORM_LABEL', array('label' => $label));
		}
	}

	function renderHidden($el) {
		$this->_rnd->assignVars('hiddens', $el->toHtml(), true);
	}

	function renderElement($el, $required, $params=null)  {

		$this->_rnd->assignBlockVars('FORM_ELEMENT', array('element' => $el->toHtml()));
		$this->_rnd->assignBlockVars('FORM_ELEMENT', $params, 1);
		if ($el->label) {
			$this->_rnd->assignBlockVars('FORM_ELEMENT.LABEL', array('label' => $el->label), 1);
		}
	}

	function endForm() { 
		$this->_rnd->parse($this->_hndl);
	    $this->html = $this->_rnd->getVar($this->_hndl);
	    $this->_complite = true;
	}
	
} // END class 

?>