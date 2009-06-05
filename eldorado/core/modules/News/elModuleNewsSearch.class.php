<?php

class elModuleNewsSearch 
{
	function getResults($pageIDs, $regex)
	{
		$ret = array(); 
		$db  = & elSingleton::getObj('elDb');
		$found = array();
		foreach ($pageIDs as $pID)
		{
			$tb  = 'el_news_'.$pID;
			$sql = 'SELECT CONCAT(\'read/1/\', id) AS path, title FROM '.$tb.' WHERE '
						.'UPPER(title)       RLIKE '.$regex.' OR '
						.'UPPER(announce)    RLIKE '.$regex.' OR '
						.'UPPER(content)     RLIKE '.$regex.' '
						.'ORDER BY published DESC';
			$found = $db->queryToArray($sql);			
			if (!empty($found))
			{
				$ret[$pID] = $found;
			}
		}
		return $ret;
	}
	
}

?>