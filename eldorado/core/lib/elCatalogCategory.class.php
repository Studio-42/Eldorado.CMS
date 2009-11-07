<?php

class elCatalogCategory extends elMemberAttribute
{
	var $tbi2c     = '';
  	var $ID        = 0;
  	var $name      = '';
  	var $level     = 1;
  	var $descrip   = '';
  	var $parentID  = 1;
	var $tree      = null;
  	var $itemClass = '';
  	var $_objName  = 'Category';


 //**************************************************************************************//
 // *******************************  PUBLIC METHODS  *********************************** //
 //**************************************************************************************//

  function getChilds( $deep=0 )
  {
    $childs = array();
    $db = & elSingleton::getObj('elDb');
    $sql = 'SELECT ch.id, ch.name, ch.descrip, ch.level-p.level AS level '
          .' FROM '.$this->tb .' AS ch, '.$this->tb . ' AS p '
          .' WHERE p.id=\''.$this->ID.'\' '
          .' AND ch._left BETWEEN p._left AND p._right '
          .' AND ch.level>p.level '.($deep ? 'AND ch.level<=p.level+'.$deep: '')
          .' ORDER BY ch._left';
    $db->query( $sql );
    while( $row = $db->nextRecord() )
    {
      array_push( $childs, $this->copy($row) );
    }
    return $childs;
  }

  function getTreeToArray( $deep=0, $asList=false, $noRoot=false, $addRoot=false )
  {
  	if (!$this->tb )
  	{
  		return array();
  	}
    $db = & elSingleton::getObj('elDb');
    $where = !$deep ? '' : ' AND level<='.$deep;
    if (!$asList)
    {
      $sql = !$addRoot
        ? 'SELECT id, name, level FROM '.$this->tb.' WHERE _left>1 '.$where.' ORDER BY _left'
        : 'SELECT id, name, level FROM '.$this->tb.' WHERE _left>0 '.$where.' ORDER BY _left';
    	return $db->queryToArray($sql, 'id');
    }
    else
    {
    	if ($noRoot)
    	{
    		$where .= ' AND _left>1 ';
    	}
    	$sql = 'SELECT id, CONCAT( REPEAT("- ", level), name) AS name FROM '.$this->tb
    				.' WHERE 1 '.$where.' ORDER BY _left';
    	return $db->queryToArray($sql, 'id', 'name');
    }
  }

  function pathToPageTitle( $path=null )
  {
    $db = &$this->_getDb();
    $sql = 'SELECT  CONCAT("'.$path.'", p.id) AS url, p.name '
          .' FROM ' . $this->tb . ' AS p, '
          .$this->tb . ' AS ch '
          .' WHERE ch.id=\''.$this->ID.'\' '
          .' AND ch._left BETWEEN p._left AND p._right '
          .' AND p.level>0'
          .' ORDER BY p._left' ;

    $db->query($sql);
    while ( $r = $db->nextRecord() )
    {
      elAppendToPagePath( $r );
    }
  }

  function countItems()
  {
  	$db = & elSingleton::getObj('elDb');
		$db->query('SELECT COUNT(i_id) AS cnt FROM '.$this->tbi2c.' WHERE c_id=\''.$this->ID.'\'');
		$r = $db->nextRecord();
		return $r['cnt'];
  }

  function isEmpty()
  {
  	return $this->getChilds() || $this->countItems() ? false : true;
  }

  function move($up=true)
  {
    $this->_initTree();
    if ( !($nID = $this->tree->getNeighbourID($this->ID, $up) ) )
    {
      return false;
    }
    return $this->tree->exchange( $this->ID, $nID );
  }

  function editAndSave()
  {
    $this->_initTree();
    $this->makeForm();
    if ( $this->form->isSubmitAndValid() )
    {
      $this->setAttrs( $this->form->getValue(true) );
      $parentID = $this->form->getElementValue( 'parent_id' );
      if ( !$this->ID )
      {
        //new node
        $vals = $this->getAttrs(); unset($vals['id'], $vals['level']);
        return $this->tree->insert( $parentID, $vals );
      }
      else
      {
        if ( $parentID != $this->parentID && ! $this->tree->move($this->ID, $parentID) )
        {//move node to another parent
          return false;
        }
        //update node data
        return $this->save();
      }
    }
    return false;
  }

  function makeForm($dir='')
  {
	
    parent::makeForm();
    if ( $this->ID )
    {
      $this->parentID = $this->tree->getParentID( $this->ID );
    }
    $this->form->add( new elSelect('parent_id', m('Parent category'), $this->parentID,
            $this->tree->quickList()) );
    $this->form->add( new elText('name', m('Name'), $this->name) );
    $this->form->add( new elEditor('descrip', m('Description'), $this->descrip, array('rows'=>24)) );
    $this->form->setRequired('name');
  }

  function delete()
  {
    $this->_initTree();
    if ( !$this->tree->delete( $this->ID ) )
    {
    	return false;
    }
    $db = & elSingleton::getObj('elDb');
    $db->query('DELETE FROM '.$this->tbi2c.' WHERE c_id='.$this->ID);
    $db->optimizeTable($this->tbi2c);
    return true;
  }

  function makeRootNode($name)
  {
  	$this->_initTree();
  	if (!$this->tree->makeRootNode( $name ))
  	{
  		return false;
  	}
  	$this->ID    = 1;
  	$this->name  = $name;
  	$this->level = 0;
  	return true;
  }
 //**************************************************************************************//
 // =============================== PRIVATE METHODS ==================================== //
 //**************************************************************************************//

  function _initMapping()
  {
    return array('id'=>'ID', 'name'=>'name', 'level'=>'level', 'descrip'=>'descrip');
  }

  function _attrsForSave()
  {
    $attrs = $this->getAttrs();
    unset( $attrs['level']);
    return $attrs;
  }

  function _initTree()
  {
    $this->tree = & elSingleton::getObj('elDbNestedSets', $this->tb);
  }

}

?>