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
	function elUser($db, $groups=array(), $salt='', $fn=false) {
		$this->_onlyGroups = !empty($groups) && is_array($groups) ? $groups : array();
		$this->_salt       = $salt;
		$this->db          = $db;
		$this->_fullName   = $fn;
		// $this->_onlyGroups = array(1, 2, 3);
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
			$sql = sprintf('SELECT %s FROM el_user, el_user_in_group WHERE %s="%s" AND user_id=uid AND group_id IN (%s) GROUP BY uid', 
				$this->attrsToString(), $this->_id, mysql_real_escape_string($ID), implode(',', $this->_onlyGroups));
			$this->db->query($sql);
			return $this->db->numRows() && !$this->attr($this->db->nextRecord());
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
		if (!$this->groups) {
			$this->_loadGroups();
		}
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
		$ret[] = array(
			'label' => m('Registration date'),
			'value' => $this->crtime<0 ? date(EL_DATE_FORMAT, $this->crtime) : m('Unknown')
			);
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
		$this->_savePrefrences();
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
	 * undocumented function
	 *
	 * @return void
	 * @author /bin/bash: niutil: command not found
	 **/
	function changePasswd() {
		parent::_makeForm();
		$this->_form->label = sprintf( m('Change password for user "%s"'), $this->login);
		$this->_form->add( new elPasswordDoubledField('pass', m('Password twice')) );
	    $this->_form->setElementRule('pass', 'password', 1, null);
		if ($this->_form->isSubmitAndValid()) {
	        $passwd = $this->_form->getElementValue('pass');
			$this->passwd($passwd);
			$ats = & elSingleton::getObj('elATS');
	        $ats->notifyUser($this, $passwd, EL_UNTF_PASSWD);
	        return true;
	      }
	}

	/**
	 * update user groups list
	 *
	 * @param  array  $gids  groups id list
	 * @return void
	 **/
	function updateGroups($gids) {
		if (!is_array($gids)) {
			$gids = array();
		}
		if ($this->UID==1) {
			$gids[] = 1;
			$gids = array_unique($gids);
		}
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
	 * remove prefrence by name
	 *
	 * @param  string  $name  prefrence name
	 * @return void
	 **/
	function removePrefrence($name) {
		if (isset($this->prefs[$name])) {
			unset($this->prefs[$name]);
			$_SESSION['userPrefs'] = $this->prefs;
		}
	}

	/**
	 * create form/save user data
	 *
	 * @param  array  $params
	 * @return bool
	 **/
	function editAndSave() {
		$this->_makeForm();

		if ($this->_form->isSubmit()) {
			$v1 = $this->_form->isSubmitAndValid();
			$v2 = $this->_validForm();
			if ($v1 && $v2) {
				$this->attr($this->_form->getValue());
				return $this->save();
			}
		}
	}

	/**
	 * count users
	 *
	 * @return int
	 **/
	function count($login='', $groupID='') {
		$sql   = 'SELECT COUNT(uid) AS num FROM el_user AS u ';
		$where = 'WHERE 1 ';
		
		if ($login) {
			$where .= ' AND login LIKE "%'.mysql_real_escape_string($login).'%" ';
		}
		
		if ($this->_onlyGroups) {
			$sql   .=', el_user_in_group AS g ';
			$where .= ' AND u.uid=g.user_id AND g.group_id IN ('.implode(',', $this->_onlyGroups).') ';
			if ($groupID != '') {
				$where .= ' AND g.group_id='.intval($groupID);
			}
		} else {
			if ($groupID != '') {
				if ($groupID > 0) {
					$sql   .=', el_user_in_group AS g ';
					$where .= ' AND u.uid=g.user_id AND g.group_id='.intval($groupID).' ';
				} else {
					$sql   .= ' LEFT JOIN el_user_in_group AS g ON g.user_id=u.uid ';
					$where .= ' AND g.group_id IS NULL ';
				}
			}
		}
		// echo "$sql<br>";
		$this->db->query($sql.$where);
		$r = $this->db->nextRecord();
		return $r['num'];
	}

	/**
	 * overwrite parent method
	 *
	 * @param  string
	 * @param  string
	 * @param  int
	 * @param  int			
	 * @return array
	 **/
	function collection($login='', $groupID='', $sort='', $start=0, $offset=0) {
		$ret    = array();
		$sql    = 'SELECT '.$this->attrsToString('u').' FROM el_user AS u ';
		$where  = ' WHERE 1';
		$order  = ' ORDER BY uid ';
		$limit  = '';
		if ($login) {
			$where .= ' AND login LIKE "%'.mysql_real_escape_string($login).'%" ';
		}
		if ($order) {
			$sort  = ' ORDER BY '.mysql_real_escape_string($sort).' ';
		}
		
		if ($offset>0) {
			$limit = ' LIMIT '.intval($start).', '.intval($offset);
		}
		
		if ($this->_onlyGroups) {
			$sql   .=', el_user_in_group AS g ';
			$where .= ' AND u.uid=g.user_id AND g.group_id IN ('.implode(',', $this->_onlyGroups).') ';
			if ($groupID != '') {
				$where .= ' AND g.group_id='.intval($groupID);
			}
		} else {
			if ($groupID != '') {
				if ($groupID > 0) {
					$sql   .=', el_user_in_group AS g ';
					$where .= ' AND u.uid=g.user_id AND g.group_id='.intval($groupID).' ';
				} else {
					$sql   .= ' LEFT JOIN el_user_in_group AS g ON g.user_id=u.uid ';
					$where .= ' AND g.group_id IS NULL ';
				}
			}
		}
		
		
		$sql = $sql.$where.$sort.$limit;
		// echo $sql;
		$this->db->query($sql);
		
		while ($r = $this->db->nextRecord()) {
			$ret[$r['uid']] = $this->copy($r);
		}
		return $ret;
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
			$sql = 'SELECT g2u.user_id AS uid, g.gid, g.name FROM el_group AS g, el_user_in_group AS g2u WHERE g2u.user_id IN ('.implode(',', $ids).') AND g.gid=g2u.group_id';
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
	 * delete user
	 *
	 * @return void
	 **/
	function delete() {
		if ($this->UID>1) {
			parent::delete(array('el_user_in_group' => 'user_id', 'el_icart' => 'uid'));
		}
	}

	//*********************************************//
	//        		PRIVATE METHODS				   //
	//*********************************************//

	/**
	 * create session key
	 *
	 * @return string
	 **/
	function _key() {
		return md5($this->UID.' '.$this->login.' '.$this->_salt);
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
			$this->_loadPrefrences();
			$db = & elSingleton::getObj('elDb');
			$db->query(sprintf('UPDATE el_icart SET sid="%s" WHERE uid=%d', mysql_real_escape_string(session_id()), $this->UID));
		}
	}

	/**
	 * load groups for authed user
	 *
	 * @return void
	 **/
	function _loadGroups() {
		if ($this->UID) {
			$sql = 'SELECT group_id FROM el_user_in_group WHERE user_id='.$this->UID;
			if ($this->_onlyGroups) {
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
	function _loadPrefrences() {
		$this->prefs = array();
		$db = & elSingleton::getObj('elDb');
		$db->query('SELECT name, val, is_serialized FROM el_user_pref WHERE user_id=\''.$this->UID.'\'');
		while ($row = $db->nextRecord()) {
			$this->prefrence($row['name'], $row['is_serialized'] ? unserialize($row['val']) : $row['val']);
		}
	}

	/**
	 * Save prefrences in Db 
	 *
	 * @return void
	 */
	function _savePrefrences() {
		if ($this->UID) {
			$db = & elSingleton::getObj('elDb');
			$db->query('DELETE FROM el_user_pref WHERE user_id=\''.$this->UID.'\'' );
			$db->optimizeTable( 'el_user_pref' );
			if ( $this->prefs ) {
				$db->prepare( 'INSERT INTO el_user_pref (user_id, name, val, is_serialized) VALUES ', '(\'%d\', \'%s\', \'%s\', \'%d\')' );
				foreach ( $this->prefs as $n=>$v ) {
					$ser = is_scalar($v);
					$db->prepareData( array($this->UID, $n, $ser ? serialize($v) : $v, $ser) );
				}
				$db->execute();
			}
		}
	}

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
	 * Valid user form
	 *
	 * @return bool
	 **/
	function _validForm() {
		$data = $this->_form->getValue();
		
		if (!$this->UID) {
			if (!preg_match('/^[a-z0-9_\-\/]{3,25}$/i', $data['login'])) {
				$this->_form->pushError('login', sprintf(m('"%s" must contain latin alfanum of underline from 3 till 25 chars'), m('Login')));
			} else {
				$sql = 'SELECT uid FROM el_user WHERE login="%s"';
				$this->db->query(sprintf($sql, mysql_real_escape_string($data['login'])));
				if ($this->db->numRows() || 'root' == $data['login']) {
					$this->_form->pushError('login', m('Login already exists'));
				}
			}
		}
		
		if (!preg_match('/^[a-zA-Z0-9\._-]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/i', $data['email'])) {
			$this->_form->pushError('email', sprintf(m('"%s" must contain valid email address'), m('Email')));
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
	 * after create new user, set default groups and password
	 *
	 * @param  bool  $isNew  is this user new?
	 * @return bool
	 **/
	function _postSave($isNew) {
		if ($isNew) {
			$ats    = &elSingleton::getObj('elATS');
			$passwd = $ats->randPasswd();
			$gid    = $ats->getDefaultGID();
			$this->passwd($passwd);
			if ($gid) {
	        	$this->updateGroups(array($gid));
	        }
			if ($this->UID != $ats->user->UID) {
				$ats->notifyUser($this, $passwd, EL_UNTF_REGISTER);
			}
		}
		return true;
	}
	
	/**
	 * return attrs mapping
	 *
	 * @return array
	 **/
	function _initMapping() {

		$fields = $this->db->fieldsNames('el_user');
		$map = array();
		foreach ($fields as $f) {
			if ($f == 'uid') {
				$map['uid'] = 'UID';
			} elseif ($f != 'pass') {
				$map[$f] = $f;
				$this->$f = '';
			}
		}
		return $map;
	}

}

?>
