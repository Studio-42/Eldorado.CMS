<?php

class elService
{
	var $name       = '';
	var $_args      = array();
	var $_mMap      = array();
	var $_rnd       = null;
	var $_tplDir    = 'common/';
	var $_tplFile   = '';
	var $_vdir      = '';
	var $_pageTitle = '';

	function init($args)
	{
		$this->_args = $args;
	}

	function run()
	{
		$m = 'defaultMethod';
		if (!empty($this->_args[0]) &&
				!empty($this->_mMap[$this->_args[0]]['m']) &&
				method_exists($this, $this->_mMap[$this->_args[0]]['m']) )
		{
			$m = $this->_mMap[$this->_args[0]]['m'];
		}
		$this->$m();
	}

	function defaultMethod()
	{

	}

	function toXML()
	{
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"  standalone=\"yes\" ?>";
	}

	function stop()
	{
		if ( !$this->_rnd )
		{
			$this->_initRenderer();
		}
		if ( !empty($this->_mMap))
		{
			$map = $this->_mMap;
			$acts = array();

			foreach ($map as $k=>$v)
			{
				if (!empty($v['g']))
				{
					if (!isset($acts[$v['g']]))
					{
						$acts[$v['g']] = array();
					}
					$g = array( 'url'     => EL_BASE_URL.'/'.$this->_vdir.'/'.$k.'/'.(isset($v['apUrl']) ? $v['apUrl'].'/' : ''),
                      			'label'   => m($v['l']),
								'ico'     => $v['ico'],
                      			'onClick' => !empty($v['onClick']) ? $v['onClick'] : ''
                    			);
                    $acts[$v['g']][] = $g;
				}
			}
		}
		$this->_rnd->renderComplite( $acts );
		elAppendToPagePath( array('url'=>$this->_vdir, 'name'=>m($this->_pageTitle) ) );
	}

	function _initRenderer()
	{
		$this->_rnd = & elSingleton::getObj('elModuleRenderer');
		$this->_rnd->init('none', array());
		$this->_rnd->setDir($this->_tplDir);
		if (!empty($this->_tplFile))
		{
			$this->_rnd->setDefTpl($this->_tplFile);
		}

	}
}

?>