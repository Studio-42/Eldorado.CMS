<?php

class elForumCategory extends elDataMapping
{
	var $_tb        = 'el_forum_cat';
	var $_tbt       = 'el_forum_topic';
	var $_tbp       = 'el_forum_post';
	var $_tbrbac    = 'el_forum_rbac';
	var $_tbrl      = 'el_forum_role';
	var $_tbm       = 'el_forum_moderator';
	var $_tblrf     = 'el_forum_log_read_forum';
	var $_tblrt     = 'el_forum_log_read_topic';
	var $_objName   = 'Forum';
	var $_hasChilds = null;
	var $ID         = 0;
	var $name       = '';
	var $descrip     = '';
	var $numTopics  = 0;
	var $numPosts   = 0;
	var $allowPosts = 1;
	var $countPosts = 1;
	
	function childs( $deep=0, $obj=false, $assoc=false )
	{
		$db  = & elSingleton::getObj('elDb');
		$sql = 'SELECT  '.$this->attrsToString('ch').', post.subject '
				.'FROM '.$this->_tb.' AS p, '.$this->_tb.' AS ch LEFT JOIN '.$this->_tbp.' AS post ON (post.cat_id=ch.id)'
	          	.' WHERE p.id=\''.$this->ID.'\' AND ch._left BETWEEN p._left AND p._right '
		        .' AND ch.level>p.level '.($deep ? 'AND ch.level<=p.level+'.$deep: '')
		        .' GROUP BY ch.id ORDER BY ch._left, post.mtime DESC';
		if ( !$obj )
		{
			return $db->queryToArray($sql, $assoc ? $this->_id : null);
		}
		$ret = array();
		$db->query($sql);
		while ($r = $db->nextRecord())
		{
			$ret[ $assoc ? $r[$this->_id] : null] = $this->copy($r);
		}
		return $ret;
	}

	function path()
	{
		$db  = & elSingleton::getObj('elDb');
		$sql = 'SELECT  p.id, p.name '.' FROM '.$this->_tb.' AS p, '.$this->_tb.' AS ch '
	          .'WHERE ch.id=\''.$this->ID.'\' AND ch._left BETWEEN p._left AND p._right AND p.level>0 '
	          .'ORDER BY p._left' ;
		return $db->queryToArray($sql);
	}
	
	function makeRootNode($name)
	{
	  	$this->_initTree();
	  	if ($this->tree->makeRootNode( $name ))
	  	{
	  		$this->ID    = 1;
		  	$this->name  = $name;
		  	$this->level = 0;
		  	return true;
	  	}
		return false;
	}
	
	function editAndSave()
	{
	    $this->_initTree();
		$this->parentID = $this->ID ? $this->tree->getParentID( $this->ID ) : 0;
	    $this->_makeForm();
	    if ( $this->_form->isSubmitAndValid() )
	    {
	      	$this->attr( $this->_form->getValue() );
	      	$parentID = $this->_form->getElementValue('parent_id');
	      	if ( !$this->ID )
	      	{
	        	//new node
	        	$vals = $this->attr(); unset($vals['id'], $vals['level']);
	        	return $this->tree->insert( $parentID, $vals );
	      	}
			elseif ($this->ID == 1)
			{
				return $this->save();
			}
	      	else
	      	{
				return ( $parentID <> $this->parentID && $this->ID<>1 && !$this->tree->move($this->ID, $parentID) ) //move node to another parent
					? false
					: $this->save();
	      	}
	    }
	    return false;
	}
	
	function changePermissions($roles, $parentRoles=null)
	{
		parent::_makeForm();
		$this->_form->label = sprintf(m('Permissions for users groups on "%s"'), $this->name);
		$msg = m('Permissions details').' <a href="#" onclick="return popUp(\''.(EL_URL.EL_URL_POPUP.'/roles/').', 500, 700\')" >'.m('see here')."</a>";
		$this->_form->add( new elCData('c1', $msg));
		
		$db = & elSingleton::getObj('elDb');
		$groups = array(-1=>m('Non athorized users'), 0=>m('Authorized users')) 
				+ $db->queryToArray('SELECT gid, name FROM el_group WHERE gid>1', 'gid', 'name');
		$rList = array(	-1 => m('Same as all authorized users'), 0=>m('Access denied'),) 
			+ array_map('m', $db->queryToArray('SELECT id, name FROM '.$this->_tbrl.' WHERE id<8 ORDER BY id', 'id', 'name'));
		
		foreach ($groups as $gid => $name) 
		{
			if (-1 == $gid)
			{
				$rolesList = array_slice($rList, 1, 6);
			}
			elseif ( 0 == $gid )
			{
				$rolesList = array_slice($rList, 1);
			}
			else
			{
				$rolesList = $rList;
			}
			$freeze = false;
			if (isset($parentRoles[$gid]) && $parentRoles[$gid] == 0)
			{
				$freeze = true;
				$rid = 0;
				$rolesList[0] .= ' ('.m('Inherit from parent forum').')';
			}
			elseif ( isset($roles[$gid]) )
			{
				$rid = $roles[$gid];
				if (isset($parentRoles[$gid]) && $rid == $parentRoles[$gid])
				{
					$rolesList[$rid] .= ' ('.m('Inherit from parent forum').')';
				}
			}
			else
			{
				if ( $gid == -1)
				{
					$rid = 2;
				}
				elseif ( $gid == 0 )
				{
					$rid = 4;
				}
				else
				{
					$rid = -1;
				}
			}

			$this->_form->add( new elSelect('roles['.$gid.']', $name, $rid, $rolesList, null, $freeze) );
		}
		
		
		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue();
			$db->query( sprintf('DELETE FROM %s WHERE cat_id=%d', $this->_tbrbac, $this->ID));
			$db->optimizeTable($this->_tbrbac);
			$sql = '';
			foreach ($data['roles'] as $gid=>$role)
			{
				if ((isset($parentRoles[$gid]) && $role == $parentRoles[$gid])|| $role==-1)
				{
					continue;
				}
				$sql .= '('.$this->ID.', '.intval($gid).', '.intval($role).'), ';
			}
			if ( $sql )
			{
				$db->query('INSERT INTO '.$this->_tbrbac.' (cat_id, gid, rid) VALUES '.substr($sql, 0, -2));
			}
			return true;
		}
		
	}
	
	function setModerators($gids)
	{
		parent::_makeForm();
		$this->_form->label = sprintf(m('Moderators for forum "%s"'), $this->name);
		$db = & elSingleton::getObj('elDb');
		
		$sql = 'SELECT g.gid, g.name AS group_name, u.uid, u.login, ug.group_id, ug.user_id '
				.'FROM el_group AS g, el_user AS u, el_user_in_group AS ug '
				.'WHERE g.gid '.($gids ? ' IN ('.implode(', ', $gids).')' : '>1')
				.' AND ug.group_id=g.gid AND u.uid=ug.user_id ORDER BY g.gid, u.login';

		$db->query($sql);
		$groups = $users = array();
		while ($r = $db->nextRecord())
		{
			if (empty($groups[$r['gid']]))
			{
				$groups[$r['gid']] = $r['group_name'];
				$users[$r['gid']] = array();
			}
			$users[$r['gid']][$r['uid']] = $r['login'];
		}
		
		$this->_form->add( new elSelect('gids', m('Select group to view users list'), null, $groups) );
		foreach ($users as $gid=>$u)
		{
			$this->_form->add( new elCheckBoxesgroup('uid['.$gid.']', m('Users'), null, $u) );
		}
		$js = "$().ready( function() {
			$('select#gids').change( function() {
				var gid=$(this).val();
				$(this).parents('table').find('tr:has(:checkbox)').each( function() {
					if ( $(':checkbox#uid\\\['+gid+'\\\]\\\[0\\\]', this).length ) {
						$(this).show();
					} else {
						$(this).hide()
					}
				});
			});
			$('select#gids').trigger('change')
		});";
		elAddJs($js, EL_JS_SRC_ONREADY);
		
		if ( $this->_form->isSubmitAndValid() )
		{
			$data = $this->_form->getValue();
			$users = array();
			foreach ($data['uid'] as $uids) 
			{
				if (!empty($uids))
				{
					$users = array_merge($users, array_map('intval', $uids));
				}
			}
			$db->query(sprintf('DELETE FROM %s WHERE cat_id=%d', $this->_tbm, $this->ID));
			if ($users)
			{
				foreach ($users as $uid)
				{
					$db->query(sprintf('INSERT INTO %s (cat_id, uid) VALUES (%d, %d)', $this->_tbm, $this->ID, $uid));
				}
			}
			return true;
		}
	}
	
	function move($up=true)
	{
	    $this->_initTree();
		return ($nID = $this->tree->getNeighbourID($this->ID, $up)) ? $this->tree->exchange( $this->ID, $nID ) : false;
	    if ( !($nID = $this->tree->getNeighbourID($this->ID, $up) ) )
	    {
	      return false;
	    }
	    return $this->tree->exchange( $this->ID, $nID );
	}
	
	function hasChilds()
	{
		if ( is_null($this->_hasChilds) && $this->ID )
		{
			$db = & elSingleton::getObj('elDb');
			$db->query( sprintf('SELECT ch.id FROM %s AS ch, %s AS p WHERE p.id=%d AND ch._left BETWEEN p._left AND p._right AND ch.id<>p.id', $this->_tb, $this->_tb, $this->ID));
			$this->_hasChilds = (bool)$db->numRows();
		}
		return $this->_hasChilds;
	}
	
	function delete()
	{
	    $this->_initTree();
	    if ( $this->tree->delete( $this->ID ) )
   	    {
  	    	$db = & elSingleton::getObj('elDb');
			$db->query(
				sprintf(
					'DELETE t, p, rbac, lf, lt FROM %s AS t, %s AS p, %s AS rbac, %s AS lf, %s AS lt 
					WHERE t.cat_id=%d AND p.cat_id=%d AND rbac.cat_id=%d AND lf.cat_id=%d AND lt.t_id=t.id', 
					$this->_tbt, $this->_tbp, $this->_tbrbac, $this->_tblrf, $this->_tblrt, $this->ID, $this->ID, $this->ID, $this->ID)
					);
			$db->optimizeTable($this->_tbt, $this->_tbp, $this->_tbrbac, $this->_tblrf, $this->_tblrt);
		    return true;
  	    }
		return false;
	}

	
	/**********************************/
	/**           PRIVATE            **/
	/**********************************/
	
	function _makeForm()
	{
	    parent::_makeForm();
	    if ( $this->ID <> 1 )
		{
			$this->_form->add( new elSelect('parent_id', m('Parent forum'), $this->parentID,  $this->tree->quickList()) );
		}
	    $this->_form->add( new elText('name', m('Name'), $this->name) );
	    $this->_form->add( new elTextArea('descrip', m('Description'), $this->descrip) );
		$this->_form->add( new elSelect('allow_posts', m('Allow posts in this forum'), $this->allowPosts, $GLOBALS['yn']));
		$this->_form->add( new elSelect('count_posts', m('Counts posts in this forum for authors'), $this->countPosts, $GLOBALS['yn']));
	    $this->_form->setRequired('name');
	  }
	
	function _initMapping()
	{
	    return array(
			'id'           => 'ID', 
			'name'         => 'name', 
			'level'        => 'level', 
			'descrip'      => 'descrip',
			'num_topics'   => 'numTopics',
			'num_posts'    => 'numPosts',
			'last_post_id' => 'lastPostID',
			'allow_posts'  => 'allowPosts',
			'count_posts'  => 'countPosts'
			);
	}

	function _attrsForSave()
	{
		$attrs = $this->attr();
	  	unset( $attrs['level']);
	  	return $attrs;
	}

	function _initTree()
	{
	  	$this->tree = & elSingleton::getObj('elDbNestedSets', $this->_tb);
	}
}

?>