<?php

/**
* 
*/
class elActionRecord extends elDataMapping
{
	var $_tb = 'el_action_log';
	var $ID  = 0;
	var $id, $uid, $mid, $object, $action, $time, $link, $value;

	function _initMapping()
	{
		return array(
			'id'     => 'ID',
			'uid'    => 'uid',
			'mid'    => 'mid',
			'object' => 'object',
			'action' => 'action',
			'time'   => 'time',
			'link'   => 'link',
			'value'  => 'value'
			);
  	}

	function count()
	{
		$sql  = 'SELECT COUNT(id) AS count FROM '.$this->_tb;
		$db   = & elSingleton::getObj('elDb');
		$db->query($sql);
		$row = $db->nextRecord();
		return $row['count'];
	}
}
