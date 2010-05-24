<?php
/**
 * user groups routine
 *
 * @package core
 * @author dio
 **/
class elUserGroup extends elDataMapping {
	var $GID        = 0;
	var $name       = '';
	var $perm       = 0;
	var $mtime      = 0;
	var $onlyGroups = array();
	var $_tb        = 'el_group';
	var $_id        = 'gid';
	var $__id__     = 'GID';
	var $_objName   = 'Users group';
	
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
	 * return groups id/names for required users
	 *
	 * @param  array  $ids  users ids
	 * @return array
	 **/
	function usersGroups($ids) {
		$ret = array();
		if (!empty($ids) AND is_array($ids)) {
			$sql = 'SELECT g2u.user_id AS uid, g.gid, g.name '
					.'FROM el_group AS g, el_user_in_group AS g2u '
					.'WHERE g2u.user_id IN ('.implode(',', $ids).') AND g.gid=g2u.group_id '
					.($this->onlyGroups ? ' AND g.gid IN ('.implode(',', $this->onlyGroups).') ' : '').
					'ORDER BY gid';
			$this->db->query($sql);
			while ($r = $this->db->nextRecord()) {
				if (!isset($ret[$r['uid']])) {
					$ret[$r['uid']] = array();
				}
				$ret[$r['uid']][$r['gid']] = $r['name'];
			}
		}
		return $ret;
	}
	
	/**
	 * return groups
	 *
	 * @param  bool   $obj   return groups as objects?
	 * @param  bool   $local return only local groups
	 * @return array
	 **/
	function collection($obj=true, $local=true) {
		return parent::collection($obj, true, $local && $this->onlyGroups ? 'gid IN ('.implode(',', $this->onlyGroups).')' : null);
	}

	/**
	 * remove users from current group
	 *
	 * @return void
	 **/
	function deleteUsers() {
		if ($this->GID) {
			$this->db->query('DELETE FROM el_user_in_group WHERE group_id="'.$this->GID.'"');
			$this->db->optimizeTable('el_user_in_group');
			$this->db->query('REPLACE INTO el_user_in_group SET user_id=1, group_id=1');
		}
	}

	/**
	 * create form/set group acl
	 *
	 * @return bool
	 **/
	function setACL() {
		if ($this->GID) {
			$nav   = & elSingleton::getObj('elNavigator');
			$db    = & elSingleton::getObj('elDb');
			$acl   = $db->queryToArray(sprintf('SELECT page_id, perm FROM el_group_acl WHERE group_id=%d', $this->GID), 'page_id', 'perm');
			$perms = array(
				0        => m('Undefined'), 
				EL_READ  => m('Read only'), 
				EL_WRITE => m('Read/write'), 
				EL_FULL  => m('Full control')
			);
			parent::_makeForm();

			$this->_form->label = sprintf(m('Permissions for group "%s"'), $this->name);
			foreach ($nav->menus as $p) {
				$val   = isset($acl[$p['id']]) ? $acl[$p['id']] : 0;
				$label = str_repeat('+ ', $p['level']-1).$p['name'];
				$this->_form->add(new elSelect('perm['.$p['id'].']', $label, $val, $perms));
			}
			
			if ($this->_form->isSubmitAndValid()) {
				$data = $this->_form->getValue();
				$perm = array();
				foreach ($data['perm'] as $id=>$v) {
					if ($v>0) {
						$perm[] = array($this->GID, $id, $v);
					}
				}
				
				$db->query(sprintf('DELETE FROM el_group_acl WHERE group_id=%d', $this->GID));
				$db->optimizeTable('el_group_acl');
				if ($perm) {
					$db->prepare('INSERT INTO el_group_acl (group_id, page_id, perm) VALUES ', '(%d, %d, "%d")');
					$db->prepareData($perm, true);
					$db->execute();
				}
				return true;
			}
		}
		
	}

	/**
	 * delete group
	 *
	 * @return void
	 **/
	function delete() {
		parent::delete(array('el_group_acl' => 'group_id', 'el_user_in_group' => 'group_id'));
	}

	/**
	 * create form for create/edit group
	 *
	 * @return void
	 **/
	function _makeForm() {
		parent::_makeForm();
		$this->_form->add( new elText('name', m('Group name'), $this->name) );
	    $this->_form->setRequired('name');
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
	
} // END class 

?>