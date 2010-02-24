<?php

include_once EL_DIR_CORE.'modules/IShop/elIShopSearch.lib.php';

class elIShopSearchAdmin
{
    var $sm    = null;
    var $tbs   = array();
    var $_db   = null;
    var $_rnd  = null;
    var $_args = array();
    var $_mMap = array('group'=>'editGroup', 'rmg'=>'rmGroup', 'el'=>'editElement', 'rme'=>'rmElement', 'sort'=>'sort');
    
    function init( &$sm )
    {
        $this->sm  = &$sm;
        $this->tbs = $sm->tbs;
        $this->_db = & elSingleton::getObj( 'elDb' );
    }
    
    function run( &$rnd, $args )
    {
        $this->_rnd = & $rnd;
        $this->_args = $args; //elPrintR($args);
        $act = $this->_arg();
        if ( $act && !empty($this->_mMap[$act]) && method_exists($this, $this->_mMap[$act]) )
        {
            return $this->{$this->_mMap[$act]}();
        }
        
        $this->_rnd->rndSearchConfForm( $this->getGroups(), $this->getTypes() );
    }
    
    function getGroups()
    {
        $elNames = array(0=>m('New search element'));
        foreach ($this->sm->skel as $type=>$data)
        {
            $elNames[$type] = m($data[1]);
        }
        
        $restrict = array();
        $this->_db->query( 'SELECT s_id, t_id FROM '.$this->tbs['tbst'] );
        while ( $r = $this->_db->nextRecord() )
        {
            
            $restrict[$r['s_id']][] = $r['t_id'];
        }
        
        $sql = 'SELECT s.id, s.label, s.sort_ndx, t.id AS type_id, t.name '
            .'FROM '.$this->tbs['tbs'].' AS s, '.$this->tbs['tbst'].' AS st, '.$this->tbs['tbt'].' AS t '
            .'WHERE st.s_id=s.id AND t.id=st.t_id '
            .'ORDER BY s.sort_ndx, s.id, t.name';
        $this->_db->query( $sql );
        while ( $r = $this->_db->nextRecord() )
        {
            if ( empty($this->_groups[$r['id']]) )
            {
                $this->_groups[$r['id']] = array('label'     => $r['label'],
                                                 'sort_ndx'  => $r['sort_ndx'],
                                                 'iTypes'    => array(),
                                                 'elements'  => array(),
                                                 'available' => $elNames
                                                 );
            }
            $this->_groups[$r['id']]['iTypes'][$r['type_id']] = $r['name'];
        }
        
        $sql = 'SELECT se.id, se.s_id, se.label,  se.source, se.conf, se.def_val '
                .'FROM '.$this->tbs['tbse'].' AS se, '.$this->tbs['tbs'].' AS s '
                .'WHERE se.s_id=s.id ORDER BY se.sort_ndx, se.id';
        $elData = $this->_db->queryToArray($sql);
        foreach ( $elData as $r )
        {
            $el = $this->sm->createElement($r['source']);
            if ( !$el || !$el->init( $r['id'], $r['label'], 'temp', $r['conf'], $r['def_val'], !empty($restrict[$r['s_id']]) ? $restrict[$r['s_id']] : array()  )  )
            {
                $sql = 'DELETE e, p FROM '.$this->tbs['tbse'].' AS e, '.$this->tbs['tbsp'].' AS p'
                    .' WHERE e.id='.$r['id'].' AND p.e_id=e.id';
                $this->_db->query($sql);
                $this->_db->optimizeTable($this->tbs['tbse']);
                $this->_db->optimizeTable($this->tbs['tbsp']);
                continue;
            }
            $this->_groups[$r['s_id']]['elements'][$r['id']] = $el;
            if ( 'prop' != $r['source'] )
            {
                unset($this->_groups[$r['s_id']]['available'][$r['source']]);
            }
           
        }
        return $this->_groups;
        elPrintR($this->_groups);
        
        return '';
        $groups = $this->sm->getGroups();
        
        if ( empty($groups) )
        {
            return null;
        }
        
        $sql = 'SELECT st.s_id, t.name '
                .'FROM '.$this->tbs['tbst'].' AS tbst, '.$this->tbs['tbt'].' AS t '
                .'WHERE st.s_id IN ('.implode(',', array_keys($groups)).') AND t.id=st.t_id ORDER t.name';
        $this->_db->query( $sql );
        while ( $r = $this->_db->nextRecord() )
        {
            $groups[$r['s_id']]['iTypes'][] = $r['name'];
        }
        return $groups;
    }
    
    function getTypes()
    {
        $types = array();
        foreach ($this->sm->skel as $k=>$v)
        {
            $types[$k] = m($v[1]);
        }
        return $types;
    }
    
    function editGroup()
    {
        $iTypes = $this->_db->queryToArray('SELECT id, name FROM '.$this->tbs['tbt'].' ORDER BY name', 'id', 'name');
        $gID    = (int)$this->_arg(1);
        $group  =  array('id'=>0, 'label'=>'', 'restrict'=>array());
        $tmp    = $this->_db->queryToArray('SELECT id, label FROM '.$this->tbs['tbs'].' WHERE id='.$gID );
        if ( !empty($tmp) )
        {
            $group = $tmp[0];
            $group['restrict'] = $this->_db->queryToArray('SELECT t_id FROM '.$this->tbs['tbst'].' WHERE s_id='.$gID, null, 't_id');
        }
        $this->_form = & elSingleton::getObj('elForm');
        $this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
        $this->_form->setLabel( $group['id'] ? m('Edit search group') : m('Create new search group') );
        $this->_form->add( new elText('label', m('Label'), $group['label']) );
        $this->_form->add( new elCheckBoxesGroup('restrict', m('Search in items types'), $group['restrict'], $iTypes ) );
        $this->_form->setRequired('label');
        
        if ( !$this->_form->isSubmitAndValid() )
        {
            return $this->_rnd->addToContent( $this->_form->toHtml() );
        }

        if ( !$this->_saveGroup($group['id'], $this->_form->getValue()) )
        {
            elThrow(E_USER_WARNING, 'Saving group was failed');
            return $this->_rnd->addToContent( $this->_form->toHtml() );
        }
        elMsgBox::put( m('Data was saved') );
        elLocation(EL_URL.'conf_search');
    }
    
    
    function editElement()
    {
        $gID  = (int)$this->_arg(1); //echo $gID;
        $this->_db->query('SELECT id FROM '.$this->tbs['tbs'].' WHERE id='.$gID);
        if ( 1 <> $this->_db->numRows() )
        {
            elThrow(E_USER_WARNING, 'Undefined group for new search element');
            return elClosePopupWindow();
        }
        $type = $this->_arg(2); //echo $type;
        if ( empty($this->sm->skel[$type]) )
        {
            elThrow(E_USER_WARNING, 'Unknown type of search element');
            return elClosePopupWindow();
        }
        $this->_makeElementForm($gID, $type, m($this->sm->skel[$type][1]));
        if ( !$this->form->isSubmitAndValid() )
        {
            return $this->_rnd->addToContent( $this->form->toHtml() );
        }
//        $data = $this->form->getValue(); elPrintR($data);
        if ( !$this->_saveElement($gID, $type, $this->form->getValue()) )
        {
            elThrow(E_USER_WARNING, 'An error occured');
            return $this->_rnd->addToContent( $this->form->toHtml() );
        }
        elMsgBox::put( m('Data saved') );
        return elClosePopupWindow();
    }
    
    function rmElement()
    {
        $ID = (int)$this->_arg(1); //elPrintR($ID);
        $this->_db->query('DELETE FROM '.$this->tbs['tbse'].' WHERE id='.$ID);
        $this->_db->query('DELETE FROM '.$this->tbs['tbsp'].' WHERE e_id='.$ID);
        $this->_db->optimizeTable($this->tbs['tbse']);
        $this->_db->optimizeTable($this->tbs['tbsp']);
        elMsgBox::put( m('Element was removed') );
        elLocation(EL_URL.'conf_search');
    }
    
    function sort()
    {
        $groups     = $this->getGroups();
        if ( empty($groups) )
        {
            elThrow(E_USER_WARNING, 'There are no one search groups to be sorted', null, EL_URL.'conf_search');
        }
        $sortNdxs   = $this->_db->queryToArray('SELECT id, sort_ndx FROM '.$this->tbs['tbse'], 'id', 'sort_ndx'); 
        $this->form = & elSingleton::getObj('elForm', 'se_'.$type, sprintf(m('Sort'), $typeName) );
        $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
        
        foreach ( $groups as $ID=>$g )
        {
            $this->form->add( new elText('sg['.$ID.']', $g['label'], $g['sort_ndx']), array('cellAttrs'=>'class="formSubheader"') );
            foreach ( $g['elements'] as $eID=>$e )
            {
                $this->form->add( new elText('se['.$eID.']', $e->label, $sortNdxs[$eID]) );
            }
        }
        
        if ( !$this->form->isSubmitAndValid() )
        {
            $this->_rnd->addToContent( $this->form->toHtml());
        }
        else
        {
            $data = $this->form->getValue(); //elPrintR($data);
            foreach ($data['sg'] as $ID=>$sortNdx )
            {
                $sql = 'UPDATE '.$this->tbs['tbs'].' SET sort_ndx='.intval($sortNdx).' WHERE id='.intval($ID);
                $this->_db->query($sql);
            }
            foreach ($data['se'] as $ID=>$sortNdx )
            {
                $sql = 'UPDATE '.$this->tbs['tbse'].' SET sort_ndx='.intval($sortNdx).' WHERE id='.intval($ID);
                $this->_db->query($sql);
            }
            elMsgBox::put( m('Data saved') );
            elLocation(EL_URL.'conf_search');
        }
    }
    
    
    function _makeElementForm($gID, $type, $typeName)
    {
        $this->form = & elSingleton::getObj('elForm', 'se_'.$type, sprintf(m('New search element "%s"'), $typeName) );
        $this->form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
        $this->form->add( new elText('label', m('Label')) );
        $this->form->setRequired('label');
        switch ($type)
        {
            case "name":
                
                
                break;
        
            case "mnftm":
                $modes = array( 'mnf' => m('by manufacturer') );
                $this->_db->query('SELECT id FROM '.$this->tbs['tbtm']);
                if ( $this->_db->numRows() )
                {
                    $modes['tm']    = m('by trade mark / display only trade marks');
                    $modes['mnftm'] = m('by trade mark / display manufacturers and trade marks list');
                }
                $this->form->add( new elSelect('mode',       m('Search mode'), 'mnf', $modes) );
                $this->form->add( new elSelect('useCountry', m('Append country to manufacturer name'), 0, $GLOBALS['yn']) );
                break;
            
            case "price":
                $this->form->add( new elText('step',  m('Price step'),      200, array('size'=>12)) );
                $this->form->add( new elSelect('num', m('Number of steps'), 10,  range(2, 20), null, false, false) );
                $this->form->setElementRule('step', 'numbers');
                break;
            
            case "prop":
                $sql = 'SELECT t.id AS typeID, t.name AS typeName, p.id, p.name '
                    .'FROM '
                    .$this->tbs['tbt'].' AS t, '
                    .$this->tbs['tbp'].' AS p, '
                    .$this->tbs['tbst'].' AS st '
                    .'WHERE st.t_id IN ( SELECT st2.t_id FROM '.$this->tbs['tbst'].' AS st2 WHERE s_id=\''.$gID.'\' ) '
                    .'AND t.id=st.t_id AND p.t_id=t.id AND p.type>='.EL_IS_PROP_LIST.' ORDER BY t.name, p.sort_ndx, p.name';
                $this->_db->query($sql);
                $types = array();
                while( $r = $this->_db->nextRecord() )
                {
                    if ( empty($types[$r['typeID']]) )
                    {
                        $types[$r['typeID']] = array('type'=>$r['typeName'], 'props'=>array());
                    }
                    $types[$r['typeID']]['props'][$r['id']] = $r['name'];
                }
        //        elPrintR($types);
                foreach ($types as $ID=>$type)
                {
                    $this->form->add( new elRadioButtons('p['.$ID.']', $type['type'], null,  $type['props'] ));
                }
                break;
        }
    }
    
    function _saveElement($gID, $type, $data)
    {
        $sql = 'INSERT INTO '.$this->tbs['tbse'].' (s_id, label, source,  sort_ndx, conf, def_val) VALUES '
            .'('.$gID.', \''.mysql_real_escape_string($data['label']).'\', \''.$type.'\', 0, \'%s\', \'%s\')';
        
       switch ($type)
       {
            case "name":
                return $this->_db->query( sprintf($sql, '', '') );
                break;
            
            case "mnftm":
                $conf = mysql_real_escape_string($data['mode']).';'.mysql_real_escape_string($data['useCountry']);
                return $this->_db->query( sprintf($sql, $conf, '') );
                break;
            
            case "price":
                $step = $data['step']>0 ? (int)$data['step'] : 200;
                $num  = $data['num'] > 1 && $data['num'] <= 100 ? (int) $data['num'] : 10;
                return $this->_db->query( sprintf($sql, $step.';'.$num, '', '') );
                break;
            
            case "prop":
                if ( empty($data['p']) )
                {
                    return $this->form->pushError('label', m('You should select at least one propery'));
                }
                
                $this->_db->query( sprintf($sql, '', '') );
                if ( false == ($ID = $this->_db->insertID()) )
                {
                    return false;
                }
                $this->_db->prepare('INSERT INTO '.$this->tbs['tbsp'].' (e_id, p_id) VALUES ', '('.$ID.', \'%d\')');
                foreach ($data['p'] AS $pID)
                {
                    $this->_db->prepareData( array($pID) );
                }
                return $this->_db->execute();
                break;
       }
    }
    
    function _saveGroup($gID, $raw)
    {
        $label = mysql_real_escape_string($raw['label']);
        if ( !$gID )
        {
            $this->_db->query('INSERT INTO '.$this->tbs['tbs'].' (label) VALUES (\''.$label.'\')');
            if ( false == ($gID = $this->_db->insertID() ) )
            {
                return false;
            }
        }
        else
        {
            if (!$this->_db->query('UPDATE '.$this->tbs['tbs'].' SET label=\''.$label.'\' WHERE id='.$gID) )
            {
                return false;
            }
        }
        if ( !empty($raw['restrict']) )
        {
            $this->_db->query('DELETE FROM '.$this->tbs['tbst'].' WHERE s_id='.$gID);
            $this->_db->optimizeTable($this->tbs['tbst']);
            $this->_db->prepare('INSERT INTO '.$this->tbs['tbst'].' (s_id, t_id) VALUES ', '('.$gID.', %d)');
            foreach ($raw['restrict'] as $tID)
            {
                $this->_db->prepareData( array($tID) );
            }
            $this->_db->execute();
        }
        return true;
    }
    
    function _getGroup($gID)
    {
        $group =  array('id'=>0, 'label'=>'', 'restrict'=>array());
        $tmp = $this->_db->queryToArray('SELECT id, label FROM '.$this->tbs['tbs'].' WHERE id='.$gID );
        if ( !empty($tmp) )
        {
            $group = $tmp[0];
            $group['restrict'] = $this->_db->queryToArray('SELECT t_id FROM '.$this->tbs['tbst'].' WHERE s_id='.$gID, null, 't_id');
        }
        return $group;        
    }
    
    function _makeGroupForm($group, $iTypes)
    {
        $this->_form = & elSingleton::getObj('elForm');
        $this->_form->setRenderer( elSingleton::getObj('elTplFormRenderer') );
        $this->_form->setLabel( $group['id'] ? m('Edit search group') : m('Create new search group') );
        $this->_form->add( new elText('label', m('Label'), $group['label']) );
        $this->_form->add( new elCheckBoxesGroup('restrict', m('Search in items types'), $group['restrict'], $iTypes ) );
        $this->_form->setRequired('label');
    }
    
    function _arg( $ndx=0 )
    {
        return !empty($this->_args[$ndx]) ? $this->_args[$ndx] : null;
    }
}
?>