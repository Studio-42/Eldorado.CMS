<?php

class elSubModuleAccessControl extends elModule
{

  var $_conf = array('sessionTimeOut'            => 86400, 
                     'registrAllow'              => 0, 
                     'defaultGID'                => 2, 
                     'authDb'                    => array(),
                     'disableUserRegisterNotify' => 0,
                     'disableUserPasswdNotify'   => 0,
                     'disableAboutNewUserNotify' => 0
                    );
                    
  var  $_timeOuts = array(
        1800    => '30 min',
        3600    => '1 hour',
        21600   => '6 hours',
        43200   => '12 hours',
        86400   => '1 day (by default)',
        604800  => '1 week',
        1209600 => '2 weeks',
        2592000 => '1 month'
        );

  var $_yn     = array( 'No', 'Yes');
  var $_aTypes = array('"Local" authorization (in this site database)', 
                       '"Remote" authorization (on other database)');
  var $_prnt   = false;
  var $_confID = 'auth';
  var $_groups = array();
  var $_params = array();


  // **********************  PUBLIC METHODS  ************************** //

  function defaultMethod()
    {
      $this->_initRenderer(); 
      $this->_rnd->render( $this->_params, null, 'ROW');
    }

  // =====================  PRIVATE METHODS  ========================== //

  function _onInit()
    {
      $this->_ats = & elSingleton::getObj('elATS');

      $this->_groups   = $this->_ats->getGroupsList();  
      unset($this->_groups[1]);
      $this->_timeOuts = array_map('m', $this->_timeOuts);
      $GLOBALS['yn']       = array_map('m', $GLOBALS['yn']);
      $this->_aTypes   = array_map('m', $this->_aTypes);

      $aType = (int)(!$this->_ats->isLocalAuth());
      $defGID = $this->_ats->getDefaultGID();
      $defGroupName = isset($this->_groups[$defGID]) 
        ? $this->_groups[$defGID] 
        : m('Undefined');
      $to = isset($this->_timeOuts[$this->_conf('sessionTimeOut')]) 
        ? $this->_timeOuts[$this->_conf('sessionTimeOut')] 
        : $this->_timeOuts[86400];
      
      $this->_params = array ( 
            array('label'=>m('Session timeout'),  
                  'val'=>$to ),
            array('label'=>m('Allow new user registration'), 
                  'val'=>$GLOBALS['yn'][(int)$this->_ats->isRegistrationAllowed()] ),
            array('label'=>m('Default group for new users'), 
                  'val'=>$defGroupName ),
            array('label'=>m('Block user registration e-mail notify'), 
                  'val'=>$GLOBALS['yn'][$this->_conf('disableUserRegisterNotify')] ),
            array('label'=>m('Block changing password e-mail notify'), 
                  'val'=>$GLOBALS['yn'][$this->_conf('disableUserPasswdNotify')] ),
            array('label'=>m('Block notify about new user to default e-mail'), 
                  'val'=>$GLOBALS['yn'][$this->_conf('disableAboutNewUserNotify')] ),                  
            array('label'=>m('Authorization type'), 
                  'val'=>$this->_aTypes[$aType] ),
             );
    }

  /**
   * create config edit form. Overloading parent's method
   */
  function &_makeConfForm()
    {
      $form = & parent::_makeConfForm();
      $form->setLabel( m('Edit site access configuration') );
      $form->addJsSrc( 'checkAuthType();', EL_JS_SRC_ONLOAD );

      $form->add( new elSelect('sessionTimeOut', m('Session timeout'),             
             (int)$this->_conf('sessionTimeOut'), $this->_timeOuts) );
      $form->add( new elSelect('registrAllow', m('Allow new user registration'), 
             (int)$this->_conf('registrAllow'),   $GLOBALS['yn']) );
      $form->add( new elSelect('defaultGID',     m('Default group for new users'), 
             (int)$this->_conf('defaultGID'),     $this->_groups) );
      $form->add( new elSelect('disableUserRegisterNotify', m('Block user registration e-mail notify'), 
              (int)$this->_conf('disableUserRegisterNotify'),   $GLOBALS['yn']) );             
      $form->add( new elSelect('disableUserPasswdNotify', m('Block changing password e-mail notify'), 
              (int)$this->_conf('disableUserPasswdNotify'),   $GLOBALS['yn']) );                           
      $form->add( new elSelect('disableAboutNewUserNotify', m('Block notify about new user to default e-mail'), 
              (int)$this->_conf('disableAboutNewUserNotify'),   $GLOBALS['yn']) );                                         
      $form->add( new elSelect('isRemoteAuth',   m('Authorization type'), 
             (int)!$this->_ats->isLocalAuth(),    $this->_aTypes,
             array('onChange'=>'checkAuthType();')), array('cellElAttrs'=>'width="40%"') );
      
      $db = array('host'=>'localhost', 'db'=>'', 'user'=>'', 'pass'=>'', 'sock'=>'');
      if ( !$this->_ats->isLocalAuth() )
      {
        $db = array_merge($db, $this->_conf('authDb') ) ;
      }

      $form->add( new elText('host', m('Auth database host'),     $db['host'], array('size'=>'30')) );
      $form->add( new elText('sock', m('Auth database socket'),   $db['sock'], array('size'=>'30')) );
      $form->add( new elText('db',   m('Auth database name'),     $db['db'],   array('size'=>'30')) );
      $form->add( new elText('user', m('Auth database user'),     $db['user'], array('size'=>'30')) );
      $form->add( new elText('pass', m('Auth database password'), $db['pass'], array('size'=>'30')) );

      $form->setElementRule('host', 'alfanum_lat', false);
      $form->setElementRule('db',   'alfanum_lat', false);
      $form->setElementRule('user', 'alfanum_lat', false);
      $form->setElementRule('pass', 'alfanum_lat', false);

      return $form;
    }

  function _validConfForm( &$form )
    {
      $vals = $form->getValue();

      $vals['sessionTimeOut'] = (int)$vals['sessionTimeOut'];
      $vals['registrAllow']   = (int)$vals['registrAllow'];
      $vals['isRemoteAuth']   = (int)$vals['isRemoteAuth'];

      // check session timeout
      if ( !isset($this->_timeOuts[$vals['sessionTimeOut']]) )
      {
        $vals['sessionTimeOut'] = 86400;
        elThrow( E_USER_NOTICE, m('Invalid session timeout value! Set it to default.') );
      }

      //check default group for new users
      if ( !isset($vals['defaultGID']) || !isset($this->_groups[$vals['defaultGID']]) )
      {
        $vals['defaultGID'] = 0;
        elThrow(E_USER_NOTICE, m('Invalid default group! Default group was not defined!') );
      }
      if ( $vals['registrAllow'] && !$vals['defaultGID'] )
      {
        elMsgBox::put( m('You allow user registration. It is a good idea to define default group for all new users.') );
      }
      
      // check auth type and Db params 

      // "local" type
      if ( !$vals['isRemoteAuth'] )
      {
      // auth type switch from remote to local
        if ( $this->_conf['authDb'] )
        {
          elMsgBox::put( m('Authorization switched to "local"! After config was saved, You, probably, need to relogin!') );
        }
        return $vals;
      }

      // "remote" type

      // empty fields
      if ( empty($vals['db']) || empty($vals['user']) )
      {
        return elThrow( E_USER_WARNING, m('Invalid database parameters') );
      }

      $conf    = &elSingleton::getObj('elXmlConf');
      $localDb = $conf->getGroup('db');
      $newDb   = array('host'=>$vals['host'], 'sock'=>$vals['sock'], 'db'=>$vals['db'], 'user'=>$vals['user'], 'pass'=>$vals['pass']);
      $authDb  = $this->_conf('authDb'); 
			if (!isset($authDb['sock']))
			{
				$authDb['sock'] = '';
			}
      // try switch to local db as to remote - invalid, no db change
      if ( !array_diff($localDb, $newDb) && !array_diff($newDb, $localDb) )
      {
        elThrow( E_USER_WARNING, 'Entered database parameters indenticaly to local database ones. There are will not be changed.');   
        $vals['authDb'] = $authDb;
        return $vals;
      }

      // db was not changed - do nothing
      if ( !empty($authDb) && !array_diff($newDb, $authDb) && !array_diff($authDb, $newDb) )
      {
        $vals['authDb'] = $authDb;
        return $vals;
      }

      // is it php bug ???
  		$testDbNew = $newDb;    unset($testDbNew['db']);//elPrintR($testDbNew);
  		$testDbAuth  = $authDb; unset($testDbAuth['db']); //elPrintR($testDbAuth); 
  		$testDbLocal  = $localDb; unset($testDbLocal['db']); //elPrintR($testDbLocal); 
			if ((!empty($authDb) && !array_diff($testDbAuth, $testDbNew) && !array_diff($testDbNew, $testDbAuth)) 
			|| (!array_diff($testDbNew, $testDbLocal) && !array_diff($testDbLocal, $testDbNew)) )
			{
				elThrow( E_USER_WARNING, 'Entered database connection parameters indenticaly to local database ones exclude db names. This will be not working correctly. There are will not be changed.');   
        $vals['authDb'] = $authDb;
        return $vals;
			}
  		
      // test new auth DB
      $db = & new elDb($newDb['user'], $newDb['pass'], $newDb['db'], $newDb['host'], $newDb['sock']); 
      if ( !$db->connect(true) )
      {
        return elThrow( E_USER_WARNING, m('Invalid Data Base parameters') );
      }

      // check for nessecery tables in DB
      $tblList = $db->queryToArray('SHOW TABLES FROM '.$db->_db, null, 'Tables_in_'.$db->_db);
      if ( empty($tblList) || !in_array('el_group', $tblList) || !in_array('el_user', $tblList) || 
           !in_array('el_user_in_group', $tblList) || !in_array('el_user_profile', $tblList)  )
      {
        return elThrow( E_USER_WARNING, m('Selected database does not contains all nessecery tables') );
      }

      elMsgBox::put( m('Authorization switched to "remote"! After config was saved, You, probably, need to relogin!') );
      $vals['authDb'] = $newDb;
      $conf = & elSingleton::getObj('elXmlConf');
      $conf->set('importGroups', array(1=>'root'), 'auth');
      $conf->save();
      return $vals;
    }

}

?>