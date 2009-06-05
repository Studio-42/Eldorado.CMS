<?php

class elCatalogItem extends elMemberAttribute
{
  var $tbi2c     = '';
  var $ID        = 0;
  var $name      = '';
  var $crTime    = 0;
  var $parents   = array();
  var $_objName  = 'Document';
  var $_sortVars = array('name', 'crtime DESC, name');

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  function getName()
  {
    return $this->getAttr('name');
  }

  /**
   * Returns items from category
   */
  function getByCategory( $catID, $sortID=0, $offset=0, $limit=10 )
  {
    $items = array();
    $db = & $this->_getDb();

    $sql = 'SELECT '.implode(',', $this->listAttrs())
          .' FROM '.$this->tb.','.$this->tbi2c
          .' WHERE c_id=\''.$catID.'\' AND id=i_id '
          .' ORDER BY '.$this->_getOrderBy($sortID).' LIMIT '.$offset.', '.$limit;

    $db->query( $sql );
    while( $row = $db->nextRecord() )
    {
      array_push( $items, $this->copy($row) );
    }
    return $items;
  }

  function getCollection()
  {
    $items = array();
    $db = & $this->_getDb();

    $sql = 'SELECT '.implode(',', $this->listAttrs())
          .' FROM '.$this->tb.','.$this->tbi2c
          .' WHERE id=i_id '
          .' ORDER BY '.$this->_getOrderBy($sortID).' LIMIT '.$offset.', '.$limit;

    $db->query( $sql );
    while( $row = $db->nextRecord() )
    {
      array_push( $items, $this->copy($row) );
    }
    return $items;
  }

  function sortItems( $catID, $sortID=0 )
  {
    $db = & $this->_getDb();
    $sql = 'SELECT id, name, sort_ndx  FROM '.$this->tb.', '.$this->tbi2c
    			.' WHERE c_id=\''.$catID.'\' AND id=i_id'
    			.' ORDER BY '.$this->_getOrderBy($sortID);
    $db->query($sql);
    parent::makeForm();
    $this->form->setLabel( m('Sort documents in current category') );
    $this->form->add( new elCData('c1', m('Set sorting indexes to place documents in require order')));
    while ($r = $db->nextRecord() )
    {
      $this->form->add( new elText('iID['.$r['id'].']', $r['name'], $r['sort_ndx'], array('size'=>10)) );
    }

    if ( $this->form->isSubmitAndValid() )
    {
      $data = $this->form->getValue();
      foreach ( $data['iID'] as $id=>$ndx )
      {
        $db->query('UPDATE '.$this->tbi2c.' SET sort_ndx='.(int)$ndx
        					.' WHERE c_id=\''.$catID.'\' AND i_id='.(int)$id);
      }
      return true;
    }
  }

  function removeItems( $catID )
  {
    $db = & $this->_getDb();
    $sql = 'SELECT id, name  FROM '.$this->tb.', '.$this->tbi2c
    			.' WHERE c_id=\''.$catID.'\' AND id=i_id ORDER BY '.$this->_getOrderBy($sortID);
    $items = $db->queryToArray($sql, 'id', 'name');
    parent::makeForm();
    $this->form->setLabel( m('Select documents to remove') );
    $this->form->add( new elCheckBoxesGroup('items', '', null, $items) );

    if ( $this->form->isSubmitAndValid() )
    {
      $data = $this->form->getValue();
      if ( !empty($data['items']) )
      {
        foreach ( $data['items'] as $iID )
        {
          $db->query('DELETE FROM '.$this->tbi2c.' WHERE i_id=\''.$iID.'\'');
          $db->query('DELETE FROM '.$this->tb.' WHERE id=\''.$iID.'\'');
          $db->optimizeTable($this->tb);
          $db->optimizeTable($this->tbi2c);
        }
      }
      return true;
    }
  }

  function delete()
  {
  	parent::delete( array($this->tbi2c => 'i_id') );
    return true;
  }

   /**
   * Create edit item form object
   */
  function makeForm( $parents )
  {
    parent::makeForm(); //elPrintR($parents); elPrintR($this->_getParents());
    if (empty($parents))
    {
        $parents = array(1);
    }
    $this->form->add( new elMultiSelectList('pids', m('Parent categories'), $this->_getParents(), $parents) );
    $this->form->add( new elText('name', m('Name'), $this->name, array('style'=>'width:100%;')) );
    $this->form->setRequired('pids[]');
    $this->form->setRequired('name');
  }
 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  function _getOrderBy($sortID)
  {
  	$orderBy = !empty($this->_sortVars[$sortID]) ? $this->_sortVars[$sortID] : $this->_sortVars[0];
  	return 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), '.$orderBy;
  }

  /**
   * return list of parent categories IDs
   */
  function _getParents()
  {
  	if (empty($this->parents) )
  	{
  		if ($this->ID)
  		{
  			$db = & $this->_getDb();
      	$sql = 'SELECT c_id FROM '.$this->tbi2c.' WHERE i_id=\''.$this->ID.'\'';
      	$this->parents = $db->queryToArray($sql, null, 'c_id');
  		}
  	}
    return $this->parents;
  }

  /**
   * After save item in DB, save parents list in i2cTb
   */
  function _postSave( )
  {
  	$pIDs = $this->form->getElementValue('pids[]'); //elPrintR($pIDs);
		$db   = & $this->_getDb();
		$this->parents = array();

		$exPIDs = $this->_getParents();
		$add = array_diff($pIDs, $exPIDs); //elPrintR($add);
		$rm = array_diff($exPIDs, $pIDs); //elPrintR($rm);
		if ($rm )
		{
			$db->query('DELETE FROM '.$this->tbi2c.' WHERE i_id=\''.$this->ID.'\' AND c_id IN ('.implode(',', $rm).')');
			$db->optimizeTable($this->tbi2c);
		}
		if ( $add )
		{
			$db->prepare('INSERT INTO '.$this->tbi2c.' (c_id, i_id) VALUES ', '(\'%d\', \'%d\')');
			foreach ( $add as $ID )
			{
				$db->prepareData( array($ID, $this->ID) );
			}
			$db->execute();
		}
		return true;
  }


}

?>