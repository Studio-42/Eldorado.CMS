<?php

include_once EL_DIR_CORE.'lib/elPlugin.class.php';
include_once EL_DIR_CORE.'plugins/Calculator/elPcCalculator.class.php';
include_once EL_DIR_CORE.'plugins/Calculator/elPcVar.class.php';

class elPluginCalculator extends elPlugin
{
	var $_db  = null;
	var $_rnd = null;
	var $_posNfo = array(
		EL_POS_LEFT   => array('PLUGIN_INFO_BLOCK_LEFT',   'left.html'),
		EL_POS_RIGHT  => array('PLUGIN_INFO_BLOCK_RIGHT',  'right.html'),
		EL_POS_TOP    => array('PLUGIN_INFO_BLOCK_TOP',    'top.html'),
		EL_POS_BOTTOM => array('PLUGIN_INFO_BLOCK_BOTTOM', 'bottom.html')
		);
	
	function onUnload()
	{

		$editable = allowPluginsCtlPage();
		$db       = & elSingleton::getObj('elDb');

		$calcs = $db->queryToArray( 'SELECT i.id, i.name, i.pos, i.tpl, i.formula, i.unit, i.dtype, i.view '
			  .'FROM el_plugin_calc AS i, el_plugin_calc2page AS p '
			  .'WHERE (p.page_id=1 OR page_id=\''.$this->pageID.'\') '
			  .'AND i.id=p.id ORDER BY i.id' );
		
		if (!$calcs)
		{
			return;
		}
		
		// elPrintR($calcs);
		elAddJs('jquery.metadata.min.js', EL_JS_CSS_FILE);
		elAddJs('plugin.calculator.min.js', EL_JS_CSS_FILE);
		elAddJs('jquery.validate.min.js', EL_JS_CSS_FILE);
		if (EL_LANG != 'en') 
		{
			elAddJs('i18n/jquery.validate.'.EL_LANG.'.js', EL_JS_CSS_FILE);
		}
		
		$rnd = & elSingleton::getObj('elTE');
		$ats = & elSingleton::getObj('elATS');
		$editable = false;
		if ($ats->getUserID()) 
		{
			$conf   = elSingleton::getObj('elXmlConf');
			$pageID = $conf->findGroup('module', 'PluginsControl');
			if ($ats->allow($pageID, EL_WRITE)) 
			{
				$editable = true;
				$nav = & elSingleton::getObj('elNavigator');
				$editURL = $nav->getPageURL($pageID).'pl_conf/Calculator/';
			}
		}
		
		$var =  new elPcVar();
		foreach ($calcs as $calc) {
			list($pos, $tplVar, $tpl) = $this->_getPosInfo($calc['pos'], $calc['tpl']);
			if (!$pos)
			{
				continue;
			}
			$rnd->setFile($tplVar, $tpl);
			
			if ($calc['view'] == 'inline') 
			{
				$rnd->assignBlockVars('PL_CALC_INLINE.CALC', $calc, 1);
				if ($editable) 
				{
					$rnd->assignBlockVars('PL_CALC_INLINE.CALC.ADMIN', array('url' => $editURL.$calc['id'].'/'), 2);
				}
				foreach ($var->collection(true, false, 'cid='.$calc['id'], 'sort_ndx, id') as $var) 
				{
					$rnd->assignBlockVars('PL_CALC_INLINE.CALC.PL_CALC_VAR', $var->toArray(), 2);
				}
			} 
			else 
			{
				elLoadJQueryUI();
				elAddJs('eldialogform.min.js', EL_JS_CSS_FILE);
				elAddCss('eldialogform.css',      EL_JS_CSS_FILE);
				$rnd->assignBlockVars('PL_CALC_DIALOG.CALC', $calc, 1);
				if ($editable) 
				{
					$rnd->assignBlockVars('PL_CALC_DIALOG.CALC.ADMIN', array('url' => $editURL.$calc['id'].'/'), 2);
				}
			}
		
		}
		$rnd->parse($tplVar, $tplVar, 1, false, true);
		$GLOBALS['parseColumns'][$pos] = 1;
	}
		
	function conf($args)
	{
		include_once EL_DIR_CORE.'plugins/Calculator/elPluginManagerCalculator.class.php';
		$this->_manager = new elPluginManagerCalculator($args);
		$this->_manager->run();
	}
	
	
	function call($args) {

		$this->_args = $args;
		include_once EL_DIR_CORE.'lib/elJSON.class.php';
		$calc =  new elPcCalculator(array('id' => (int)$args[0]));
		if (!$calc->fetch()) 
		{
			exit(elJSON::encode(array('error' => m('Requested calculator does not exists'))));
		}
		
		$json = $calc->toJSON();
		$l = strlen($json);
		header('Content-type: text/html; charset=utf-8');
		header('Content-Length: '.$l);
		die($json.'');

	}
		
		
}
?>