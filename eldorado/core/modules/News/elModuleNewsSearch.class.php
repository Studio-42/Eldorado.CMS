<?php

class elModuleNewsSearch 
{
	function getResults($pageIDs, $regex1, $regex2)
	{
		$ret = array(); 
		$db  = & elSingleton::getObj('elDb');
		$nav = & elSingleton::getObj('elNavigator');
		
		foreach ($pageIDs as $pID)
		{
			$tb  = 'el_news_'.$pID;
			$url = $nav->getPageURL($pID);
			$title = $nav->getPageFullTitle($pID);

			$sql = 'SELECT CONCAT(\''.$url.'read/1/\', n.id) AS path, IFNULL(m.content, n.title) AS title, '
						.'IF(UPPER(IFNULL(m.content, n.title)) RLIKE '.$regex1.', 1, 0) + 
						IF(UPPER(n.title) RLIKE '.$regex1.', 1, 0) + 
						IF(UPPER(IFNULL(n.content, n.announce)) RLIKE '.$regex1.', 1, 0 ) AS w '
						.'FROM '.$tb.' AS n '
						.'LEFT JOIN el_metatag AS m ON m.page_id="'.$pID.'" AND m.i_id=n.id AND m.name="title" WHERE '
						.'UPPER(title) RLIKE '.$regex2.' OR '
						.'UPPER(m.content) RLIKE '.$regex2.' OR '
						.'UPPER(IFNULL(n.content, n.announce)) RLIKE '.$regex2.' '
						.'ORDER BY published DESC';

			$db->query($sql);
			
			while ($r = $db->nextRecord())
			{
				$ret[$r['path']] = array('title' => $title.' &raquo; '.$r['title'], 'weight' => $r['w']);
			}
		}
		return $ret;
	}
	
}

?>