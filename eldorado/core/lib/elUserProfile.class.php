<?php

class elUserProfile extends elMemberAttribute
{
	var $tb          = 'el_user';
	var $UID         = 0;
	var $_uniq       = 'uid';
	var $login       = '';
	var $email       = '';
	var $f_name      = '';
	var $l_name      = '';
	var $s_name      = '';
	var $postal_code = '';
	var $address     = '';
	var $phone       = '';
	var $fax         = '';
	var $company     = '';
	var $icq_uin     = '';
	var $web_site    = '';

	function toArray()
	{
		$ret = array();
		$sql = 'SELECT field, label FROM el_user_profile WHERE '
					.'field IN (\''.implode("','", $this->memberMapping()).'\') ';
					//.'ORDER BY sort_ndx, field';
		$this->db->query($sql);
		while ($r = $this->db->nextRecord())
		{
			$ret[] = array('label'=>m($r['label']), 'value'=>$this->getAttr($r['field']));
		}
		return $ret;
	}

	function getSkelConf()
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql  = 'SELECT field, rq, sort_ndx FROM el_user_profile_use ORDER BY sort_ndx, field';
		$conf =  $db->queryToArray($sql, 'field');
		$sql  = 'SELECT field, label FROM el_user_profile WHERE field IN(\''.implode("','", array_keys($conf)).'\')';
		$this->db->query($sql);
		while ($r = $this->db->nextRecord())
		{
			$conf[$r['field']]['label'] = $r['label'];
		}
		return $conf;
	}

	function setSkelConf($conf)
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql = 'UPDATE el_user_profile_use SET rq=\'%d\', sort_ndx=\'%d\' WHERE field=\'%s\'';
		foreach ( $conf as $k=>$v )
		{
			$db->safeQuery($sql, $v['rq'], $v['sort_ndx'], $k);
		}
	}

	function getSkel()
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql  = 'SELECT field, rq, sort_ndx FROM el_user_profile_use WHERE field IN (\''
					 .implode("','", $this->listAttrs()).'\') ORDER BY sort_ndx, field ';
		$skel = $db->queryToArray($sql, 'field');

		$sql  = 'SELECT field, label, type, opts, rule, is_func FROM el_user_profile WHERE '
					 .'field IN (\''.implode("','", $this->listAttrs()).'\') ';
		$this->db->query($sql);
		while ( $r = $this->db->nextRecord() )
		{
			$skel[$r['field']] = $skel[$r['field']] + $r;
		}
		return $skel;
	}

	function getFullName()
	{
		$name = trim($this->getAttr('f_name').' '.$this->getAttr('s_name').' '.$this->getAttr('l_name'));
		return $name ? $name : $this->getAttr('login');
	}

	function getEmail($format=true)
	{
	  return $format ? '"'.$this->getFullName().'"<'.$this->getAttr('email').'>' : $this->getAttr('email');
	}

	function _initMapping()
	{
		$sql = 'SELECT field FROM el_user_profile_use WHERE rq>\'0\' OR field=\'login\' OR field=\'email\'';
		$ats = & elSingleton::getObj('elATS');
		$db = & $ats->getACLDb();
		$map = $db->queryToArray($sql, 'field', 'field');
		$map['uid'] = 'UID';
		return $map;
	}

}



?>