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
		'type'   => 'el_ishop_%d_itype',
		'item'   => 'el_ishop_%d_item',
		'mnf'    => 'el_ishop_%d_mnf',
		'tm'     => 'el_ishop_%d_tm',
		'propn'  => 'el_ishop_%d_prop',
		'prop'   => 'el_ishop_%d_prop_value',
		'p2i'    => 'el_ishop_%d_p2i'
		);
	var $_conf    = array();
	// var $_propIDs = array();
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

		$this->_db = & elSingleton::getObj('elDb');
		$sql = sprintf('SELECT id, label, sort_ndx, type, prop_id, prop_view, noselect_label, display_on_load, position FROM %s ORDER BY sort_ndx, id', $this->_tb['search']);
		$this->_conf = $this->_db->queryToArray($sql, 'id');
		$this->_form = elSingleton::getObj('elForm', 'ishop-finder-form-'.$this->_pageID, '', $this->_url);
		$this->_form->setRenderer(elSingleton::getObj('elIShopFinderFormRenderer'));
		
		$types = array(
			'type' => EL_IS_ITYPE,
			'mnf'  => EL_IS_MNF,
			'tm'   => EL_IS_TM,
			// 'prop' => EL_IS_PROP
			);
		$props = array();
		
		foreach ($this->_conf as $id => $v) {
			if (empty($v['noselect_label'])) {
				$v['noselect_label'] = m('not selected');
			}
			
			if ($v['type'] == 'prop') {
				if (empty($v['prop_id'])) {
					unset($this->_conf[$id]);
					$this->rmField($id);
				} else {
					$props[$v['prop_id']] = array('type_id' => 0, 'prop_type' => 0, 'opts' => array());
				}
			} elseif ($v['type'] != 'price') {
				$this->_conf[$id]['opts'] = $this->_list($v['type'], $v['noselect_label']);
				if (empty($this->_conf[$id]['opts'])) {
					unset($this->_conf[$id]);
					$this->rmField($id);
				}
			}
		}
		
		if ($props) {
			$ids = implode(',', array_keys($props));
			$sql = sprintf('SELECT id, t_id, type FROM %s WHERE id IN (%s) AND (type="1" OR type="3")', $this->_tb['propn'], $ids);
			$this->_db->query($sql);
			while ($r = $this->_db->nextRecord()) {
				$props[$r['id']]['type_id'] = $r['t_id'];
				$props[$r['id']]['prop_type'] = $r['type'];
			}
			$this->_db->query(sprintf('SELECT id, p_id, value FROM %s WHERE p_id IN (%s) ORDER BY p_id, value', $this->_tb['prop'], $ids));
			while ($r = $this->_db->nextRecord()) {
				$props[$r['p_id']]['opts'][$r['id']] = $r['value'];
			}
			
			foreach ($this->_conf as $id=>$f) {
				if ($f['type'] == 'prop' && !empty($props[$f['prop_id']])) {
					$this->_conf[$id] = array_merge_recursive($this->_conf[$id], $props[$f['prop_id']]);
				}
			}
		}
		// elPrintr($this->_conf);
		foreach ($this->_conf as $id => $v) {
			$attrs  = array();
			$params = array('class' => 'elem-'.$v['type'].' view-'.$v['position'], 'rel' => $v['position']);

			switch ($v['type']) {
				case 'type':
					$el = new elSelect('type', $v['label'], 0, $v['opts'], $attrs);
					break;
				case 'mnf':
					$el = new elSelect('mnf', $v['label'], 0, $v['opts'], $attrs);
					break;
				case 'tm':
					$el = new elSelect('tm', $v['label'], 0, $v['opts'], $attrs);
					break;
				case 'price':
					$el = & new elFormContainer('c-'.$id, $v['label']);
					$el->setTpls($this->_tpls);
					$el->add(new elText('price[0]', m('from').':', '', $attrs+array('size' => '')));
					$el->add(new elText('price[1]', m('to').':',   '', $attrs+array('size' => '')));
					break;
				default:
					$attrs['el_itype'] = $v['type_id'];
					$params['class'] .= ' prop-'.$id;
					if ($v['position'] == 'normal' && $v['display_on_load'] == 'no') {
						$params['class'] .= ' hide';
					}
					if ($v['prop_type'] == 3) {
						if ($v['prop_view'] == 'normal') {
							$el = new elSelect('props-'.$v['prop_id'], $v['label'], 0, array(m($v['noselect_label']))+$v['opts'], $attrs);
						} else {
							$el = & new elFormContainer('c-'.$id, $v['label']);
							$el->setTpls($this->_tpls);
							$el->add(new elSelect('props-'.$v['prop_id'].'[0]', m('from').':', 0, $v['opts'], $attrs+array('size' => 10)));
							$el->add(new elSelect('props-'.$v['prop_id'].'[1]', m('to').':', array_pop(array_keys($v['opts'])), $v['opts'], $attrs));
						}
					} else {
						if ($v['prop_view'] == 'normal') {
							$el = new elText('props-'.$v['prop_id'], $v['label'], $attrs);
						} else {
							$el = & new elFormContainer('c-'.$id, $v['label']);
							$el->setTpls($this->_tpls);
							$el->add(new elText('props-'.$v['prop_id'].'[0]', m('from').':', '', $attrs));
							$el->add(new elText('props-'.$v['prop_id'].'[1]', m('to').':',   '', $attrs));
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
		// elPrintr($data);
		foreach ($data as $name=>$val) {
			// echo $name.' <br>';
			$id = (int)str_replace('props-', '', $name);
			$sql = '';
			if ($id>0 && false != ($p = $this->_conf('prop', $id))) {
				// elPrintr($p);
				
				if ($p['prop_type'] == 1) {
					
					if (is_array($val)) {
						if (empty($val[0]) && empty($val[1])) {
							continue;
						}
						$sql = empty($res) 
							? sprintf('SELECT i_id FROM %s WHERE p_id=%d ', $this->_tb['p2i'], $id)
							: sprintf('SELECT i_id FROM %s WHERE i_id IN (%s) AND p_id=%d ', $this->_tb['p2i'], implode(',', $res), $id);
							
						if (!empty($val[0])) {
							$sql .= sprintf('AND %.2f <= CONVERT(REPLACE(value, " ", ""), DECIMAL)', $val[0]);
						}
						if (!empty($val[1])) {
							$sql .= sprintf('AND %.2f >= CONVERT(REPLACE(value, " ", ""), DECIMAL)', $val[1]);
						}
					} elseif (!empty($val)) {
						$sql = empty($res) 
							? sprintf('SELECT i_id FROM %s WHERE p_id=%d AND value LIKE "%%%s%%"', $this->_tb['p2i'], $id, trim($val))
							: sprintf('SELECT i_id FROM %s WHERE i_id IN (%s) AND p_id=%d AND value LIKE "%%%s%%"', $this->_tb['p2i'], implode(',', $res), $id, trim($val));
					} else {
						continue;
					}

				} else {
					if (is_array($val)) {
						$propIDs = array_keys($p['opts']);
						$k1      = array_search($val[0], $propIDs);
						$k2      = array_search($val[1], $propIDs);
						$offset  = min($k1, $k2);
						$ids     = array_slice($propIDs, $offset, max($k1, $k2)-$offset+1);
					} elseif ($val > 0) {
						$ids = array((int)$val);
					} else {
						continue;
					}
				}

				if (!$sql) {
					$sql = empty($res)
						? sprintf('SELECT i_id FROM %s WHERE p_id=%d AND value IN (%s)', $this->_tb['p2i'], $id, implode(',', $ids))
						: sprintf('SELECT i_id FROM %s WHERE i_id IN (%s) AND p_id=%d AND value IN (%s)', $this->_tb['p2i'], implode(',', $res), $id, implode(',', $ids));
					
				}
				// echo $sql.'<br>';
				if (false == ($res = $this->_db->queryToArray($sql, null, 'i_id'))) {
					return $res;
				}
			}
		}
		
		return $res;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function getParams($name, $value) {
		$ret  = array();
		$type = $this->_conf('type');
		$mnf  = $this->_conf('mnf');
		$tm   = $this->_conf('tm');

		if ($name == 'type') {
			if ($mnf) {
				$ids = $value ? $this->_db->query(sprintf('SELECT mnf_id FROM %s WHERE type_id=%d GROUP BY mnf_id', $this->_tb['item'], $value), 'mnf_id', 'mnf_if') : $mnf['opts'];
				$ret['mnf'] = $this->_pack($mnf['opts'], $ids, $mnf['noselect_label']);
			}
			
			if ($tm) {
				$ids = $value ? $this->_db->query(sprintf('SELECT tm_id FROM %s WHERE type_id=%d GROUP BY tm_id', $this->_tb['item'],   $value), 'tm_id', 'tm_id') : $tm['opts'];
				$ret['tm'] = $this->_pack($tm['opts'], $ids, $tm['noselect_label']);
			}
			
			$ret['types'] = $value ? array((int)$value) : $this->_db->queryToArray('SELECT id FROM '.$this->_tb['type'], null, 'id');

		} elseif ($name == 'mnf') {
			if ($tm) {
				$ids = $value ? $this->_db->queryToArray(sprintf('SELECT id FROM %s WHERE mnf_id=%d GROUP BY id', $this->_tb['tm'], $value), 'id', 'id') : $tm['opts'];
				$ret['tm'] = $this->_pack($tm['opts'], $ids, $tm['noselect_label']);
			}
			$ret['types'] = $value 
				? $this->_db->queryToArray(sprintf('SELECT type_id FROM %s WHERE mnf_id=%d GROUP BY type_id', $this->_tb['item'], $value), null, 'type_id')
				: $this->_db->queryToArray('SELECT id FROM '.$this->_tb['type'], null, 'id');
		} elseif ($name == 'tm') {
			if ($value) {
				if (false != ($r = $this->_db->queryToArray(sprintf('SELECT mnf_id FROM %s WHERE id=%d', $this->_tb['tm'], $value)))) {
					$ret['mnfID'] = $r[0]['mnf_id'];
				}
				$ret['types'] = $this->_db->queryToArray(sprintf('SELECT type_id FROM %s WHERE tm_id=%d GROUP BY type_id', $this->_tb['item'], $value), null, 'type_id');
			}
		}

		return $ret;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function getConf() {
		return $this->_conf;
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function fieldExists($id) {
		return !empty($this->_conf[$id]);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function updateField($data) {
		$id       = !empty($data['id']) ? (int)$data['id'] : 0;
		$label    = isset($data['label']) ? mysql_real_escape_string(trim($data['label'])) : '';
		$type     = !empty($data['type']) && in_array($data['type'], array('type', 'price', 'mnf', 'tm', 'prop')) ? mysql_real_escape_string($data['type']) : 'price';
		$propID   = !empty($data['prop_id']) && $data['prop_id'] >0 ? (int)$data['prop_id'] : 0;
		$propView = !empty($data['prop_view']) && in_array($data['prop_view'], array('normal', 'period')) ? mysql_real_escape_string($data['prop_view']) : 'normal';
		$nsLabel  = !empty($data['noselect_label']) ? mysql_real_escape_string($data['noselect_label']) : '';
		$onLoad   = !empty($data['display_on_load']) && in_array($data['display_on_load'], array('yes', 'no')) ? mysql_real_escape_string($data['display_on_load']) : 'yes';
		$pos      = !empty($data['position']) && in_array($data['position'], array('normal', 'advanced')) ? mysql_real_escape_string($data['position']) : 'normal';

		if ($type == 'prop') {
			$pIDs = $this->_db->queryToArray('SELECT id FROM '.$this->_tb['propn'], 'id', 'id');
			if (!$pIDs[$propID]) {
				$propID = 0;
				$type = 'price';
			}
		} else {
			$propID = 0;
		}
		if ($this->fieldExists($id)) {
			$sql = 'UPDATE %s SET label="%s", type="%s", prop_id="%d", prop_view="%s", noselect_label="%s", display_on_load="%s", position="%s" WHERE id="%d" LIMIT 1';
			$sql = sprintf($sql, $this->_tb['search'], $label, $type, $propID, $propView, $nsLabel, $onLoad, $pos, $id);
		} else {
			$sortNdx = count($this->_conf)+1;
			$sql = 'INSERT INTO %s (label, sort_ndx, type, prop_id, prop_view, noselect_label, display_on_load, position) 
							VALUES ("%s",  %d,        "%s", %d,     "%s",      "%s",           "%s",            "%s")';
			$sql = sprintf($sql, $this->_tb['search'], $label, $sortNdx, $type, $propID, $propView, $nsLabel, $onLoad, $pos);
			
		}
		return $this->_db->query($sql);
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function rmField($id) {
		if (isset($this->_conf[$id])) {
			$this->_db->query(sprintf('DELETE FROM %s WHERE id=%d LIMIT 1', $this->_tb['search'], $id));
			$this->_db->optimizeTable($this->_tb['search']);
		}
	}
	
	/**
	 * Update sort_ndx field in table
	 *
	 * @return void
	 **/
	function updateSort($ndxs) {
		$i = 1;
		foreach ($ndxs as $id => $ndx) {
			if (isset($this->_conf[$id])) {
				$sql = sprintf('UPDATE %s SET sort_ndx=%d WHERE id=%d LIMIT 1', $this->_tb['search'], $i++, $id);
				$this->_db->query($sql);
			}
		}
	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Dmitry Levashov
	 **/
	function _pack($src, $ids, $default='') {
		$ret = array();
		foreach ($src as $id=>$name) {
			if (isset($ids[$id])) {
				$ret[] = array('id' => $id, 'name' => $name);
			}
		}
		if (!empty($ret)) {
			array_unshift($ret, array('id' => 0, 'name' => $default));
		}
		return $ret;
	}
	
	/**
	 * return list of types/mnfs/tms 
	 *
	 * @param  int     $type    obj type
	 * @param  string  $default text for default (not selected) value
	 * @return array
	 **/
	function _list($type, $default='') {
		$sql = 'SELECT t.id, t.name FROM %s as t, %s AS i WHERE i.%s=t.id GROUP BY t.id ORDER BY t.name';
		switch ($type) {
			case 'type':
				$sql = sprintf($sql, $this->_tb['type'], $this->_tb['item'], 'type_id');
				break;
			case 'mnf':
				$sql = sprintf($sql, $this->_tb['mnf'], $this->_tb['item'], 'mnf_id');
				break;
			case 'tm':
				$sql = sprintf($sql, $this->_tb['tm'], $this->_tb['item'], 'tm_id');
				break;
			default:
				return array();
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