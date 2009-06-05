<?php

class elSubModuleGroups extends elModule
{
	var $_mMapAdmin = array(
		'edit'  => array('m'=>'editGroup', 'ico'=>'icoUsersGroupNew', 'l'=>'Create group', 'g' => 'Actions'),
		'rm'    => array('m'=>'rmGroup'),
		'clean' => array('m'=>'cleanGroup'),
		'acl'   => array('m'=>'editAcl')
		);

	var $_mMapConf  = array('conf'  => array('m'=>'configure', 'ico'=>'icoUsersGroupSet', 'l'=>'Groups export'));

	var $_prnt = false;

	// **************************  PUBLIC METHODS  ******************************* //

	function defaultMethod()
	{
		$ats = & elSingleton::getObj('elATS'); 
		$sql = 'SELECT gid, name, COUNT(uid) AS numUsers '
					.'FROM el_group LEFT JOIN el_user_in_group ON group_id=gid '
					.'LEFT JOIN el_user ON user_id=uid  '
					.'WHERE gid IN ('.implode(',', array_keys($ats->getGroupsList())).')  GROUP BY gid ORDER BY gid';
		$this->_initRenderer();
		$this->_rnd->rndGroups( $ats->_dbAuth->queryToArray($sql, 'gid') );
	}


	function editGroup()
	{
		$group = & elSingleton::getObj( 'elUsersGroup' );
		$ats = & elSingleton::getObj('elATS');
		$group->db = &$ats->_dbAuth;
		$group->setUniqAttr( (int)$this->_arg() );
		$group->fetch();
		if ( 1 == $group->GID )
		{
			elThrow(E_USER_WARNING, 'Group "root" can not be modified or deleted', null, EL_URL.$this->_smPath);
		}

		if ( !$group->editAndSave() )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent($group->formToHtml());
		}
		else
		{
			if ( !$ats->isLocalAuth() )
			{
				$ats->addGroupToImportList( $group->GID, $group->name );
			}
			elMsgBox::put('Data saved');
			elLocation(EL_URL.$this->_smPath);
		}
	}

	function rmGroup()
	{
		$group = & elSingleton::getObj( 'elUsersGroup' );
		$ats = & elSingleton::getObj('elATS');
		$group->db = &$ats->_dbAuth;
		$group->setUniqAttr( (int)$this->_arg() );
		if ( !$group->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
			array(m('Users group'),$ID), EL_URL.$this->_smPath);
		}
		if ( 1 == $group->GID )
		{
			elThrow(E_USER_WARNING, 'Group "root" can not be modified or deleted', null, EL_URL.$this->_smPath);
		}
		if ( !$group->isEmpty() )
		{
			elThrow(E_USER_WARNING, 'Non empty object "%s" "%s" can not be deleted',
			array(m('Users group'), $group->name), EL_URL.$this->_smPath);
		}

		$group->delete();
		if ( !$ats->isLocalAuth() )
		{
			$ats->rmGroupFromImportList( $group->GID );
		}
		elMsgBox::put( sprintf(m('Group "%s" was deleted'),  $group->name) );
		elLocation(EL_URL.$this->_smPath);

	}

	function cleanGroup()
	{
		$group = & elSingleton::getObj( 'elUsersGroup' );
		$ats = & elSingleton::getObj('elATS');
		$group->db = &$ats->_dbAuth;
		$group->setUniqAttr( (int)$this->_arg() );
		if ( !$group->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
			array(m('Users group'),$ID), EL_URL.$this->_smPath);
		}
		$sql = 'DELETE FROM el_user_in_group WHERE group_id=\''.$group->GID.'\'';
		if ( 1 == $group->GID )
		{
			$sql .= ' AND user_id!=1';
		}
		$ats->_dbAuth->query($sql);
		$ats->_dbAuth->optimizeTable('el_user_in_group');
		elMsgBox::put( sprintf(m('All users was removed from group "%s"'), $group->name) );
		elLocation(EL_URL.$this->_smPath);
	}


	function editACL()
	{
		$group = & elSingleton::getObj( 'elUsersGroup' );
		$ats   = & elSingleton::getObj( 'elATS' );
		$group->db = &$ats->_dbAuth;
		$group->setUniqAttr( (int)$this->_arg() );

		if ( !$group->fetch() )
		{
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"',
			array(m('Users group'),$ID), EL_URL.$this->_smPath);
		}
		if ( 1 == $group->GID )
		{
			elThrow(E_USER_WARNING, 'Group "root" can not be modified or deleted', null, EL_URL.$this->_smPath);
		}
		$group->db = &$ats->_dbACL;
		if ( !$group->setACL() )
		{
			$this->_initRenderer();
			$this->_rnd->addToContent( $group->formToHtml() );
		}
		else
		{
			elMsgBox::put( sprintf(m('Permissions for group "%s" was saved'), $group->name) );
			elLocation( EL_URL.$this->_smPath );
		}
	}

	function configure()
	{
		$ats = &elSingleton::getObj('elATS');
		$this->_initRenderer();
		if ( $ats->isLocalAuth() )
		{
			elThrow(E_USER_WARNING, 'There is local authorization is used now. Groups export does not available.',
			null, EL_URL.$this->_smPath);
		}
		if ( !$ats->setImportGroupsList() )
		{
			$this->_rnd->addToContent( $ats->formToHtml());
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.$this->_smPath);
		}
	}

	// =========================  PRIVARE METHODS  ============================ //

	function _onInit()
	{
		$ats = &elSingleton::getObj('elATS');
		if ( $ats->isLocalAuth() )
		{
			unset($this->_mMap['conf']);
		}
	}


}

?>