<?php

class elIShopFinder {
	var $_pageID  = 0;
	var $_url     = EL_URL; 
	var $_label   = '';
	var $_tb      = '';
	var $_tbt     = '';
	var $_tbi     = '';
	var $_tbmnf   = '';
	var $_tbtm    = '';
	var $_conf    = array();
	var $_db      = null;
	var $_form    = null;
	var $_factory = null;
	
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elIShopFinder($pageID, $label='') {
		$nav = elSingleton::getObj('elNavigator');
		$this->_pageID  = $pageID;
		$this->_url     = $nav->getPageURL($pageID).'search/';
		$this->_label   = $label;
		$this->_factory = & elSingleton::getObj('elIShopFactory');
		$this->_tb      = $this->_factory->tb('tbs');
		$this->_tbt     = $this->_factory->tb('tbt');
		$this->_tbi     = $this->_factory->tb('tbi');
		$this->_tbmnf   = $this->_factory->tb('tbmnf');
		$this->_tbtm    = $this->_factory->tb('tbtm');
		$this->_tbp     = $this->_factory->tb('tbp');
		$this->_db      = & elSingleton::getObj('elDb');
		$sql = sprintf('SELECT id, label, sort_ndx, type, price_step, prop_id, prop_view, noselect_label, display_on_load FROM %s ORDER BY id, sort_ndx', $this->_tb);
		$this->_conf = $this->_db->queryToArray($sql, 'id');
		foreach ($this->_conf as $id=>$v) {
			if ($v['prop_id']) {
				$p = $this->_factory->getFromRegistry(EL_IS_PROP, $v['prop_id']);
				$opts = $p->opts();
				if ($p->type != EL_IS_PROP_LIST || empty($opts)) {
					unset($this->_conf[$id]);
				} else {
					$this->_conf[$id]['prop'] = $p;
					$this->_conf[$id]['opts'] = $opts;
				}
			}
		}
	}

	/**
	 * Return true if form is configured and can be used
	 *
	 * @return bool
	 **/
	function isConfigured() {
		return !empty($this->_conf);
	}
	
	/**
	 * load config and create form
	 *
	 * @return void
	 **/
	function init() {

		$this->_form = elSingleton::getObj('elForm', 'ishop-finder-form-'.$this->_pageID, $this->_label, $this->_url);
		$this->_form->setRenderer(elSingleton::getObj('elIShopFinderFormRenderer'));
		foreach ($this->_conf as $id=>$v) {
			switch ($v['type']) {
				case 'type':
					$this->_conf[$id]['opts'] = $this->_getOpts(EL_IS_ITYPE, $v['noselect_label']);
					$this->_form->add(new elSelect('type', $v['label'], 0, $this->_conf[$id]['opts']), array('class' => 'ishop-type-itype'));
					break;
				case 'mnf':
					$this->_conf[$id]['opts'] = $this->_getOpts(EL_IS_MNF, $v['noselect_label']);
					$this->_form->add(new elSelect('mnf', $v['label'], 0, $this->_conf[$id]['opts']), array('class' => 'ishop-type-mnf'));
					break;
				case 'tm':
					$this->_conf[$id]['opts'] = $this->_getOpts(EL_IS_TM, $v['noselect_label']);
					$this->_form->add(new elSelect('tm', $v['label'], 0, $this->_conf[$id]['opts']), array('class' => 'ishop-type-tm'));
					break;
				case 'price':
					if ($v['price_step'] > 0 && ($max = $this->_getMaxPrice()) > 0) {
						$this->_conf[$id]['opts'] = $this->_getPriceOpts();
						$this->_form->add(new elSelect('price', $v['label'], 0, $this->_conf[$id]['opts']), array('class' => 'ishop-type-price'));
					} else {
						$c = & new elFormContainer('c-'.$id, $v['label']);
						$c->add(new elText('price[0]', m('from').':', '', array('size' => 10)));
						$c->add(new elText('price[1]', m('to').':', '', array('size' => 10)));
						$this->_form->add($c, array('class' => 'ishop-type-price'));
					}
					break;
				default:
					$params = array(
						'class' => 'ishop-type-prop ishop-type-prop-'.$id,
						'style' => $v['display_on_load'] == 'yes' ? '' : 'style="display:none"'
						);
					if ($v['prop_view'] == 'normal') {
						$this->_form->add(new elSelect('prop-'.$v['prop']->ID, $v['label'], 0, $v['opts']), $params);
					} else {
						$c = & new elFormContainer('c-'.$id, $v['label']);
						$c->add(new elSelect('prop-'.$v['prop']->ID, m('from').':', 0, $v['opts']));
						$c->add(new elSelect('prop-'.$v['prop']->ID.'-2', m('to').':', array_pop(array_keys($v['opts'])), $v['opts']));
						$this->_form->add($c, $params);
					}
			}
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function formToHtml() {
		if (!$this->_form) {
			$this->init();
		}
		return !empty($this->_conf) ? $this->_form->toHtml() : '';
	}
	

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function _getOpts($type, $label) {
		$opts = array($label ? m($label) : m('not selected'));
		foreach ($this->_factory->getAllFromRegistry($type) as $id=>$v) {
			$opts[$id] = $v->name;
		}
		return $opts;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function _getMaxPrice() {
		$this->_db->query('SELECT MAX(price) AS max_price FROM '.$this->_tbi);
		if ($this->_db->numRows()) {
			$r = $this->_db->nextRecord();
			return $r['max_price'];
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function _getPriceOpts($step, $max)	{
		$opts = array();
		$cur = 0;
		do {
			$next = $cur+$step;
			$opts[$cur.'_'.$next] = $cur.' - '.$next;
			$cur = $next;
		} while ($cur < $max);
		return $opts;
	}
	
}

?>