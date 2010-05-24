<?php
/**
 * Users groups management
 *
 * @package modules
 * @author dio
 **/
class elSubModuleGroups extends elModule {
	var $_mMapAdmin = array(
		'edit'  => array('m'=>'editGroup', 'ico'=>'icoUsersGroupNew', 'l'=>'Create group', 'g' => 'Actions'),
		'rm'    => array('m'=>'rmGroup'),
		'clean' => array('m'=>'cleanGroup'),
		'acl'   => array('m'=>'editAcl')
		);

	var $_mMapConf  = array('conf'  => array('m'=>'configure', 'ico'=>'icoUsersGroupSet', 'l'=>'Groups export'));

	/**
	 * display groups list
	 *
	 * @return void
	 **/
	function defaultMethod() {
		$igroups = $this->_ats->getImportGroups();
		$group   = $this->_ats->createGroup();
		$groups  = $group->collection(false);
		$this->_initRenderer();
		$this->_rnd->rndGroups($groups, $group->countUsers());
	}

	/**
	 * create/edit group
	 *
	 * @return void
	 **/
	function editGroup() {
		
		if (!$this->_ats->isLocalAuth()) {
			elThrow(E_USER_WARNING, 'Remote authorization is used. Groups cannot be modified', null, EL_URL.$this->_smPath);
		}
		
		$group = $this->_ats->createGroup();
		$group->idAttr((int)$this->_arg());
		$group->fetch();
		if (1 == $group->GID) {
			elThrow(E_USER_WARNING, 'Group "root" can not be modified or deleted', null, EL_URL.$this->_smPath);
		}

		if (!$group->editAndSave()) {
			$this->_initRenderer();
			$this->_rnd->addToContent($group->formToHtml());
		} else {
			elMsgBox::put('Data saved');
			elLocation(EL_URL.$this->_smPath);
		}
	}

	/**
	 * delete group
	 *
	 * @return void
	 **/
	function rmGroup() {
		
		if (!empty($_POST['action'])) {
			if (!$this->_ats->isLocalAuth()) {
				elThrow(E_USER_WARNING, 'Remote authorization is used. Groups cannot be modified', null, EL_URL.$this->_smPath);
			}
			$group = $this->_ats->createGroup();
			$ID    = (int)$this->_arg();
			$group->idAttr($ID);
			if (!$group->fetch()) {
				elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array(m('Users group'),$ID), EL_URL.$this->_smPath);
			}
			if (1 == $group->GID) {
				elThrow(E_USER_WARNING, 'Group "root" can not be modified or deleted', null, EL_URL.$this->_smPath);
			}
			$group->delete();
			elMsgBox::put( sprintf(m('Group "%s" was deleted'),  $group->name) );
		}
		elLocation(EL_URL.$this->_smPath);
	}

	/**
	 * delete users from group
	 *
	 * @return void
	 **/
	function cleanGroup() {
		
		if (!empty($_POST['action'])) {
			if (!$this->_ats->isLocalAuth()) {
				elThrow(E_USER_WARNING, 'Remote authorization is used. Groups cannot be modified', null, EL_URL.$this->_smPath);
			}
			$group = $this->_ats->createGroup();
			$ID    = (int)$this->_arg();
			$group->idAttr($ID);
			if (!$group->fetch()) {
				elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array(m('Users group'),$ID), EL_URL.$this->_smPath);
			}
			$group->deleteUsers();
			elMsgBox::put( sprintf(m('All users was removed from group "%s"'), $group->name) );
		}
		elLocation(EL_URL.$this->_smPath);
	}

	/**
	 * set acl for group
	 *
	 * @return void
	 **/
	function editACL() {
		
		$group = $this->_ats->createGroup();
		$ID    = (int)$this->_arg();
		$group->idAttr($ID);
		if (!$group->fetch()) {
			elThrow(E_USER_WARNING, 'There is no object "%s" with ID="%d"', array(m('Users group'),$ID), EL_URL.$this->_smPath);
		}
		if (1 == $group->GID) {
			elThrow(E_USER_WARNING, 'Group "root" can not be modified or deleted', null, EL_URL.$this->_smPath);
		}
		
		if (!$group->setACL()) {
			$this->_initRenderer();
			$this->_rnd->addToContent( $group->formToHtml() );
		} else {
			elMsgBox::put( sprintf(m('Permissions for group "%s" was saved'), $group->name) );
			elLocation( EL_URL.$this->_smPath );
		}
	}

	function configure()
	{
		$ats = &elSingleton::getObj('elATS');
		$this->_initRenderer();
		if ( $this->_ats->isLocalAuth() )
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
	/**
	 * disable methods base upon auth type
	 *
	 * @return void
	 **/
	function _onInit() {
		$this->_ats = &elSingleton::getObj('elATS');
		if ($this->_ats->isLocalAuth()) {
			unset($this->_mMap['conf']);
		} 
	}


}// END class 

?>