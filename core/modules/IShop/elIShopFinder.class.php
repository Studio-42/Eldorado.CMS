<?php
/**
 * IShop search
 *
 * @package modules
 **/
class elIShopFinder {
	var $_pageID  = 0;
	var $_url     = EL_URL; 
	var $_tb = array(
		'search' => 'el_ishop_%d_search',
		'type'   => 'el_ishop_%d_type',
		'item'   => 'el_ishop_%d_item',
		'mnf'    => 'el_ishop_%d_mnf',
		'tm'     => 'el_ishop_%d_tm',
		'prop'   => 'el_ishop_%d_prop_value',
		'p2i'    => 'el_ishop_%d_p2i'
		);
	var $_conf    = array();
	var $_db      = null;
	var $_form    = null;
	var $_tpls = array(
		'header'  => '',
		'footer'  => '',
		'label'   => '',
		'element' => '<div class="ishop-finder-container"><span class="ishop-finder-container-label">%s</span> <span class="ishop-finder-container-el">%s </span> </div>'
		);
	
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elIShopFinder($pageID) {
		$nav = elSingleton::getObj('elNavigator');
		$this->_pageID  = $pageID;
		$this->_url     = $nav->getPageURL($pageID).'search/';
		foreach ($this->_tb as $k=>$v) {
			$this->_tb[$k] = sprintf($v, $pageID);
		}

		$this->_db      = & elSingleton::getObj('elDb');
		$sql = sprintf('SELECT id, label, sort_ndx, type, prop_id, prop_view, noselect_label, display_on_load, position FROM %s ORDER BY sort_ndx, id', $this->_tb['search']);
		$this->_conf = $this->_db->queryToArray($sql, 'id');
		$this->_form = elSingleton::getObj('elForm', 'ishop-finder-form-'.$this->_pageID, '', $this->_url);
		$this->_form->setRenderer(elSingleton::getObj('elIShopFinderFormRenderer'));
		foreach ($this->_conf as $id => $v) {
			$attrs  = array('rel' => $v['position']);
			$params = array('class' => 'ishop-type-'.$v['type'].' ishop-search-'.$v['position'], 'rel' => $v['position']);

			switch ($v['type']) {
				case 'type':
					$opts = $this->_list(EL_IS_ITYPE, $v['noselect_label']);
					if (empty($opts)) {
						unset($this->_conf[$id]);
						continue;
					} else {
						$this->_conf[$id]['opts'] = $opts;
						$el = new elSelect('type', $v['label'], 0, $opts, $attrs);
					}
					break;
				case 'mnf':
					$opts = $this->_list(EL_IS_MNF, $v['noselect_label']);
					if (empty($opts)) {
						unset($this->_conf[$id]);
						continue;
					} else {
						$this->_conf[$id]['opts'] = $opts;
						$el = new elSelect('mnf', $v['label'], 0, $opts, $attrs);
					}
					break;
				case 'tm':
					$opts = $this->_list(EL_IS_TM, $v['noselect_label']);
					if (empty($opts)) {
						unset($this->_conf[$id]);
						continue;
					} else {
						$this->_conf[$id]['opts'] = $opts;
						$el = new elSelect('tm', $v['label'], 0, $opts, $attrs);
					}
					break;
				case 'price':
					$el = & new elFormContainer('c-'.$id, $v['label']);
					$el->setTpls($this->_tpls);
					$el->add(new elText('price[0]', m('from').':', '', $attrs));
					$el->add(new elText('price[1]', m('to').':',   '', $attrs));
					break;
				default:
					$opts = $this->_list(EL_IS_PROP, $v['prop_view'] == 'normal' ? $v['noselect_label'] : '', $v['prop_id']);
					if (empty($opts)) {
						unset($this->_conf[$id]);
						continue;
					} else {
						$this->_conf[$id]['opts'] = $opts;
						$params['class'] .= ' ishop-type-prop-'.$id.($v['display_on_load'] == 'no' ? ' hide' : '');
						if ($v['prop_view'] == 'normal') {
							$el = new elSelect('props['.$v['prop_id'].']', $v['label'], 0, $opts, $attrs);
						} else {
							$el = & new elFormContainer('c-'.$id, $v['label']);
							$el->setTpls($this->_tpls);
							$el->add(new elSelect('props-'.$v['prop_id'].'[0]', m('from').':', 0, $opts, $attrs));
							$el->add(new elSelect('props-'.$v['prop_id'].'[1]', m('to').':', array_pop(array_keys($opts)), $opts, $attrs));
						}
					}
					
			}
			if ($el) {
				$this->_form->add($el, $params);
				unset($el);
			}
		}
		$this->formIsSubmit = $this->_form->isSubmit();
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
	 * @param  string  $label  form label
	 * @param  string  $type   form view type - normal/advanced
	 * @return string
	 **/
	function formToHtml($label='', $type='normal') {
		$this->_form->setLabel($label);
		$this->_form->renderer->type = $type;
		return !empty($this->_conf) ? $this->_form->toHtml() : '';
	}
	
	/**
	 * find and return items ids
	 *
	 * @return array
	 **/
	function find() {
		$res   = array();
		$where = array();
		if (!$this->isConfigured() || !$this->formIsSubmit) {
			return $res;
		}
		$data = $this->_form->getValue();

		if (!empty($data['type'])) {
			$where[] = sprintf('type_id=%d', $data['type']);
		}
		if (!empty($data['mnf'])) {
			$where[] = sprintf('mnf_id=%d', $data['mnf']);
		}
		if (!empty($data['tm'])) {
			$where[] = sprintf('tm_id=%d', $data['tm']);
		}
		if (is_array($data['price']) && (!empty($data['price'][0]) || !empty($data['price'][1])) && ($data['price'][0] > 0 || $data['price'][1] > 1)) {
			$min = $data['price'][0] > 0 ? $data['price'][0] : 0;
			$max = $data['price'][1] > 0 ? $data['price'][1] : time();
			$where[] = sprintf('price BETWEEN %d AND %d', $min, $max);
		}

		if (!empty($where)) {
			$res = $this->_db->queryToArray(sprintf('SELECT id FROM %s WHERE %s', $this->_tb['item'], implode(' AND ', $where)), null, 'id');
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
					? sprintf('SELECT i_id FROM %s WHERE p_id=%d AND value IN (%s)', $this->_tb['p2i'], $id, implode(',', $ids))
					: sprintf('SELECT i_id FROM %s WHERE i_id IN (%s) AND p_id=%d AND value IN (%s)', $this->_tb['p2i'], implode(',', $res), $id, implode(',', $ids));
				if (false == ($res = $this->_db->queryToArray($sql, null, 'i_id'))) {
					return $res;
				}
			}
		}
		
		return $res;
	}
	
	/**
	 * return list of types/mnfs/tms or property values
	 *
	 * @param  int     $type    obj type
	 * @param  string  $default text for default (not selected) value
	 * @param  int     $propID  property id
	 * @return array
	 **/
	function _list($type, $default='', $propID=0) {
		$sql = 'SELECT t.id, t.name FROM %s as t, %s AS i WHERE i.%s=t.id GROUP BY t.id ORDER BY t.name';
		switch ($type) {
			case EL_IS_ITYPE:
				$sql = sprintf($sql, $this->_tb['type'], $this->_tb['item'], 'type_id');
				break;
			case EL_IS_MNF:
				$sql = sprintf($sql, $this->_tb['mnf'], $this->_tb['item'], 'mnf_id');
				break;
			case EL_IS_TM:
				$sql = sprintf($sql, $this->_tb['tm'], $this->_tb['item'], 'tm_id');
				break;
			case EL_IS_PROP:
				$sql = sprintf('SELECT id AS id, value AS name FROM %s WHERE p_id=%d ORDER BY name', $this->_tb['prop'], $propID);
				break;
		}
		if (false != ($opts = $this->_db->queryToArray($sql, 'id', 'name'))) {
			return $default ? array(m($default)) + $opts : $opts;
		}
	}
	
	/**
	 * return conf item by type
	 *
	 * @param  string  $type    item type
	 * @param  int     $propID  property id for prop type
	 * @return array
	 **/
	function _conf($type, $propID=0) {
		foreach ($this->_conf as $id=>$v) {
			if ($v['type'] == $type) {
				if ($type != 'prop') {
					return $v;
				} elseif ($v['prop_id'] == $propID) {
					return $v;
				}
			}
		}
	}
	
} // END class 

?>