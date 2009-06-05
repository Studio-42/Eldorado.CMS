<?php

class elSubModuleLayoutControl extends elModule
{
	var $_confID = 'layout';

	var $_conf   = array(
						'servicesTpl'        => '0',
						'authFormPosition'   => 'l',
						'userInfoPosition'   => 'l',
						'searchFormPosition' => 'l',
						'ICartPosition'      => 'l',
						'iCartDisplayEmpty'  => 1
	);
	var $_opts = array(
						'servicesTpl'        => array('label'=>'Services use template',  'val'=>'', 'optype'=>'_tpls'),
						'authFormPosition'   => array('label'=>'Login form position',    'val'=>'', 'optype'=>'_pos'),
						'userInfoPosition'   => array('label'=>'User info position',     'val'=>'', 'optype'=>'_pos'),
						'userFullName'       => array('label'=>'Display user full name', 'val'=>'', 'optype'=>'yn'),
						'searchFormPosition' => array('label'=>'Search form position',   'val'=>'', 'optype'=>'_pos'),
						'ICartPosition'      => array('label'=>'Shopping cart position',   'val'=>'', 'optype'=>'_pos'),
						'iCartDisplayEmpty'  => array('label'=>'Display empty shopping cart', 'val'=>'', 'optype'=>'yn'),
						
						);

	var $_pos    = array(
						'l' => 'left',
						'r' => 'right',
						't' => 'top',
						'n' => 'No');
	var $_tpls = array();

	function defaultMethod()
	{
		$this->_initRenderer();
		$data = array();
		foreach ($this->_opts as $k=>$v)
		{
			$data[$k] = array('label'=>$v['label'], 'val'=>$v['opts'][$v['val']]);
		} 
		$this->_rnd->render( $data, null, 'ROW');
	}

	function &_makeConfForm()
	{
		$form = & parent::_makeConfForm(); 
		foreach ( $this->_opts as $k=>$v )
		{
			$form->add( new elSelect($k, m($v['label']), $v['val'], $v['opts']) );
		}
		return $form;
	}

	function _getAltTplsList()
	{
		$ret = array();
		$tmp = glob(EL_DIR.'style/alternative/*.html'); 
		foreach ($tmp as $file)
		{
			if ( !is_file($file) || !is_readable($file) )
			{
				continue;
			}
			$f = basename($file);
			/**
			$prew = '';
			if ( file_exists(EL_DIR.'style/alternative/preview/'.$f.'.png') )
			{
				$prew = EL_BASE_URL.'/style/alternative/preview/'.$f.'.png';
			}
			elseif ( file_exists(EL_DIR.'style/alternative/preview/'.$f.'.jpg') )
			{
				$prew = EL_BASE_URL.'/style/alternative/preview/'.$f.'.jpg';
			}
			$tpl = empty($prew) ? $f : '<img src="'.$prew.'" />'.$f;
			**/
			$ret[$f] = $f;
			
		}
		if (!empty($ret))
		{
			array_unshift($ret, m('Default'));
		}
		return $ret;
	}

	function _onInit()
	{
		$conf = & elSingleton::getObj('elXmlConf');
		$this->_tpls = $this->_getAltTplsList(); //elPrintR($this->_tpls);
		if ( empty($this->_tpls) )
		{
			unset($this->_opts['servicesTpl']);
		}
		if (!$conf->findGroup('module', 'IShop') && !$conf->findGroup('module', 'TechShop'))
		{
			unset($this->_opts['ICartPosition']);
			unset($this->_opts['iCartDisplayEmpty']);
		}
		$this->_pos = array_map('m', $this->_pos);
		foreach ( $this->_opts as $k=>$v )
		{
			if (1)// isset($this->_conf[$k]) )
			{
				$this->_opts[$k]['label'] = m($this->_opts[$k]['label']);
				$this->_opts[$k]['val'] = $this->_conf[$k];
				switch ($v['optype'])
				{
					case '_pos':
						$this->_opts[$k]['opts'] = $this->_pos;
						break;
				
					case 'yn':
						$this->_opts[$k]['opts'] = $GLOBALS['yn'];
						break;
					
					case '_tpls':
						$this->_opts[$k]['opts'] = $this->_tpls;
						break;
				}
				
			}
		}
	}
}

?>