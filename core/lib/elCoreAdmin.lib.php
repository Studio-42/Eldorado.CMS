<?php
// useful for fast creation of select fields
$GLOBALS['yn'] 	    = array( m('No'), m('Yes'));
$GLOBALS['posLR']   = array(
	EL_POS_LEFT  => m('left'),
	EL_POS_RIGHT => m('right')
	);
$GLOBALS['posLRT']  = array(
	EL_POS_LEFT  => m('left'),
	EL_POS_RIGHT => m('right'),
	EL_POS_TOP   => m('top')
	);
$GLOBALS['posLRTB'] = array(
	EL_POS_LEFT   => m('left'),
	EL_POS_TOP    => m('top'),
	EL_POS_RIGHT  => m('right'),
	EL_POS_BOTTOM => m('bottom')
	);
$GLOBALS['posNLRTB'] = array(
	0             => m('No'),
	EL_POS_LEFT   => m('left'),
	EL_POS_TOP    => m('top'),
	EL_POS_RIGHT  => m('right'),
	EL_POS_BOTTOM => m('bottom')
	);




function elGetNavTree($delim='', $rootName=null) {
	$db   = & elSingleton::getObj('elDb');
	$name = null === $rootName ? 'name' : 'IF(id<>1, name, "'.mysql_real_escape_string($rootName).'") ';
    $name = -1 != $rootName ? 'CONCAT( REPEAT("'.$delim.'  ", level), '.$name.') AS name' : 'CONCAT( REPEAT("'.$delim.'  ", level-1), '.$name.') AS name';
	$sql  = 'SELECT id, '.$name.'  FROM el_menu ORDER BY _left';
	return $db->queryToArray($sql, 'id', 'name');
}



?>