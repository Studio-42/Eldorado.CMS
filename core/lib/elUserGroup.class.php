<?php

class elUserGroup extends elDataMapping {
	var $GID    = 0;
	var $name   = '';
	var $perm   = 0;
	var $mtime  = 0;
	var $_tb    = 'el_group';
	var $_id    = 'gid';
	var $__id__ = 'GID';
	
	
	/**
	 * return number of user in every groups
	 *
	 * @return array
	 **/
	function countUsers() {
		$sql = 'SELECT gid, COUNT(user_id) AS num FROM el_group, el_user_in_group, el_user WHERE group_id=gid AND user_id=uid GROUP BY gid';
		return $this->db->queryToArray($sql, 'gid', 'num');
	}
	
	/**
	 * update mtime before save
	 *
	 * @return array
	 **/
	function _attrsForSave() {
		$this->mtime = time();
		return parent::_attrsForSave();
	}
	
	/**
	 * return attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {
		return array(
			'gid'   => 'GID',
			'name'  => 'name',
			'perm'  => 'perm',
			'mtime' => 'mtime'
			);
	}
	
}

?>