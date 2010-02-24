<?php

class elModuleTechShopSearch
{
  function getResults($pageIDs, $regex1, $regex2)
  {
 		$ret = array();
    	$db  = & elSingleton::getObj('elDb');
		$nav = & elSingleton::getObj('elNavigator');
		
		foreach ($pageIDs as $pageID)
		{
			
			$itb   = 'el_techshop_'.$pageID.'_item';
	        $i2cTb = 'el_techshop_'.$pageID.'_i2c';
			$url   = $nav->getPageURL($pageID);
			$title = $nav->getPageFullTitle($pageID);
			
			$sql = 'SELECT i.id, i2c.c_id, i.name, '
				.'IF(UPPER(IFNULL(m.content, i.name)) RLIKE '.$regex1.', 1, 0) + 
				IF(UPPER(i.name) RLIKE '.$regex1.', 1, 0) + 
				IF(UPPER(i.descrip) RLIKE '.$regex1.', 1, 0) AS w '
				.'FROM ('.$i2cTb.' AS i2c, '.$itb.' AS i ) '
				.'LEFT JOIN el_metatag AS m ON m.page_id='.$pageID.' AND m.c_id=i2c.c_id AND m.name="title" '
				.'WHERE i2c.i_id=i.id AND (UPPER(IFNULL(m.content, i.name)) RLIKE '.$regex2.' OR UPPER(i.descrip) RLIKE '.$regex2.' OR UPPER(i.name) RLIKE '.$regex2.') '
				.'GROUP BY i.id ORDER BY i.name';
			
			
			$db->query($sql);
			while ($r = $db->nextRecord())
			{
				$ret[$url.'item/'.$r['c_id'].'/'.$r['id'].'/'] = array('title' => $title.' &raquo; '.$r['name'], 'weight' => $r['w']);
			}
				
		}
		
		return $ret;
  }

}


?>