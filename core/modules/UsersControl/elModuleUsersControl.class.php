<?php
include_once 'elMultiModule.class.php';

class elModuleUsersControl extends elMultiModule
{
  var $_subModules = array( 'users'  => array('module'=>'Users',  'label'=>'Users'),
			 										  'groups' => array('module'=>'Groups', 'label'=>'Groups'));

  var $required = 1;
}

?>