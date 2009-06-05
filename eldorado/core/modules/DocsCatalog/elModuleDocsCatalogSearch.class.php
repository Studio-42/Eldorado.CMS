<?php

class elModuleDocsCatalogSearch
{
   function getResults($pageIDs, $regex)
   {
      $ret = array();
      $db  = & elSingleton::getObj('elDb');

      foreach ($pageIDs as $pageID)
      {
         $ctb   = 'el_dcat_'.$pageID.'_cat';
         $itb   = 'el_dcat_'.$pageID.'_item';
         $i2cTb = 'el_dcat_'.$pageID.'_i2c';
         $found = array();

         $sql = 'SELECT DISTINCT p.id, p.name FROM '.$ctb.' AS ch, '.$ctb.' AS p WHERE ('
         .'(UPPER( ch.name )   RLIKE '.$regex.' OR '
         .'UPPER( ch.descrip ) RLIKE '.$regex.') '
         .'AND ch.level >0 ) '
         .'AND ch._left BETWEEN p._left AND p._right AND ch.level = p.level +1 ORDER BY p._left';

         $db->query($sql);
         while ($r = $db->nextRecord() )
         {
            $found[] = 1== $r['id']
            ? array('path'=>'', 'title'=>'')
            : array('path' => $r['id'].'/', 'title' => $r['name']);
         }

         $sql = 'SELECT id, c_id, name FROM '.$itb.', '.$i2cTb.' WHERE ('
         .'UPPER(name)     RLIKE '.$regex.' OR '
         .'UPPER(announce) RLIKE '.$regex.' OR '
         .'UPPER(content)  RLIKE '.$regex.') AND '
         .'i_id=id '
         .'ORDER BY name DESC';
         $items = $db->queryToArray($sql, 'id');

         // full path to item in name - cat1::cat2:...::item name
         foreach ( $items as $item)
         {
            $sql = 'SELECT p.id, p.name FROM '.$ctb.' AS p, '.$ctb.' AS ch WHERE '
               .'ch.id=\''.$item['c_id'].'\' AND (p._left<=ch._left AND p._right>=ch._right AND p.level>0) ORDER BY p._left ';
            $p = $db->queryToArray($sql, 'id', 'name'); //elPrintR($p);
            if ( !empty($p) )
            {
               $item['name'] = implode(' :: ', $p).' :: '.$item['name'];
            }
            $found[] = array('path' => 'item/'.$item['c_id'].'/'.$item['id'].'/', 'title' => $item['name']);
         }

         if (!empty($found))
         {
            $ret[$pageID] = $found;
         }
      }
      return $ret;
   }

}


?>