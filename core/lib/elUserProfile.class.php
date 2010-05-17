<?php

class elUserProfile extends elDataMapping
{
	var $_tb         = 'el_user';
	var $_id         = 'uid';
	var $__id__      = 'UID';
	var $UID         = 0;
	var $login       = '';
	var $email       = '';
	var $db          = null;

	function elUserProfile($db) {
		$this->db = $db;
	}

	/**
	 * return full name or login
	 *
	 * @return string
	 **/
	function getFullName() {
		$name = trim($this->attr('f_name').' '.$this->attr('s_name').' '.$this->attr('l_name'));
		return $name ? $name : $this->attr('login');
	}

	/**
	 * return full name or login
	 *
	 * @return string
	 **/
	function getEmail($format=true) {
		return $format ? '"'.$this->getFullName().'"<'.$this->attr('email').'>' : $this->attr('email');
	}

	/**
	 * return array with fields labels and values
	 *
	 * @return string
	 **/
	function get() {
		$ret = array();
		$this->db->query('SELECT id, label FROM el_user_profile ORDER BY sort_ndx, label');
		while ($r = $this->db->nextRecord()) {
			$ret[] = array('label'=>m($r['label']), 'value'=>$this->attr($r['id']));
		}
		return $ret;
	}

	/**
	 * return profile form constructor
	 *
	 * @return object
	 **/
	function getSkel() {
		if (!$this->_skel) {
			$this->_skel = & new elUserProfileSkel($this->toArray());
		}
		
		return $this->_skel;
	}

	function _getSkelConf()
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql  = 'SELECT field, label, rq, sort_ndx FROM el_user_profile ORDER BY sort_ndx, field';
		$conf =  $db->queryToArray($sql, 'field');
		return $conf;
	}

	function _setSkelConf($conf)
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql = 'UPDATE el_user_profile SET rq="%d", sort_ndx="%d" WHERE field="%s"';
		foreach ( $conf as $k=>$v )
			$db->safeQuery($sql, $v['rq'], $v['sort_ndx'], $k);
	}

	function _getSkel()
	{
		$ats  = & elSingleton::getObj('elATS');
		$db   = & $ats->getACLDb();
		$sql  = 'SELECT field, label, type, opts, rule, is_func, rq, sort_ndx FROM el_user_profile '
		      . 'WHERE field IN ("'.implode('", "', $this->attrsList()).'") ORDER BY sort_ndx, field';
		$skel = $db->queryToArray($sql, 'field');
		return $skel;
	}



	function _initMapping()
	{

		$map = array('uid' => 'UID');
		$this->db->query('SELECT id FROM el_user_profile ORDER BY sort_ndx, label');
		while($r = $this->db->nextRecord()) {
			$this->{$r['id']} = '';
			$map[$r['id']] = $r['id'];
		}

		// elPrintR($map);
		return $map;
	}

}

include_once EL_DIR_CORE.'lib'.DIRECTORY_SEPARATOR.'elFormConstructor.class.php';

class elUserProfileSkel extends elFormConstructor {
	
	var $UID = 0;
	
	/**
	 * constructor
	 *
	 * @return void
	 **/
	function elUserProfileSkel($data=array()) {
		
		$el              = & new elUserProfileField();
		$this->_elements = $el->collection(true, true, '', 'sort_ndx');
		foreach ($data as $id=>$val) {
			if (isset($this->_elements[$id])) {
				$this->_elements[$id]->setValue($val);
			}
		}
		if (!empty($data['login'])) {
			$this->_elements['login']->disabled = true;
			unset($this->_elements['login']);
		}
		$this->UID = $data['uid'];
	}
	
	
	/**
	 * return complete form
	 *
	 * @return object
	 **/
	function getForm($url=EL_URL, $method='POST') {
		$this->label = $this->UID ? m('User profile') : m('New user registration');
		$form = parent::getForm($url, $method);
		$form->registerRule('elCheckUserUniqFields', 'func', 'elCheckUserUniqFields', null);
		$form->setElementRule('email', 'elCheckUserUniqFields', true, $this->UID);
		if (!$this->UID) {
			// $form->add(new elCaptcha('__reg__', m('Enter code from picture')));
		}
		
		return $form;
	}
	
}

class elUserProfileField extends elFormConstructorElement {
	
	var $_tb = 'el_user_profile';
	
	function _initMapping()
  	{
    	return array( 
			'id'        => 'ID',
		    'type'      => 'type',
		    'label'     => 'label',
		    'value'     => 'value',
		    'opts'      => 'opts',
			'directory' => 'directory',
			'required'  => 'required',
		    'rule'      => 'rule',
		    'file_size' => 'fileSize',
			'file_type' => 'fileType',
		    'error'     => 'error',
		    'sort_ndx'  => 'sortNdx'
		);
	}
}


?>
