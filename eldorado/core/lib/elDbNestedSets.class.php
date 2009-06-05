<?php

class elDbNestedSets
{
	var $db     = null;
	var $tb     = '';
	var $id     = 'id';
	var $left   = '_left';
	var $right  = '_right';
	var $level  = 'level';
	var $fields = array('name');


	function elDbNestedSets($tb, $fields=null, $keys=null)
	{
		$this->db = & elSingleton::getObj('elDb');
		$this->tb = $tb;
		if ( is_array($fields) )
		{
			$this->fields = $fields;
		}
		if ( is_array($keys) )
		{
			echo 'elDbNestedSets get keys! Who is here?';
			foreach ( $this->keys as $k=>$v )
			{
				$this->$k = $v;
			}
		}
	}

	function setTb( $tb )
	{
		$this->tb = $tb;
	}

	function makeRootNode($name)
	{
		$this->db->query('TRUNCATE TABLE '.$this->tb);
		$this->db->query('SELECT * FROM '.$this->tb);
		if ( !$this->db->numRows() )
		{
			return $this->db->query('INSERT INTO '.$this->tb.' (name, _left, _right, level) '
					.'VALUES ("'.mysql_real_escape_string($name).'", 1, 2, 0 )');
		}
		return true;
	}
	
	function getChilds($ID, $deep=0, $includeTop=false, $relLevels=true, $class=null)
	{
		$level = $relLevels
		? 'ch.'.$this->level . '-p.'.$this->level.' AS level'
		: 'ch.'.$this->level ;
		$sql = 'SELECT ch.'.$this->id.', '.$level.', ch.'.implode(', ch.', $this->fields)
		. ' FROM '.$this->tb . ' AS ch, '.$this->tb . ' AS p '
		. ' WHERE p.'.$this->id . '=\''.$ID.'\' AND ch.'.$this->left . ' BETWEEN p.'.$this->left
		. ' AND p.'.$this->right;
		if ( !$includeTop )
		{
			$sql .= ' AND ch.'.$this->level . '>p.'.$this->level;
		}
		if ( $deep )
		{
			$sql .= ' AND ch.'.$this->level .'<=p.'.$this->level.'+'.$deep;
		}
		$sql .= ' ORDER BY ch.'.$this->left;

		if ( !$class )
		{
			return $this->db->queryToArray($sql);
		}
		else
		{
			$cats = array();
			$this->db->query($sql);

			while ( $row = $this->db->nextRecord() )
			{
				array_push( $cats, new $class($row) );
			}
			return $cats;
		}
	}

	function quickList($field='name', $markLevels=true)
	{
		$sql = 'SELECT '.$this->id.', '.$this->level.', '.$field
		. ' FROM '.$this->tb . ' ORDER BY  '.$this->left;
		$this->db->query($sql);
		if ( !$markLevels )
		{
			return $this->db->queryToArray(null, $this->id, $field);
		}

		$res = array();
		while ( $row = $this->db->nextRecord() )
		{
			$res[$row[$this->id]] = str_repeat('+ ', $row[$this->level]).$row[$field];
		}
		return $res;
	}

	function getPath($ID, $fields=array('name'), $includeRoot=false)
	{
		$sql = 'SELECT p.'.$this->id . ', p.'.implode(', p.', $fields)
		. ' FROM '.$this->tb . ' AS p, '.$this->tb . ' AS ch '
		. ' WHERE ch.'.$this->id.'=\''.$ID.'\' AND ch.'.$this->left
		. ' BETWEEN p.'.$this->left.' AND p.'.$this->right;
		if ( !$includeRoot )
		{
			$sql .= ' AND p.'.$this->level.'>0';
		}
		$sql .= ' ORDER BY p.'.$this->left;

		$this->db->query($sql);
		return $this->db->numRows() ? $this->db->queryToArray() : array();
	}

	function getNodeInfo($ID, $incFields=false)
	{
		$f = $incFields ? ',' . implode(',', $this->fields) : '';
		$sql = 'SELECT ' . $this->left .', ' . $this->right . ', ' . $this->level . $f
		. ' FROM ' . $this->tb . ' WHERE ' . $this->id . '=\''.(int)$ID.'\' ';
		$this->db->query( $sql );
		// return $this->db->numRows() ? $this->db->nextRecord() : null;

		if ( $this->db->numRows() )
		{
			$row = $this->db->nextRecord();
			return array($row[$this->left], $row[$this->right], $row[$this->level]);
		}
		return false;
	}

	function getParentInfo($ID)
	{
		$sql = 'SELECT p.'.$this->id .', p.'.$this->left.', p.'.$this->right.', p.'.$this->level
		. ' FROM '.$this->tb . ' AS p, ' . $this->tb .' AS ch '
		. ' WHERE ch.'.$this->id.'=\''.$ID.'\' AND ch.'
		.$this->left.' BETWEEN p.'.$this->left.' AND p.'.$this->right
		//	. ' AND p.level=ch.level-1'
		.' ORDER BY '.$this->left.' DESC LIMIT 1,1'
		;
		$this->db->query($sql);
		return $this->db->numRows() ? $this->db->nextRecord() : null;
	}

	function getParentID( $ID )
	{
		$parent = $this->getParentInfo( $ID );
		return $parent ? $parent[$this->id] : null;
	}

	function insert($parentID, $data=null)
	{
		if ( !( list($left, $right, $level) = $this->getNodeInfo($parentID) ) )
		{
			return false;
		}
		$sql = 'UPDATE IGNORE ' . $this->tb . ' SET '
		. $this->left.'=IF('.$this->left.'>'.$right.', '.$this->left.'+2, '.$this->left.'), '
		. $this->right.'=IF('.$this->right.'>='.$right.', '.$this->right.'+2, '.$this->right.') '
		. 'WHERE '.$this->right . '>='.$right;

		if ( !$this->db->query($sql) )
		{
			return false;
		}

		if ( is_array($data) )
		{
			$fldNames = implode(',', array_keys($data)) . ',';
			$fldValues = '\'' . implode('\', \'', $data) . '\', ';
		}
		else
		{
			$fldNames = $fldValues = '';
		}
		$fldNames .= $this->left . ','.$this->right.','.$this->level;
		$fldValues .= $right . ',' . ($right+1) . ',' . ($level+1);

		$sql = 'INSERT INTO ' . $this->tb . ' (' . $fldNames . ') VALUES (' .$fldValues . ')' ;
		return $this->db->query($sql) ? $this->db->insertID() : false;
	}

	function update($ID, $data) {}

	function move($ID, $parentID)
	{
		if ( !(list($left, $right, $level) = $this->getNodeInfo($ID)) )
		{
			return elThrow(E_USER_WARNING, m('Category with ID="%d" does not exists'), $ID);
		}
		if ( !(list($pLeft, $pRight, $pLevel) = $this->getNodeInfo($parentID)) )
		{
			return elThrow(E_USER_WARNING, m('Category with ID="%d" does not eists'), $parentID);
		}
		if ( $pLeft >= $left && $pRight <= $right )
		{
			return elThrow(E_USER_WARNING, m('You can not move category to herself of to her childs category'));
		}

		// echo "$left, $right, $level <br>>$pLeft, $pRight, $pLevel";  return;
		if ($pLeft < $left && $pRight > $right && $pLevel < $level - 1 )
		{
			$sql = 'UPDATE '.$this->tb.' SET '
			. $this->level.'=IF('.$this->left.' BETWEEN '.$left.' AND '.$right.', '.$this->level.sprintf('%+d', -($level-1)+$pLevel).', '.$this->level.'), '
			. $this->right.'=IF('.$this->right.' BETWEEN '.($right+1).' AND '.($pRight-1).', '.$this->right.'-'.($right-$left+1).', '
			.'IF('.$this->left.' BETWEEN '.($left).' AND '.($right).', '.$this->right.'+'.((($pRight-$right-$level+$pLevel)/2)*2 + $level - $pLevel - 1).', '.$this->right.')),  '
			. $this->left.'=IF('.$this->left.' BETWEEN '.($right+1).' AND '.($pRight-1).', '.$this->left.'-'.($right-$left+1).', '
			.'IF('.$this->left.' BETWEEN '.$left.' AND '.($right).', '.$this->left.'+'.((($pRight-$right-$level+$pLevel)/2)*2 + $level - $pLevel - 1).', '.$this->left. ')) '
			. 'WHERE '.$this->left.' BETWEEN '.($pLeft+1).' AND '.($pRight-1)
			;
		}
		elseif($pLeft < $left)
		{
			$sql = 'UPDATE '.$this->tb.' SET '
			. $this->level.'=IF('.$this->left.' BETWEEN '.$left.' AND '.$right.', '.$this->level.sprintf('%+d', -($level-1)+$pLevel).', '.$this->level.'), '
			. $this->left.'=IF('.$this->left.' BETWEEN '.$pRight.' AND '.($left-1).', '.$this->left.'+'.($right-$left+1).', '
			. 'IF('.$this->left.' BETWEEN '.$left.' AND '.$right.', '.$this->left.'-'.($left-$pRight).', '.$this->left.') '
			. '), '
			. $this->right.'=IF('.$this->right.' BETWEEN '.$pRight.' AND '.$left.', '.$this->right.'+'.($right-$left+1).', '
			. 'IF('.$this->right.' BETWEEN '.$left.' AND '.$right.', '.$this->right.'-'.($left-$pRight).', '.$this->right.') '
			. ') '
			. 'WHERE '.$this->left.' BETWEEN '.$pLeft.' AND '.$right
			// !!! added this line (Maxim Matyukhin)
			.' OR '.$this->right.' BETWEEN '.$pLeft.' AND '.$right
			;
		}
		else
		{
			$sql = 'UPDATE '.$this->tb.' SET '
			. $this->level.'=IF('.$this->left.' BETWEEN '.$left.' AND '.$right.', '.$this->level.sprintf('%+d', -($level-1)+$pLevel).', '.$this->level.'), '
			. $this->left.'=IF('.$this->left.' BETWEEN '.$right.' AND '.$pRight.', '.$this->left.'-'.($right-$left+1).', '
			. 'IF('.$this->left.' BETWEEN '.$left.' AND '.$right.', '.$this->left.'+'.($pRight-1-$right).', '.$this->left.')'
			. '), '
			. $this->right.'=IF('.$this->right.' BETWEEN '.($right+1).' AND '.($pRight-1).', '.$this->right.'-'.($right-$left+1).', '
			. 'IF('.$this->right.' BETWEEN '.$left.' AND '.$right.', '.$this->right.'+'.($pRight-1-$right).', '.$this->right.') '
			. ') '
			. 'WHERE '.$this->left.' BETWEEN '.$left.' AND '.$pRight
			// !!! added this line (Maxim Matyukhin)
			. ' OR '.$this->right.' BETWEEN '.$left.' AND '.$pRight
			;
		}


		return $this->db->query($sql);
	}

	function delete($ID)
	{
		if ( !(list($left, $right, $level) = $this->getNodeInfo($ID)) )
		{
			return false;
		}
		$sql = 'DELETE FROM '.$this->tb.' WHERE '.$this->left.' BETWEEN '.$left.' AND '.$right;
		if( !$this->db->query($sql) )
		{
			return false;
		}
		$this->db->optimizeTable($this->tb);
		
		$delta = ($right - $left)+1;
		$sql = 'UPDATE '.$this->tb.' SET '
		. $this->left.'=IF('.$this->left.'>'.$left.','.$this->left.'-'.$delta.','.$this->left.'),'
		. $this->right.'=IF('.$this->right.'>'.$left.','.$this->right.'-'.$delta.','.$this->right.') '
		. 'WHERE '.$this->right.'>'.$right;
		return $this->db->query($sql);
	}

	function getNeighbourID( $ID, $upper=true )
	{
		list($l,$r,$lv) = $this->getNodeInfo( $ID );
		$parentID = $this->getParentID( $ID );

		$db = &elSingleton::getObj('elDb');
		$sql = 'SELECT ch.'.$this->id.', ch.name FROM '.$this->tb.' AS ch, '.$this->tb.' AS p '
		.' WHERE p.'.$this->id.'=\''.$parentID.'\' '
		.' AND ch.'.$this->left.' BETWEEN p.'.$this->left.' AND p.'.$this->right
		.' AND ch.'.$this->level.'=p.'.$this->level.'+1 '
		.' AND ch.'.$this->left;
		$sql .= $upper ? '<'.$l.' ORDER BY ch.'.$this->left.' DESC' : '>'.$l.' ORDER BY ch.'.$this->left;
		$sql .= ' LIMIT 0,1';
		$db->query($sql);
		if ( !$db->numRows() )
		{
			return null;
		}
		$row = $db->nextRecord();
		return $row['id'];
	}

	function exchange($ID1, $ID2)
	{
		$parentID1 = $this->getParentID( $ID1 );
		$parentID2 = $this->getParentID( $ID2 );
		if ( $parentID1 != $parentID2 )
		{
			return false;
		}
		list( $l1, $r1) = $this->getNodeInfo($ID1); //echo $lNodeLeft.' - ';
		list( $l2, $r2) = $this->getNodeInfo($ID2); //echo $upNodeLeft;

		if ( $l1 < $l2 )
		{
			$delta1 = ($r2-$l2+1);
			$delta2 = -($r1-$l1+1);
			$where = $l1.' AND '.$r2;
		}
		else
		{
			$delta1 = -($r2-$l2+1);
			$delta2 = ($r1-$l1+1);

			$where = $l2.' AND '.$r1;
		}

		$sql = 'UPDATE '.$this->tb.' SET '
		.$this->left.'=IF ('.$this->left.' BETWEEN '.$l1.' AND '.$r1.', '
		.$this->left.'+'.$delta1.', '.$this->left.'+'.$delta2.'), '
		.$this->right.'=IF ('.$this->right.' BETWEEN '.$l1.' AND '.$r1.', '
		.$this->right.'+'.$delta1.', '.$this->right.'+'.$delta2.') '
		.'WHERE '.$this->left.' BETWEEN '.$where;

		$this->db->query( $sql );
		return true;
	}

}



?>