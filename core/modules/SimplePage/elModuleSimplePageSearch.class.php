<?php

class elModuleSimplePageSearch
{
	function getResults($pageIDs, $regex1, $regex2)
	{
		
		$ret = array(); 
		$db  = & elSingleton::getObj('elDb');
		$nav = & elSingleton::getObj('elNavigator');
		
		$sql = 'SELECT p.id, mn.name, '
			.'IF(UPPER(m.content) RLIKE '.$regex1.', 1, 0) + IF(UPPER(mn.name) RLIKE '.$regex1.', 1, 0) + IF(UPPER(p.content) RLIKE '.$regex1.', 1, 0) AS w '
			.'FROM el_menu AS mn, el_page AS p LEFT JOIN el_metatag AS m ON m.page_id=p.id AND m.name="title" '
			.'WHERE mn.id=p.id AND (UPPER(IFNULL(m.content, mn.name)) RLIKE '.$regex2.' OR UPPER(p.content) RLIKE '.$regex2.')';
			
		$db->query($sql);
		while ($r = $db->nextRecord())
		{
			$ret[$nav->getPageURL($r['id'])] = array('title' => $r['name'], 'weight' => $r['w']);
		}
		return $ret;
	}
}
?>