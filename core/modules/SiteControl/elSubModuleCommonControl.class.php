<?php

/**
 * @pakage eldorado
 * @subpakage modules
 * SubModule of elModuleSiteControl
 * Display and edit site common configuration
 */
class elSubModuleCommonControl extends elModule
{
	var $_prnt   = false;
	var $_confID = 'common';
	var $_conf   = array(
		'siteName'      => 'ELDORADO.CMS',
		'owner'         => '',
		'contacts'      => '',
		'phones'        => '',
		'jsCacheTime'   => 24,
		'gzOutputAllow' => 1,
		'debug'         => 0,
		'timer'         => 1,
		'locale'        => 'en',
		'imgURL'        => ''
	);

	/**
   * array site common config
   */
	var $_params = array(
	'siteName'      => array('label'=>'Site name',                          'val'=>'', 'int'=>0),
	'owner'         => array('label'=>'Site owner',                         'val'=>'', 'int'=>0),
	'contacts'      => array('label'=>'Contacts information',               'val'=>'', 'int'=>0),
	'phones'        => array('label'=>'Phones',                             'val'=>'', 'int'=>0),
	'jsCacheTime'   => array('label'=>'Js and css file cache time (hours)', 'val'=>'', 'int'=>0),
	'gzOutputAllow' => array('label'=>'Compress output',                    'val'=>'', 'int'=>1),
	'debug'         => array('label'=>'Display debug messages',             'val'=>'', 'int'=>1),
	'timer'         => array('label'=>'Display working time',               'val'=>'', 'int'=>1),
	'locale'        => array('label'=>'Interface language',                 'val'=>'', 'int'=>0),
	'currency'      => array('label'=>'Currency',     				        'val'=>'', 'int'=>0)
	);

	var $_lc = array();

	// *********************  PUBLIC METHODS  **************************** //

	/**
   * Display site common configuration parametrs
   */
	function defaultMethod()
	{
		$this->_initRenderer(); //elPrintR($this->_conf);
		$this->_rnd->render( $this->_params, null, 'ROW');
	}

	// ===================  PRIVATE METHODS ===========================  //



	/**
   * create config edit form. Overloading parent's method
   */
	function &_makeConfForm()
	{
		$form = &parent::_makeConfForm();
		$form->setLabel( m('Edit site common configuration') );

		foreach ($this->_params as $k=>$v)
		{
			if ( 'locale' == $k )
			{
				$form->add( new elSelect($k, $v['label'], $this->_conf[$k], $this->_lc) );
			}
			elseif ('currency' == $k )
			{
				
				$currency = &elSingleton::getObj('elCurrency');
				$form->add( new elSelect($k, $v['label'], $currency->current['intCode'], $currency->getList()) );
				// $form->add( new elSelect($k, $v['label'], $this->_currInfo['currency'], $curr) );
			}
			elseif ('jsCacheTime' == $k)
			{
				$time = array(0, 1, 2, 3, 6, 12, 24);
				$form->add( new elSelect($k, $v['label'], (int)$this->_conf[$k], array(0, 1, 2, 3, 6, 12, 24), null, false, false) );
			}
			elseif ( !$v['int'] )
			{
				$form->add( new elText($k, $v['label'], $this->_conf[$k]) );
			}
			else
			{
				$form->add( new elSelect($k, $v['label'], $this->_conf[$k], $GLOBALS['yn']) );
			}
		}
		return $form;
	}


/**
   * Save new configuration in conf file
   */
	function _updateConf( $newConf )
	{
		$conf = &elSingleton::getObj('elXmlConf');
		foreach ($newConf as $k=>$v)
		{
			if ( isset($this->_conf[$k]) )
			{
				$conf->set($k, $newConf[$k], $this->_confID);
			}
			elseif ('currency' == $k )
			{
				$conf->set($k, $newConf[$k], 'currency');
			}
		}
		$conf->save();
	}

	/**
   * Load site common configuration from group "common"
   */
	function _onInit()
	{
	  if ( false != ($d = dir( EL_DIR_CORE.'locale' ) ) )
	  {
	    while ( $entr = $d->read() )
	    {
	      if ( 0 !== strpos('.', $entr) && file_exists($d->path.'/'.$entr.'/elLcConst.php') )
	      {
	        $this->_lc[$entr] = $entr;
	      }
	    }
	    $d->close();
	  }

		foreach ( $this->_params as $k=>$v )
		{
		  if ( isset($this->_conf[$k]) )
			{
				$this->_params[$k]['label'] = m($this->_params[$k]['label']);
				$this->_params[$k]['val']   = $this->_params[$k]['int'] ? $GLOBALS['yn'][$this->_conf[$k]] : $this->_conf[$k];
			}
			elseif('currency' == $k)
			{
				$this->_params[$k]['label'] = m($this->_params[$k]['label']);
				$currency = &elSingleton::getObj('elCurrency');
				$this->_params[$k]['val']   = m($currency->current['name']);
			}
			elseif('jsCacheTime' == $k)
			{
				$this->_params[$k]['label'] = m($this->_params[$k]['label']);
				$this->_params[$k]['val']   = $this->_conf[$k];
			}
		}
	}


}


?>
