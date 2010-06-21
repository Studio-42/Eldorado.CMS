<?php

class elIShopFinder {
	var $_pageID  = 0;
	var $_url     = EL_URL; 
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
	function elIShopFinder($pageID) {
		$nav = elSingleton::getObj('elNavigator');
		$this->_pageID  = $pageID;
		$this->_url     = $nav->getPageURL($pageID).'search/';
		$this->_factory = & elSingleton::getObj('elIShopFactory');
		$this->_tb      = $this->_factory->tb('tbs');
		$this->_tbt     = $this->_factory->tb('tbt');
		$this->_tbi     = $this->_factory->tb('tbi');
		$this->_tbmnf   = $this->_factory->tb('tbmnf');
		$this->_tbtm    = $this->_factory->tb('tbtm');
		$this->_tbp     = $this->_factory->tb('tbp');
		$this->_tbp2i   = $this->_factory->tb('tbp2i');
		$this->_db      = & elSingleton::getObj('elDb');
		$sql = sprintf('SELECT id, label, sort_ndx, type, price_step, prop_id, prop_view, noselect_label, display_on_load FROM %s ORDER BY sort_ndx, id', $this->_tb);
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
		$this->_init();
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
	 * return form html if configured
	 *
	 * @return string
	 **/
	function formToHtml($label='') {
		$this->_form->setLabel($label);
		return !empty($this->_conf) ? $this->_form->toHtml() : '';
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function find() {
		$res = array();
		if (!$this->isConfigured() || !$this->formIsSubmit) {
			return $res;
		}
		
		$item = $this->_factory->create(EL_IS_ITEM);
		// elPrintr($item);
		$data = $this->_form->getValue();
		// elPrintr($data);
		// elPrintr($_POST);
		
		$where = array();
		
		if (!empty($data['type'])) {
			$where[] = sprintf('type_id=%d', $data['type']);
		}
		if (!empty($data['mnf'])) {
			$where[] = sprintf('mnf_id=%d', $data['mnf']);
		}
		if (!empty($data['tm'])) {
			$where[] = sprintf('tm_id=%d', $data['tm']);
		}
		if (!empty($data['price'])) {
			if (is_array($data['price'])) {
				if ($data['price'][0] > 0 || $data['price'][1] > 0) {
					$where[] = 'price BETWEEN '.min((int)$data['price'][0], (int)$data['price'][1]).' AND '.max((int)$data['price'][0], (int)$data['price'][1]);
				}
				
			} else {
				
			}
		}
		
		if (!empty($where)) {
			$res = $this->_db->queryToArray(sprintf('SELECT id FROM %s WHERE %s', $this->_tbi, implode(' AND ', $where)), null, 'id');
			if (empty($res)) {
				return $res;
			}
		}
		

		foreach ($data as $name=>$val) {
			$id = (int)str_replace('props-', '', $name);
			if ($id>0 && false != ($p = $this->_conf('prop', $id))) {
				if (is_array($val)) {
					$propIDs = array_keys($p['opts']);
					$k1      = array_search($val[0], $propIDs);
					$k2      = array_search($val[1], $propIDs);
					$offset  = min($k1, $k2);
					$ids     = array_slice($propIDs, $offset, max($k1, $k2)-$offset+1);
				} else {
					$ids = array((int)$val);
				}
				
				$sql = empty($res)
					? sprintf('SELECT i_id FROM %s WHERE p_id=%d AND value IN (%s)', $this->_tbp2i, $id, implode(',', $ids))
					: sprintf('SELECT i_id FROM %s WHERE i_id IN (%s) AND p_id=%d AND value IN (%s)', $this->_tbp2i, implode(',', $res), $id, implode(',', $ids));
				if (false == ($res = $this->_db->queryToArray($sql, null, 'i_id'))) {
					return $res;
				}
				
			}
		}
		
		// elPrintr($this->_conf);
		return $res;
	}
	
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function _conf($type, $propID=0) {
		foreach ($this->_conf as $id=>$v) {
			if ($v['type'] == $type && $v['prop_id'] == $propID) {
				return $v;
			}
		}
	}
	
	/**
	 * create form
	 *
	 * @return void
	 **/
	function _init() {

		$this->_form = elSingleton::getObj('elForm', 'ishop-finder-form-'.$this->_pageID, '', $this->_url);
		$this->_form->setRenderer(elSingleton::getObj('elIShopFinderFormRenderer'));
		$containerTpls = array(
			'header'  => '',
			'footer'  => '',
			'label'   => '',
			'element' => '<div class="ishop-form-container"><span class="ishop-form-container-label">%s</span> <span class="ishop-form-container-el">%s </span> </div>'
			);
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
						$c->setTpls($containerTpls);
						$c->add(new elText('price[0]', m('from').':', '', array()));
						$c->add(new elText('price[1]', m('to').':', '', array('size' => 5)));
						$this->_form->add($c, array('class' => 'ishop-type-price'));
					}
					break;
				default:
					$params = array(
						'class' => 'ishop-type-prop ishop-type-prop-'.$id,
						'style' => $v['display_on_load'] == 'yes' ? '' : 'style="display:none"'
						);
					if ($v['prop_view'] == 'normal') {
						$this->_form->add(new elSelect('props['.$v['prop']->ID.']', $v['label'], 0, $v['opts']), $params);
					} else {
						$c = & new elFormContainer('c-'.$id, $v['label']);
						$c->setTpls($containerTpls);
						$c->add(new elSelect('props-'.$v['prop']->ID.'[0]', m('from').':', 0, $v['opts']));
						$c->add(new elSelect('props-'.$v['prop']->ID.'[1]', m('to').':', array_pop(array_keys($v['opts'])), $v['opts']));
						$this->_form->add($c, $params);
					}
			}
		}
		
		$this->formIsSubmit = $this->_form->isSubmit();
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