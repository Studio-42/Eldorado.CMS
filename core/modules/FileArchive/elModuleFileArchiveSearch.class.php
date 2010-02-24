<?php

class elModuleFileArchiveSearch
{
	function getResults($pageIDs, $regex1, $regex2)
	{
		$ret = array();
		$db  = & elSingleton::getObj('elDb');
		$nav = & elSingleton::getObj('elNavigator');
		
		foreach ($pageIDs as $pageID)
		{
			$ctb   = 'el_fa_'.$pageID.'_cat';
			$itb   = 'el_fa_'.$pageID.'_item';
	        $i2cTb = 'el_fa_'.$pageID.'_i2c';
			$url   = $nav->getPageURL($pageID);
			$title = $nav->getPageFullTitle($pageID);
			
			$sql = 'SELECT i.id, i2c.c_id, c.name, '
				.'IF(UPPER(IFNULL(m.content, i.name)) RLIKE '.$regex1.', 1, 0) + 
				IF(UPPER(i.name) RLIKE '.$regex1.', 1, 0) + 
				IF(UPPER(i.descrip) RLIKE '.$regex1.', 1, 0) AS w '
				.'FROM '.$ctb.' AS c, ('.$i2cTb.' AS i2c, '.$itb.' AS i ) '
				.'LEFT JOIN el_metatag AS m ON m.page_id='.$pageID.' AND m.c_id=i2c.c_id AND m.name="title" '
				.'WHERE i2c.i_id=i.id AND c.id=i2c.c_id AND (UPPER(IFNULL(m.content, i.name)) RLIKE '.$regex2.' OR UPPER(i.descrip) RLIKE '.$regex2.' OR UPPER(i.name) RLIKE '.$regex2.') '
				.'GROUP BY c.id ORDER BY i.name';
			
			$db->query($sql);
			while ($r = $db->nextRecord())
			{
				$ret[$url.$r['c_id'].'/'] = array('title' => $title.' &raquo; '.$r['name'], 'weight' => $r['w']);
			}
		}
		return $ret;
	}

}


?>