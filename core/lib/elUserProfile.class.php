<?php

class elUserProfile extends elDataMapping
{
	var $_tb         = 'el_user';
	var $_id         = 'uid';
	var $__id__      = 'UID';
	var $UID         = 0;
	var $login       = '';
	var $email       = '';


	function toArray()
	{
		$ret = array();
		// $sql = 'SELECT field, label FROM el_user_profile_ '
		//      . 'WHERE field IN ("'.implode('", "', $this->attrsList()).'") '
		//      . 'ORDER BY sort_ndx, field';
		// $this->db->query($sql);
		// while ($r = $this->db->nextRecord()) {
		// 	$ret[] = array('label'=>m($r['label']), 'value'=>$this->attr($r['field']));
		// }
		
		return $ret;
	}

	function getSkelConf()
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql  = 'SELECT field, label, rq, sort_ndx FROM el_user_profile ORDER BY sort_ndx, field';
		$conf =  $db->queryToArray($sql, 'field');
		return $conf;
	}

	function setSkelConf($conf)
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql = 'UPDATE el_user_profile SET rq="%d", sort_ndx="%d" WHERE field="%s"';
		foreach ( $conf as $k=>$v )
			$db->safeQuery($sql, $v['rq'], $v['sort_ndx'], $k);
	}

	function getSkel()
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql  = 'SELECT field, label, type, opts, rule, is_func, rq, sort_ndx FROM el_user_profile '
		      . 'WHERE field IN ("'.implode('", "', $this->attrsList()).'") ORDER BY sort_ndx, field';
		$skel = $db->queryToArray($sql, 'field');
		return $skel;
	}

	function getFullName()
	{
		// TODO
		$name = null;
		$name = trim($this->attr('f_name').' '.$this->attr('s_name').' '.$this->attr('l_name'));
		return $name ? $name : $this->attr('login');
	}

	function getEmail($format=true)
	{
	  return $format ? '"'.$this->getFullName().'"<'.$this->attr('email').'>' : $this->attr('email');
	}

	function _initMapping()
	{
		return array('login' => 'login', 'email' => 'email', 'f_name' => 'f_name', 'l_name' => 'l_name');
		
		
		$sql = 'SELECT field FROM el_user_profile_ WHERE rq>"0" OR field="login" OR field="email" ORDER BY sort_ndx';
		$ats = & elSingleton::getObj('elATS');
		$db  = & $ats->getACLDb();
		$map = array('uid' => 'UID');
		$db->query($sql);
		while($r = $db->nextRecord()) {
			$this->{$r['field']} = '';
			$map[$r['field']] = $r['field'];
		}
		
		// $map = $db->queryToArray($sql, 'field', 'field');
		// $map['uid'] = 'UID';
		// elPrintR($map);
		return $map;
	}

}



?>
