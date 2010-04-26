<?php
// ver 2.0
include_once EL_DIR_CORE.'lib/elPlugin.class.php';

class elPluginInfoBlock extends elPlugin
{
	var $_db  = null;
	var $_rnd = null;
	var $_posNfo = array(
		EL_POS_LEFT   => array('PLUGIN_INFO_BLOCK_LEFT',   'left.html'),
		EL_POS_RIGHT  => array('PLUGIN_INFO_BLOCK_RIGHT',  'right.html'),
		EL_POS_TOP    => array('PLUGIN_INFO_BLOCK_TOP',    'top.html'),
		EL_POS_BOTTOM => array('PLUGIN_INFO_BLOCK_BOTTOM', 'top.html')
		);

	function onUnload()
	{
		$editable = allowPluginsCtlPage();
		$db       = & elSingleton::getObj('elDb');

		$sql = 'SELECT i.id, i.name, i.content, i.pos, i.tpl '
			  .'FROM el_plugin_ib AS i, el_plugin_ib2page AS p '
			  .'WHERE (p.page_id=1 OR page_id=\''.$this->pageID.'\') '
			  .'AND i.id=p.id ORDER BY i.id';

		$db->query( $sql );
		if (!$db->numRows())
		{
			return;
		}
		$rnd = & elSingleton::getObj('elTE');

		while ( $r = $db->nextRecord() )
		{
			
			list($pos, $tplVar, $tpl) = $this->_getPosInfo($r['pos'], $r['tpl']);
			
			if (!$pos)
			{
				echo $r['tpl'];
				continue;
			}
			$rnd->setFile($tplVar, $tpl);
			$rnd->assignVars( $r );
			if ($editable)
			{
				$url = EL_URL.EL_URL_POPUP.'/__pl__/'.$this->name.'/'; //echo $this->name;
				$rnd->assignBlockVars('PL_IB_EDIT', array('id'=>$r['id'], 'name'=>$this->name));
			}
			if (!empty($r['name']))
			{
				$rnd->assignBlockVars('PL_IB_NAME', array('name'=>$r['name']));
			}
			$rnd->parse($tplVar, $tplVar, 1, false, true);
			$GLOBALS['parseColumns'][$pos] = 1;
		}
	}

	function call($args)
	{
		$act = $args[0];
		$ID  = !empty($args[1]) ? (int)$args[1] : 0;

		elLoadMessages('PluginAdminInfoBlock');
		elLoadMessages('CommonAdmin');
		include_once 'elCoreAdmin.lib.php';
		$ib  = & elSingleton::getObj('elInfoBlock');
		$ib->setUniqAttr($ID);
		$ib->fetch();

		if ( 'rm' == $act )
		{
			if ( !$ib->ID )
			{
				$_SESSION['msgNoDisplay'] = 1;
				elThrow(E_USER_WARNING, 'Object "%s" with ID="%s" does not exists', array('InfoBlock', $args[1]) );
				elAddJs('window.opener.location=\''.EL_URL.'\';window.close();', EL_JS_SRC_ONLOAD);
			}

			$ib->delete( array($ib->tbPages=>'id') );
			elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $ib->getObjName(), $ib->ID) );
			elLocation(EL_URL);
		}

		if (!$ib->ID)
		{
			$ib->_pages = array(1);
		}
		if (!$ib->editAndSave())
		{
			$rnd = & elSingleton::getObj('elTE');
			$rnd->assignVars( 'PAGE', $ib->formToHtml(), 1 );
		}
		else
		{
			$_SESSION['msgNoDisplay'] = 1;
			elMsgBox::put( m('Data saved') );
			elAddJs('window.opener.location=\''.EL_URL.'\';window.close();', EL_JS_SRC_ONLOAD);
		}
	}

	function conf($args)
	{
		$this->_loadManager();
		$this->_args = $args;
		$action = $this->_arg();
		if ('edit' == $action)
		{
			$this->_manager->edit( (int)$this->_arg(1) );
		}
		elseif ('rm' == $action)
		{
			$this->_manager->remove( (int)$this->_arg(1) );
		}
		else
		{
			$this->_manager->displayAll();
		}

	}

	function _loadManager()
	{
		if ( elSingleton::incLib('plugins/InfoBlock/elPluginManagerInfoBlock.class.php') )
		{
			$this->_manager = & elSingleton::getObj('elPluginManagerInfoBlock');
			$this->_manager->master = &$this;
		}
	}

	function _onSwitchOn()
	{
		$this->_loadManager();
		$this->_manager->checkTables();
	}

}

?>