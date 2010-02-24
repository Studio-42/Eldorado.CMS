<?php
include_once 'elMultiModule.class.php';
class elModuleNavigationControl extends elMultiModule
{
  var $_prnt = false;
  var $_subModules = array( 'main' => array('module' => 'MainNav',  'label' => 'Main navigation'),
                            'add'  => array('module' => 'AddNav',   'label' => 'Additional navigation'),
                            'meta' => array('module' => 'MetaTags', 'label' => 'Meta tags'),
                            'modules' => array('module'=>'ModulesControl', 'label'=>'Modules control')
       );
}

?>