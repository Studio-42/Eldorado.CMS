<?php

class elModuleSimplePageSearch
{
	function getResults($pageIDs, $regex)
	{
		$ret = array(); 
		$db  = & elSingleton::getObj('elDb');
		$db->query('SELECT id FROM el_page WHERE UPPER(content) RLIKE '.$regex);
		while ( $r = $db->nextRecord() )
		{
			$ret[$r['id']] = array( array('title' => '', 'path' => '') );
		}
		return $ret;
	}
}
?>