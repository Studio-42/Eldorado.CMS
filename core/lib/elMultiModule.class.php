<?php

class elMultiModule extends elModule
{ 
  	var $_subModules       = array();
  	var $_subModulesAdmin  = array();
  	var $_defModuleHandler = '';
	var $_module = null;

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
      
      	$defModHandler    = $this->_defModuleHandler;
      	$subModuleName    = $this->_subModules[$h]['module']; 
      	$subModule        = 'elSubModule'.$subModuleName; 
      	if ( !elSingleton::incLib('modules/'.$name.'/elSubModule'.$subModuleName.'.class.php') ) 
      	{
        	return elThrow(E_USER_ERROR, m('Page "%s" has incorrect module configuration'), EL_URL, null, null, __FILE__, __LINE__ );
      	}

      	if ( EL_READ < $aMode && elSingleton::incLib('modules/'.$name.'/elSubModuleAdmin'.$subModuleName.'.class.php') )
  		{
    		$subModule = 'elSubModuleAdmin'.$subModuleName; 
  		}

		$this->_module = &new $subModule;
		$this->_module->_setTabs( $tabs, $h );
      	$this->_module->init($pageID, $args, $name, $aMode, $import);

      	if ( $h != $defModHandler )
  		{ 
    		elAppendToPagePath( array('url'=>$this->_smPath, 'name'=>m($this->_module->_tabs[$h]['label'])) );
  		}
    }

	function run()
	{
		$this->_module && $this->_module->run();
	}

	function stop()
	{
		$this->_module && $this->_module->stop();
	}
	
	function toXML()
	{
		if ($this->_module) {
			return  $this->_module->toXML();
		}
		
	}

}
?>