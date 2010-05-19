<?php

class elUser extends elDataMapping
{
	var $_tb       = 'el_user';
	var $db        = null;
	var $_id       = 'uid';
	var $__id__    = 'UID';
	var $UID       = 0;
	var $groups    = array();
	var $prefs     = array();
	var $_profile   = null;
	var $_fullName = false;
	var $_onlyGroups = array();
	var $_salt = '';

	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elUser($db, $groups=array(), $salt='', $fn=false)
	{
		$this->_onlyGroups = $groups;
		$this->_salt       = $salt;
		$this->db          = $db;
		$this->_fullName   = $fn;
	}

	/**
	 * fetch data from db
	 *
	 * @return bool
	 **/
	function fetch() {
		if (!$this->_onlyGroups) {
			return parent::fetch();
		}
		
		if ( false != ($ID = $this->idAttr()) ) {
			$this->idAttr(0);
			$db = $this->_db();
			$db->query(sprintf('SELECT DISTINCT %s FROM el_user, el_user_in_group WHERE %s="%s" AND user_id=uid AND group_id IN (%s)', 
				$this->attrsToString(), $this->_id, mysql_real_escape_string($ID), implode(',', $this->_onlyGroups)));
			return $db->numRows() && !$this->attr( $db->nextRecord() );
		}
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
		$name = $this->_fullName || $force
			? trim($this->attr('f_name').' '.$this->attr('s_name').' '.$this->attr('l_name'))
			: '';
		return $name ? $name : $this->login;
	}

	/**
	 * return user email
	 *
	 * @param  bool  $format  format email?
	 * @return string
	 **/
	function getEmail($format=true) {
		return $format ? '"'.$this->getFullName().'"<'.$this->attr('email').'>' : $this->attr('email');
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
	 * return fields labels and values
	 *
	 * @return array
	 **/
	function getData() {
		$ret = array();
		$this->db->query('SELECT id, label FROM el_user_profile ORDER BY sort_ndx, label');
		while ($r = $this->db->nextRecord()) {
			$ret[] = array('label'=>m($r['label']), 'value'=>$this->attr($r['id']));
		}
		return $ret;
	}
	
	/**
	 * return profile (user constructor)
	 *
	 * @return elUserProfile
	 **/
	function getProfile() {
		if (!$this->_profile) {
			include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elUserProfile.class.php';
			$this->_profile = & new elUserProfile($this->db, $this->toArray());
		}
		return $this->_profile;
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
	 * return prefrence by name DEPRICATED
	 *
	 * @param  string $name
	 * @return mixed
	 **/
	function getPref($name)	{
		echo 'elUser::getPref called';
		return isset($this->prefs[$name]) ? $this->prefs[$name] : null;
	}
	
	/**
	 * autologin user
	 *
	 * @param  int  $sessTimeout  session timeout for root
	 * @return bool
	 **/
	function autoLogin($sessTimeout) {
		
		$this->prefs = isset($_SESSION['userPrefs']) && is_array($_SESSION['userPrefs']) 
			? $_SESSION['userPrefs'] 
			: array();
		
		if (!empty($_SESSION['UID']) 
		&&  !empty($_SESSION['key'])) {
			$this->UID = (int)$_SESSION['UID'];
		
			if (!$this->fetch() 
			|| $this->_key() != $_SESSION['key']
			|| ($this->UID == 1 && time() - $this->atime > $sessTimeout)) {
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
				$this->db->query('INSERT INTO el_user (uid, login, pass, crtime, mtime) VALUES (1, "root", "'.md5("eldorado-cms").'" '.time().', '.time().')');
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
	function logout() {
		$this->_savePrefs();
		$this->clean();
		$this->groups = $this->prefs = array();
		$_SESSION['UID'] = 0;
		$_SESSION['key'] = '';
		$_SESSION['userPrefs'] = array();
	}

	/**
	 * save new password in db
	 *
	 * @param string $p    password
	 * @return void
	 **/
	function passwd($p) {
		$this->db->query(sprintf('UPDATE el_user SET pass="%s" WHERE uid=%d LIMIT 1', md5($p), $this->UID));
	}

	/**
	 * update user groups list
	 *
	 * @return void
	 **/
	function updateGroups($gids) {
		$this->db->query('DELETE FROM el_user_in_group WHERE user_id=\''.$this->UID.'\'');
	    $this->db->optimizeTable('el_user_in_group');
	    if ($gids) {
	      $this->db->prepare('INSERT INTO el_user_in_group (user_id, group_id) VALUES ', '(%d, %d)');
	      foreach ($gids as $gid) {
	        $this->db->prepareData( array($this->UID, $gid) );
	      }
	      $this->db->execute();
	    }
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




	function &getProfile_()
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
	//        		PRIVATE METHODS				   //
	//*********************************************//

	/**
	 * create form for edit user
	 *
	 * @return void
	 **/
	function _makeForm() {
		$this->getProfile();
		$this->_form = $this->_profile->getForm(EL_URL, 'POST', $this->toArray());
	}

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
		
		if ( $newVisist ) {
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
	 * Valid user form
	 *
	 * @return bool
	 **/
	function _validForm() {
		$data = $this->_form->getValue();
		
		if (!$this->UID) {
			if (!preg_match('/^[a-z0-9_\-\/]{3,25}$/i', $data['login'])) {
				$this->_form->pushError('login', m('"%s" must contain latin alfanum of underline from 3 till 25 chars'));
			} else {
				$sql = 'SELECT uid FROM el_user WHERE login="%s"';
				$this->db->query(sprintf($sql, mysql_real_escape_string($data['login'])));
				if ($this->db->numRows()) {
					$this->_form->pushError('login', m('Login already exists'));
				}
			}
		}
		
		if (!preg_match('/^[a-zA-Z0-9\._-]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/i', $data['email'])) {
			$this->_form->pushError('email', m('"%s" must contain valid email address'));
		} else {
			$sql = 'SELECT uid FROM el_user WHERE email="%s" AND uid!="%d"';
			$this->db->query(sprintf($sql, mysql_real_escape_string($data['email']), $this->UID));
			if ($this->db->numRows()) {
				$this->_form->pushError('email', m('E-mail already exists'));
			}
		}
		return !$this->_form->hasErrors();
	}

	/**
	 * update mtime
	 *
	 * @return array
	 **/
	function _attrsForSave() {
		$this->mtime = time();
		if (!$this->UID || !$this->crtime) {
			$this->crtime = time();
		}
		return parent::_attrsForSave();
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function _postSave($isNew) {
		if ($isNew) {
			$ats = &elSingleton::getObj('elATS');
			$this->passwd($ats->randPasswd());
			if ( 1 < ($GID = (int)$ats->conf('defaultGID')) ) {
	        	$this->updateGroups(array($GID));
	        }
		}
		return true;
	}
	

	/**
	 * return attr mapping
	 *
	 * @return array
	 **/
	function _initMapping() {

		$fields = $this->db->fieldsNames('el_user');
		$map = array();
		foreach ($fields as $f) {
			if ($f == 'uid') {
				$map['uid'] = 'UID';
			} else {
				$map[$f] = $f;
				$this->$f = '';
			}
		}
		return $map;
		elPrintR($map);

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
