<?php
include_once EL_DIR_CORE.'lib/elCatalogItem.class.php';

class elTSItem extends elCatalogItem
{
	var $ID         = 0;
	var $mnfID      = 0;
	var $code       = '';
	var $name       = '';
	var $announce   = '';
	var $descrip    = '';
	var $price      = 0;
	var $sortNdx    = 0;
	var $crtime      = 0;
	var $mnfName    = '';
	var $mnfCountry = '';
	var $parents    = array();
	var $models     = array();
	var $hasModels  = false;
	var $hasFeatures = false;
	var $hasFakePrice   = false;

	var $tbi2c      = '';
	var $tbm        = '';
	var $tbmnf      = '';
	var $tbftg      = '';
	var $tbft       = '';
	var $tbft2i     = '';
	var $tbft2m     = '';
	var $tbprice    = '';

	var $features   = array();
	var $fakePriceList  = array();

	function fetch()
	{
		if ($this->ID) {
			$db = & elSingleton::getObj('elDb');
			$db->query('SELECT '.$this->attrsToString('i').', m.name AS mnfName, m.country AS mnfCountry, md.id AS hasModels '
						.'FROM '.$this->_tb.' AS i LEFT JOIN ' .$this->tbmnf.' AS m ON m.id=i.mnf_id '
						.'LEFT JOIN '.$this->tbm.' AS md ON md.i_id=i.id '
						.'WHERE i.id='.$this->ID.' GROUP BY i.id ');
			if (false != ($r = $db->nextRecord()))
			{
				$this->attr($r);
				$this->hasModels  = $r['hasModels'] ? true : false;
				$this->mnfName    = $r['mnfName'];
				$this->mnfCountry = $r['mnfCountry'];
				return true;
			}
		}
	}

	function copy($attrs)
	{
		$copy = parent::copy($attrs);
		$copy->mnfName    = !empty($attrs['mnfName']) ? $attrs['mnfName'] : '';
		$copy->mnfCountry = !empty($attrs['mnfCountry']) ? $attrs['mnfCountry'] : '';
		return $copy;
	}

	function toArray()
	{
		$ret = parent::toArray();
		$ret['mnfName']    = $this->mnfName;
		$ret['mnfCountry'] = $this->mnfCountry;
		return $ret;
	}

	function getPriceList() {
		if ($this->fakePriceList) {
			return $this->fakePriceList;
		}
		$price = array();
		foreach ($this->models as $model) {
			$price[] = array(
				'modelID' => $model->ID,
				'name'    => trim($model->code.' '.$model->name),
				'price'   => $model->price
				);
		}
		return $price;
	}

	function delete($ref = null) {
		if ($this->ID) {
			parent::delete();
			$db = elSingleton::getObj('elDb');
			$db->query('DELETE FROM '.$this->tbft2i.' WHERE i_id="'.$this->ID.'"');
			$db->optimizeTable($this->tbft2i);
			if ($this->hasModels) {
				$mids = $db->queryToArray('SELECT id FROM '.$this->tbm.' WHERE i_id="'.$this->ID.'"', null, 'id');
				if ($mids) {
					$db->query('DELETE FROM '.$this->tbm.' WHERE i_id="'.$this->ID.'"');
					$db->optimizeTable($this->tbm);
					$db->query('DELETE FROM '.$this->tbft2m.' WHERE m_id IN ('.implode(',', $mids).')');
					$db->optimizeTable($this->tbm);
				}
			}
			return true;
		}
	}

	/**
	 * Edit/save item/models fratures
	 *
	 * @param  integer $catID  current category ID
	 * @return boolean
	 **/
	function setFeatures($catID) {
		if ($this->models) {
			$this->_makeModelsFeaturesForm($catID);
			if ($this->_form->isSubmitAndValid()) {
				$db = & elSingleton::getObj('elDb');
				$data = $this->_form->getValue();
				$an = isset($data['an']) && is_array($data['an']) ? $data['an'] : array();
				$sp = isset($data['sp']) && is_array($data['sp']) ? $data['sp'] : array();
				foreach ($data as $k=>$v) {
					if (is_array($data[$k]) && strpos($k, 'ft-') === 0) {
						$mid = str_replace('ft-', '', $k);
						$db->prepare('REPLACE INTO '.$this->tbft2m.' (m_id, ft_id, value, is_announced, is_split) VALUES ', '(%d, %d, "%s", "%d", "%d")');
						foreach ($v as $fid=>$val) {
							$db->prepareData(array($mid, $fid, mysql_real_escape_string($val), !empty($an[$fid]), !empty($sp[$fid])));
						}
						$db->execute();
					}
				}
				return true;
			}
		} else {
			$this->_makeFeaturesForm($catID);
			if ($this->_form->isSubmitAndValid()) {
				$data = $this->_form->getValue();
				$ft = isset($data['ft']) && is_array($data['ft']) ? $data['ft'] : array();
				$an = isset($data['an']) && is_array($data['an']) ? $data['an'] : array();
				$db = & elSingleton::getObj('elDb');
				foreach ($ft as $fid=>$val) {
					$sql = 'REPLACE INTO '.$this->tbft2i.' SET '
						.'i_id="'.$this->ID.'", '
						.'ft_id="'.$fid.'", '
						.'value="'.mysql_real_escape_string($val).'", '
						.'is_announced="'.intval(!empty($an[$fid])).'"';
					$db->query($sql);
				}
				return true;
			}
		}
	}

	/**
	 * Change features list
	 *
	 * @return void
	 **/
	function changeFeaturesList($groups)
	{
		$this->_makeSelectFeaturesForm($groups);
		if ($this->_form->isSubmitAndValid()) {
			$raw = $this->_form->getValue();
			$new = $current = array();
			if (is_array($raw['ftg'])) {
				foreach ($raw['ftg'] as $group) {
					foreach ($group as $fid) {
						$new[] = $fid;
					}
				}
			}
			foreach ($this->features as $group) {
				if (!empty($group['features'])) {
					$current = array_merge($current, array_keys($group['features']));
				}
			}
			
			if ($raw == $current) {
				return true;
			}
			
			$rm  = array_diff($current, $new);
			$add = array_diff($new, $current);
			$db  = elSingleton::getObj('elDb');
			
			if ($this->hasModels) {
				if ($rm) {
					$db->query('DELETE FROM '.$this->tbft2m.' WHERE m_id IN ('.implode(',', array_keys($this->models)).') AND ft_id IN ('.implode(',', $rm).')');
					$db->optimizeTable($this->tbft2m);
				}
				if ($add) {
					$db->prepare('REPLACE INTO '.$this->tbft2m.' (m_id, ft_id) VALUES ', '(%d, %d)');
					foreach ($this->models as $mid=>$model) {
						foreach ($add as $fid) {
							$db->prepareData(array($mid, $fid));
						}
					}
					$db->execute();
				}
			} else {
				if ($rm) {
					$db->query('DELETE FROM '.$this->tbft2i.' WHERE i_id="'.$this->ID.'" AND ft_id IN ('.implode(',', $rm).')');
					$db->optimizeTable($this->tbft2i);
				}
				if ($add) {
					$db->prepare('REPLACE INTO '.$this->tbft2i.' (i_id, ft_id) VALUES ', '(%d, %d)');
					foreach ($add as $fid) {
						$db->prepareData(array($this->ID, $fid));
					}
					$db->execute();
				}
			}
			return true;
		}
	}

	/**
	 * Change item(s) image
	 *
	 * @return boolean
	 **/
	function changeModelImage( $mID, $tmbSize )
	{
		$this->_makeModelImgForm($mID);

		if ( $this->_form->isSubmitAndValid() )
		{
			$db      = &elSingleton::getObj('elDb');
			$data    = $this->_form->getValue();
			if (empty($data['mIDs']) || !is_array($data['mIDs'])) {
				return true;
			}

			$img = str_replace(EL_BASE_URL, '', $data['imgURL']);
			$img = '/' != DIRECTORY_SEPARATOR ? str_replace('/', DIRECTORY_SEPARATOR, $img) : $img;

			if ($img) {
				$tmb = dirname($img).DIRECTORY_SEPARATOR.'mini_'.basename($img);
				if (!file_exists('.'.$tmb) || filemtime('.'.$tmb) < filemtime('.'.$img)) {
					$image = & elSingleton::getObj('elImage');
					if (!$image->tmb('.'.$img, '.'.$tmb, $tmbSize, ceil($tmbSize/(4/3))))
					{
						return elThrow(E_USER_WARNING, $image->error);
					}
				}
			}
			foreach ($data['mIDs'] as $id) {
				$db->query(sprintf('UPDATE %s SET img="%s" WHERE id="%d"', $this->tbm, mysql_real_escape_string($img), $id));
			}
			return true;
		}
	}

 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

	function _initMapping()
	{
		return array(
			'id'       => 'ID',
			'mnf_id'   => 'mnfID',
			'code'     => 'code',
			'name'     => 'name',
			'announce' => 'announce',
			'descrip'  => 'descrip',
			'crtime'   => 'crtime',
			'price'    => 'price'
			);
	}

	/**
	 * Create edit item form
	 *
	 * @param  array $parents  parents categories id's
	 * @return void
	 **/
	function _makeForm( $parents )
	{
		parent::_makeForm($parents);
		$db  = & elSingleton::getObj('elDb');
		$mnfsList = $db->queryToArray('SELECT id, name FROM '.$this->tbmnf.' ORDER BY name', 'id', 'name');
		$this->_form->add( new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $mnfsList) );
		$this->_form->add( new elText('code', m('Code'), $this->code, array('style'=>'width:100%;')) );
		if (!$this->hasModels)
		{
		  $this->_form->add( new elText('price', m('Price'), $this->price) );
		}
		$this->_form->add( new elEditor('announce', m('Announce'),    $this->announce, array('class' => 'small')) );
		$this->_form->add( new elEditor('descrip',  m('Description'), $this->descrip) );
		$this->_form->add( new elDateSelector('crtime', m('Publish date'), $this->crtime) );
	}

	/**
	 * Create form for editing item own features
	 *
	 * @param  Integer $catID  current category ID
	 * @return void
	 **/
	function _makeFeaturesForm($catID) {
		$this->_form = & elSingleton::getObj( 'elForm', 'mf' );
		$rnd = & elSingleton::getObj('elTplGridFormRenderer', 3);
		$rnd->addButton( new elSubmit('s', '', m('Submit'), array('class'=>'submit')));
		$rnd->addButton( new elReset('r', '',  m('Drop'),   array('class'=>'submit')));
		$this->_form->setRenderer( $rnd );
		$this->_form->setLabel( sprintf(m('Set features for "%s %s"'), $this->code, $this->name ) );
		$this->_form->add( new elCData('f', m('Features')) );
		$this->_form->add( new elCData('a', m('Announced')) );
		$this->_form->add( new elCData('v', m('Values')) );
		foreach ($this->features as $gid => $group) {
			$this->_form->add( new elCData('gr_'.$gid, $group['name']), array('colspan'=>3, 'class'=>'form_subheader') );
			foreach ($group['features'] as $fid=>$f) {
				$this->_form->add( new elCData('ft_'.$fid, $f['name'].' '.$f['unit']) );
				$attrs = $f['is_announced'] ? array('checked' => 'true') : array();
				$attrs['title'] = m('Check if You want display this feature in items list');
				$this->_form->add( new elCheckBox('an['.$fid.']', '', 1, $attrs) );
				$this->_form->add( new elText('ft['.$fid.']', '', $f['value'], array('style' => 'width:100%')) );
			}
		}
		$url = '<a href="{URL}item_ft/'.$catID.'/'.$this->ID.'/" class="link forward2">'.m('Change features list').'</a>';
		$this->_form->add( new elCData('change', $url), array('colspan'=>3) );
	}

	/**
	 * Create form for editing item models features
	 *
	 * @param  Integer $catID  current category ID
	 * @return void
	 **/
	function _makeModelsFeaturesForm($catID) {
		$cols = sizeof($this->models)+3;
		$this->_form = & elSingleton::getObj( 'elForm', 'mf' );
		$rnd = & elSingleton::getObj('elTplGridFormRenderer', $cols);
		$rnd->addButton( new elSubmit('s', '', m('Submit'), array('class'=>'submit')));
		$rnd->addButton( new elReset('r', '',  m('Drop'),   array('class'=>'submit')));
		$this->_form->setRenderer( $rnd );
		$this->_form->setLabel( sprintf(m('Set features for "%s %s"'), $this->code, $this->name ) );
		$this->_form->setLabel( sprintf(m('Set features for "%s %s"'), $this->code, $this->name ) );
		$this->_form->add( new elCData('f', m('Features')) );
		$this->_form->add( new elCData('a', m('Announced')) );
		$this->_form->add( new elCData('sp', m('&lt;-&gt;')) );
		foreach ( $this->models as $mid => $model)
		{
			$this->_form->add( new elCData('t_'.$model->ID, $model->code.' '.$model->name) );
		}
		$gattrs = array('colspan'=>$cols, 'class'=>'form_subheader');
		foreach ($this->features as $gid => $group) {
			$this->_form->add( new elCData('gr_'.$gid, $group['name']), $gattrs );
			foreach ($group['features'] as $fid => $f) {
				$this->_form->add( new elCData('ft_'.$fid, $f['name'].' '.$f['unit']) );
				$attrs = $f['is_announced'] ? array('checked' => 'true') : array();
				$attrs['title'] = m('Check if You want display this feature in items list');
				$this->_form->add( new elCheckBox('an['.$fid.']', '', 1, $attrs) );
				$attrs = $f['is_split'] ? array('checked' => 'true') : array();
				$attrs['title'] = m('Check to use one value for all fields');
				$this->_form->add( new elCheckBox('sp['.$fid.']', '', 1, $attrs) );
				foreach ($this->models as $model) {
					$this->_form->add( new elText('ft-'.$model->ID.'['.$fid.']', '', isset($model->features[$fid]) ? $model->features[$fid] : '', array('style' => 'width:100%')) );
				}
			}
		}
		$url = '<a href="{URL}item_ft/'.$catID.'/'.$this->ID.'/" class="link forward2">'.m('Change features list').'</a>';
		$this->_form->add( new elCData('change', $url), array('colspan'=>3) );
	}

	/**
	 * Create form for selecting features
	 *
	 * @return void
	 **/
	function _makeSelectFeaturesForm($groups)
	{
		$this->_form = & elSingleton::getObj( 'elForm', 'mf'.get_class($this),  sprintf( m(!$this->{$this->__id__} ? 'Create object "%s"' : 'Edit object "%s"'), m($this->_objName))  );
		$this->_form->setRenderer( elSingleton::getObj($this->_formRndClass) );
		foreach ($groups as $gid=>$group) {
			$features = array();
			$selected = array();
			foreach ($group->features as $fid=>$f) {
				$features[$fid] = $f->name.' '.$f->unit;
				if (isset($this->features[$gid]['features'][$fid])) {
					$selected[] = $fid;
				}
			}
			$this->_form->add(new elMultiSelectList('ftg['.$gid.']', $group->name, $selected, $features));
		}
		
		
	}

	/**
	 * Create form for changing image
	 *
	 * @return void
	 **/
	function _makeModelImgForm($mID)
	{
	  $this->_form = & elSingleton::getObj( 'elForm', 'mf'  );
	  $this->_form->setRenderer( elSingleton::getObj($this->_formRndClass) );
	  $this->_form->setLabel( sprintf(m('%s %s - set image for models'), $this->code, $this->name) );
	  $targets = array('all'=>m('All models'));
	  foreach ($this->models as $m)
	  {
	     $targets[$m->ID] = $m->code.' '.$m->name;
	  }
	  $msl = & new elMultiSelectList('mIDs', m('Models'), array($mID), $targets);
	  $msl->setSwitchValue('all');
	  $this->_form->add( $msl );
		$this->_form->setRequired('mIDs[]');
		elLoadJQueryUI();
		elAddCss('elfinder.css', EL_JS_CSS_FILE);
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.form.min.js', EL_JS_CSS_FILE);
		elAddJs('elfinder.min.js', EL_JS_CSS_FILE);

		if (file_exists(EL_DIR.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'elfinder'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.EL_LANG.'.js'))
		{
			elAddJs('elfinder'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR.EL_LANG.'.js', EL_JS_CSS_FILE);
		}
	
		$this->_form->add(new elHidden('imgURL', '', $this->models[$mID]->img ? EL_BASE_URL.$this->models[$mID]->img : '') );
		$this->_form->add(new elCData('img',  "<a href='#' class='link link-image' id='ishop-sel-img'>".m('Select or upload image file')."</a>"));
		$this->_form->add(new elCData('rm',   "<a href='#' class='link link-delete' id='ishop-rm-img'>".m('Delete image')."</a> "));
		$this->_form->add(new elCData('prev', "<fieldset id='ishop-sel-prev'><legend>".m('Preview')."</legend></fieldset>"));
		
		$js = "
		$('#ishop-sel-img').click(function(e) {
			e.preventDefault();
			$('<div />').elfinder({
				url  : '".EL_URL."__finder__/', 
				lang : '".EL_LANG."', 
				editorCallback : function(url) { $('#imgURL').val(url).trigger('change');}, 
				dialog : { width : 750, modal : true}});
		});
		$('#ishop-rm-img').click(function(e) {
			e.preventDefault();
			$('#imgURL').val('').parents('form').submit();
		});
		$('#imgURL').bind('change', function() {
			var p = $('#ishop-sel-prev').empty();
			if (this.value) {
				var pw = p.width();
				var img = $('<img />').attr('src', this.value).load(function() {
					var w = parseInt($(this).css('width'));
					if (w>=pw) $(this).css('width', (pw-10)+'px')
				})
				p.append(img);
			}
			// this.value && p.append($('<img />').attr('src', this.value));
		}).trigger('change');
		";
		// elAddJs($js, EL_JS_SRC_ONREADY);
	}
	

}
?>