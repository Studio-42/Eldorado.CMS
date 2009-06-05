<?php
include_once EL_DIR_CORE.'lib/elMultiModule.class.php';

class elModuleSiteControl extends elMultiModule
{
  var $_subModules      = array(
				'common'   => array('module'=>'CommonControl', 'label'=>'Common configuration'),
				'layout'   => array('module'=>'LayoutControl', 'label'=>'Layout configuration'),
				'emails'   => array('module'=>'EmailsControl', 'label'=>'E-mail addresses'),
				'counters' => array('module'=>'CountersControl', 'label'=>'Counters code')
				);
  var $_subModulesAdmin = array('access' => array('module'=>'AccessControl', 'label'=>'Access configuration'));

  var $required = 1;
}

?>