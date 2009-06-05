<?php

class elMetaTagsCollection
{
    var $pageID = 0;
    var $cID = 0;
    var $iID = 0;
    var $tbc = 0;
    var $title = '';
    var $tags = array();
    
    function init($pageID, $catID=0, $itemID=0, $tbc='')
    {
        $this->pageID = (int)$pageID;
        $this->cID    = $catID>1 ? (int)$catID : 0;
        $this->iID    = (int)$itemID;
        $this->tbc    = $tbc;
        
        $this->_load();
        
        if ( $this->cID && $this->tbc )
        {
            $this->_load('cat');
        }
        elseif ( $this->cID )
        {
            $this->_load('imgCat');
        }
        elseif ( $this->iID )
        {
            $this->_load('item');
        }
        //elPrintR($this);
    }
    
    function get()
    {
        if ( !$this->pageID )
        {
            $nav = &elSingleton::getObj('elNavigator');
            $this->init($nav->getCurrentPageID());
        }
        return array($this->title, $this->tags);
    }
    
    function _load( $what=null )
    {
        static $db = null;
        if ( !$db )
        {
           $db = &elSingleton::getObj('elDb'); 
        }
        if ('item' == $what )
        {
            $sql = 'SELECT UPPER(name) AS name, content FROM el_metatag WHERE page_id='.$this->pageID.' AND i_id='.$this->iID.' AND c_id=0';
            
        }
        elseif ( 'cat' == $what )
        {
            $sql = 'SELECT UPPER(t.name) AS name, t.content FROM el_metatag AS t, '.$this->tbc.' AS ch, '.$this->tbc.' AS p '
                .'WHERE t.page_id=\''.$this->pageID.'\' AND '
                .'( ( (ch.id='.$this->cID.' AND ch._left BETWEEN p._left AND p._right) AND (t.c_id=p.id AND t.i_id=0) ) OR '
                .($this->iID ? 't.i_id='.$this->iID.' ' : '0').' ) '
                .' ORDER BY p._left';
        }
        elseif ('imgCat' == $what)
        {
            $sql = 'SELECT UPPER(name) AS name, content FROM el_metatag WHERE page_id='.$this->pageID.' AND c_id='.$this->cID.' AND i_id=0';
        }
        else
        {
            $sql = 'SELECT UPPER(t.name) AS name, t.content FROM el_metatag AS t, el_menu AS ch, el_menu AS p '
                .'WHERE t.c_id=0 AND t.i_id=0 AND (ch.id='.$this->pageID.' AND ch._left BETWEEN p._left AND p._right AND t.page_id=p.id )'
                .' ORDER BY p._left';
                
        }

        $db->query($sql);
        while ( $r = $db->nextRecord() )
        {
            if ( 'TITLE' == $r['name'] )
            {
                $this->title = $r['content']; 
            }
            else
            {
                $this->tags[$r['name']] = $r;
            }
            //echo $this->title;
        }
    }

}

?>