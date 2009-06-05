<?php

class elMemberAttribute
{
	var $db               = null;
	var $tb               = '';
	var $form             = null;
	var $_uniq            = 'id';
	var $_formRndClass    = 'elTplFormRenderer';
	var $_objName         = 'Object';


	function elMemberAttribute( $attrs=null, $tb=null, $uniq=null )
	{
		if ( $uniq )
		{
			$this->setUniq($uniq);
		}
		if ( is_array($attrs) )
		{
			$this->setAttrs($attrs);
		}
		if ( $tb )
		{
			$this->setTb($tb);
		}
	}

	function getTb()
	{
		return $this->tb;
	}

	function setTb($tb)
	{
		$this->tb = $tb;
	}

	function getUniq()
	{
		return $this->_uniq;
	}

	function setUniq($uniq)
	{
		$this->_uniq = $uniq;
	}

	function setUniqAttr( $attr )
	{
		$this->setAttr($this->_uniq, $attr);
	}

	function getUniqAttr()
	{
		return $this->getAttr( $this->_uniq );
	}

	function getObjName()
	{
		return m($this->_objName);
	}

	function memberMapping()
	{
		$class = get_class($this);
		if ( !isset($GLOBALS['mapping'][$class]) )
		{
			$GLOBALS['mapping'][$class] = $this->_initMapping();
		}
		return $GLOBALS['mapping'][$class];
	}

	function copy( $attrs=null )
	{
		$copy = $this;
		if ( is_array($attrs) )
		{
			$copy->setAttrs($attrs);
		}
		return $copy;
	}


	function _initMapping()
	{
		return array();
	}

	function fetch()
	{
		if ( $this->getUniqAttr() )
		{
			$sql = 'SELECT '.implode(',', $this->listAttrs())
			.' FROM '.$this->tb.' WHERE '
			.$this->_uniq.'=\''.mysql_real_escape_string($this->getUniqAttr()).'\' ' ; 
			$db = & $this->_getDb();
			$db->query($sql);
			if ( $db->numRows() )
			{
				$this->setAttrs( $db->nextRecord() );
				return true;
			}
		}
		return false;
	}

	function getCollection($field=null, $orderBy=null, $offset=0, $limit=0, $where=null )
	{
		$db    = & $this->_getDb();;
		$order = $orderBy ? $orderBy : $this->_uniq;
		$where = $where ? ' WHERE '.$where : '';
		$limit = $limit > 0 ? ' LIMIT '.intval($offset).', '.intval($limit).' ' : ' ';
		if ( $field )
		{
			$sql = 'SELECT '.$this->_uniq.', '.$field.' FROM '.$this->tb.$where.' ORDER BY '.$order.$limit;
			return $db->queryToArray( $sql, $this->_uniq, $field);
		}

		$coll = array();
		$db->query('SELECT '.implode(',', $this->listAttrs()).' FROM '.$this->tb.$where.' ORDER BY '.$order.$limit);

		while ( $r = $db->nextRecord() )
		{
			$coll[$r[$this->_uniq]] = $this->copy($r);
		}
		return $coll;
	}

	/**
	 * Return list with objects fields in format ID=>array(field=>value...)
	 * If $fields contains one field name return list in format ID=>fieldValue
	 *
	 * @param string $fields
	 * @param string $where
	 * @param string $orderBy
	 * @param int    $offset
	 * @param int    $limit
	 * @return array
	 */
	function getCollectionToArray($fields=null, $where=null, $orderBy=null, $offset=0, $limit=0)
	{
		$db    = & $this->_getDb();;
		$order = $orderBy   ? $orderBy : $this->_uniq;
		$where = $where     ? ' WHERE '.$where : '';
		$limit = $limit > 0 ? ' LIMIT '.intval($offset).', '.intval($limit).' ' : ' ';
		$onlyField = null;
		if ( !$fields )
		{
			$fields = implode(',', $this->listAttrs());
		}
		elseif (!strstr($fields, $this->_uniq))
		{
			$onlyField = strpos( $fields, ',') ? null : $fields;
			$fields = $this->_uniq.','.$fields;
		}
		$sql = 'SELECT '.$fields.' FROM '.$this->tb.$where.' ORDER BY '.$order.$limit; 
		return $db->queryToArray($sql, $this->_uniq, $onlyField);
	}


	function countAll($where='1')
	{
		$db = & $this->_getDb();
		$db->query('SELECT COUNT(*) AS num FROM '.$this->tb.' WHERE '.$where);
		$r = $db->nextRecord();
		return $r['num'];
	}

	function listAttrs()
	{
		return array_keys($this->memberMapping());
	}

	function listAttrsToStr($prefix='')
	{
		return !$prefix
			? implode(',', array_keys($this->memberMapping()) )
			: $prefix.'.'.implode(','.$prefix.'.', array_keys($this->memberMapping()));
	}
	function getAttr($attr)
	{
		$mapping = $this->memberMapping();
		return isset($mapping[$attr]) ? $this->{$mapping[$attr]} : null;
	}

	function getAttrs()
	{
		$ret = array();
		$mapping = $this->memberMapping();
		foreach ( $mapping as $attr=>$member )
		{
			$ret[$attr] = $this->$member;
		}
		return $ret;
	}


	function setAttr($attr, $val)
	{
		$mapping = $this->memberMapping();
		if (isset($mapping[$attr]))
		{
			$member = $mapping[$attr];
			$this->$member = empty($val) && !empty($this->_defVals[$attr]) ? $this->_defVals[$attr] : $val;
			//$this->$member = $val;
		}
	}

	function setAttrs($values)
	{
		if (is_array($values))
		{
			$mapping = $this->memberMapping();
			foreach ($values as $attr=>$val)
			{
				if (isset($mapping[$attr]))
				{
					$member = $mapping[$attr];
					$this->$member = empty($val) && !empty($this->_defVals[$attr]) ? $this->_defVals[$attr] : $val;
				}
			}
		}
	}

	function cleanAttrs()
	{
		$mapping = $this->memberMapping();
		foreach ( $mapping as $attr=>$member )
		{
			$this->$member = '';
		}
	}

	function toArray()
	{
		return $this->getAttrs();
	}

	function attributeMapping()
	{
		$mapping = $this->memberMapping();
		return array_flip($mapping);
	}


	function save()
	{
		$db   = & $this->_getDb();;
		$vals = $this->_attrsForSave();

		if ( !$vals[$this->_uniq] )
		{
			unset($vals[$this->_uniq]);
			$vals = array_map('mysql_real_escape_string', $vals);
			$sql = 'INSERT INTO '.$this->tb
			.' ('. implode(',', array_keys($vals)).') VALUES '
			.'(\''.implode('\',\'', $vals).'\')';
		}
		else
		{
			$sql = 'UPDATE '.$this->tb.' SET ';
			foreach ( $vals as $k=>$v)
			{
				if ( $k != $this->_uniq )
				{
					$sql .= $k.'=\''.$v.'\',';
				}
			}
			$sql = substr($sql, 0, -1).' WHERE '.$this->_uniq.'=\''.$vals[$this->_uniq].'\'';
		}

		if ( $db->query($sql) )
		{
			if ( !$this->getUniqAttr() )
			{
				$this->setUniqAttr( $db->insertID() );
			}
			return true;
		}

		return false;
	}

	function delete( $refTbs=null )
	{
		$ID = $this->getUniqAttr();
		if ( $ID )
		{
			$ID = mysql_real_escape_string( $ID );
			$db = & $this->_getDb();
			$db->query('DELETE FROM '.$this->tb.' WHERE '.$this->getUniq().'=\''.$ID.'\'');
			$db->optimizeTable($this->tb);
			if ( !empty($refTbs) && is_array($refTbs) )
			{
				foreach ( $refTbs as $tb=>$key )
				{
					$db->query('DELETE FROM '.$tb.' WHERE '.$key.'=\''.$ID.'\'');
					$db->optimizeTable($tb);
				}
			}
		}
	}

	function deleteAll()
	{
		$db = & $this->_getDb();
		return $db->query('TRUNCATE TABLE '.$this->tb);
	}
	//  some methods for editing object

	function editAndSave( $params=null )
	{
		$this->makeForm( $params );

		if ( $this->form->isSubmitAndValid() && $this->_validForm() )
		{
			$this->setAttrs( $this->form->getValue());
			if ( $this->save() )
			{
				return $this->_postSave();
			}
		}
	}

	function formToHtml()
	{
		return $this->form ? $this->form->toHtml() : '';
	}

	function makeForm( $params=null )
	{
		$label = !$this->getUniqAttr() ? 'Create object "%s"' : 'Edit object "%s"';
		$this->form = & elSingleton::getObj( 'elForm', 'mf',  sprintf( m($label), m($this->_objName))  );
		$this->form->setRenderer( elSingleton::getObj($this->_formRndClass) );
	}

	function _validForm()
	{
		return true;
	}

	function _postSave()
	{
		return true;
	}

	function _attrsForSave()
	{
		return array_map('mysql_real_escape_string',  $this->getAttrs() );
	}

	function &_getDb()
	{
		if ( !$this->db )
		{
			$this->db = & elSingleton::getObj('elDb');
		}
		return $this->db;
	}

}

?>
