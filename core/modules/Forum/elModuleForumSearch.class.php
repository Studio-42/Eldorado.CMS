<?php

class elModuleForumSearch
{
   	function getResults($pageIDs, $regex1, $regex2)
   	{
      	$ret = array();
      	$db  = & elSingleton::getObj('elDb');
		$nav = & elSingleton::getObj('elNavigator');
		
		foreach ($pageIDs as $pageID)
		{
			$url   = $nav->getPageURL($pageID);
			$title = $nav->getPageFullTitle($pageID);
			
			$sql = 'SELECT id, cat_id, topic_id, subject,   '
				.'IF(UPPER(subject) RLIKE '.$regex1.', 2, 0) + IF(UPPER(message) RLIKE '.$regex1.', 1, 0) AS w '
				.'FROM el_forum_post '
				.'WHERE UPPER(subject) RLIKE '.$regex2.' OR UPPER(message) RLIKE '.$regex2.' ORDER BY cat_id, topic_id, id';
			
			
			$db->query($sql);
			while ($r = $db->nextRecord())
			{
				$ret[$url.'topic/'.$r['cat_id'].'/'.$r['topic_id'].'/#'.$r['id']] = array('title' => $title.' &raquo; '.$r['subject'], 'weight' => $r['w']);
			}
				
		}
		
		return $ret;
     }
}


?>