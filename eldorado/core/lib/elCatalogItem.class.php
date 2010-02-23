<?php

class elCatalogItem extends elDataMapping
{
  var $tbi2c     = '';
  var $ID        = 0;
  var $name      = '';
  var $crtime    = 0;
  var $parents   = array();
  var $_objName  = 'Document';
  var $_sortVars = array('name', 'crtime DESC, name');

 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

	function getName()
	{
		return $this->attr('name');
	}

	/**
	* Returns items from category
	*/
	function getByCategory( $catID, $sortID=0, $offset=0, $limit=0 )
	{
		$items = array();
		$db = & elSingleton::getObj('elDb');

		$sql = 'SELECT '.$this->attrsToString()
			.' FROM '.$this->_tb.','.$this->tbi2c
			.' WHERE c_id=\''.$catID.'\' AND id=i_id '
			.' ORDER BY '.$this->_getOrderBy($sortID);

		if ($limit>0)
		{
			$sql .= ' LIMIT '.$offset.', '.$limit;
		}
		$db->query( $sql );
		while( $row = $db->nextRecord() )
		{
			$items[] = $this->copy($row);
		}
		return $items;
	}

	function getCollection()
	{
		$items = array();
		$db = & elSingleton::getObj('elDb');

		$sql = 'SELECT '.$this->attrsToString()
		.' FROM '.$this->_tb.','.$this->tbi2c
		.' WHERE id=i_id '
		.' ORDER BY '.$this->_getOrderBy($sortID).' LIMIT '.$offset.', '.$limit;

		$db->query( $sql );
		while( $row = $db->nextRecord() )
		{
			$items[] = $this->copy($row);
		}
		return $items;
	}

	function sortItems( $catID, $sortID=0 )
	{
		parent::_makeForm();
		$this->_form->setLabel( m('Sort documents in current category') );
		$this->_form->add( new elCData('c1', m('Set sorting indexes to place documents in require order')));
		$db = & elSingleton::getObj('elDb');
		$sql = 'SELECT id, name, sort_ndx  FROM '.$this->_tb.', '.$this->tbi2c
			.' WHERE c_id=\''.$catID.'\' AND id=i_id'
			.' ORDER BY '.$this->_getOrderBy($sortID);
		$db->query($sql);
		
		while ($r = $db->nextRecord() )
		{
			$this->_form->add( new elText('iID['.$r['id'].']', $r['name'], $r['sort_ndx'], array('size'=>10)) );
		}

		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue();
			foreach ( $data['iID'] as $id=>$ndx )
			{
				$db->query('UPDATE '.$this->tbi2c.' SET sort_ndx='.(int)$ndx.' WHERE c_id=\''.$catID.'\' AND i_id='.(int)$id);
			}
			return true;
		}
	}

	function removeItems( $catID, $sortID=0 )
	{
		$db = & elSingleton::getObj('elDb');
		$sql = 'SELECT id, name  FROM '.$this->_tb.', '.$this->tbi2c
			.' WHERE c_id=\''.$catID.'\' AND id=i_id ORDER BY '.$this->_getOrderBy($sortID);
		$items = $db->queryToArray($sql, 'id', 'name');
		parent::_makeForm();
		$this->_form->setLabel( m('Select documents to remove') );
		$this->_form->add( new elCheckBoxesGroup('items', '', null, $items) );

		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue();
			if ( !empty($data['items']) )
			{
				foreach ( $data['items'] as $iID )
				{
					$item = $this->copy(array());
					$item->clean();
					$item->idAttr($iID);
					if ($item->fetch()) {
						$item->delete();
					}
				}
			}
			return true;
		}
	}

	function delete()
	{
		return parent::delete( array($this->tbi2c => 'i_id') );
	}

 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

	function _initMapping()
	{
		return array(
			'id'   => 'ID',
			'name' => 'name'
			);
	}

	function _getOrderBy($sortID)
	{
		return 'IF(sort_ndx>0, LPAD(sort_ndx, 4, "0"), "9999"), '.(!empty($this->_sortVars[$sortID]) ? $this->_sortVars[$sortID] : $this->_sortVars[0]);
	}

	/**
	* Create edit item form object
	*
	* @param array $parents  parent categories list
	* @return void
	*/
	function _makeForm( $parents )
	{
		parent::_makeForm(); 
		
		$this->_form->add( new elMultiSelectList('pids', m('Parent categories'), $this->_getParents(), empty($parents) ? array(1) : $parents) );
		$this->_form->add( new elText('name', m('Name'), $this->name, array('style'=>'width:100%;')) );
		$this->_form->setRequired('pids[]');
		$this->_form->setRequired('name');
	}

	function _getParents() {
		if (empty($this->parents)) {
			$db = & elSingleton::getObj('elDb');
			$this->parents = $db->queryToArray('SELECT c_id FROM '.$this->tbi2c.' WHERE i_id=\''.$this->ID.'\'', null, 'c_id');
		}
		return $this->parents;
	}

	/**
	* After save item in DB, save parents list in i2cTb
	*
	* @param array $parents  parent categories list
	* @return void
	*/
	function _postSave( )
	{
		$pIDs = $this->_form->getElementValue('pids[]'); 
		$db   = & elSingleton::getObj('elDb');
		$this->parents = array();

		$exPIDs = $this->_getParents();
		$add = array_diff($pIDs, $exPIDs); 
		$rm = array_diff($exPIDs, $pIDs); 
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