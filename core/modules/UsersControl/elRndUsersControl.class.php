<?php

class elRndUsersControl extends elModuleRenderer
{
	var $_tpls = array( 
		'groups'  => 'groups.html',
		'profile' => 'profile.html' );

	/**
	 * render users list
	 *
	 * @param  array  $users   users
	 * @param  array  $groups  users groups
	 * @param  int    $page    current page number
	 * @param  int    $num	   pages number
	 * @return void
	 **/
	function rndUsers($users, $groups, $page, $num) {
		elLoadJQueryUI();
		elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
		
		$this->_setFile();

		foreach ($users as $user) {
			$data = array(
				'uid'    => $user->UID,
				'login'  => $user->login,
				'email'  => $user->email,
				'visits' => $user->visits,
				'atime'  => $user->atime ? date(EL_DATE_FORMAT, $user->atime) : '',
				'groups' => isset($groups[$user->UID]) ? implode(', ', $groups[$user->UID]) : ''
				);
			$this->_te->assignBlockVars('USER', $data);
			if ($this->_admin) {
				$this->_te->assignBlockVars('USER.ADMIN', array('uid' => $user->UID, 'login' => $user->login), 1);
			}
		}

		if ($num>1) {
			$this->_te->setFile('PAGER', 'common/pager.html');
			if ($page > 1) {
				$this->_te->assignBlockVars('PAGER.PREV', array('url'=>EL_URL, 'num'=>$num-1));
			}
			for ($i=1; $i<=$num; $i++) {
				$this->_te->assignBlockVars($i != $page ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('url'=>EL_URL, 'num'=>$i));
			}
			if ($page < $num) {
				$this->_te->assignBlockVars('PAGER.NEXT', array('url'=>EL_URL, 'num'=>$page+1));
			}
			$this->_te->parse('PAGER');
		}
	}



	//**************    GROUPS CONTROL SUBMODULE *************************//

	/**
	 * render groups list
	 *
	 * @return void
	 **/
	function rndGroups($groups, $cnt) {
		elLoadJQueryUI();
		elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
		
		$this->_setFile('groups');
		
		foreach ($groups as $id=>$g) {
			$g['numUsers'] = isset($cnt[$id]) ? $cnt[$id] : 0;
			$this->_te->assignBlockVars('GROUP', $g);
			if ($this->_admin) {
				$this->_te->assignBlockVars('GROUP.ADMIN', array('gid' => $id), 1);
			}
		}
	}

	function _rndGroups( $groups)
	{
		elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
		$this->_setFile('groups');
		foreach ($groups as $g)
		{
			$this->_te->assignBlockVars('GROUP', $g);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('GROUP.ADMIN', $g, 1);
			}
		}
	}

	function rndGroupsConfig($groups)
	{
		$this->_setFile('conf');
		foreach ( $groups as $gid=>$name )
		{
			$this->_te->assignBlockVars('GROUP', array('gid'=>$gid, 'name'=>$name) );
		}

	}


}

?>
