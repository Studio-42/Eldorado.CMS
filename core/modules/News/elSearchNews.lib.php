<?php

function elSearchNews( &$conf, $pageID, $regexp )
{
  $tb = 'el_news_'.$pageID;
  $db = & elSingleton::getObj('elDb');
  $ret = array();

  $sql = 'SELECT id, title FROM '.$tb.' WHERE '
    .'title    RLIKE \''.$regexp.'\' OR '
    .'announce RLIKE \''.$regexp.'\' OR '
    .'content  RLIKE \''.$regexp.'\' '
    .'ORDER BY published DESC';
  $db->query( $sql );
  while ( $r = $db->nextRecord() )
    {
      $ret[] = array('read/1/'.$r['id'], $r['title']);
    }
  return $ret;
} 

?>