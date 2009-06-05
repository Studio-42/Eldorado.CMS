<?php

class elRndSiteControl extends elModuleRenderer
{
  var $_tpls    = array('emails'=>'emails.html');
  var $_admTpls = array('emails'=>'adminEmails.html');

  // ********************  PUBLIC METHODS  ************************ //

  function renderAccessConf( $conf )
    {
      $this->_setFile( );

      $ats = &elSingleton::getObj('elATS');
      $groups = $ats->getGroupsList();
      if ( isset($conf['defaultGID']) && $conf['defaultGID'] && isset($groups[$conf['defaultGID']]) )
	{
	  $group = $groups[$conf['defaultGID']];
	}
      else
	{
	  $group = m('Undefined');
	}
      $timeOut = (int)$conf['sessionTimeOut'];
      $def     = '';;

      if ( !$timeOut )
	{
	  $ats     = &elSingleton::getObj('elATS');
	  $timeOut = $ats->getSessionTimeOut();
	  $def     = ' ('.m('Default value').')';
	}
      $t = mktime(0,0,0,1,1,2000); 
      $timeOut = $timeOut < 86400 
	? date('H:i', $t+$timeOut) 
	: intval($timeOut/86400).''.m('days').date(' H:i', $t+$timeOut);

      $this->_te->assignBlockVars( 'ROW', array('label'=>m('Session timeout'), 'val'=>$timeOut.$def ) );
      $this->_te->assignBlockVars( 'ROW', array('label'=>m('Default group'),   'val'=>$group) );
      $this->_te->assignBlockVars( 'ROW', array('label'=>m('Allow registration for users'), 
						'val'=>$conf['allowRegister'] ? m('Yes') : m('No')) );
      $this->_te->assignBlockVars( 'ROW', array('label'=>m('Display form for login'), 
						'val'=>$conf['displayLoginForm'] ? m('Yes') : m('No')) );

      if ( empty($conf['authDb']) )
	{
	  $this->_te->assignBlockVars( 'ROW', array('label'=>m('Auth type'), 'val'=>m('local')) );
	}
      else
	{
	  $this->_te->assignBlockVars( 'ROW', array('label'=>m('Auth type'), 'val'=>m('remote')) );
	  $this->_te->assignBlockVars( 'ROW', array('label'=>m('DB host'),   'val'=>$conf['authDb']['host']) );
	  $this->_te->assignBlockVars( 'ROW', array('label'=>m('DB name'),   'val'=>$conf['authDb']['db']) );

	}
      
    }

}


?>