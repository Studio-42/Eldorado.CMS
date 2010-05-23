<?php

/**
 * Простой data mapping (Замена elMemberAttribute)
 *
 * @package eldorado.core
 * @author dio
 **/
class elDataMapping
{
	var $_tb           = '';
	var $_form         = null;
	var $_id           = 'id';
	var $__id__        = 'ID';
	var $_formRndClass = 'elTplFormRenderer';
	var $_objName      = 'Object';
	var $_new          = false;
	var $db            = null;
	

	function elDataMapping($attr=null, $tb=null, $id=null)
	{
		$this->tb($tb);
		$this->id($id);
		$this->attr($attr);
	}
	
	function getObjName()
	{
		return m($this->_objName);
	}
	
	function tb($tb=null)
	{
		return is_null($tb) ? $this->_tb : $this->_tb = $tb;
	}
	
	function id($id=null)
	{
		if (!is_null($id))
		{
			$map = $this->_memberMapping();
			$this->__id__ = $map[$this->_id = $id];
		}
		return $this->_id;
	}
	
	function attr($attr=null, $val=null)
	{
		$map = $this->_memberMapping(); 
		if (is_null($attr))
		{
			$ret = array();
			foreach ( $map as $a=>$m )
			{
				$ret[$a] = $this->$m;
			}
			return $ret;
		}
		if ( !is_array($attr) )
		{
			return !isset($map[$attr]) ? null : (is_null($val) ? $this->{$map[$attr]} : $this->{$map[$attr]} = $val);
		}
		foreach ($attr as $a=>$v)
		{
			if ( !empty($map[$a]) )
			{
				$this->{$map[$a]} = $v;
			}
		}
	}
	
	function idAttr($val=null)
	{
		return is_null($val) ? $this->{$this->__id__} : $this->{$this->__id__} = $val;
	}
	
	function toArray()
	{
		return $this->attr();
	}
	
	function objName()
	{
		return $this->_objName;
	}
	
	function attrsList()
	{
		return array_keys($this->_memberMapping());
	}

	function attrsToString($prefix='')
	{
		return !$prefix
			? implode(',', array_keys($this->_memberMapping()) )
			: $prefix.'.'.implode(','.$prefix.'.', array_keys($this->_memberMapping()));
	}
	
	function clean()
	{
		$map = $this->_memberMapping();
		foreach ( $map as $a=>$m )
		{
			$this->$m = '';
		}
	}
	
	function copy( $attrs=null )
	{
		$copy = $this;
		if ( is_array($attrs) )
		{
			$copy->attr($attrs);
		}
		return $copy;
	}
	
	function fetch()
	{
		if ( false != ($ID = $this->idAttr()) )
		{
			$this->idAttr(0);
			$db = $this->_db();
			$db->query(sprintf('SELECT %s FROM %s WHERE %s="%s" LIMIT 1', $this->attrsToString(), $this->_tb, $this->_id, mysql_real_escape_string($ID)));
			return $db->numRows() && !$this->attr( $db->nextRecord() );
		}
	}
	
	function collection($obj=false, $assoc=false, $clause=null, $sort=null, $offset=0, $limit=0, $onlyFields=null)
	{
		$db = $this->_db();
		$sql = sprintf('SELECT %s FROM %s %s %s %s', 
			$onlyFields && $onlyFields ? $this->_id.', '.$onlyFields : $this->attrsToString(), 
			$this->_tb, 
			$clause ? 'WHERE '.$clause : '', 
			$sort ? 'ORDER BY '.$sort : '', 
			$limit>0 ? 'LIMIT '.intval($offset).', '.intval($limit) : ''
			);
		if ( !$obj )
		{
			return $db->queryToArray($sql, $assoc ? $this->_id : null);
		}
		$ret = array();
		$db->query($sql);
		while ($r = $db->nextRecord())
		{
			if ($assoc) 
			{
				$ret[$r[$this->_id]] = $this->copy($r);
			} 
			else 
			{
				$ret[] = $this->copy($r);
			} 
		}
		return $ret;
	}
	
	function namesList($name, $clause, $sort=null)
	{
		$db = $this->_db();
		$sql = sprintf('SELECT %s, %s FROM %s %s ORDER BY %s', $this->_id, $name, $this->_tb, $clause ? 'WHERE '.$clause : '', $sort ? $sort : $this->_id );
		return $db->queryToArray($sql, $this->_id, $field);
	}
	
	function editAndSave( $params=null )
	{
		$this->_makeForm( $params );

		if ( $this->_form->isSubmitAndValid() && $this->_validForm() )
		{
			$this->attr( $this->_form->getValue() );
			return $this->save($params);
		}
	}

	function formToHtml()
	{
		if ( !$this->_form )
		{
			$this->_makeForm();
		}
		return $this->_form->toHtml();
	}

	function getForm($params=null) {
		if ( !$this->_form ) {
			$this->_makeForm($params);
		}
		return $this->_form;
	}

	function save($params=null)
	{
		$db = $this->_db();
		$isNew = !(bool)$this->idAttr();
		$vals = $this->_attrsForSave();
		if ( !$vals[$this->_id] )
		{
			unset($vals[$this->_id]);
			$sql  = 'INSERT INTO '.$this->_tb.'('.implode(',', array_keys($vals)).') VALUES '.'(\''.implode('\',\'', $vals).'\')';
		}
		else
		{
			$sql = 'UPDATE '.$this->_tb.' SET ';
			foreach ( $vals as $k=>$v)
			{
				if ( $k != $this->_id )
				{
					$sql .= $k.'=\''.$v.'\',';
				}
			}
			$sql = substr($sql, 0, -1).' WHERE '.$this->_id.'=\''.$vals[$this->_id].'\' LIMIT 1';
		}

		if ( !$db->query($sql) )
		{
			return false;
		}
		if ( !$this->{$this->__id__} )
		{
			$this->idAttr( $db->insertID() );
		}
		return $this->_postSave($isNew, $params);
	}
	
	
	function delete( $ref=null )
	{
		if ( $this->idAttr() )
		{
			$ID = mysql_real_escape_string( $this->idAttr() );
			$db = $this->_db();
			$db->query('DELETE FROM '.$this->_tb.' WHERE '.$this->_id.'=\''.$ID.'\' LIMIT 1');
			$db->optimizeTable($this->_tb);
			if ( !empty($ref) && is_array($ref) )
			{
				foreach ( $ref as $tb=>$key )
				{
					$db->query('DELETE FROM '.$tb.' WHERE '.$key.'=\''.$ID.'\'');
					$db->optimizeTable($tb);
				}
			}
		}
	}

	function deleteAll()
	{
		$db = $this->_db();
		return $db->query('TRUNCATE TABLE '.$this->_tb);
	}

	/********************************************/
	/**                PRIVATE                 **/
	/********************************************/	
	function _memberMapping()
	{		
		$class = get_class($this);
		if ( !isset($GLOBALS['mapping'][$class]) )
		{
			$GLOBALS['mapping'][$class] = $this->_initMapping();
		}
		return $GLOBALS['mapping'][$class];
	}
	
	function _initMapping()
	{
		return array();
	}
	
	function _makeForm( $params=null )
	{
		$this->_form = & elSingleton::getObj( 'elForm', 'mf'.get_class($this),  sprintf( m(!$this->{$this->__id__} ? 'Create object "%s"' : 'Edit object "%s"'), m($this->_objName))  );
		$this->_form->setRenderer( elSingleton::getObj($this->_formRndClass) );
	}
	
	function _validForm()
	{
		return true;
	}
	
	function _attrsForSave()
	{
		return array_map('mysql_real_escape_string', $this->attr());
	}
	
	
	function _postSave($isNew, $params=null)
	{
		return true;
	}
	
	function _db() {
		if (!$this->db) {
			$this->db = & elSingleton::getObj('elDb');
		}
		return $this->db;
	}
} // END class 
?>