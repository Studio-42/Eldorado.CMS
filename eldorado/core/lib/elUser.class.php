<?php

class elUser extends elMemberAttribute
{
	var $tb        = 'el_user';
	var $db        = null;
	var $_uniq     = 'uid';
	var $UID       = 0;
	var $groups    = array();
	var $login     = '';
	var $crTime    = 0;
	var $mTime     = 0;
	var $aTime     = 0;
	var $visits    = 0;
	var $prefs     = array();
	var $profile   = null;
	var $_fullName = false;

	function elUser()
	{
		if ( !isset($_SESSION['userPrefs']) )
		{
			$_SESSION['userPrefs'] = array();
		}
		$this->prefs = @$_SESSION['userPrefs'];
	}

	function &getProfile()
	{
		if ( !$this->profile )
		{
			$this->_initProfile();
		}
		if ( $this->UID <> $this->profile->UID )
		{
			$this->_updateProfile();
		}
		return $this->profile;
	}

	function allowFullName($allow=true)
	{
	  $this->_fullName = (bool)$allow;
	}

	function getFullName($force=false)
	{
		if ( ($this->_fullName || $force) && $this->UID )
		{
			$profile = & $this->getProfile();
			return $profile->getFullName();
		}
		return $this->login;
	}

	function getEmail($format=true)
	{
		if ( $this->UID )
		{
			$profile = & $this->getProfile();
			return $profile->getEmail($format);
		}
		return '';
	}

	function getProfileAttr($attr)
	{
	 if ( $this->UID )
		{
			$profile = & $this->getProfile();
			return $profile->attr($attr);
		}
		return '';
	}

	function getProfileAttrs()
	{
	 if ( $this->UID )
		{
			$profile = & $this->getProfile();
			return $profile->attr();
		}
		return array();
	}

	function getGroups()
	{
		return $this->groups;
	}

	function isRoot()
	{
		return 1 == $this->UID;
	}

	function isInGroupRoot()
	{
		return in_array(1, $this->groups);
	}

	function isAuthed()
	{
		return $this->UID;
	}

	function onLogin($attrs, $dbHash, $al, $onlyGroups=null, $upVisit=false)
	{
		$now = time();
		$attrs['atime'] = $now;
		$this->setAttrs($attrs);
		$this->_updateProfile();
		$this->_loadGroups($onlyGroups);
		if ( $upVisit )
		{
			$this->_loadPrefs();
		}

		$key = md5($this->UID.' '.$this->login.' '.$dbHash);
		$_SESSION['UID'] = $this->UID;
		$_SESSION['al']  = $al;
		$_SESSION['key'] = $key;
		$sql = 'UPDATE el_user SET atime='.$now
					.($upVisit ? ', visits=visits+1' : '')
					.' WHERE uid='.$this->UID;

		$this->db->query($sql);
	}

	function onLogout()
	{
		$this->_savePrefs();
		$this->cleanAttrs();
		$this->_updateProfile();
		$this->groups = array();
		$_SESSION['UID'] = 0;
		$_SESSION['key'] = '';
	}

	function toArray()
	{
		$profile = & $this->getProfile();
		return $profile->toArray();
	}

	function getPref($name)
	{
		return isset($this->prefs[$name]) ? $this->prefs[$name] : null;
	}

	function setPref($name, $val)
	{
		$this->prefs[$name]    = $val;
		$_SESSION['userPrefs'] = $this->prefs;
	}

	function dropPref( $name )
	{
		if ( isset($this->prefs[$name]) )
		{
			unset($this->prefs[$name]);
			$_SESSION['userPrefs'] = $this->prefs;
		}
	}

	//*********************************************//
	//        		PRIVATE METHODS									 //
	//*********************************************//
	function _initMapping()
	{
		$map = array(
								'uid'    => 'UID',
								'login'  => 'login',
								'atime'  => 'aTime',
								'crtime' => 'crTime',
								'mtime'  => 'mTime',
								'visits' => 'visits'
								);
		return $map;
	}

	function _initProfile()
	{
		if ( !$this->profile )
		{
			$this->profile     = & new elUserProfile();
			$this->profile->db = & $this->_getDb();
		}
	}

	function _updateProfile()
	{
		$this->_initProfile();
		if ( $this->UID )
		{
			$this->profile->idAttr($this->UID);
			$this->profile->fetch();
		}
		else
		{
			$this->profile->clean();
		}
	}

	function _loadGroups($onlyGroups=null)
	{
		if ($this->UID )
		{
			if ( !$this->db )
			{
				$this->_getDb();
			}
			$sql = 'SELECT group_id FROM el_user_in_group WHERE user_id='.$this->UID;
			if ( !empty($onlyGroups) && is_array($onlyGroups) )
			{
				$sql .= ' AND group_id IN (1, '.implode(',', $onlyGroups).')';
			}
			$this->groups = $this->db->queryToArray($sql, null, 'group_id');
		}
	}

	/**
	 * Load prefrences and put in session
	 * @return viod
	 */
	function _loadPrefs()
	{
		$ats = & elSingleton::getObj('elATS');
		$db  = & $ats->getACLDb();
		$this->prefs = array();
		$db->query('SELECT name, val, is_serialized FROM el_user_pref WHERE user_id=\''.$this->UID.'\'');
		while ( $row = $db->nextRecord())
		{
			$this->prefs[$row['name']] = $row['is_serialized'] ? unserialize( $row['val'] ) : $row['val'];
		}
		$_SESSION['userPrefs'] = $this->prefs;
	}

	/**
	 * Save prefrences in Db and clean it
	 * @return void
	 */
	function _savePrefs()
	{
		if (!$this->UID)
		{
			return;
		}
		$ats = & elSingleton::getObj('elATS');
		$db  = & $ats->getACLDb();
		$db->query( 'DELETE FROM el_user_pref WHERE user_id=\''.$this->UID.'\'' );
		$db->optimizeTable( 'el_user_pref' );
		if ( $this->prefs )
		{
			$db->prepare( 'INSERT INTO el_user_pref (user_id, name, val, is_serialized) VALUES ',
										'(\'%d\', \'%s\', \'%s\', \'%d\')' );
			foreach ( $this->prefs as $n=>$v )
			{
				$isSerialize = (int)!is_string($v);
				$db->prepareData( array($this->UID, $n, !$isSerialize ? $v : serialize($v), $isSerialize) );
			}
			$db->execute();
		}
		$this->prefs = $_SESSION['userPrefs'] = array();
	}

	function &_getDb()
	{
		if ( !$this->db )
		{
			$ats      = & elSingleton::getObj('elATS');
			$this->db = & $ats->getAuthDb();
		}
		return $this->db;
	}

}

?>
