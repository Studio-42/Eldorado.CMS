<?php

class elUsersGroup extends elMemberAttribute
{
  var $GID      = 0;
  var $name     = '';
  var $perm     = 0;
  var $mtime    = 0;
  var $_uniq    = 'gid';
  var $tb       = 'el_group';
  var $_objName = 'Users group';

  function _initMapping()
    {
      $map = array('gid'=>'GID', 'name'=>'name');
      return $map;
    }

  function isEmpty()
    {
      $db = & $this->_getDb();
      $db->query('SELECT user_id FROM el_user_in_group WHERE group_id=\''.$this->GID.'\'');
      return !$db->numRows();
    }

  function makeForm()
    {
      parent::makeForm();
      $this->form->add( new elText('name', m('Group name'), $this->name) );
      $this->form->setRequired('name');
    }

  function setAcl()
    {
      $this->_loadAcl();
      $this->makeAclForm();

      if ( $this->form->isSubmitAndValid() )
	{
	  $vals = $this->form->getValue(true); 
	  $acl = array();
	  foreach ( $vals['perm'] as $id=>$perm )
	    {
	      if ( $perm )
		{
		  $acl[$id] = $perm;
		}
	    }
	  $this->saveAcl( $acl );
	  return true;
	}
    }

  function saveAcl( $acl )
    {
      $db = &  $this->_getDb();
      $db->query( 'DELETE FROM el_group_acl WHERE group_id=\''.$this->GID.'\'' );
      $db->optimizeTable('el_group_acl');
      if ( $acl )
	{
	  $db->prepare('INSERT INTO el_group_acl (group_id, page_id, perm) VALUES ', '(\'%d\', \'%d\', \'%d\')');
	
	  foreach ( $acl as $id=>$perm )
	    {
	      if ( $perm > 0 )
		{
		  $db->prepareData( array($this->GID, $id, $perm) );
		}
	    }
	  $db->execute();
	}
    }

  function makeAclForm()
    {
      parent::makeForm();
      $this->form->setLabel( sprintf( m('Permissions for group "%s"'), $this->name) );
      $perms =  getPermNames();
      $perms[0] = m('Undefined');
      ksort($perms);
      foreach ( $this->acl as $id=>$page )
	{
	  $this->form->add( new elSelect('perm['.$id.']', $page['name'], $page['perm'], $perms) );
	}
    }

  function _loadAcl()
    {
      $db = & $this->_getDb();
      $sql = 'SELECT id, CONCAT( REPEAT(" - ", level-1), name) AS name, el_group_acl.perm '
	.'FROM el_menu LEFT JOIN el_group_acl '
	.'ON page_id=id AND group_id=\''.$this->GID.'\' '
	.'WHERE level>0 ORDER BY _left';
      $db->query( $sql );
      $this->acl = $db->queryToArray($sql, 'id');
    }

}
 
?>