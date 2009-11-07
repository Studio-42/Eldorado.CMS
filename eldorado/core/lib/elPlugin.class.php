<?php
// ver 2.0
/**
 * Abstract class for plugins
 */
class elPlugin
{
	/**
   * @_params  array  plugin's parameters - store in conf file
   */
	var $_params = array();

	/**
   * @_args  array  plugin's "call" method arguments
   */
	var $_args    = array();
	var $pageID   = 1;
	var $name     = '';
	var $form     = null;


	function elPlugin($name, $pageID, $params)
	{
		$this->name    = $name;
		$this->pageID  = $pageID;
		$this->_params = $params;
		elLoadMessages('Plugin'.$this->name);
		elAddCss('plugins/'.$this->name.'.css');
	}

	// called after plugin loaded
	function onLoad() {}

	// called before plugin unloaded
	function onUnload() {}

	//called by core while plugin is requested directly [http://.../__pl__/md5(class_name($this))/ ]
	function call( $args )
	{
		$this->_args = $args;
	}

	function findSources($module)
	{
		$nav = & elSingleton::getObj('elNavigator'); 
		return $nav->findByModule($module);
	}

	/**
	 * Set plugin activity status
	 * On, Off or Disable
	 * Disable sets only for plugins if there are no one pages with required module exists
	 *
	 * @param string $status
	 */
	function setStatus($status)
	{
		$db   = & elSingleton::getObj('elDb');
		$db->query('UPDATE el_plugin SET status=\''.mysql_real_escape_string($status).'\' '
							.'WHERE name=\''.$this->name.'\'');
		if ('on' == $status)
		{
			$this->_onSwitchOn();
		}
		elseif ('off' == $status)
		{
			$this->_onSwitchOff();
		}
		else
		{
			$this->_onDisable();

		}
	}

	/**
	 * For plugins which require some modules check if at least one page with this module exists
	 *
	 * @return bool
	 */
	function checkRequiredModule()
	{
		if (empty($this->reqModule))
		{
			return true;
		}
		$conf = & elSingleton::getObj('elXmlConf');
		return $conf->findGroup('module', $this->reqModule);
	}

	//return params value by key or null
	function _param($k)
	{
		return isset($this->_params[$k]) ? $this->_params[$k] : NULL;
	}

	//return args value by number or null
	function _arg($num=0)
	{
		return isset($this->_args[$num]) ? $this->_args[$num] : NULL;
	}

	/**
	 * Do some action when plugin swicthing on
	 *
	 */
	function _onSwitchOn() {}

	/**
	 * Do some action when plugin swicthing off
	 *
	 */
	function _onSwitchOff()
	{
		$this->_dropConfParams();
	}

	/**
	 * Do some action when plugin disabling
	 *
	 */
	function _onDisable()
	{
		$this->_dropConfParams();
	}

	/**
	 * Remove all plugin params from conf file
	 *
	 */
	function _dropConfParams()
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$conf->dropGroup('plugin'.$this->name);
		$conf->save();
	}

	function _getPosInfo($pos, $altTpl=null)
	{
		if (empty($this->_posNfo))
		{
			return array(null, null, null);
		}
		$pos  = !empty($this->_posNfo[$pos]) ? $pos : EL_POS_LEFT;
		$tpl  = empty($altTpl) ? $this->_posNfo[$pos][1] : $altTpl;
		$file = 'plugins/'.$this->name.'/'.$tpl;

		return file_exists(EL_DIR_STYLES.$file)
		  ? array($pos, $this->_posNfo[$pos][0], $file)
		  : array(null, null, null);

	}
}

?>