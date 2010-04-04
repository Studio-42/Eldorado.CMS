<?php

/**
 * Logs data modification made by user/admin
 *
 * @package elActionLog
 * @author Troex Nevelin <troex@fury.scancode.ru>
 **/

/**
 * To find all usage: grep -R elActionLog core/modules/*
 * Only exist in next modules:
 *   DocsCatalog
 *   FAQ
 *   FileArchive
 *   LinksCatalog
 *   News
 *   SimplePage
 *   TechShop
 **/

function elActionLog($obj, $act = false, $link = false, $value = false)
{
	$elActLog = array();
	$nav = & elSingleton::getObj('elNavigator');
	$ats = & elSingleton::getObj('elATS');

	$elActLog['uid'] = $ats->getUserID();
	$elActLog['mid'] = $nav->curPageID;

	unset($nav);
	unset($ats);

	if (is_object($obj))
		$elActLog['object'] = $obj->_objName;
	else
		$elActLog['object'] = $obj;
	
	if ($act == false)
		if ($obj->_new == true)
			$elActLog['action'] = 'new';
		else
			$elActLog['action'] = 'edit';
	else
		$elActLog['action'] = $act;

	$elActLog['time'] = time();
	$elActLog['link'] = str_replace(EL_BASE_URL, '', EL_URL) . $link;
	$elActLog['value'] = substr(strip_tags($value), 0, 63);

	$db = & elSingleton::getObj('elDb');
	$tb = 'el_action_log';
	if ($db->isTableExists($tb))
	{
		$sql = 'INSERT INTO '.$tb.' ('.implode(',', array_keys($elActLog)).') VALUES '.'(\''.implode('\',\'', $elActLog).'\')';
		$db->query($sql);
	}

	// uncomment for debug use
	// elMsgBox::put('elActionLog: '.print_r($elActLog, true));
}
