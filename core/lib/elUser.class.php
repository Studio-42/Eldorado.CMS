<?php

class elUser extends elDataMapping
{
	var $_tb       = 'el_user';
	var $db        = null;
	var $_id       = 'uid';
	var $__id__    = 'UID';
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
	var $_onlyGroups = array();
	var $_salt = '';

	function elUser($db=null, $groups=array(), $salt='', $fn=false)
	{
		$this->_onlyGroups = $groups;
		$this->_salt = $salt;
		$this->db = $db ? $db : elSingleton::getObj('elDb');
		$this->profile     = & new elUserProfile($this->db);
		$this->_fullName = $fn;
	}

	/**
	 * return true if user is root
	 *
	 * @return bool
	 **/
	function isRoot() {
		return 1 == $this->UID;
	}

	/**
	 * return true if user is in group root
	 *
	 * @return bool
	 **/
	function isInGroupRoot() {
		return in_array(1, $this->groups);
	}
	
	/**
	 * return true if user authed
	 *
	 * @return bool
	 **/
	function isAuthed() {
		return $this->UID;
	}

	/**
	 * return user full name or login
	 *
	 * @param  bool  $force  return full name in any case
	 * @return string
	 **/
	function getFullName($force=false) {
		return $this->_fullName || $force ? $this->profile->getFullName() : $this->login;
	}

	/**
	 * return user email
	 *
	 * @param  bool  $format  format email?
	 * @return string
	 **/
	function getEmail($format=true) {
		return $this->UID ? $profile->getEmail($format) : '';
	}

	/**
	 * return user groups IDs
	 *
	 * @return array
	 **/
	function getGroups() {
		return $this->groups;
	}
	
	/**
	 * return user data as array
	 *
	 * @return array
	 **/
	function toArray() {
		return $this->profile->toArray();
	}
	
	/**
	 * autologin user
	 *
	 * @param  int  $sessTimeout  session timeout for root
	 * @return bool
	 **/
	function autoLogin($sessTimeout) {
		
		$this->prefs = isset($_SESSION['userPrefs']) && is_array($_SESSION['userPrefs']) ? $_SESSION['userPrefs'] : array();
		
		if (!empty($_SESSION['UID']) && !empty($_SESSION['key'])) {
			$this->UID = (int)$_SESSION['UID'];
		
			if ($this->_onlyGroups) {
				$sql = 'SELECT DISTINCT '.$this->attrsToString()
	  					.' FROM el_user, el_user_in_group WHERE '
	  					.'uid='.intval($this->UID).' AND user_id=uid AND '
	  					.'group_id IN (\''.implode('\',\'', $this->_onlyGroups).'\')';
				$this->db->query($sql);
				if ($this->db->numRows() == 1) {
					$this->attr($this->db->nextRecord());
					$res = true;
				} else {
					$res = false;
				}
			} else {
				$res = $this->fetch();
			}
			
			if (!$res || $this->_key() != $_SESSION['key']) {
				$this->logout();
				return false;
			}

			if (time() - $this->atime > $sessTimeout && $this->UID == 1) {
				$this->logout();
				return false;
			}
			$this->_onLogin();
			return true;
		}
	
	}

	/**
	 * auth user
	 *
	 * @param  string  $login
	 * @param  string  $pass
	 * @param  bool    $case is login case sensetive		
	 * @return bool
	 **/
	function login($login, $pass, $case=true) {
		
		if ($login == 'root') {
			$this->db->queryToArray('SELECT login FROM el_user WHERE uid=1');
			if (!$this->db->numRows()) {
				$this->db->query('INSERT INTO el_user (uid, login, crtime, mtime) VALUES (1, "root", '.time().', '.time().')');
			} else {
				$r = $this->db->nextRecord();
				if ($r['login'] != 'root') {
					$this->db->query('UPDATE el_user SET login="root", mtime='.time().' WHERE uid=1');
				}
			}
		}
		
		$login = mysql_real_escape_string($case ? $login : strtolower($login));
		$field = $case ? 'login' : 'LOWER(login)';
		$pass = md5($pass);

		if ($this->_onlyGroups) {
			$sql = sprintf('SELECT DISTINCT %s FROM el_user, el_user_in_group WHERE %s="%s" AND pass="%s" AND user_id=uid AND group_id IN (%s)', $this->attrsToString(), $field, $login, $pass, implode(', ', $this->_onlyGroups));
		} else {
			$sql = sprintf('SELECT %s FROM el_user WHERE %s="%s" AND pass="%s"', $this->attrsToString(), $field, $login, $pass);
		}
		$this->db->query($sql);
		if (!$this->db->numRows()) {
			return false;
		}
		
		$this->attr($this->db->nextRecord());
		$this->_onLogin(true);
		return true;
		
	}

	/**
	 * logout user
	 *
	 * @return void
	 **/
	function logout()
	{
		$this->_savePrefs();
		$this->clean();
		$this->_loadProfile();
		$this->groups = $this->prefs = array();
		$_SESSION['UID'] = 0;
		$_SESSION['key'] = '';
		$_SESSION['userPrefs'] = array();
	}

	/**
	 * set/get prefrence
	 *
	 * @param  string  $name  prefrence name
	 * @param  mixed   $value new value
	 * @return mixed
	 **/
	function prefrence($name=null, $value=null) {
		if (empty($name)) {
			return $this->prefs;
		}
		if (!is_null($value)) {
			$this->prefs[$name] = $value;
			$_SESSION['userPrefs'] = $this->prefs;
		}
		return isset($this->prefs[$name]) ? $this->prefs[$name] : null;
	}

	/**
	 * set/get prefrence
	 *
	 * @param  string  $name  prefrence name
	 * @param  mixed   $value new value
	 * @return mixed
	 **/
	function removePrefrence($name) {
		if (isset($this->prefs[$name])) {
			unset($this->prefs[$name]);
			$_SESSION['userPrefs'] = $this->prefs;
		}
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

	

	







	function getPref($name)	{
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

	/**
	 * some actions after (auto)login
	 *
	 * @param  bool   $newVisist update visits counter
	 * @return void
	 **/
	function _onLogin($newVisist=false) {
		$_SESSION['key'] = $this->_key();
		$_SESSION['UID'] = $this->UID;
		$this->_loadGroups();
		$this->atime = time();
		$this->db->query('UPDATE el_user SET atime='.$this->atime.($newVisist ? ', visits=visits+1' : '').' WHERE uid='.$this->UID);
		$this->_loadProfile();
		
		if ( $newVisist )
		{
			$this->_loadPrefs();
			$db = & elSingleton::getObj('elDb');
			$db->query(sprintf('UPDATE el_icart SET sid="%s" WHERE uid=%d', mysql_real_escape_string(session_id()), $this->UID));
		}
		
	}

	/**
	 * create session key
	 *
	 * @return string
	 **/
	function _key() {
		return md5($this->UID.' '.$this->login.' '.$this->_salt);
	}

	/**
	 * load profile data
	 *
	 * @return void
	 **/
	function _loadProfile() {
		$this->profile->clean();
		if ($this->UID) {
			$this->profile->idAttr($this->UID);
			$this->profile->fetch();
		}
	}

	/**
	 * load groups for authed user
	 *
	 * @return void
	 **/
	function _loadGroups() {
		if ($this->UID )
		{
			$sql = 'SELECT group_id FROM el_user_in_group WHERE user_id='.$this->UID;
			if ( !empty($this->_onlyGroups) && is_array($this->_onlyGroups) )
			{
				$sql .= ' AND group_id IN (1, '.implode(',', $this->_onlyGroups).')';
			}
			$this->groups = $this->db->queryToArray($sql, null, 'group_id');
		}
	}

	/**
	 * Load prefrences and put in session
	 *
	 * @return viod
	 */
	function _loadPrefs()
	{
		$this->prefs = array();
		$this->db->query('SELECT name, val, is_serialized FROM el_user_pref WHERE user_id=\''.$this->UID.'\'');
		while ($row = $this->db->nextRecord()) {
			$this->setPref($row['name'], $row['is_serialized'] ? unserialize($row['val']) : $row['val']);
		}
	}

	/**
	 * Save prefrences in Db 
	 *
	 * @return void
	 */
	function _savePrefs()
	{
		if ($this->UID) {
			$this->db->query('DELETE FROM el_user_pref WHERE user_id=\''.$this->UID.'\'' );
			$this->db->optimizeTable( 'el_user_pref' );
			if ( $this->prefs ) {
				$this->db->prepare( 'INSERT INTO el_user_pref (user_id, name, val, is_serialized) VALUES ', '(\'%d\', \'%s\', \'%s\', \'%d\')' );
				foreach ( $this->prefs as $n=>$v ) {
					$ser = is_scalar($v);
					$this->db->prepareData( array($this->UID, $n, $ser ? serialize($v) : $v, $ser) );
				}
				$this->db->execute();
			}
		}
	}

	/**
	 * return attr mapping
	 *
	 * @return array
	 **/
	function _initMapping() {

		return array(
			'uid'    => 'UID',
			'login'  => 'login',
			'crtime' => 'crtime',
			'mtime'  => 'mtime',
			'atime'  => 'atime',
			'visits' => 'visits'
			);
	}

}

?>
