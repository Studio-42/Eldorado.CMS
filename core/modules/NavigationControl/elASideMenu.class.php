<?php

class elASideMenu
{
    var $_tb    = 'el_amenu';
    var $_tbDst = 'el_amenu_dest';
    var $_tbSrc = 'el_amenu_source';
    var $ID     = 0;
    var $name   = '';
    var $pos    = 'l';
    var $dst    = array();
    var $src    = array();
    
    function fetch()
    {
        $db  = & elSingleton::getObj('elDb');
        $db->query('SELECT id, name, pos FROM '.$this->_tb.' WHERE id='.intval($this->ID));
        if ( !$db->numRows() )
        {
            return false;
        }
        $r          = $db->nextRecord();
        $this->ID   = $r['id'];
        $this->name = $r['name'];
        $this->pos  = $r['pos'];
        $this->dst  = $db->queryToArray('SELECT d.p_id, m.name FROM '.$this->_tbDst.' AS d, el_menu AS m WHERE d.m_id=\''.$this->ID.'\' AND d.p_id=m.id ORDER BY m._left', 'p_id', 'name');
        $this->src  = $db->queryToArray('SELECT s.p_id, m.name FROM '.$this->_tbSrc.' AS s, el_menu AS m WHERE s.m_id=\''.$this->ID.'\' AND s.p_id=m.id ORDER BY m._left', 'p_id', 'name');
        return true;
    }
    
    function getAll()
    {
        $ret = array();
        $db  = & elSingleton::getObj('elDb');
        
        $db->query('SELECT id, name, pos FROM '.$this->_tb.' ORDER BY id');
        while ($r = $db->nextRecord() )
        {
            $ret[$r['id']]       = $this;
            $ret[$r['id']]->ID   = $r['id'];
            $ret[$r['id']]->name = $r['name'];
            $ret[$r['id']]->pos  = $r['pos'];
        }
        if (! empty($ret) )
        {
            $db->query('SELECT d.m_id, d.p_id, m.name FROM '.$this->_tbDst.' AS d, el_menu AS m WHERE d.p_id=m.id ORDER BY m._left');
            while ($r = $db->nextRecord() )
            {
                $ret[$r['m_id']]->dst[$r['p_id']] = $r['name'];
            }
            $db->query('SELECT s.m_id, s.p_id, m.name FROM '.$this->_tbSrc.' AS s, el_menu AS m WHERE s.p_id=m.id ORDER BY m._left');
            while ($r = $db->nextRecord() )
            {
                $ret[$r['m_id']]->src[$r['p_id']] = $r['name'];
            }
        }
        
        return $ret;
    }
    
    
    
    
    function delete()
    {
        if ( !$this->ID )
        {
            return;
        }
        $db = & elSingleton::getObj('elDb');
        $db->query('DELETE FROM '.$this->_tb.' WHERE id='.$this->ID);
        $db->query('DELETE FROM '.$this->_tbDst.' WHERE m_id='.$this->ID);
        $db->query('DELETE FROM '.$this->_tbSrc.' WHERE m_id='.$this->ID);
        $db->optimizeTable( $this->_tb );
        $db->optimizeTable( $this->_tbDst );
        $db->optimizeTable( $this->_tbSrc );
    }
    
    function editAndSave()
    {
        $this->_makeForm();
        if ($this->form->isSubmitAndValid())
        {
            $data = $this->form->getValue(); //elPrintR($data);
            return $this->_save( $data );
            
        }
        return false;
    }
    
    function _save($data)
    {
        $db = & elSingleton::getObj('elDb');
        $name = mysql_real_escape_string($data['name']);
        $pos = !empty($GLOBALS['posLR'][$data['pos']]) ? $data['pos'] : 'l';
        if ( $this->ID )
        {
            if ( !$db->query( 'UPDATE '.$this->_tb.' SET name=\''.$name.'\', pos=\''.$pos.'\' WHERE id='.$this->ID ) )
            {
                return false;
            }
        }
        else
        {
            if ( !$db->query( 'INSERT INTO '.$this->_tb.' SET name=\''.$name.'\', pos=\''.$pos.'\' ' ) )
            {
                return false;
            }
            $this->ID = $db->insertID();
        }
        
        $db->query('DELETE FROM '.$this->_tbDst.' WHERE m_id='.$this->ID);
        $db->query('DELETE FROM '.$this->_tbSrc.' WHERE m_id='.$this->ID);
        $db->optimizeTable( $this->_tbDst );
        $db->optimizeTable( $this->_tbSrc );
        
        $db->prepare('INSERT INTO '.$this->_tbDst.' (m_id, p_id) VALUES ', '(%d, %d)');
        foreach ($data['dst'] as $pID )
        {
            $db->prepareData( array($this->ID, $pID) );
        }
        $db->execute();
        
        $db->prepare('INSERT INTO '.$this->_tbSrc.' (m_id, p_id) VALUES ', '(%d, %d)');
        foreach ($data['src'] as $pID )
        {
            $db->prepareData( array($this->ID, $pID) );
        }
        $db->execute();
        return true;
    }
    
    function _makeForm()
    {
        $this->form = & elSingleton::getObj('elForm');
        $this->form->setLabel( $this->ID ? m('Edit additional side menu') : m('New additional side menu') );
        $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
        $this->form->add( new elText('name', m('Name'), $this->name) );
        $this->form->add( new elSelect('pos', m('Position'), $this->pos, $GLOBALS['posLR']) );
        
        $pages = elGetNavTree('+', -1);
        unset($pages[1]);
        
        $d =  &new elMultiSelectList('dst', m('Pages, where menu displays'), array_keys($this->dst),  $pages );
        $this->form->add( $d );
        
        $s =  &new elMultiSelectList('src', m('Pages, included in menu'), array_keys($this->src),  $pages );
        $this->form->add( $s );
        
        $this->form->setRequired('dst[]');
        $this->form->setRequired('src[]');

    }
    
    function formToHtml()
    {
        return $this->form->toHtml();
    }
    
    function rm()
    {
        
    }
    
    function toArray()
    {
        
    }
    
}

?>