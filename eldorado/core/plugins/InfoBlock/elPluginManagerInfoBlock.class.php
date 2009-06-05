<?php

class elPluginManagerInfoBlock
{

	function edit( $ID )
	{
		$ib  = & elSingleton::getObj('elInfoBlock');
		if ( $ID )
		{
			$ib->setUniqAttr($ID);
			$ib->fetch();
		}

		if (!$ib->editAndSave())
		{ 
			$rnd = & elSingleton::getObj('elTE'); 
			$rnd->assignVars( 'PAGE', $ib->formToHtml(), 1 );
		}
		else
		{
			elMsgBox::put( m('Data saved') );
			elLocation(EL_URL.'pl_conf/'.$this->master->name);
		}
	}

	function remove($ID)
	{
		$ib  = & elSingleton::getObj('elInfoBlock');
		$ib->setUniqAttr($ID);
		$ib->fetch();
		if ( !$ib->ID )
		{
			elThrow(E_USER_ERROR, 'There is no object "%s" with ID="%d"',
				array($ib->getObjName(), $ID), EL_URL.'pl_conf/'.$this->master->name);
		}
		$ib->delete( array($ib->tbPages=>'id') );
		elMsgBox::put( sprintf(m('Object "%s" "%s" was deleted'), $ib->getObjName(), $ib->ID) );
		elLocation(EL_URL.'pl_conf/'.$this->master->name);
	}

	function displayAll()
	{
		$ib  = & elSingleton::getObj('elInfoBlock');


		$ibList = $ib->getCollection();
		$rnd = & elSingleton::getObj('elTE');
		$rnd->setFile('list', 'plugins/'.$this->master->name.'/adminList.html');
		$rnd->assignVars('pluginName', $this->master->name);
		if (empty($ibList))
		{
			$rnd->parse('list', 'PAGE', true);
			return $rnd->assignVars('PAGE', m('There are no one info block was found'), true);
		}
		foreach ($ibList as $one)
		{
			$attrs         = array();
			$attrs['id']   = $one->getAttr('id');
			$attrs['name'] = $one->getAttr('name');
			if (empty($attrs['name']))
			{
				//$attrs['name'] = m('Info block').' '.$attrs['id'];
				$attrs['name'] = substr( strip_tags($one->getAttr('content')), 0, 128).'...';
			}
			$attrs['pages']    = implode('<br />', $one->getPagesNames());
			$attrs['position'] = $GLOBALS['posLRTB'][$one->getAttr('pos')];
			$rnd->assignBlockVars('IB', $attrs);
		}
		$rnd->parse('list', 'PAGE', true);
	}



	function checkTables()
	{
		$db = & elSingleton::getObj('elDb');
		if ( ! $db->isTableExists('el_plugin_ib') )
		{
			$sql = "CREATE TABLE el_plugin_ib (
							id tinyint (2) NOT NULL auto_increment,
							content text,
							pos enum('".EL_POS_LEFT."', '".EL_POS_RIGHT."','".EL_POS_TOP."','".EL_POS_BOTTOM."') default '".EL_POS_LEFT."',
							PRIMARY KEY (id)
							)";
			if ( !$db->query($sql) )
			{
				return elThrow(E_USER_WARNING, 'Could not create table "%s" in DB', 'el_plugin_ib');
			}
		}
		if (!$db->isTableExists('el_plugin_ib2page') )
		{
			$sql = "CREATE TABLE el_plugin_ib2page (
							id tinyint(2) NOT NULL,
							page_id int(3) NOT NULL,
							PRIMARY KEY(id, page_id)
						)";
			if (!$db->query($sql))
			{
				return elThrow(E_USER_WARNING, 'Could not create table "%s" in DB', 'el_plugin_ib2page');
			}
		}
		return true;
	}


}

?>