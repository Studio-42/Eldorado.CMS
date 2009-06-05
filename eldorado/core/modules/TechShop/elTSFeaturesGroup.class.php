<?php

class elTSFeaturesGroup extends elMemberAttribute
{
	var $ID       = 0;
	var $name     = '';
	var $sortNdx  = 0;
	var $_objName = 'Features group';
	var $features = array();

	function getFeatures()
	{
		return $this->features;
	}

	function getFeaturesNames()
	{
		$ret = array();
		foreach ($this->features as $f)
		{
			$ret[$f->ID] = $f->name.' ('.$f->unit.')';
		}
		return $ret;
	}

	function setFeature($ft, $isAnn=false, $isSplit=false)
	{
		if (empty($this->features[$ft->ID]))
		{
			$ft->isAnn = $isAnn;
			$ft->isSplit = $isSplit;
			$this->features[$ft->ID] = $ft;
		}
	}

	function setFeatureValue($ftID, $vID, $val)
	{
		if (!empty($this->features[$ftID]))
		{
			$this->features[$ftID]->setValue($vID, $val);
		}
	}

	function setItemFeatureValue($ftID, $iID, $val)
	{
		if (!empty($this->features[$ftID]))
		{
			$this->features[$ftID]->setItemValue($iID, $val);
		}
	}

	function setModelFeatureValue($ftID, $mID, $val)
	{
		if (!empty($this->features[$ftID]))
		{
			$this->features[$ftID]->setModelValue($mID, $val);
		}
	}


	function getCollection($field=null, $orderBy=null, $offset=0, $limit=0, $where=null )
	{
		$orderBy = 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name';
		$coll    = parent::getCollection(null, $orderBy, $offset, $limit, $where);
		if (!empty($coll))
		{
			$db      = & elSingleton::getObj('elDb');
			$factory = & elSingleton::getObj('elTSFactory');
			$ft      = $factory->create(EL_TS_FT);
			$sql     = 'SELECT '.$ft->listAttrsToStr().' FROM '.$ft->tb
								.' ORDER BY IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name';
			$db->query($sql);
			while ($r = $db->nextRecord())
			{
				if (!empty($coll[$r['gid']]))
				{
					$coll[$r['gid']]->setFeature( $ft->copy($r) );
				}
			}
		}
		return $coll;
	}

	function isEmpty()
	{
		if ($this->ID)
		{
			$db = & elSingleton::getObj('elDb');
			$db->query('SELECT id FROM '.$this->tbft.' WHERE gid=\''.$this->ID.'\'');
			return !$db->numRows();
		}
		return true;
	}

	function makeForm()
	{
		parent::makeForm();
		$this->form->add( new elText('name', m('Name'), $this->getAttr('name')) );
	}


	function sortGroups()
	{
	  parent::makeForm();
	  $this->form->setLabel( m('Sort features groups') );
    $db   = & elSingleton::getObj('elDb');
		$ftgs = parent::getCollection(null, 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name');

    foreach ( $ftgs as $ftg )
    {
      $this->form->add( new elText('ftgs_sort['.$ftg->ID.']', $ftg->name, $ftg->sortNdx) );
    }
    if (!$this->form->isSubmitAndValid() )
    {
      return false;
    }

    $data = $this->form->getValue();
    $data = $data['ftgs_sort'];
    $tpl = 'UPDATE '.$this->tb.' SET sort_ndx=%d WHERE id=%d';
    foreach ($data as $ID=>$sortNdx)
    {
      $db->query( sprintf($tpl, $sortNdx, $ID) );
    }
    return true;
	}

	function sortFeatures()
	{
	  parent::makeForm();
	  $this->form->setLabel( sprintf( m('Sort features in group "%s"'), $this->name) );
	  $db      = & elSingleton::getObj('elDb');
    $factory = & elSingleton::getObj('elTSFactory');
		$ftObj   = $factory->create(EL_TS_FT);
		$fts     = $ftObj->getCollection(null, 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), name', 0, 0, 'gid='.$this->ID);

    foreach ( $fts as $ft )
    {
      $this->form->add( new elText('fts_sort['.$ft->ID.']', $ft->name, $ft->sortNdx) );
    }

    if ($this->form->isSubmitAndValid() )
    {
      $data = $this->form->getValue();
      $data = $data['fts_sort'];
      $tpl = 'UPDATE '.$ftObj->tb.' SET sort_ndx=%d WHERE id=%d';
      foreach ($data as $ID=>$sortNdx)
      {
        $db->query( sprintf($tpl, $sortNdx, $ID) );
      }
      return true;
    }
    return false;
	}

	function _initMapping()
	{
		return array('id' => 'ID', 'name' => 'name', 'sort_ndx' => 'sortNdx');
	}

}

?>