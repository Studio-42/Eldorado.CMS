<?php

/**
 * Logs data modification made by user/admin
 *
 * @package elActionLog
 * @author Troex Nevelin <troex@fury.scancode.ru>
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
	$elActLog['link'] = EL_URL . $link;
	$elActLog['value'] = substr(strip_tags($value), 0, 63);

	// TODO update datamapping and memberattributes
	// $elActLog[''] = ;
	// $elActLog[''] = ;
	// $elActLog[''] = ;

	$db  = & elSingleton::getObj('elDb');
	$sql = 'INSERT INTO el_action_log ('.implode(',', array_keys($elActLog)).') VALUES '.'(\''.implode('\',\'', $elActLog).'\')';
	$db->query($sql);

	elMsgBox::put('elActionLog: '.print_r($elActLog, true));
}
