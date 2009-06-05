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
	var $mTime      = 0;
	var $mnfName    = '';
	var $mnfCountry = '';
	var $parents    = array();
	var $models     = array();

	var $tb         = '';
	var $tbi2c      = '';
	var $tbm        = '';
	var $tbmnf      = '';
	var $tbftg      = '';
	var $tbft       = '';
	var $tbft2i     = '';
	var $tbft2m     = '';

	var $ft         = array();

	function getModels()
	{
		if (empty($this->models) && $this->ID)
		{
			$factory = & elSingleton::getObj('elTSFactory');
			$this->models = $factory->getItemModels($this->ID);
		}
		return $this->models;
	}

	function getFt()
	{
		if ($this->ID  && empty($this->ft))
		{
			$factory = & elSingleton::getObj('elTSFactory');
			$this->ft = !$this->hasModels()
				? $factory->getItemFt($this->ID)
				: $factory->getModelsFt(array_keys($this->models));
		}
		return $this->ft;
	}

	function hasModels()
	{
		$this->getModels();
		return !empty($this->models);
	}

	function isModelExists($mID)
	{
	   $this->getModels();
	   return !empty($this->models[$mID]);
	}

	function fetch()
	{
		if (!$this->ID)
		{
			return false;
		}
		$db = & elSingleton::getObj('elDb');

		$sql = 'SELECT '.$this->listAttrsToStr('i').', m.name AS mnfName, m.country AS mnfCountry '
					.'FROM '.$this->tb.' AS i LEFT JOIN ' .$this->tbmnf.' AS m ON m.id=i.mnf_id '
					.'WHERE i.id='.$this->ID;
		$db->query($sql);
		if ($db->numRows())
		{
			$r = $db->nextRecord();
			$this->setAttrs($r);
			$this->mnfName    = $r['mnfName'];
			$this->mnfCountry = $r['mnfCountry'];
		}
		return true;
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

	function setFt($catID)
	{
		return !$this->hasModels()
			? $this->_setItemFt($catID)
			: $this->_setModelsFt($catID);
	}


	function changeFtList(  )
	{
		return !$this->hasModels()
			? $this->_changeItemFtList()
			: $this->_changeModelsFtList();
	}


	function deleteModel( &$model )
	{
	   if ( !$model->ID )
	   {
	      return;
	   }
	   $model->delete();
	   $db = & elSingleton::getObj('elDb');
	   $db->query( 'DELETE FROM '.$this->tbft2m.' WHERE m_id=\''.$model->ID.'\' ' );
	   $db->optimizeTable( $this->tbft2m );
	}


	function makeForm( $parents )
	{
		parent::makeForm($parents);
		$this->form->add( new elSelect('mnf_id', m('Manufacturer'), $this->mnfID, $this->_getMnflist()) );
		$this->form->add( new elText('code', m('Code'), $this->code, array('style'=>'width:100%;')) );
		if (!$this->hasModels())
		{
		  $this->form->add( new elText('price', m('Price'), $this->price) );
		}
		$this->form->add( new elEditor('announce', m('Announce'),    $this->announce, array('rows'=>'300')) );
		$this->form->add( new elEditor('descrip',  m('Description'), $this->descrip) );
		$this->form->add( new elDateSelector('crtime', m('Publish date'), $this->crTime) );
	}

	function _makeModelImgForm($mID)
	{
	  $this->form = & elSingleton::getObj( 'elForm', 'mf'  );
	  $this->form->setRenderer( elSingleton::getObj($this->_formRndClass) );
	  $this->form->setLabel( sprintf(m('%s %s - set image for models'), $this->code, $this->name) );
	  $targets = array('all'=>m('All models'));
	  foreach ($this->models as $m)
	  {
	     $targets[$m->ID] = $m->code.' '.$m->name;
	  }
	  $msl = & new elMultiSelectList('mIDs', m('Models'), array($mID), $targets);
	  $msl->setSwitchValue('all');
	  $this->form->add( $msl );
	  //$this->form->setRequired('mIDs[]');
	  $attrs = array('onClick'=>'return popUp(\''.EL_BASE_URL.'/'.EL_URL_POPUP.'/__fm__/'.'\', 500, 400)');
	  $this->form->add( new elSubmit('s', m('Select or upload image file'), m('Open file manager'), $attrs) );
	  $imgURL = $this->models[$mID]->img;
	  $imgHtml = ' ';
	  if (!empty($imgURL))
	  {
	     $imgURL = EL_BASE_URL.$imgURL;
	     $imgHtml = '<img src="'.$imgURL.'" />';
	     $this->form->add( new elCheckBox('rm', m('Delete image'), 1, array('onClick'=>'updatePreview(this.checked?1:0);')));
	  }
	  $this->form->add( new elHidden('imgURL',     '', $imgURL) );
	  $this->form->add( new elHidden('imgURLSave', '', $imgURL) );
	  $this->form->add( new elCData('i', '<div id="imgPrew" align="center">'.$imgHtml.'</div>'));
	}

	function changeModelImage( $mID, $tmbSize )
	{
	  $this->_makeModelImgForm($mID);

	  if ( !$this->form->isSubmitAndValid() )
	  {
	     return false;
	  }

	  $db      = &elSingleton::getObj('elDb');
	  $data    = $this->form->getValue();

	  unset($data['mIDs'][0]);
	  if ( !in_array($mID, $data['mIDs']) )
	  {
	     $db->query('UPDATE '.$this->tbm.' SET img="" WHERE id='.(int)$mID);
	  }
	  if ( empty($data['mIDs']) )
	  {
	    return true;
	  }
    foreach ($data['mIDs'] as $k=>$v)
    {
      $data['mIDs'][$k] = (int)$v;
    }
	  $img = '';
	  if ( !empty($data['imgURL']) )
	  {
	     $img    = str_replace(EL_BASE_URL, '', $data['imgURL']);
	     $tmb    = dirname($img).'/mini_'.basename($img);
	     $imager = & elSingleton::getObj('elImager');
	     if (!$imager->copyResized('.'.$img, '.'.$tmb, 100, 100))
	     {
	        return elThrow(E_USER_WARNING, $imager->getError());
	     }
	  }
	  $sql = 'UPDATE '.$this->tbm.' SET img=\''.$img.'\' WHERE i_id=\''.$this->ID.'\' AND id IN('.implode(',', $data['mIDs']).')';
	  $db->query($sql);
	  return true;
	}

 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

	function _initMapping()
	{
		$map = array(
			'id'       => 'ID',
			'mnf_id'   => 'mnfID',
			'code'     => 'code',
			'name'     => 'name',
			'announce' => 'announce',
			'descrip'  => 'descrip',
			'crtime'   => 'crtime',
			'price'    => 'price'
			);
		return $map;
	}

	function _getMnfList()
	{
		$db  = & elSingleton::getObj('elDb');
		$sql = 'SELECT id, name FROM '.$this->tbmnf.' ORDER BY name';
		return $db->queryToArray($sql, 'id', 'name');
	}

	function _setItemFt($catID)
	{
		$this->getFt();
		$this->form = & elSingleton::getObj( 'elForm', 'mf' );
		$rnd = & elSingleton::getObj('elTplGridFormRenderer', 3);
		$rnd->addButton( new elSubmit('s', '', m('Submit'), array('class'=>'submit')));
		$rnd->addButton( new elReset('r', '',  m('Drop'),   array('class'=>'submit')));
		$this->form->setRenderer( $rnd );
		$this->form->setLabel( sprintf(m('Set features for "%s %s"'), $this->code, $this->name ) );
		$this->form->add( new elCData('f', m('Features')) );
		$this->form->add( new elCData('a', m('Announced')) );
		$this->form->add( new elCData('v', m('Values')) );

		$grAttrs = array('colspan'=>3, 'class'=>'form_subheader');

		foreach ( $this->ft as $group )
		{
			$this->form->add( new elCData('gr_'.$group->ID, $group->name), $grAttrs );
			foreach ( $group->features as $ft )
			{
				$this->form->add( new elCData('ft_'.$ft->ID, $ft->name.' ('.$ft->unit.')') );
				$annAttrs = $ft->isAnn ? array('checked'=>'on') : array();
				$annAttrs['title'] = m('Check if You want display this feature in items list');
				$this->form->add( new elCheckBox('ann['.$ft->ID.']', '', 1, $annAttrs) );
				$this->form->add( new elText('ft['.$ft->ID.']', '', $ft->getItemValue($this->ID)) );
			}
		}
		$url = '<a href="{URL}item_ft/'.$catID.'/'.$this->ID.'/">'.m('Change features list').'</a>';
		$this->form->add( new elCData('change', $url), array('colspan'=>3) );

		if ($this->form->isSubmitAndValid())
		{
			$data = $this->form->getValue();
			if (!empty($data['ft']))
			{
				$db = &elSingleton::getObj('elDb');
				foreach ($data['ft'] as $ID=>$val)
				{
					$sql = 'UPDATE '.$this->tbft2i
								.' SET value=\''.$val.'\', '
								.'is_announced=\''.(empty($data['ann'][$ID]) ? '0' : '1').'\' '
								.'WHERE i_id=\''.$this->ID.'\' AND ft_id=\''.$ID.'\'';
					$db->query($sql);
				}
			}
			return true;
		}
	}

	function _setModelsFt($catID)
	{
		$this->getModels();
		$cols = sizeof($this->models)+3;
		$this->getFt();
		$this->form = & elSingleton::getObj( 'elForm', 'mf' );
		$rnd = & elSingleton::getObj('elTplGridFormRenderer', $cols);
		$rnd->addButton( new elSubmit('s', '', m('Submit'), array('class'=>'submit')));
		$rnd->addButton( new elReset('r',  '', m('Drop'),   array('class'=>'submit')));
		$this->form->setRenderer( $rnd );
		$this->form->setLabel( sprintf(m('Set features for "%s %s"'), $this->code, $this->name ) );

		$this->form->add( new elCData('f', m('Features')) );
		$this->form->add( new elCData('a', m('Announced')) );
		$this->form->add( new elCData('sp', m('<span style="white-space:nowrap"><-></span>')) );
		foreach ( $this->models as $m)
		{
			$this->form->add( new elCData('t_'.$m->ID, $m->code.' '.$m->name) );
		}
		$grAttrs = array('colspan'=>$cols, 'class'=>'form_subheader');
		$ftAttrs = array('size'=>16);
		foreach ( $this->ft as $group)
		{
			$this->form->add( new elCData('gr_'.$group->ID, $group->name), $grAttrs );

			foreach ( $group->features as $ft)
			{ //elPrintR($ft);
				$this->form->add( new elCData('ft_'.$ft->ID, $ft->name) );
				$annAttrs = $ft->isAnn ? array('checked'=>'on') : array();
				$annAttrs['title'] = m('Check if You want display this feature in items list');
				$this->form->add( new elCheckBox('ann['.$ft->ID.']', '', 1, $annAttrs) );
				$splitAttrs = $ft->isSplit ? array('checked'=>'on') : array();
				$this->form->add( new elCheckBox('split['.$ft->ID.']', '', 1, $splitAttrs) );

				foreach ( $this->models as $m)
				{
					$this->form->add( new elText('ft_'.$ft->ID.'['.$m->ID.']', '', $ft->getModelValue($m->ID), $ftAttrs) );
				}
			}
		}

		$url = '<a href="{URL}item_ft/'.$catID.'/'.$this->ID.'/">'.m('Change features list').'</a>';
		$this->form->add( new elCData('url', $url), array('colspan'=>$cols) );

		if ( $this->form->isSubmitAndValid() )
		{
			$db   = & elSingleton::getObj('elDb');
			$data = $this->form->getValue(); //elPrintR($data); return;
			$db->prepare('REPLACE INTO '.$this->tbft2m.' (m_id, ft_id, value, is_announced, is_split) VALUES ', '(%d, %d, "%s", "%s", "%s")');

			$ftIDs = $this->_getFtIDs();
			foreach ($ftIDs as $ftID)
			{
				if (!empty($data['ft_'.$ftID]) && is_array($data['ft_'.$ftID]))
				{
					$isAnn   = empty($data['ann'][$ftID]) ? '0' : '1';
					$isSplit = empty($data['split'][$ftID]) ? '0' : '1';
					foreach ($data['ft_'.$ftID] as $mID=>$ftValue)
					{
						$db->prepareData( array($mID, $ftID, $ftValue, $isAnn, $isSplit));
					}
				}
			}

			$db->execute();
			return true;
		}
	}
	function _getFtIDs()
	{
		$ret = array();
		foreach ($this->ft as $ftg)
		{
			$ret = array_merge($ret, array_keys($ftg->features));
		}
		return $ret;
	}

	function _changeItemFtList()
	{
		$this->getFt();
		$factory  = & elSingleton::getObj('elTSFactory');
		$ftg      = $factory->create(EL_TS_FTG);
		$ftgList  = $ftg->getCollection();  //elPrintR($ftgList);

		$this->form = & elSingleton::getObj( 'elForm', 'mf')  ;
		$this->form->setRenderer( elSingleton::getObj($this->_formRndClass) );
		$this->form->setLabel( sprintf(m('Change features list for "%s %s"'), $this->code, $this->name ) );
		foreach ($ftgList as $group)
		{
			$this->form->add( new elCData('g_'.$group->ID, $group->name ), array('cellAttrs'=>'style="font-weight:bold"')  );

			$val = !empty($this->ft[$group->ID]) ? array_keys($this->ft[$group->ID]->features) : null;
			if ( false != ($fts = $group->getFeaturesNames()))
			{
				$this->form->add( new elCheckBoxesGroup('ft['.$group->ID.']', '', $val, $fts) );
			}
		}

		if ($this->form->isSubmitAndValid())
		{
			$oldFtIDs = $this->_getFtIDs();
			$newFtIDs = array();
			$raw      = $this->form->getValue(); //elPrintR($raw);
			foreach( $raw['ft'] as $group)
			{
				$newFtIDs = array_merge($newFtIDs, $group);
			}

			$add = array_diff( $newFtIDs, $oldFtIDs);
			$rm  = array_diff( $oldFtIDs, $newFtIDs );

			$db  = & elSingleton::getObj('elDb');
			if (!empty($add))
			{
				$db->prepare('REPLACE INTO '.$this->tbft2i.' (i_id, ft_id) VALUES ', '(%d, %d)');
				foreach ($add as $ftID)
				{
					$db->prepareData( array($this->ID, $ftID) );
				}
				$db->execute();
			}
			if (!empty($rm))
			{
				$sql = 'DELETE FROM '.$this->tbft2i.' WHERE i_id=\''.$this->ID.'\' AND ft_id IN ('.implode(',', $rm).')';
				$db->query($sql);
				$db->optimizeTable($this->tbft2i);
			}
			//elPrintR($add); elPrintR($rm);
			return true;
		}

	}

	function _changeModelsFtList()
	{
		$factory  = & elSingleton::getObj('elTSFactory');
		$this->ft = $factory->getModelsFt( array_keys($this->getModels() )); //elPrintR($this->ft);
		$ftg      = $factory->create(EL_TS_FTG);
		$ftgList  = $ftg->getCollection();  //elPrintR($ftgList);

		$this->form = & elSingleton::getObj( 'elForm', 'mf')  ;
		$this->form->setRenderer( elSingleton::getObj($this->_formRndClass) );
		$this->form->setLabel( sprintf(m('Change features list for "%s %s"'), $this->code, $this->name ) );
		foreach ($ftgList as $group)
		{
			$this->form->add( new elCData('g_'.$group->ID, $group->name ), array('cellAttrs'=>'style="font-weight:bold"')  );
			$val = !empty($this->ft[$group->ID]) ? array_keys($this->ft[$group->ID]->features) : null;
			if ( false != ($fts = $group->getFeaturesNames()))
			{
				$this->form->add( new elCheckBoxesGroup('ft['.$group->ID.']', '', $val, $fts) );
			}
		}

		if ($this->form->isSubmitAndValid())
		{
			$oldFtIDs = $this->_getFtIDs();
			$newFtIDs = array();
			$raw      = $this->form->getValue(); //elPrintR($raw);
			foreach( $raw['ft'] as $group)
			{
				$newFtIDs = array_merge($newFtIDs, $group);
			}

			$add = array_diff( $newFtIDs, $oldFtIDs); //elPrintR($add);
			$rm  = array_diff( $oldFtIDs, $newFtIDs );// elPrintR($rm);
			$db  = & elSingleton::getObj('elDb');
			if ($add)
			{
				$db->prepare('REPLACE INTO '.$this->tbft2m.' (m_id, ft_id) VALUES ', '(%d, %d)');
				foreach ($this->models as $mID=>$m)
				{
					foreach ($add as $ftID)
					{
						$db->prepareData( array($mID, $ftID) );
					}
				}
				$db->execute();
			}

			if ($rm)
			{
				$sql = 'DELETE FROM '.$this->tbft2m.' WHERE m_id IN ('.implode(',', array_keys($this->models)).') AND ft_id IN ('.implode(',', $rm).')';
				$db->query($sql);
				$db->optimizeTable($this->tbft2m);
			}
			return true;
		}
	}


}
?>