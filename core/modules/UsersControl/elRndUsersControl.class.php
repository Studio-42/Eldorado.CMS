<?php

class elRndUsersControl extends elModuleRenderer
{
	var $_tpls = array( 'groups'  => 'groups.html',
						'nav'     => 'nav.html',
						'profile' => 'profile.html' );

	var $_admTpls = array('groups'=>'adminGroups.html');

	/**
	 * Рисует список пользователей с сортируемой таблице
	 *
	 * @param  array  $users   массив пользователей
	 * @param  array  $groups  массив имен групп пользователей
	 * @param  int    $page    номер текущей стр списка
	 * @param  int    $num	   кол-во страниц
	 * @return void
	 **/
	function rndUsers( $users, $groups, $page, $num )
	{
		elAddJs('jquery.tablesorter.min.js', EL_JS_CSS_FILE);
		
		$this->_setFile();

		foreach ( $users as $user )
		{
			if ( !empty($groups[$user['uid']]) )
			{
				$user['groups'] = implode(', ', $groups[$user['uid']]);
			}
			$user['atime'] = $user['atime'] ? date('d.m.y H:i', $user['atime']) : '';
			$this->_te->assignBlockVars('USER', $user);
			if ($this->_admin)
			{
				$this->_te->assignBlockVars('USER.ADMIN', array('uid' => $user['uid'], 'login' => $user['login']), 1);
			}
		}

		if ( $num>1)
		{
			$this->_te->setFile('PAGER', 'common/pager.html');
			if ( $page > 1 )
			{
				$this->_te->assignBlockVars('PAGER.PREV', array('url'=>EL_URL, 'num'=>$num-1));
			}
			for ( $i=1; $i<=$num; $i++ )
			{
				$this->_te->assignBlockVars($i != $page ? 'PAGER.PAGE' : 'PAGER.CURRENT', array('url'=>EL_URL, 'num'=>$i));
			}
			if ( $page < $num )
			{
				$this->_te->assignBlockVars('PAGER.NEXT', array('url'=>EL_URL, 'num'=>$page+1));
			}
			$this->_te->parse('PAGER');
		}
	}


	/**
   * отрисовывает профайл пользователя
   */
	function rndProfile( $userData )
	{
		$this->_setFile('profile');
		foreach ( $userData as $r )
		{
			$this->_te->assignBlockVars('PROFILE_ROW', array('label'=>m($r['label']), 'value'=>$r['value']) );
		}
		$this->_te->parse('PAGE', 'PAGE', true, true);
		return $this->_te->getVar('PAGE');
	}

	//**************    GROUPS CONTROL SUBMODULE *************************//

	function rndGroups( $groups)
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
