<?php

class elMultiModule extends elModule
{ 
  var $_subModules       = array();
  var $_subModulesAdmin  = array();
  var $_defModuleHandler = '';

  /**
   * Replace himself by subModule class object 
   */
  function init( $pageID, $args, $name, $aMode=EL_READ, $import=false )
    {
      if ( EL_READ < $aMode )
      {
        $this->_subModules += $this->_subModulesAdmin; 
      }

      // if not set, use first _subModules element
      if ( !$this->_defModuleHandler )
      {
        $this->_defModuleHandler = key($this->_subModules);
      }
      // submodule for replace
      if ( !empty($args[0]) && isset($this->_subModules[$args[0]]) )
      {
        $h = array_shift($args);
      }
      elseif ( !empty($this->_defModuleHandler) )
      {
        $h = $this->_defModuleHandler;
      }
      else
      {
        $h = key($this->_subModules);
      }
      // create tabs
      $tabs = array();
      foreach ( $this->_subModules as $handle=>$m )
      {
        $path = $handle == $this->_defModuleHandler ? '' : $handle.'/';
        $tabs[$handle] = array('path'=>$path, 'label'=>m($m['label']) );
      }
      
      // replace himself by subModule 
      $defModHandler    = $this->_defModuleHandler;
      $subModuleName    = $this->_subModules[$h]['module']; 
      $subModule        = 'elSubModule'.$subModuleName; 
      if ( !elSingleton::incLib('modules/'.$name.'/elSubModule'.$subModuleName.'.class.php') ) 
      {
        return elThrow(E_USER_ERROR, m('Page "%s" has incorrect module configuration'), 
                        EL_URL, null, null, __FILE__, __LINE__ );
      }

      if ( EL_READ < $aMode && elSingleton::incLib('modules/'.$name.'/elSubModuleAdmin'.$subModuleName.'.class.php') )
  {
    $subModule = 'elSubModuleAdmin'.$subModuleName; 
  }

      $this = new $subModule; 
      $this->_setTabs( $tabs, $h );
      $this->init($pageID, $args, $name, $aMode, $import);

      if ( $h != $defModHandler )
  { //echo 'HERE IT!'; elPrintR(array('url'=>$this->_smPath, 'name'=>m($this->_tabs[$h]['label'])));
    elAppendToPagePath( array('url'=>$this->_smPath, 'name'=>m($this->_tabs[$h]['label'])) );
  }
    }
}
?>